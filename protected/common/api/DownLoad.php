<?php
/**
 * Miniyun web文件(夹)移动or重命名
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class DownLoad extends CApiComponent
{
    public $_userId;        // 当前登录用户的id
    public $fileId;        // file
    public $checkSelf = false; // 是否需要验证是自己的文件

    /**
     *
     * 构造函数，初始化一些参数
     * 
     * @since 1.1.2
     */
    public function __construct ()
    {
        parent::init();
        $this->result = array();
        $this->result["state"] = false;
        $this->result["msg_code"] = 0;
        $this->result["msg"] = Yii::t("front_common", "download_error");
    }

    /**
     *
     * 下载文件
     * 
     * @since 1.1.2
     */
    public function invoke(){
        if (!isset($this->fileId)){
            $this->handleResult(false, 0, Yii::t("front_common", "download_error"));
            return;
        }
        if (!isset($this->_userId)){
            $this->handleResult(false, 0, Yii::t("front_common", "download_no_permission"));
            return;
        }
        //查询文件属性
        $fileDetail = UserFile::model()->findByPk($this->fileId);
        if (empty($fileDetail) || $fileDetail["is_deleted"] == 1){
            $this->handleResult(false, 0, Yii::t("front_common", "download_no_permission"));
            return;
        }
        //判断是否需要检测文件属于自己, 不属于自己时判断是否拥有权限
        if ($this->checkSelf && $this->_userId != $fileDetail["user_id"]){
            $hasRead = Yii::app()->privilege->hasShareFilePermissionUser($this->_userId, $fileDetail, MPrivilege::RESOURCE_READ);
            if (!$hasRead){
                $this->handleResult(false, 0, Yii::t("front_common", "download_no_permission"));
                return;
            }
        }

        $file_name = $fileDetail['file_name'];
        $version_id = $fileDetail['version_id'];

        //查询版本属性
        $file_version = FileVersion::model()->findByPk($version_id);
        $signature = $file_version["file_signature"];
        $content_type = $fileDetail["mime_type"];

        //为第三方源下载添加hook
        $data = array("hash"=>$signature, "filename"=>$file_name, "content_type"=>$content_type);
        $downloadUrl = apply_filters("web_download_url", $data);
        if ($downloadUrl !== $data && !empty($downloadUrl)){
             Yii::app()->request->redirect($downloadUrl);
            return;
        }

        //获取文件绝对路径地址
        $dataObj = Yii::app()->data;
        $file_path = CUtils::getPathBySplitStr ( $signature );
        if ($dataObj->exists( $file_path ) === false) {
            throw new ApiException("File don't exists.");
        }

        //
        // 检查是否输出
        //
        if (headers_sent ()) {
            $this->handleResult(false, 0, Yii::t("front_common", "download_error"));
            return;
        }
        if( CUtils::outContent($file_path, $content_type, $file_name)) {
            $this->handleResult(true, 0, Yii::t("front_common", "download_success"));
            return true;
        }
        
    }



    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException ($exception)
    {
        $this->handleResult(false, 0, Yii::t("front_common", "download_error"));
//        echo CJSON::encode($this->result);
    }

    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleError()
     */
    public function handleError($code, $message, $file, $line) {
        $this->handleResult(false, 0, Yii::t("front_common", "download_error"));
//        echo CJSON::encode($this->result);
    }
}