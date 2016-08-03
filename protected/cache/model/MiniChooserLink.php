<?php
/**
 * 缓存miniyun_chooser_links表的记录，V1.2.0该类接管部分miniyun_chooser_links的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniChooserLink extends MiniCache{

	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.chooserLink";

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
	 
	private function db2Item($item){
		if(empty($item)) return NULL;
		$value                = array();
		$value["app_key"]     = $item["app_key"];
        $value["link_id"]     = $item["link_id"];
		$value["created_at"]  = $item["created_at"]; 
		$value["updated_at"]  = $item["updated_at"];
		return $value;
	}
    /**
     * 把数据库值序列化
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
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
     * 创建对象
     * @param int $linkId
     * @param string $appKey
     * @param string $session
     * @return bool
     */
	public function create($linkId,$appKey,$session){
		$mode = ChooserLink::model()->find("app_key=:app_key and link_id=:link_id",array("app_key"=>$appKey,"link_id"=>$linkId));
		if(!isset($mode)){
			$mode  = new ChooserLink();
		}
		$mode->app_key = $appKey;
		$mode->link_id = $linkId;
        $mode->session = $session;
		$mode->save();
		return true;
	}

    /**根据link _id删除数据
     * @param $linkId
     * @return int
     */
    public function deleteByLinkId($linkId){
        $data= ChooserLink::model()->deleteAll("link_id=".$linkId);
        return $data;
    }
    /**
     * 根據session獲取link對象
     */
    public function getBySession($session){
        $item = ChooserLink::model()->findAll("session=:session",array("session"=>$session));
        return $this->db2list($item);
    }
}