<?php
/**
 * 缓存miniyun_file_versions表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreVersion extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniDocVersion";

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
     * 按照id逐一放入内存
     */
    private function getCacheKey($id){
        return PluginMiniDocVersion::PluginMiniStoreVersion."_".$id;
    }
    /**
     * 把数据库值序列化
     * @param $items version表记录列表
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
     * 通过id获得记录
     * @param string $id
     * @return array
     */
    private function get4DbById($id){
        $item =  FileVersion::model()->find("id=:id",array("id"=>$id));
        return $this->db2Item($item);
    }
    /**
     * 把数据库值序列化
     * @param $item version表记录
     * @return array
     */
    private function db2Item($item){
        if(!isset($item)) return NULL;
        $value                   = array();
        $value["id"]             = $item->id;
        $value["file_signature"] = $item->file_signature;
        $value["file_size"]      = $item->file_size;
        $value["ref_count"]      = $item->ref_count;
        $value["mime_type"]      = $item->mime_type;
        $value["created_at"]      = $item->created_at;
        $value["createTime"]      = strtotime($item->created_at);
        $value['replicate_status'] = $item->replicate_status;
        return  $value;
    }

    /**
     * 冗余备份成功
     * @param $signature
     */
    public function replicateSuccess($signature){
        $item = FileVersion::model()->find("file_signature=:file_signature",array("file_signature"=>$signature));
        if(isset($item)){
            $item->replicate_status = 2;
            $item->save();
        }
    }
}