<?php
/**
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserManager {
    /**
     * 缓存
     * @var CApcCache
     */
    private $_cache         = null;
    /**
     * 用户信息
     * @var User
     */
    private $_user          = null;
    /**
     * 用户设备信息
     * @var UserDevice
     */
    private $_device        = null;
    
    /**
     * 标志是否通过web浏览器访问false表示非浏览器，true表示浏览器
     * @var boolean
     */
    public $agent           = false;
    
    /**
     *  静态成品变量 保存全局实例
     *  @access private
     *  @var UserManager
     */
    static private $_instance      = null;
    
    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        $this->_cache = new CApcCache();
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
     * @param string $deviceId token信息用户的基本信息
     * @return user $value 返回最终需要执行完的结果
     */
    public function getUserOauth2($deviceId) {
        $device = UserDevice::model()->findByPk($deviceId);
        if (empty($device)) 
            return false;
        $user  = User::model()->findByPk($device['user_id']);
        if (empty($user)){
            return false;
        }
    
        $this -> _user    = $user;
        $this -> _device  = $device;
        return $user;
    }
    
    /**
     * 从session中获取用户信息
     */
    public function getUserFromSession() {
        $user = Yii::app()->session["user"];
        if (empty($user)){
            return false;
        }
        // 获取web端设备id
        $device = UserDevice::model()->findByAttributes(array('user_id'=>$user['id'], 'user_device_type'=>1));
        if (empty($device))
            return false;
        
        $this -> _user    = $user;
        $this -> _device  = $device;
        return $user;
    }
    
    /**
     * 获取用户信息
     * @return User
     */
    public function getUser() {
        return $this->_user;
    }
    
    /**
     * 获取用户设备信息
     * @return UserDevice
     */
    public function getDevice() {
        return $this->_device;
    }
    
    
}