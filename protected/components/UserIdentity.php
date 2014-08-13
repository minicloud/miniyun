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
        $device     = DeviceManager::getDevice($user["id"], MConst::DEVICE_WEB, "web", $_SERVER['HTTP_USER_AGENT']);
        $this->setSession($user, $device);
        $this->errorCode=MConst::ERROR_NONE;
        return true;
    }
    /**
     * 
     * 设置session对应的信息
     * @since 0.9.7
     */
    public function setSession($user, $device) {
        Yii::app()->session["user"] = $user;
        //初始化系统需要的状态信息
        Yii::app()->session["appId"] = 1;//设置appId为1

        $deviceId = 0;
        if(!empty($device)){
            $deviceId = $device["id"];
        }
        Yii::app()->session["deviceId"] = $deviceId;//设置设备ID
    }

    /**
     * 根据cookie中的accessToken获得用户信息
     */
    private function getUserByAccessToken(){
        if(!array_key_exists("accessToken",$_COOKIE)){
            return NULL;
        }
        $accessToken  = $_COOKIE['accessToken'];
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
     * 
     * 使用cookie自动登陆
     * @since 0.9.7
     */
    public function cookieLogin() {
        $user = $this->getUserByAccessToken();
        if($user===NULL){
            return false;
        }
        //对设备进行检测
        $device = DeviceManager::getDevice($user["id"], MConst::DEVICE_WEB, "web", $_SERVER['HTTP_USER_AGENT']);
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