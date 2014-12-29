<?php
/**
 * 验证模块
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MUserManager
{
    private $_current_user           = null;
    private $_current_device         = null;
    private $_isWeb                  = false;

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
     * 根据设备获得用户信息
     */
    public function getUserOauth2($deviceId) {
		$device = MiniUserDevice2::getInstance()->getUserDevice2($deviceId);
		if($device===NULL){
			return NULL;
		}
		$user   = MiniUser2::getInstance()->getUser2($device["user_id"]);
		if($user===NULL){
			return NULL;
		}
        $this -> _current_user    = $user;
        $this -> _current_device  = $device;
        return $user;
    }
    /**
     * web端入口，从session中获取用户
     */
    public function getUserFromSession() {
        $user     = Yii::app()->session["user"];
        $deviceId = Yii::app()->session["deviceId"];
        if (empty($user) || empty($deviceId)){
            return false;
        }
        // 获取web端设备id
    	$device              = MiniUserDevice2::getInstance()->getUserDevice2($deviceId);
		if($device===NULL){
			return false;
		}
        
        $userId                  = $user['id'];
        $userInfo                 = MiniUser2::getInstance()->getUser2($userId);
        $userInfo["appId"]        = 1;
        
        $this -> _current_user    = $userInfo;
        $this -> _current_device  = $device;
        return $user;
    }

    public function isWeb(){
    	return $this->_isWeb;
    }
    public function setIsWeb($isWeb){
    	$this->_isWeb = $isWeb;
    }
    /**
     * 获取当前用户的信息
     */
    public function getCurrentUser() {
        $needSetOnline = false;
        if(MiniHttp::clientIsBrowser()){
            //javascript判断用户是否登录，在服务器端本地进行用户初始化
            $this->_current_user = NULL;
            $userIdentity = new UserIdentity(NULL, NULL);
            $userIdentity->cookieLogin();
            $sessionUser = Yii::app()->session["user"];
            $this->_current_user = $sessionUser;
            $needSetOnline = true;
        }else{
            if(empty($this->_current_user)){
                $sessionUser = Yii::app()->session["user"];
                $this->_current_user = $sessionUser;
                $needSetOnline = true;
            }
        }
        if($needSetOnline){
            $appId    = Yii::app()->session["appId"];
            $deviceId = Yii::app()->session["deviceId"];
            $userId   = $this->_current_user['id'];
            MiniOnlineDevice::getInstance()->setOnlineDeviceValue($userId,$appId,$deviceId);
        }
        return $this->_current_user;
    }

    /**
     * 设置当前用户的信息
     */
    public function setCurrentUser($user) {
        return $this->_current_user = $user;
    }

    /**
     * 获取当前用户设备的信息
     */
    public function getCurrentDevice() {
        return $this->_current_device;
    }

    /**
     * 设置当前用户设备的信息
     */
    public function setCurrentDevice($device) {
        return $this->_current_device = $device;
    }

}