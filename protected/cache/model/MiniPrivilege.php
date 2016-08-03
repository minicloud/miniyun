<?php
/**
 * 缓存privilege表的记录，V1.2.0该类接管部分privilege的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniPrivilege extends MiniCache{
	
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.privilege";

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
	 * 把数据库值序列化
	 * @param array $items
	 */
    private function db2list($items){
    	$data  = array();
		foreach($items as $item) {
			$value                 = array();
			$value["id"]           = $item["id"];
			$value["user_id"]      = $item["user_id"];
			$value["file_path"]    = $item["file_path"];
			$value["permission"]   = $item["permission"];
			$value["created_at"]   = $item["created_at"];
			$value["updated_at"]   = $item["updated_at"]; 
			array_push($data, $value);
		}
		return $data;
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
	 * 获得用户的共享列表
	 * @param int $userId
	 */
	public function getPrivilege($userId){
		$sql    = "user_id=:user_id";
		$order  = 'file_path desc';
		$params = array("user_id"=>$userId);
		$items  = UserPrivilege::model()->findAll(array('order'=>$order, 'condition'=>$sql,"params"=>$params));
		return  $this->db2list($items);
	}
}
?>