<?php
/**
 * 缓存miniyun_refresh_tokens表的记录，V1.2.0该类接管miniyun_refresh_tokens的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniRefreshToken extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.MiniRefreshToken";

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
	 * 按照token逐一放入内存
	 * @param string $token
     * @return string
	 */
	private function getCacheKey($token){
		return MiniRefreshToken::$CACHE_KEY."_".$token;
	}
	/**
	 * 通过db获得记录
	 * @param string $token
     * @return boolean
	 */
	private function get4Db($token){
		$item =  ORefreshToken::model()->find("oauth_token=:oauth_token",array("oauth_token"=>$token));
		if(isset($item)){
			$value = array();
			$value["client_id"]      = $item->client_id;
			$value["oauth_token"]    = $item->oauth_token;
			$value["refresh_token"]  = $item->refresh_token;
			$value["expires"]        = $item->expires;
			return  $value;
		}
		return false;
	}

	/**
	 * 根据token获得Token完整信息
	 * @param $token
     * @return array
	 */
	public function getToken($token){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->get4Db($token);
		}
		//先判断是否已经缓存，否则进行直接缓存
		$datastr   = $this->get($this->getCacheKey($token));
		if($datastr===false){
			Yii::trace(MiniRefreshToken::$CACHE_KEY." set token:".$token,"miniyun.cache1");
			$tokenObject = $this->get4Db($token);
			$this->set($this->getCacheKey($token),serialize($tokenObject));
		}else{
			Yii::trace(MiniRefreshToken::$CACHE_KEY." get token:".$token,"miniyun.cache1");
			$tokenObject = unserialize($datastr);
		}
		return $tokenObject;
	}
	/**
	 *
	 * 根据token删除 refresh的Token
	 * @param string $token
	 */
	public function deleteToken($token){
		$item =  ORefreshToken::model()->find("oauth_token=:oauth_token",array("oauth_token"=>$token));
		if(isset($item)){
			$item->delete();
		}
		if($this->hasCache){
			//删除一级缓存
			$key = $this->getCacheKey($token);
			$this->deleteCache($key);
		}
	}
	/**
	 * 新建对象
	 */
    public function create($oauthToken,$clientId,$refresh_token,$expires){
    	$item =  ORefreshToken::model()->find("oauth_token=:oauth_token",array("oauth_token"=>$token));
		if(!isset($item)){
			$item                = new ORefreshToken();
			$item->oauth_token   = $oauthToken;
			$item->client_id     = $clientId;
			$item->refresh_token = $refresh_token;
			$item->expires       = $expires;
			$item->save();
		}
    }
}