<?php
/**
 * 用户账号与密码验证
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserIdentity extends CUserIdentity
{
    public $autoLogin = false;
    /**
     * (non-PHPdoc)
     * @see CUserIdentity::authenticate()
     * 用户认证
     * @since 1.1.1
     */
    public function authenticate()
    {
        $uerObject = new CUserValid();
        $user = $uerObject->validUser(trim($this->username),$this->password);
        //如果存在错误代码  则返回
        if (!$user){
            $this->errorCode = $uerObject->errorCode;
            return false;
        }
        if(!isset($user) || !$user){
            $this->errorCode = MConst::ERROR_PASSWORD_INVALID;
            if (CUserValid::$userDisabled) {
                $this->errorCode = MConst::ERROR_USER_DISABLED;
            } elseif ($uerObject->errorCode == MConst::ERROR_USERNAME_INVALID) {
                $this->errorCode = MConst::ERROR_USERNAME_INVALID;
            }
            return false;
        }
        return $this->loadUser($user);
    }

    /**
     * 装载user并且初始化session
     * @param $user
     * @return bool
     */
    public function loadUser($user){

        //对设备进行检测
        $device     = $this->getCurrentDevice($user);
        $this->setSession($user, $device);
        $this->errorCode=MConst::ERROR_NONE;
        return true;
    }
    /**
     * 
     * 设置session对应的信息
     * @param $user
     * @param $device
     */
    public function setSession($user, $device) {
        //初始化系统需要的状态信息
        Yii::app()->session["appId"] = $device["user_device_type"];
        $user["appId"] = $device["user_device_type"];
        Yii::app()->session["user"] = $user;
        $deviceId = 0;
        if(!empty($device)){
            $deviceId = $device["id"];
        }
        Yii::app()->session["deviceId"] = $deviceId;//设置设备ID
        //提前设置session的值
        $_SESSION['company_id'] = $user['company_id'];
        $this->setAppOptions($user['company_id']);
    }
    /**
     *
     * 设置参数
     *
     */
    private function setAppOptions($companyId){
        $params = Yii::app()->params["app"];
        //用户基础地址
        $baseUri   = CUtils::getBaseUrl();
        $options   = MiniOption::getInstance()->getOptions($companyId);
        foreach ($options as $key=>$option){
            $key   = $option["option_name"];
            $value = $option["option_value"];
            if("site_name" == $key){
                $params["siteName"]  = $value;
                $params["name"]      = $value;
            }
            else if("skin" == $key){
                $params["skinUrl"]   = $baseUri."/static/skin/".$value."/";
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
        Yii::app()->params["app"] =$params;
    }
    /**
     * 根据cookie中的accessToken获得用户信息
     */
    private function getUserByAccessToken(){
        //当accessToken在session中的时候，他的优先级最高，然后cookie里面的accessToken
        //这里解决新版客户端网页加载的问题
        $accessToken = Yii::app()->session["accessToken"];
        if(empty($accessToken)){
            //PC客户端从Post参数获得access_token
            if(MiniHttp::isPCClient()){
                $accessToken = MiniHttp::getParam("accessToken","");
            }else{
                if(!array_key_exists("accessToken",$_COOKIE)){
                    $accessToken = MiniHttp::getParam("accessToken","");
                    if(empty($accessToken)){
                        return NULL;
                    }
                }else{
                    $accessToken  = $_COOKIE['accessToken'];
                }
            }
        }
        if(empty($accessToken)){
            return NULL;
        }
        $accessInfo = MiniToken2::getInstance()->getAccessInfo2($accessToken);
        if (!isset($accessInfo)) {
            return NULL;
        }
        $user  = MUserManager::getInstance()->getUserOauth2($accessInfo["device_id"]);//获取用户的信息
        return $user;
    }

    /**
     * 获得当前登录的设备
     * @param $user
     * @return null
     */
    private function getCurrentDevice($user){
        //提前设置session的值
        $_SESSION['company_id'] = $user['company_id'];
        $deviceType = 1;
        if(MiniHttp::isPCClient()){
            if(MiniHttp::isWindowsOS()){
                $deviceType = 2;//Windows 客户端
            }else if(MiniHttp::isMacOS()){
                $deviceType = 3;//Mac 客户端
            }else{
                $deviceType = 5;//Linux 客户端
            }
        }
        //对设备进行检测
        if($deviceType==1){
            $device = DeviceManager::getDevice($user["id"], MConst::DEVICE_WEB, "web", $_SERVER['HTTP_USER_AGENT']);
        }else{
            $device = MiniUserDevice::getInstance()->getFirstByDeviceTypeAndDeviceName($user["id"],$deviceType);
        }
        return $device;
    }
    /**
     * 
     * 使用cookie自动登陆
     * @since 0.9.7
     */
    public function cookieLogin() {
        $user = $this->getUserByAccessToken();
        if($user===NULL){
            return false;
        }
        $device = $this->getCurrentDevice($user);
        $this->setSession($user, $device);
        return true;
    }
    /**
     * 
     * 清理cookie,登出时候调用
     * @since 0.9.7
     */
    public function cleanCookies() {
        $this->removeCookie('accessToken');
    }
    /**
     * 删除Cookie
     * @param $key
     * @return null
     */
    private function removeCookie($key){
        if(!array_key_exists($key,$_COOKIE)){
            return NULL;
        }
        unset($_COOKIE[$key]);
        setcookie($key, null, -1, '/');
    }
}