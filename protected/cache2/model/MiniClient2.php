<?php
/**
 * 在会话期内，只需一次读client对象
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniClient2 extends  MiniCache2{
    private $client;
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
	 * 根据clientId获得Client完整信息，在整个会话期内只有一个Client对象
	 * @param $token
	 */
	public function getClient2($clientId){
		if($this->hasCache2===true && $this->client!==NULL){
			Yii::trace("MiniClient2 cache 2 get clientId:".$clientId,"miniyun.cache2");
			return $this->client;
		}
		$this->client = MiniClient::getInstance()->getClient($clientId);
		return $this->client;
	}
}