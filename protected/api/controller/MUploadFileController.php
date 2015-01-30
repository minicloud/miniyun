<?php
/**
 * Data控制器主要处理逻辑部分
 * iPhone 使用 以及pcClient不需要多线程上传地址接口
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */


class MUploadFileController extends MApplicationComponent implements MIController{
	
    /**
     * 控制器执行主逻辑函数
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke()
    {
        Yii::trace(Yii::t('api','Begin to process {class}::{function}',
            array('{class}'=>get_class($this), '{function}'=>__FUNCTION__)),"miniyun.api");
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        // keys，是作为参数的键值，进行请求合法验证
        $keys = array ('Filename','key');
        if (MSecurity :: verification($keys, $_POST) == false) {
            Yii::log(Yii::t('api',"Request is Error, keys:'{$keys}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            throw new MException(Yii::t('api',MConst::INVLID_REQUEST."1"), MConst::UPLOAD_FILE_FAILS);
        }
        // 处理创建文件
        if (!MUtils::create(DOCUMENT_ROOT_BLOCK, $_POST, $_FILES, true)) {
            throw new MException(Yii::t('api',MConst::INVLID_REQUEST."2"), MConst::UPLOAD_FILE_FAILS);
        }
        Yii::trace(Yii::t('api','end to process {class}::{function}',
            array('{class}'=>get_class($this), '{function}'=>__FUNCTION__)),"miniyun.api");
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