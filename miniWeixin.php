<?php
/**
 * 迷你云touch的入口
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */


class SiteAppInfo{

    private $user;
    public function  SiteAppInfo(){

    }
    /**
     * 获得站点信息，获得自定义的名称与Logo
     * @return array|null
     */
    public  function getSiteInfo(){
        $app = new AppService();
        return $app->info();
    }
    /**
     * 判断是否是默认账号
     * @return array|null
     */
    public  function defaultAccount(){
        $app = new AppService();
        return $app->onlyDefaultAccount();
    }

    public function getCode(){
            $data= MiniOption::getInstance()->getOptionValue("code");
            return $data;
        }
    /**
     * 获得当前用户
     * @return array|null
     */
    public  function getUser(){
        if(isset($this->user)){
            return $this->user;
        }
        $user     = MUserManager::getInstance()->getCurrentUser();
        if(!empty($user)){

            $user = MiniUser::getInstance()->getUser($user["id"]);
            $data = array();
            $data['user_uuid']         = $user["user_uuid"];
            $data['user_name']         = $user["user_name"];
            $data['display_name']      = $user["nick"];
            $data['space']             = (double)$user["space"];
            $data['used_space']        = (double)$user["usedSpace"];
            $data['email']             = $user["email"];
            $data['phone']             = $user["phone"];
            $data['avatar']            = $user["avatar"];
            $data['is_admin']          = $user["is_admin"];
            $data['code']              = MiniOption::getInstance()->getOptionValue("code");
            $this->user = $data;
            return $data;
        }
        return NULL;
    }
}
/**
 *
 * 加载资源
 */
class MiniWeixin{
    /**
     * 控制器名称
     * @var
     */
    private $controller;
    /**
     * 动作名称
     * @var
     */
    private $action;
    /**
     * 迷你云服务器地址
     * @var
     */
    private $staticServerHost;
    /**
     * 当前用户选择的语言版本
     * @var
     */
    private $language;
    /**
     * 网页客户端版本号
     * @var
     */
    private $version;
    /**
     * 云端存储的主目录
     * @var
     */
    private $cloudFolderName;
    private $appInfo;

    /**
     *
     */
    private $webApp = NULL;
    public function MiniWeixin(){
        //如果系统尚未初始化，则直接跳转到安装页面
        $configPath  = dirname(__FILE__).'/protected/config/miniyun-config.php';
        if (!file_exists($configPath)) {
            echo("迷你云尚未安装");
            exit;
        }
        //加载系统信息
        $config = dirname(__FILE__).'/protected/config/main.php';
        $yii    = dirname(__FILE__).'/yii/framework/yii.php';
        require_once($yii);
        $this->webApp = Yii::createWebApplication($config);
        MiniAppParam::getInstance()->load();
        MiniPlugin::getInstance()->load();

        $port = $_SERVER["SERVER_PORT"];
        if($port=="443"){
            $this->staticServerHost = "https://".STATIC_SERVER_HOST."/";
        }else{
            $this->staticServerHost = "http://".STATIC_SERVER_HOST."/";
        }
    }

    /**
     * 调整URL，如是PC客户端发起的请求，在后面自动加上client_type=pc类型
     * 区分是否是PC客户端还是网页客户端发起的请求
     * @param $url
     */
    private function redirectUrl($url){
        header('Location: '.$url);
        exit;
    }
    private  function loadHtml($head){
        $ieHead = "";
        //输出头信息
        $content = "<!doctype html><html id='ng-app'><head><meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge,chrome=1\" />";
        $content .="<meta name=\"apple-mobile-web-app-capable\" content=\"yes\"/>";
        $content .="<meta name=\"viewport\" content=\"user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimal-ui\"/>";
        $content .="<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"yes\" />"; 
        $content .="<meta http-equiv=\"content-type\" content=\"text/html;charset=utf-8\"/>".$ieHead.$head."<script>";
        $appInfo = $this->appInfo;
        //打印APP INFO
        $content .= "var appInfo={};appInfo.info = JSON.parse('".json_encode($appInfo->getSiteInfo())."');";
        //是否系统仅有管理员
        $content .= "appInfo.only_default_account = JSON.parse('".json_encode($appInfo->defaultAccount())."');";
        //打印用户是否登录
        $user    = $appInfo->getUser();
        $info = array("success"=>true);
        if(empty($user)){
            $info = array("success"=>false);
        }
        $content .= "appInfo.login = JSON.parse('".json_encode($info)."');";
        if(!empty($user)){
            $content .= "appInfo.user = JSON.parse('".json_encode($user)."');";
        }
        //打印用户是否是管理员
        $info = array("success"=>false);
        if(!empty($user)){
            if($user["is_admin"]){
                $info = array("success"=>true);
            }
        }
        $content .= "appInfo.is_admin = JSON.parse('".json_encode($info)."');";
        //输出服务器时间
        $info = array("time"=>time());
        $content .= "appInfo.time = JSON.parse('".json_encode($info)."');";
        
        $body = "";
        $body .= "<body ng-app=\"ngApp\" ng-controller=\"MainController\">";
        $body .= "<div class=\"app-body\" ng-class=\"{loading: loading}\">";
        $body .= "<div ng-show=\"loading\" class=\"app-content-loading\">";
        $body .= "<i class=\"fa fa-spinner fa-spin loading-spinner\"></i>";
        $body .= "</div>";
        $body .= "<ng-view class=\"app-content\" ng-hide=\"loading\"></ng-view>";
        $body .= "</div>";
        $body .= "</div>";
        $body .= " </div></body>";
         //输出body信息
        $content .= "</script></head>".$body."</html>";       
        echo($content);
    }
    /**
     * 加载资源
     */
    public function load(){
        date_default_timezone_set("PRC");
        @ini_set('display_errors', '1');

        $this->appInfo = new SiteAppInfo();
        //默认业务主路径
        $this->cloudFolderName = "mini-box";
        $language = $this->getCookie("language");
        if(empty($language)){
            $language = "zh_cn";
        }
        $this->language = $language;
        $v = $this->getCookie("cloudVersion");
        if(empty($v)){
            $v = "1.0";
        }
        //$this->version = $v;
        $this->version = time();
        $header = "";
		//生产状态，将会把js/css文件进行合并处理，提高加载效率
		$header .= "<script id='miniBox' static-server-host='".$this->staticServerHost."' host='".MiniHttp::getMiniHost()."' version='".$v."' type=\"text/javascript\"  src='".$this->staticServerHost."miniLoad.php?t=js&c=box&a=index&v=".$v."&l=".$this->language."' charset=\"utf-8\"></script>";
		$header .= "<link rel=\"stylesheet\" type=\"text/css\"  href='".$this->staticServerHost."miniLoad.php?t=css&c=box&a=index&v=".$v."&l=".$this->language."'/>";
 
        $this->loadHtml($header);
    }
    /**
     *
     * 根据浏览器的版本返回语言
     */
    private function getCookie($name){
        $value = NULL;
        if(array_key_exists($name,$_COOKIE)){
            $value = $_COOKIE[$name];
        }
        return $value;
    }
}