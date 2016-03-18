<?php
/**
 * 缓存miniyun_break_files表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniBreakFile extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniBreakFile";

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
     * 把数据库记录集合序列化
     * @param $items 数据库对象集合
     * @return array
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }
    /**
     * 把数据库记录序列化
     * @param $item 数据库对象
     * @return array
     */
    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                        = array();
        $value["id"]                  = $item->id;
        $value["file_signature"]      = $item->file_signature;
        $value["store_node_id"]       = $item->store_node_id; 
        $value["created_at"]          = $item->created_at;
        $value["updated_at"]          = $item->updated_at;
        return $value;
    } 
    /**
     * 根据文件signature获得记录
     * @param string $signature 文件hash值
     * @param string $storeNodeId 迷你存储节点ID
     * @return array
     */
    public function create($signature,$storeNodeId){
        $item = BreakFile::model()->find("file_signature=:file_signature",array(
                "file_signature"=>$signature
            ));
        if(!isset($item)){
            $item = new BreakFile();
        }
        $item->file_signature = $signature;
        $item->store_node_id = $storeNodeId;
        $item->save();
        return $this->db2Item($item);
    }
    /**
     * 删除记录
     * @param string $signature 文件hash值
     * @return array
     */
    public function deleteBySignature($signature){
        $item = BreakFile::model()->find("file_signature=:file_signature",array(
            "file_signature"=>$signature
        ));
        if(isset($item)){
            $item->delete();
        }
    }
    /**
     * 根据文件signature获得记录
     * @param string $signature 
     * @return array
     */
    public function getBySignature($signature){
        $item = BreakFile::model()->find("file_signature=:file_signature",array(
                "file_signature"=>$signature
            ));
        if(isset($item)){
            return $this->db2Item($item);
        }
        return null;
    }
}