<?php
/**
 * 缓存整个miniyun_onlineDevices表的记录，V1.2.0该类接管所有miniyun_onlineDevices的操作
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniOnlineDevice extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY  = "cache.model.MiniOnlineDevice";

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
	 * Cache转换为hashTable
	 */
	private function cache2OnlineDevices(){
		$data = $this->get(MiniOnlineDevice::$CACHE_KEY);
		if($data==false){
			return false;
		}
		return unserialize($data);
	}
	/**
	 * 最近5分钟在线设备的记录
	 */
	private function getAll4Db(){
		$date    = date("Y-m-d H:i:s",time());
		$newDate = date('Y-m-d H:i:s',strtotime("$date -5 minute"));
		$items = OnlineDevice::model()->findAll("updated_at >= '{$newDate}' order by updated_at desc");
		$data  = array();
		foreach($items as $item) {
			$value = array();
			$value["device_id"]      = $item->device_id;
			$value["application_id"] = $item->application_id;
			$value["updated_at"]     = $item->updated_at;
			array_push($data, $value);
		}
		return $data;
	}
	/**
	 * 更新OnlineDevice
	 */
	private function set2Db($userId,$appId,$deviceId){
		$onlineDevice   = OnlineDevice::model()->find("user_id=? and application_id=? and device_id=?",array($userId,$appId,$deviceId));
		if(isset($onlineDevice)){
			$beforeDate = $onlineDevice->updated_at;
			if((time() - strtotime($beforeDate)) >= 5*60){
				$onlineDevice->updated_at = time();
				$onlineDevice->save();//更新update_at
			}
		}else{
			$onlineDevice                 = new OnlineDevice();
			$onlineDevice->user_id        = $userId;
			$onlineDevice->application_id = $appId;
			$onlineDevice->device_id      = $deviceId; 
			//兼容V2.2逻辑，PHP兼容Nodejs  
            if($appId==1){
                $clientId='JsQCsjF3yr7KACyT';
            }
            if($appId==2){
                $clientId='d6n6Hy8CtSFEVqNh';
            }
            if($appId==3){
                $clientId='c9Sxzc47pnmavzfy';
            }
            if($appId==4){
                $clientId='MsUEu69sHtcDDeCp';
            }
            if($appId==5){
                $clientId='V8G9svK8VDzezLum';
            }
            if($appId==6){
                $clientId='Lt7hPcA6nuX38FY4';
            } 
	        $onlineDevice->client_id = $clientId;
			$onlineDevice->save();
		}
		return true;
	}
	/**
	 * 设置设备下线
	 * @param string $deviceId
	 */
	private function setOffline2Db($deviceId){
		$online = OnlineDevice::model()->find("device_id=:device_id",array("device_id"=>$deviceId));
		if (!isset($online)){
			$data               = $online->updated_at;
			$newData            = date('Y-m-d H:i:s',strtotime("$data -6 minute"));
			$online->updated_at = $newData;
			$online->save();
		}
	}
	/**
	 * 获得当前在线设备总数
	 */
	public function getOnlineCount(){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return count($this->getAll4Db());
		}
		$dataStr = $this->get(MiniOnlineDevice::$CACHE_KEY);
		if($dataStr===false){
			return NULL;
		}
		$cacheItems   = unserialize($dataStr);
		return count($cacheItems);
	}
	/**
	 * 获得当前在线的设备
	 * 如果开启了内存管理，向内存请求的同时，清理掉过期的设备，然后将最新的集合写入cache
	 */
	public function getOnlineDevices(){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->getAll4Db();
		}
		$dataStr = $this->get(MiniOnlineDevice::$CACHE_KEY);
		if($dataStr===false){
			return NULL;
		}
		$hasClean     = false;
		$cacheItems   = unserialize($dataStr);
		Yii::trace(MiniOnlineDevice::$CACHE_KEY." get cache onlieDevice","miniyun.cache1");
		//把过期的设备清理掉
		foreach ($cacheItems as $deviceId=>$value){
			//value包括了{appid,time}
			if((time()-$value[1])>5*60){
				unset($cacheItems[$deviceId]);
				$hasClean = true;
			}
		}
		//把新的集合写入内存
		if($hasClean===true){
			$this->set(MiniOnlineDevice::$CACHE_KEY, serialize($cacheItems));
		}
		//返回值
		$data  = array();
		foreach ($cacheItems as $deviceId=>$value){
			$item = array();
			$item["device_id"]      = $deviceId;
			$item["application_id"] = $value[0];
			$item["updated_at"]     = $value[1];
			array_push($data, $item);
		}
		return $data;

	}
	/**
	 *设置设备下线
	 */
	public function setOfflineDevice($deviceId){
		if($this->hasCache===false){
			$this->setOffline2Db($deviceId);//更新到DB，保持V1.2.0以前的逻辑
			return;
		}
		$onlieDevices      = array();
		$dataStr           = $this->get(MiniOnlineDevice::$CACHE_KEY);
		if(!($dataStr===false)){
			$onlieDevices  = unserialize($dataStr);
			unset($onlieDevices[$deviceId]);

		}
		$this->set(MiniOnlineDevice::$CACHE_KEY, serialize($onlieDevices));
	}
	/**
	 * 更新设备状态值
	 */
	public function setOnlineDeviceValue($userId,$appId,$deviceId){
		if($userId==false || $appId==false || $deviceId==false){
			return;
		}
		if($this->hasCache===false){
			$item = $this->set2Db($userId,$appId,$deviceId);//更新到DB，保持V1.2.0以前的逻辑
			return;
		}
		if($this->hasCache===true){
			//TODO 这里存在隐患，因为每个客户端会定时请求，会存在获得值+解析的成本
			$onlineDevice            = array();
			$dataStr                 = $this->get(MiniOnlineDevice::$CACHE_KEY);
			if(!($dataStr===false)){
				$onlineDevice        = unserialize($dataStr);

			}
			$onlineDevice[$deviceId] = array($appId,time());
			Yii::trace(MiniOnlineDevice::$CACHE_KEY." set cache onlieDevice","miniyun.cache1");
			$this->set(MiniOnlineDevice::$CACHE_KEY, serialize($onlineDevice));
		}

	}
	/**
	 * 删除在线的设备
	 * @param int $deviceId
	 */
	public function deleteOnlineDevice($deviceId){
		//删除自己
		$criteria            = new CDbCriteria;
		$criteria->condition = "device_id=:device_id";
		$criteria->params    = array("device_id" =>$deviceId);
		OnlineDevice::model()->deleteAll($criteria);
	}
}