<?php
/**
 * 缓存file_version_meta表的记录，V1.2.0该类接管file_version_meta的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniVersionMeta extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.MiniVersionMeta";
    public static $MINI_DOC_SERVER = "mini_doc_server_id";
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
	private function db2Item($item){
		if(!isset($item)) return NULL;
		$value                   = array();
		$value["id"]             = $item->id;
		$value["version_id"]     = $item->version_id;
		$value["meta_key"]       = $item->meta_key;
		$value["meta_value"]     = $item->meta_value; 
		return  $value;
	}
	/**
	 * 根据version.id+version.key获得version的相关记录
	 */
	public function getMeta($id,$key){
		$criteria            = new CDbCriteria();
		$criteria->condition = "version_id=:version_id and meta_key =:meta_key";
		$criteria->params    = array('version_id'=>$id, 'meta_key' => $key);
		$item                = FileVersionMeta::model()->find($criteria);
		return $this->db2Item($item);
	}
    /**
     *
     * 创建版本的meta信息
     * @param int $id
     * @param string $key
     * @param string $value
     *
     * @since 1.1.2
     */
    public function create($id, $key, $value){
    	$criteria                 = new CDbCriteria();
		$criteria->condition      = "version_id=:version_id and meta_key =:meta_key";
		$criteria->params         = array('version_id'=>$id, 'meta_key' => $key);
        $meta                     = FileVersionMeta::model()->find($criteria);
        if (empty($meta)){
            $meta                 = new FileVersionMeta();
            $meta["version_id"]   = $id;
            $meta["meta_key"]     = $key;
            $meta["meta_value"]   = $value;
            $meta->save();
        } else {
            if ($meta["meta_value"] != $value){
                $meta["meta_value"]  = $value;
                $meta->save();
            }
        }
    }
    /**
     * 根据versionId删除所有记录
     * @param $versionId
     */
    public function deleteByVersionId($versionId){
    	FileVersionMeta::model()->deleteAll("version_id=:version_id", array("version_id"=>$versionId));
    }
}