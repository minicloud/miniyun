<?php
/**
 * 缓存整个miniyun_tags表的记录，V1.2.0该类接管所有miniyun_tags的操作
 * 同时为Option添加会话缓存，也就是二级缓存，降低对caceh的压力以及反序列化开销
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniTag extends MiniCache{
	private $options = NULL;
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY    = "cache.model.MiniTag";

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
	 * 把数据库值序列化
	 */
	private function db2list($items){
		$data  = array();
		foreach($items as $item) {
			array_push($data, $this->db2Item($item));
		}
		return $data;
	}
	private function db2Item($item){
		if(empty($item)) return NULL;
		$value                     = array();
		$value["id"]               = $item["id"];
		$value["name"]             = $item["name"]; 
		$value["user_id"]          = $item["user_id"]; 
		return $value;
	}
    /**
     * 根据用户ID与文件ID获得标签
     */
	public function getByUserIdAndFileId($userId,$fileId){
		$condition  = 'id in (select tag_id from ' . FileTag::model()->tableName();
        $condition .= ' where file_id=:file_id) and user_id in(0,:user_id)';
        $items      = Tag::model()->findAll($condition, array(':file_id'=>$fileId,':user_id'=>$userId));
        return $this->db2list($items);
	}
    /**
     * 删除meta信息
     */
    public function deleteByFileId($fileId){
        $criteria            = new CDbCriteria;
        $criteria->condition = "file_id=:file_id";
        $criteria->params    = array("file_id" =>$fileId);
        FileTag::model()->deleteAll($criteria);
        return true;
    }
}