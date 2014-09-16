<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class ConvertActionModel extends CFormModel {
    public $hash;
    public $miniDocServerId;
    
    public function getContent() {
        if (!$this->validate()) {
            return false;
        }
        $content_type = 'application/force-download';
                $data = array('hash'=>$this->hash, 'filename'=>$this->hash, 'content_type' => $content_type);
        $downloadUrl = apply_filters('web_download_url', $data);
        if ($downloadUrl !== $data && !empty($downloadUrl)){
             Yii::app()->request->redirect($downloadUrl);
             return false;
        }
                $dataObj = Yii::app()->data;
        $file_path = MiniUtil::getPathBySplitStr ( $this->hash );
        if ($dataObj->exists( $file_path ) === false) {
           return false;
        }
                if (headers_sent()) {
            return false;
        }
        if( MiniUtil::outContent($file_path, $content_type, $this->hash)) {
            return true;
        }
        return true;
    }
    
    
    
    public function rules() {
        return array(
            array('hash', 'required'),
        );
    }
    
    
    protected function beforeValidate() {
        foreach ($this->attributeNames() as $key) {
            $this->$key = Yii::app()->request->getParam($key);
        }
        return parent::beforeValidate();
    }
    
    public function success(){
        if (!$this->validate()) {
            return false;
        }
        $version  = MiniVersion::getInstance()->getBySignature($this->hash);
        if($version===NULL){
            return false;
        }

        MiniVersionMeta::getInstance()->create($version["id"],MiniVersionMeta::$MINI_DOC_SERVER,$this->miniDocServerId);
        return true;
    }
}