<?php
/**
 * 在会话期内，只需一次读user对象
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniUser2 extends  MiniCache2{
    private $user;
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
		parent::MiniCache2();
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
	 * 根据id获得User完整信息，在整个会话期内只有一个User对象
	 * @param $id
	 */
	public function getUser2($id){
		if($this->hasCache2===true && $this->user!==NULL && $this->user["user_id"]===$id){
			Yii::trace("cache 2 get userId:".$id,"miniyun.cache2");
			return $this->user;
		}
		$this->user = MiniUser::getInstance()->getUser($id);
		return $this->user;
	}
	/**
	 * 根据id获得User完整信息，在整个会话期内只有一个User对象
	 * 本函数当前仅用于用户登陆认证环节
	 * 用户修改密码，要对一级缓存更新，二级缓存可不更新
	 * @param $name
     * @return array
	 */
	public function getUserByName2($name){
		if($this->hasCache2===true && $this->user!==NULL){
			Yii::trace("cache 2 get userName:".$name,"miniyun.cache2");
			return $this->user;
		}
		$this->user = MiniUser::getInstance()->getUserByName($name);
		return $this->user;
	} 
}