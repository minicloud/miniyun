<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 *
 */
?>
<?php

class ConvertModel extends CFormModel
{

    public $path;
    public $key;
    public $hash;
    public $type;
    public $contentUrl;
    public $host;


    public function init()
    {
        $this->path = MiniHttp::getParam('path', NULL);
        //外链进行匿名访问
        $this->key = MiniHttp::getParam('key', NULL);
        $this->initData();
    }

    private function initData()
    {
        if (empty($this->path)) {
            throw new CHttpException(500);
        }
        //如果文件浏览是外链，则可直接访问
        $hasPrivilege = false;
        if (!empty($this->key)) {
            $link = MiniLink::getInstance()->getByKey($this->key);
            if (!empty($link)) {
                $file = MiniFile::getInstance()->getById($link["file_id"]);
                $versionId = $file['version_id'];
                $pos = strpos($file["file_path"], $this->path);
                if ($pos !== false) {
                    $hasPrivilege = true;
                }
            }
        }
        //非外链接模式，就检查当前用户是否登录
        if ($hasPrivilege === false) {
            $share = new MiniShare();
            $minFileMeta = $share->getMinFileMetaByPath($this->path);
            if($minFileMeta===NULL){
                throw new CHttpException(500);
            }
            $versionId = $minFileMeta['version_id'];
        }
        $version = MiniVersion::getInstance()->getVersion($versionId);
        if (!$version) {
            throw new CHttpException(500);
        }
        $this->hash = $version['file_signature'];
    }

    public function isNeedConvert()
    {
        $action = MiniHttp::getParam('action', NULL);
        $serverId = NULL;
        if ($action != "again") {
            $version = MiniVersion::getInstance()->getBySignature($this->hash);
            if ($version !== NULL) {
                $meta = MiniVersionMeta::getInstance()->getMeta($version["id"], MiniVersionMeta::$MINI_DOC_SERVER);
                if ($meta !== NULL) {
                    $serverId = $meta["meta_value"];
                }
            }
        }
        if ($serverId !== NULL) {
            $server = MiniDocNode::getInstance()->getByID($serverId);
            if ($server["run_status"] == MiniDocNode::$SUCCESS) {
//                if (CUtils::validServer($server["ip"], $server["port"])) {
//                    $this->host = "http://" . $server["ip"] . ":" . $server["port"];
//                    $this->contentUrl = $this->host . "/content/" . $this->type . "/" . $this->hash;
//                    return false;
//                } else {
//                    MiniDocNode::getInstance()->modifyServerRunStatus($serverId);
//                }
            }
        }
        return true;
    }
}