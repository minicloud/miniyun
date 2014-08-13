<?php
/**
 * 下载块文件
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */


class MPartDownloadController
    extends MApplicationComponent 
    implements MIController
{
    /**
     * 控制器执行主逻辑函数
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke()
    {
        Yii::trace(Yii::t('api','Begin to process {class}::{function}',
            array('{class}'=>get_class($this), '{function}'=>__FUNCTION__)),"miniyun.api");
        //调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        // keys，是作为参数的键值，进行请求合法验证
        $keys = array ('Filename','key');
        if (MSecurity :: verification($keys, $_POST) == false) {
            Yii::log(Yii::t('api',"Request is Error, keys:'{$keys}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            throw new MException(Yii::t('api',MConst::FILE_NOT_EXIST), MConst::DOWNLOAD_FILE_FAILS);
        }
        $this->downloadFile();
    }
    
    /**
     * 处理下载文件逻辑 
     */
    private function downloadFile() {
        $file_name  = $_POST["Filename"];
        $key        = $_POST["key"];
        $path       = '';
        // 参数检查
        if (strlen(trim($file_name)) <= 0 || strlen(trim($key)) <= 0) {
            Yii::log(Yii::t('api',"Request is Error, file_name:'{$file_name}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            throw new MException(Yii::t('api',MConst::FILE_NOT_EXIST), MConst::DOWNLOAD_FILE_FAILS);
        }
        // 全路径
        $path = str_replace("\${filename}", $file_name, $key);
        if (is_null($path) || strlen(trim($path)) <= 0) {
            Yii::log(Yii::t('api',"Request is Error, file_name:'{$file_name}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            throw new MException(Yii::t('api',MConst::FILE_NOT_EXIST), MConst::DOWNLOAD_FILE_FAILS);
        }
    
        $file_path = DOCUMENT_ROOT_BLOCK . $path;
    
        //文件不存在
        if (file_exists($file_path) == false) {
            Yii::log(Yii::t('api',"File do not exist, path:'{$file_path}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            throw new MException(Yii::t('api',MConst::FILE_NOT_EXIST), MConst::DOWNLOAD_FILE_FAILS);
        }
    
        $content_type = 'application/force-download';
        MUtils::download($file_path, $content_type, $file_name);
    }
    
    /**
     * Data处理异常入口地址
     */
    public function handleException($exception)
    {
        return header("HTTP/1.1 ". $exception->getCode());
    }

}

?>