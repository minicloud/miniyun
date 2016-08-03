<?php
/**
 * 缓存miniyun_tokens表的记录，V1.2.0该类接管所有miniyun_tokens的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniToken extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.MiniToken";

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
	 * Token按照key逐一放入内存
	 * @param string $token
     * @return string
	 */
	private function getCacheKey($token){
		return MiniToken::$CACHE_KEY."_".$token;
	}
	private function db2Item($item){
		if(!isset($item)) return NULL;
		$value = array();
		$value["oauth_token"]    = $item["oauth_token"];
		$value["client_id"]      = $item["client_id"];
		$value["device_id"]      = $item["device_id"];
		$value["expires"]        = $item["expires"];
		$value["scope"]          = $item["scope"];
		return  $value;
	}
	/**
	 * 根据token获得Model对象
	 * @param string $token
     * @return array|boolean
	 */
	private function get4Db($token){
		$item =  OTokens::model()->find("oauth_token=:oauth_token",array("oauth_token"=>$token));
		if(isset($item)){
			return $this->db2Item($item);
		}
		return false;
	}
	/**
	 * 根据token获得Token对象
	 * @param $token
     * @return array
	 */
	private function getToken($token){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->get4Db($token);
		}
		//先判断是否已经缓存，否则进行直接缓存
		$dataStr         = $this->get($this->getCacheKey($token));
		if($dataStr===false){
			Yii::trace(MiniToken::$CACHE_KEY." set token:".$token,"miniyun.cache1");
			$tokenObject = $this->get4Db($token);
			$this->set($this->getCacheKey($token),serialize($tokenObject));
		}else{
			Yii::trace(MiniToken::$CACHE_KEY." get token:".$token,"miniyun.cache1");
			$tokenObject = unserialize($dataStr);
		}
		return $tokenObject;
	}
	/**
	 * 根据token获得Access的完整信息
	 * @param $token
     * @return array|NULL
	 */
	public function getAccessInfo($token){
		$tokenObject      = $this->getToken($token);
		if($tokenObject!=false){
			$clientId     = $tokenObject["client_id"];
			$clientObject = MiniClient::getInstance()->getClient($clientId);
			if($clientObject!==false){//只有Client存在的情况才返回Token
				$value                = array();
				$value["client_id"]   = $clientObject["client_id"];
				$value["oauth_token"] = $tokenObject["oauth_token"];
				$value["expires"]     = $tokenObject["expires"];
				$value["device_id"]   = $tokenObject["device_id"];
				$value["scope"]       = $tokenObject["scope"];
				return $value;
			}
		}
		return NULL;
	}
	/**
	 * 根据ClientId+DeviceId获得Token的Model对象
	 * @param int $clientId
	 * @param int $deviceId
     * @return model
	 */
	private function getTokenByClientAndDeviceId($clientId,$deviceId){
		$model = OTokens::model()->find("client_id=:clientId and device_id=:deviceId",array("clientId"=>$clientId,"deviceId"=>$deviceId));
		return $model;
	}
	/**
	 * 根据ClientId与DeviceId获得 Token对象
	 * @param string $clientId
	 * @param string $deviceId
     * @return array
	 */
	public function getToken4Login($clientId,$deviceId){
		$model = $this->getTokenByClientAndDeviceId($clientId, $deviceId);
		return $this->db2Item($model);
	}
	/**
	 *
	 * 用户修改密码后，把此前的Token进行清理
	 * 以便强制其它设备重新登陆，仅用于更新用户密码的场景updatePassword
	 * @param string $userId
	 */
	public function cleanByUserId($userId){
		$devices            = UserDevice::model()->findAll("user_id=:userId",array("userId"=>$userId));
		foreach($devices as $device){
			$deviceId       = $device["id"];
			$this->deleteToken($deviceId);	
		}
	}
	/**
	 * 根据设备删除Token
	 * @param int $deviceId
	 */
	public function deleteToken($deviceId){
		$tokenObjects    = OTokens::model()->findAll("device_id=:deviceId",array("deviceId"=>$deviceId));
		if(isset($tokenObjects)){
			foreach ($tokenObjects as $tokenObject){
				$token      = $tokenObject["oauth_token"];
				//删除refresh_token
				MiniRefreshToken::getInstance()->deleteToken($token);
				if($this->hasCache){
					//清理一级缓存
					$key    = $this->getCacheKey($token);
					$this->deleteCache($key);
				}
				//清理数据库记录
				$tokenObject->delete();
			}
		}
	}
	/**
	 * 新建Token
	 */
	public function create($oauth_token,$client_id,$device_id,$expires,$scope){
		$model = $this->getTokenByClientAndDeviceId($client_id, $device_id);
		if(!isset($model)){
			$model           = new OTokens();
		} 
		$model->oauth_token = $oauth_token;
		$model->client_id   = $client_id;
		$model->device_id   = $device_id;
		$model->expires     = $expires;
		$model->scope       = $scope;
		$model->save();
	}
    /**
     * 删除设备对应的accessToken
     * @param $deviceId
     */
    public function deleteByDeviceId($deviceId){
        $token = OTokens::model()->find("device_id=:id", array(":id"=>$deviceId));
        if(isset($token)){
            $accessToken = $token->oauth_token;
            ORefreshToken::model()->delete("oauth_token=:oauthToken",array(":oauthToken"=>$accessToken));
            $token->delete();
        }
    }
    /**
     * 删除设备对应的accessToken
     * @param $deviceIds
     */
    public function deleteByDeviceIds($deviceIds){
        $ids = explode(",",$deviceIds);
        foreach($ids as $id){
            $this->deleteByDeviceId($id);
        }
    }
}