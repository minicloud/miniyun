<?php
/**
 * 缓存miniyun_userDeviceMetas表的记录，V1.2.0该类接管miniyun_userDeviceMetas的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniUserDeviceMeta extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.UserDeviceMeta";

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
	 * 按照id逐一放入内存
	 * @param string $token
	 */
	private function getCacheKey($id){
		return MiniUserDeviceMeta::$CACHE_KEY."_".$id;
	}
	/**
	 * 删除设备Meta
	 * @param int $id
	 */
	public function deleteMeta($deviceId){
		$metas = UserDeviceMeta::model()->findAll("device_id=:deviceId",array("deviceId"=>$deviceId));
		foreach ($metas as $meta){
			$meta->delete();
		}
	}
	/**
	 * 创建记录
	 * @param int    $user_id
	 * @param int    $device_id
	 * @param string $meta_name
	 * @param string $meta_value
	 */
	public function create($user_id, $device_id, $meta_name, $meta_value){
		$model             = new UserDeviceMeta(); 
		$model->device_id  = $device_id;
		$model->meta_name  = $meta_name;
		$model->meta_value = $meta_value;
		$model->save();
	}
	/**
	 * 把db对象转换为hashtable
	 */
	private function db2Item($item){
		if(!isset($item)) return NULL;
		$value               = array();
		$value["id"]         = $item->id; 
		$value["device_id"]  = $item->device_id;
		$value["meta_name"]  = $item->meta_name;
		$value["meta_value"] = $item->meta_value;
		return $value;
	}
	/**
	 * 获得设备的事件ID
	 * @param int $deviceId
	 * @param string $key
	 */
	public function getEvent($deviceId){
		$item = UserDeviceMeta::model()->find("device_id=:device_id and meta_name=:meta_name",array("device_id"=>$deviceId,"meta_name"=>'event_id'));
		return $this->db2Item($item);
	}
	/**
	 * 更新设备对应的事件ID
	 * @param int $deviceId
	 * @param string $key
	 */
	public function updateEvent($deviceId,$value){
		$item = UserDeviceMeta::model()->find("device_id=:device_id and meta_name=:meta_name",array("device_id"=>$deviceId,"meta_name"=>"event_id"));
		if(isset($item)){
			$item->meta_value = $value;
			$item->save();
		}
	}
	 
}
?>