<?php
/**
 * 在会话期内，只需一次读userMaxEventId对象
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniEvent2 extends  MiniCache2{
	private $userMaxEventId;
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
	 * 整个会话期内只读一次用户最大事件ID
	 * @param $userId
	 */
	public function getMaxIdByUser2($userId){
		if($this->hasCache2===true && $this->userMaxEventId!==NULL){
			Yii::trace("cache 2 get userMaxEventId userId:".$userId,"miniyun.cache2");
			return $this->userMaxEventId;
		}
		$this->userMaxEventId = MiniEvent::getInstance()->getMaxIdByUser($userId);
		return $this->userMaxEventId;
	}
}