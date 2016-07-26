<?php
/**
 * 缓存整个miniyun_options表的记录，V1.2.0该类接管所有miniyun_options的操作
 * 同时为Option添加会话缓存，也就是二级缓存，降低对caceh的压力以及反序列化开销
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniOption extends MiniCache{
	private $options = NULL;
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY    = "cache.model.MiniOption";

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
	 * 将整个表缓存到Cache
	 */
	private function db2Cache(){
		$data = $this->getAll4Db();
		$this->set(MiniOption::$CACHE_KEY, serialize($data));
		return $data;
	}
	/**
	 * 通过所有Options记录
	 */
	private function getAll4Db($companyId){
		$items                     = Option::model()->findAll("company_id=".$companyId);
		$data                      = array();
		foreach($items as $item) {
			$key                   = $item->option_name;
			$value                 = array();
			$value["option_id"]    = $item->option_id;
			$value["option_name"]  = $item->option_name;
			$value["option_value"] = $item->option_value;
			$data["$key"]          = $value;
		}
		return $data;
	}
	/**
	 * 更新Option的值
	 */
	private function set2Db($key,$value){
		$item                   =  Option::model()->find("option_name=:option_name",array("option_name"=>$key));
		if(!isset($item)){
			$item               = new Option();
			$item->option_name  = $key;
		}
		$item->option_value     = $value;
		$item->save();
		return $item;
	}
	/**
	 * 获得系统所有的Options
	 */
	public function getOptions($companyId){
		if($this->options!==NULL){
			Yii::trace(MiniOption::$CACHE_KEY." cache 2 Options","miniyun.cache2");
			return $this->options;
		}
		if($this->hasCache===false){
			//设置二级缓存
			$this->options =  $this->getAll4Db($companyId);
			return $this->options;
		}
		$dataStr    = $this->get(MiniOption::$CACHE_KEY);
		if($dataStr===false){
			//设置一级缓存
			Yii::trace(MiniOption::$CACHE_KEY." set cache Options","miniyun.cache1");
			$data = $this->db2Cache();
		}else{
			Yii::trace(MiniOption::$CACHE_KEY." get cache Options","miniyun.cache1");
			$data = unserialize($dataStr);
		}
		//设置二级缓存
		$this->options = $data;
		return $this->options;
	}
	 
	/**
	 * 更新value
	 * @param string $key
	 * @param string $value
	 */
	public function setOptionValue($key,$value){
		if($this->options===NULL){
			$this->options         = $this->getOptions();
		}
		//更新DB
		$item                      = $this->set2Db($key, $value);//更新到DB
		//更新二级缓存
		$cacheItem                 = array();
		$cacheItem["option_id"]    = $item->option_id;
		$cacheItem["option_name"]  = $item->option_name;
		$cacheItem["option_value"] = $item->option_value;
		$this->options[$key]       = $cacheItem;
		//更新一级缓存
		if($this->hasCache===true){
			$this->set(MiniOption::$CACHE_KEY, serialize($this->options));
		}
	}
	/**
	 *
	 * 根据key查询value的值
	 * @param string $key
     * @return array|null
     */
	public  function  getOptionValue($key){
		if($this->options===NULL){
			$this->options      = $this->getOptions();
		}
		Yii::trace(MiniOption::$CACHE_KEY." cache 2 Options key:".$key,"miniyun.cache2");
		if(array_key_exists($key, $this->options)){
			return $this->options[$key]["option_value"];
		}
		return NULL;
		 
	}
}