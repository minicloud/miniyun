<?php
/**
 * 上传分块文件到cache目录
 * pcClient不需要多线程上传地址接口 android
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */


class MPartUploadController
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
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        // keys，是作为参数的键值，进行请求合法验证
        $keys = array ('Filename','key');
        # 重新序列化参数
        $post = array();
        foreach ($_POST as $key => $value) {
            if ($key == "Filename") {
                $name = explode("_part_",$value);
                $post[$key] = $name[0];
            } else {
                $post[$key] = $value;
            }
        }
        if (MSecurity :: verification($keys, $post) == false) {
            Yii::log(Yii::t('api',"Request is Error, verification error"), CLogger::LEVEL_ERROR,"miniyun.api");
            throw new MException(Yii::t('api',MConst::INVLID_REQUEST."3"), MConst::UPLOAD_FILE_FAILS);
        }
        // 处理创建文件
        if (!MUtils::create(DOCUMENT_CACHE, $_POST, $_FILES)) {
            throw new MException(Yii::t('api',MConst::INVLID_REQUEST."4"), MConst::UPLOAD_FILE_FAILS);
        }
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