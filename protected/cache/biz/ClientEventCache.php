<?php
/**
 * 客户端事件请求进行缓存，当客户端消费完毕所有事件后
 * 此后每次请求仅在db统计出max(admin.event.id)以及max(user.device_id.event.id)
 * 然后与内存进行比较，如果不想等则说明进行了变化，执行老逻辑，否则就返回0记录值
 * 这样可降低db连接压力
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ClientEventCache extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY="cache.biz.ClientEventCache";
	/**
	 *
	 * 获得用户在cache记录Key
	 * @param int $userId
	 */
	public function getUserKey($userId){
		return ClientEventCache::$CACHE_KEY."_user_".$userId;
	} 
	/**
	 *
	 * 获得设备在cache记录Key
	 * @param int $userId
	 */
	public function getDeviceEventKey($deviceId){
		return ClientEventCache::$CACHE_KEY."_device_event".$deviceId;
	} 
	/**
	 * 设置Cach点 
	 * @param int $deviceId
	 */
	public function setPoint($userId,$deviceId,$eventId){
		$userMaxId          = MiniEvent2::getInstance()->getMaxIdByUser2($userId);
		$this->set($this->getUserKey($userId), $userMaxId);        //当前用户最大的事件ID
		$this->set($this->getDeviceEventKey($deviceId), $eventId); //当前消费到的事件ID
	}
	/**
	 * 判断是否向db读取数据
	 * 返回值为true表示从db中获得，否则从cache中获得
	 * @param int $deviceId
	 * @return boolean 
	 */
	public function check2Db($userId,$deviceId,$eventId){
		$cacheUserId          = $this->get($this->getUserKey($userId));
		$cacheEventId         = $this->get($this->getDeviceEventKey($deviceId));
		if($cacheUserId==false || $cacheEventId==false){//当3者之一都不存在记录，
			return true;
		}
		$userMaxId            = MiniEvent2::getInstance()->getMaxIdByUser2($userId);
		if($cacheUserId!=$userMaxId ||  $cacheEventId!=$eventId ){
			return true;
		}
		return false;
	}
	/**
	 * 从Cache中获得事件列表 
	 * @return json 
	 */
	public function getValue(){
		$response              = array ();
		$response ["contents"] = array ();
		return  $response;
	}
}
?>