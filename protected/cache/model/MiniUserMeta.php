<?php
/**
 * 缓存miniyun_userMetas表的记录，V1.2.0该类接管所有miniyun_userMetas的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniUserMeta extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.UserMeta";

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
	 * 按照userId逐一放入内存
	 * @param int $userId
     * @return string
     */
	private function getCacheKey($userId){
		return MiniUserMeta::$CACHE_KEY."_".$userId;
	}
	/**
	 * 通过db获得记录
	 * @param int $userId
     * @return array|null
     */
	private function get4Db($userId){
		
		$items                       =  UserMeta::model()->findAll("user_id=:user_id",array("user_id"=>$userId));
        if(isset($items)){
			 $value                  = array(); 
			 foreach($items as $item){
			 	$itemKey             = $item["meta_key"];
			 	$itemValue           = $item["meta_value"];
			 	if($itemKey==="space"){
			 		$value["space"]  = doubleval($itemValue)*1024*1024;
			 	}else{
			 		$value[$itemKey] = $itemValue;
			 	} 
			}
			return  $value;
		}
		return NULL;
		
	}
	 
	/**
	 * 根据userId完整信息
	 * 用于MiniUser->getUser()，返回用户信息的时候，一并组装出完整的用户对象
	 * @param $userId
     * @return array|mixed|null
     */
	public function getUserMetas($userId){
		
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->get4Db($userId);
		}
		//先判断是否已经缓存，否则进行直接缓存
		$dataStr    = $this->get($this->getCacheKey($userId));
		if($dataStr===false){
			Yii::trace(MiniUserMeta::$CACHE_KEY." set cache User userId:".$userId,"miniyun.cache1");
			$object = $this->get4Db($userId);
			$this->set($this->getCacheKey($userId),serialize($object));
		}else{
			Yii::trace(MiniUserMeta::$CACHE_KEY." get cache User userId:".$userId,"miniyun.cache1");
			$object = unserialize($dataStr);
		} 
		return $object;
	} 
	/**
	 * 更新用户属性
	 * @param int    $userId
	 * @param string $key
	 * @param string $value
     * @return array
     */
	public function updateMeta($userId,$key,$value){
		//更新数据库
		$userMeta= UserMeta::model()->findByAttributes(array('user_id'=>$userId, 'meta_key'=>$key));
        if(empty($userMeta)){
           $userMeta             = new UserMeta();
           $userMeta["user_id"]  = $userId;
           $userMeta["meta_key"] = $key;
         }
         $userMeta["meta_value"] = $value;
         $userMeta->save();
         if($this->hasCache===true){
         	//清空缓存以用户Id为主键的cache
			$userCacheId       = $this->getCacheKey($userId);
			$this->deleteCache($userCacheId);
			//清空缓存用户信息
			MiniUser::getInstance()->cleanCache($userId);
         }
        if($key==="nick"){
            //如修改昵称，则将用户的拼音信息一起更换
            MiniUser::getInstance()->updateUserNamePinYin($userId);
        }
        if($key==="is_admin"){
        	//兼容3.0逻辑
        	$criteria            = new CDbCriteria();
	        $criteria->condition = 'id=:id';
	        $criteria->params    = array('id'=> $userId);
	        $user = User::model()->find($criteria);
	        if(isset($user)){ 
	            $user->role = ($value=='1'?9:1);
	            $user->save(); 
	        }
        }
        return $userMeta;
	}
	/**
	 * 删除用户的Meta与cache
	 * @param int $userId
	 */
	public function deleteMeta($userId){
		UserMeta::model()->deleteAll("user_id=:userId",array("userId"=>$userId));
		if($this->hasCache){
			//清空缓存，以用户Id为主键的cache
			$userCacheId       = $this->getCacheKey($userId);
			$this->deleteCache($userCacheId);
		}
	}
    public function deleteAvatar($userId,$avatar){
        $criteria            = new CDbCriteria();
        $criteria->condition = 'meta_value=:meta_value';
        $criteria->params    = array('meta_value'=> $avatar);
        $userMeta = UserMeta::model()->find($criteria);
        if(isset($userMeta)){
            $userMeta->delete();
            return true;
        }
        return false;
    }
    /**
     * 添加用户meta信息
     * @param $metas 用户的一些扩展信息
     * @param $user 用户名、用户状态及uuid
     *
     */
	public function create($user,$metas){
		//存储用户扩展信息
		if (array_key_exists('extend', $metas) && !empty($metas['extend'])){
			foreach ($metas['extend'] as $key=>$value){
				if ($value === null) {
					continue;
				}
				$userMeta =UserMeta::model()->find("user_id=:user_id and meta_key=:key", array(":user_id"=>$user['id'],':key'=>$key));
                if (!empty($userMeta)){
					if ($userMeta['meta_value'] != $value){
						$userMeta['meta_value']  = $value;
						$userMeta->save();
					}
				}else{
					$userMeta = new UserMeta();
					$userMeta["user_id"]   = $user["id"];
					$userMeta["meta_key"]  = $key;
					$userMeta["meta_value"]= $value;
					$userMeta->save();
				}
				if($key==="is_admin"){
		        	//兼容3.0逻辑
		        	$criteria            = new CDbCriteria();
			        $criteria->condition = 'id=:id';
			        $criteria->params    = array('id'=> $user["id"]);
			        $user = User::model()->find($criteria);
			        if(isset($user)){ 
			            $user->role = ($value=='1'?9:1);
			            $user->save(); 
			        }
		        }
			}
		}
	}
}