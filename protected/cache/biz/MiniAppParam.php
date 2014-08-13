<?php
/**
 * 加载应用需要的参数
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MiniAppParam extends MiniCache{

    private $params            = NULL;
    private static  $CACHE_KEY = "cn.miniyun.MiniAppParam";

    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    static private $_instance = null;

    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        parent::MiniCache();
    }

    /**
     * 静态方法, 单例统一访问入口
     * @return object  返回对象的唯一实例
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     *
     * 根据浏览器的版本返回语言
     */
    private function setLanguage(){
        $language = null;
        if(array_key_exists("language",$_COOKIE)){
            $language = $_COOKIE["language"];
        }
        if(empty($language) || !($language=="zh_cn" || $language=="zh_tw" || $language=="en")){
            $language = "zh_cn";
        }
        Yii::app()->setLanguage($language);
    }
    /**
     * 加载初始化信息
     */
    public function load() {
        // 迷你云特殊默认语言
        $this->setLanguage();
        $key                        = MiniAppParam::$CACHE_KEY;
        //如果这里为false,表示还处于安装状态，这里不用进行cache
        if(Yii::app()->params['app']['initialized']===false){
            if($this->hasCache===true){
                $this->deleteCache($key);
            }
            Yii::app()->params["app"] = $this->getParams();
            return;
        }
        //二级缓存
        if($this->params!==NULL){
            Yii::app()->params["app"] = $this->params;
            return;
        }
        //一级缓存
        if($this->hasCache===true){
            //先判断是否已经缓存，否则进行直接缓存
            $datastr     = $this->get($key);
            if($datastr===false){
                Yii::trace($key." set cache","miniyun.cache1");
                $object  = $this->getParams();
                $this->set($key,serialize($object));
            }else{
                Yii::trace($key." get cache:","miniyun.cache1");
                $object  = unserialize($datastr);
            }
            $this->params = $object;
        }else{
            $this->params = $this->getParams();//DB加载
        }
        Yii::app()->params["app"] = $this->params;
    }
    /**
     * 把参数从一级缓存/二级缓存进行清空，用于管理员在后台更新APP状态的场景
     */
    public function clean(){
        $key           = MiniAppParam::$CACHE_KEY;
        //删除二级缓存
        $this->params  = NULL;
        //删除一级缓存
        if($this->hasCache===true){
            $this->deleteCache($key);
        }
    }
    /**
     *
     * 获得系统参数
     */
    private function getParams(){
        $params     = $this->getDefaultParams();
        if (Yii::app()->params["app"]["initialized"]){//当系统初始化好的情况下
            $params = $this->renewOptions($params);
        }
        return $params;
    }
    /**
     *
     * 获取默认的app信息
     *
     */
    private function getDefaultParams(){
        $entryUri             = $this->obtainEntryUri();//浏览器显示地址中是否需要包含index.php
        $params               = array(//app信息描述
            'name'            => NAME_ZH,//产品名称
            'title'           => "我的私有云",//产品标题
            'logo'            => "/statics/images/logo.png",//LOGO
            'logoSmall'       => "/statics/images/logo-small.png",//LOGO small類型
            'host'            => CUtils::getBaseUrl(),//域名
            'company'         => "www.miniyun.cn",//公司名称
            'sysSpace'        => 0,//默认系统无限空间
            'defaultSpace'    => defined('DEFAULT_USER_SPACE')? DEFAULT_USER_SPACE:100,//默认个人100M空间
            'enabledReigster' => true,//是否可自主註冊
            'registerUrl'     => Yii::app()->getBaseUrl()."/index.php/site/register",//用户註冊url地址
            'getpwUrl'        => Yii::app()->getBaseUrl()."/index.php/site/forgetpasswd",//用户忘记密码的url地址
            'enableMail'      => "1",//系统是否可发送邮件
            'mid'             => "",//此系统mid
            'uploadSize'      => (int)ini_get("upload_max_filesize") < (int)ini_get("post_max_size") ? ini_get("upload_max_filesize")*1024*1024 : ini_get("post_max_size")*1024*1024,
            'skinUrl'         => CUtils::getBaseUrl()."/statics/skin/skin_gray",//默认存储路径
            'skin'            => "skin_gray",// 皮肤
            'entryUri'        => $entryUri,// index.php的字符串
            'indexUri'        => CUtils::getBaseUrl().$entryUri,// index.php的路径
            'permission'      => unserialize('a:9:{s:13:"resource.read";i:1;s:13:"folder.create";i:1;s:13:"folder.rename";i:1;s:13:"folder.delete";i:1;s:11:"file.create";i:1;s:11:"file.modify";i:1;s:11:"file.rename";i:1;s:11:"file.delete";i:1;s:16:"permission.grant";i:0;}'),// 系统默认权限
            'absoluteUrl'     => Yii::app()->getRequest()->getHostInfo().Yii::app()->request->baseUrl,      // 系统的绝对路径

        );
        //合并数组
        $appMain              = Yii::app()->params["app"];
        $params               = array_merge($appMain, $params);
        return $params;
    }
    /**
     *
     * 设置参数
     *
     */
    private function renewOptions($params){
        //用户基础地址
        $baseUri   = CUtils::getBaseUrl();
        $options   = MiniOption::getInstance()->getOptions();
        foreach ($options as $key=>$option){
            $key   = $option["option_name"];
            $value = $option["option_value"];
            if("site_name" == $key){
                $params["siteName"]  = $value;
                $params["name"]      = $value;
            }
            else if("skin" == $key){
                $params["skinUrl"]   = $baseUri."/statics/skin/".$value."/";
                $params["skin"]      = $value;
            }
            else if("site_title" == $key){
                $params["siteTitle"] = $value;
                $params["title"]     = $value;
            }
            else if("site_logo_url" == $key){
                $params["logo"]      = $value;
                $params["siteLogo"]  = $value;
            }
            else if("site_logo_small_url" == $key){
                $params["logoSmall"] = $value;
                $params["siteLogoSmall"] = $value;
            }
            else if("site_default_space" == $key){
                $params["defaultSpace"]  = $value;
            }
            else if("site_sys_space" == $key){
                $params["sysSpace"]  = $value;
            }
            else if("site_company" == $key){
                $params["company"]       = $value;
            }
            else if("user_register_enabled" == $key){
                $params["enabledReigster"] = $value=="1"?true:false;
            }
            else if("user_create_url" == $key && !empty($value)){
                $params["registerUrl"]    = $value;
            }
            else if("user_getpwd_url" == $key && !empty($value)){
                $params["getpwUrl"]      = $value;
            }
            else if("mail_enabled_email" == $key){
                $params["enableMail"]    = $value;
            }
            else if("mid" == $key){
                $params["mid"]           = $value;
            }
            else if("default_permission" == $key){
                $params["permission"] = unserialize($value);
            }
        }
        return $params;
    }
    /**
     * 浏览器显示地址中是否需要包含index.php
     */
    private  function obtainEntryUri() {
        if (defined("SUPPORT_NO_INDEX") && SUPPORT_NO_INDEX){
            $entryUri = "";
        } else {
            $entryUri = "/index.php";
        }
        return $entryUri;
    }
}