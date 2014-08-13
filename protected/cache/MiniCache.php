<?php
/**
 * 迷你云支持Memcache一级缓存，将一些频繁的查询信息放入内存，降低数据库的压力
 * 这些存储的数据与会话状态无关，会话状态由cache2进行管理
 * 它里面不能使用二级缓存的对象
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MiniCache{
	/**
	 * 内存访问对象
	 * @var CMemCache
	 */
	private $cache;
	/**
	 * 系统级别的前缀
	 * @var string
	 */
	private $keyPrefix="cn.miniyun";
	/**
	 * 是否使用cache，比如memcache等载体
	 * @var boolean
	 */
	protected   $hasCache        = false;
	/**
	 * 
	 * cache失效时间,默认24小时
	 * @var int
	 */
	private     $cachingDuration = 86400;
	public function MiniCache(){
		if(isset($this->cache)){
			return true;
		}
		if(MEMCACHE_ENABLED){
			$cache                = Yii::app()->memcache;
			if($cache->isInitialized==false){
				$this->hasCache   = false;
			}else{
				$cache->keyPrefix = $this->keyPrefix;
				$this->cache      = $cache;
				$this->hasCache   = true;
			}
		}else{
			$this->hasCache   = false;
		}
	}
	public function get($key){
		if($this->hasCache===false){
			return false;
		}
		return $this->cache->get(MEMCACHE_KEY . $key);
	}

    /**
     * 获得当前用户
     * @return mixed
     */
    public function getCurrentUser(){
        return MUserManager::getInstance()->getCurrentUser();
    }
	public function set($key,$value){
		if($this->hasCache===false){
			return false;
		}
		return $this->cache->set(MEMCACHE_KEY . $key,$value,$this->cachingDuration);
	}
	public function deleteCache($key){
		if($this->hasCache===false){
			return true;
		}
		return $this->cache->delete(MEMCACHE_KEY . $key);
	}
}