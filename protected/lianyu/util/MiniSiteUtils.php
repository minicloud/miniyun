<?php
/**
 * 站点基础方法
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniSiteUtils
{
    /**
     *
     * 获取插件安装目录下的插件
     * @since 1.0.7
     */
    public static function getSiteBasic() {
        $upData = self::getSiteBasicArray();
        $data     = urlencode(base64_encode(json_encode($upData)));
        return $data;
    }

    /**
     *
     * 获取插件安装目录下的插件
     * @since 1.1.0
     */
    public static function getSiteBasicArray() {
        $siteUrl = urlencode(Yii::app()->params['app']['absoluteUrl'].Yii::app()->params['app']['entryUri']);

        $upData = array();
        $upData["siteurl"]         = $siteUrl;
        $upData["sitename"]        = Yii::app()->params['app']['name'];
        $upData["app_version"]     = APP_VERSION;
        $upData["windows_version"] = "1.0";
        $upData["android_version"] = "1.0";
        $upData["ios_version"]     = "1.0";
        $upData["php_version"]     = phpversion();
        $upData["mid"]             = Yii::app()->params['app']['mid'];
        $upData["site_id"]          = self::getSiteID();
        return $upData;
    }

    /**
     *
     * 获取插件安装目录下的插件
     * @since 1.0.7
     */
    public static function getSiteID(){
        $siteId = MiniOption::getInstance()->getOptionValue("site_id");
        if ($siteId===NULL){
            $siteId         = md5(MiniUtil::random(32));
            MiniOption::getInstance()->setOptionValue("site_id", $siteId);
        }
        return $siteId;
    }
    /**
     *
     * 获得站点code
     * @since 2.0
     */
    public static function getSiteCode(){
        $code = MiniOption::getInstance()->getOptionValue("code");
        if ($code===NULL){
            $code = "";
        }
        return $code;
    }
    /**
     *
     * 获取web端上传限制大小
     * @since 1.1.2
     */
    public static function getUploadSizeLimit(){
        return Yii::app()->params['app']['uploadSize'];
    }

    /**
     *
     * 获取web端上传类型大小
     * @since 1.1.2
     */
    public static function getUploadTypeLimit(){
        return "\"*\"";
    }



    /**
     *
     * 获取tmp目录的路径
     * @since 1.1.2
     */
    public static function getDocumentTemp(){
        return DOCUMENT_TEMP;
    }

    /**
     *
     * 获取base目录的路径
     * @since 1.1.2
     */
    public static function getBasePath(){
        return BASE;
    }

    /**
     *
     * 获取upload_block目录的路径
     * @since 1.1.2
     */
    public static function getDocumentRootBlock(){
        return DOCUMENT_ROOT_BLOCK;
    }
    /**
     *
     * 获得微信信息加密的Token
     * @since 1.0.7
     */
    public static function getWxToken(){
        $wxToken = MiniOption::getInstance()->getOptionValue("wx_token");
        if ($wxToken===NULL){
            $wxToken         = md5(MiniUtil::random(32));
            MiniOption::getInstance()->setOptionValue("wx_token", $wxToken);
        }
        return $wxToken;
    }

}