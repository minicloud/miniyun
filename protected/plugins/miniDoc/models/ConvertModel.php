<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class ConvertModel extends CFormModel {

    public $cid;
    public $key;
    public $name;
    public $hash;
    public $rev;
    public $type;
    public $contentUrl;
    public $host;


    public function init() {
        $this->cid = Yii::app()->request->getParam('cid', NULL);
        $this->rev = Yii::app()->request->getParam('rev', NULL);
        //外链进行匿名访问
        $this->key = Yii::app()->request->getParam('key', NULL);
        $this->initData();
    }
    
    private function initData() {
        if (empty($this->cid)) {
            throw new CHttpException(500);
        }
        $file = MiniFile::getInstance()->getById($this->cid);
        if ($file) {
            $this->name = $file['file_name'];
        } else {
            throw new CHttpException(500);
        }
        //如果文件浏览是外链，则可直接访问
        $hasPrivilege = false;
        if(!empty($this->key)){
            $link = MiniLink::getInstance()->getByKey($this->key);
            if(!empty($link)){
                if($link["file_id"]==$file["id"]){
                    $hasPrivilege = true;
                }
            }
        }
        if($hasPrivilege===false){
            $user_id = MUserUtils::obtainUserID();
            $hasRead = Yii::app()->privilege->hasShareFilePermissionUser($user_id, $file, MPrivilege::RESOURCE_READ);
            if (!$hasRead && $file['user_id'] != $user_id) {
                throw new CHttpException(500);
            }
        }
        if (empty($this->rev)) {
            $this->rev = $file['version_id'];
        }
        $version = MiniVersion::getInstance()->getVersion($this->rev);
        if (!$version) {
            throw new CHttpException(500);
        }
        $this->hash = $version['file_signature'];
    }
    
    public function isNeedConvert(){
                $action   = Yii::app()->request->getParam('action', NULL);
        $serverId = NULL;
        if($action!="again"){
            $version  = MiniVersion::getInstance()->getBySignature($this->hash);
            if($version!==NULL){
                $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],MiniVersionMeta::$MINI_DOC_SERVER);
                if($meta!==NULL){
                    $serverId = $meta["meta_value"];
                }
            }
        }
        if($serverId!==NULL){
            $server = MiniDocNode::getInstance()->getByID($serverId);
            if($server["run_status"]==MiniDocNode::$SUCCESS){
                if(CUtils::validServer($server["ip"],$server["port"])){
                    $this->host = "http://".$server["ip"].":".$server["port"];
                    $this->contentUrl = $this->host."/content/".$this->type."/".$this->hash;
                    return false;
                }else{
                                        MiniDocNode::getInstance()->modifyServerRunStatus($serverId);
                }
            }
        }
        return true;
    }
}