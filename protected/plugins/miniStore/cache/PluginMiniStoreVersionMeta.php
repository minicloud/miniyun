<?php
/**
 * 缓存miniyun_file_version_metas表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreVersionMeta extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniFileVersionMeta";

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
        $value                      = array();
        $value["id"]                = $item["id"];
        $value["version_id"]        = $item["version_id"];
        $value["meta_key"]          = $item["meta_key"];
        $value["meta_value"]        = $item["meta_value"]; 
        $value["created_at"]        = $item["created_at"];
        $value["updated_at"]        = $item["updated_at"];
        return $value;
    }

    /**
     * 为文件增加冗余备份节点
     * @param $signature
     * @param $nodeId
     */
    public function addReplicateNode($signature,$nodeId){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(!empty($version)){
            $meta = FileVersionMeta::model()->find("version_id=:version_id and meta_key='store_id'",array("version_id"=>$version["id"]));
            if(isset($meta)){
                $value = $meta->meta_value;
                $isExist = false;
                $ids = explode(",",$value);
                foreach($ids as $id){
                    if($id===$nodeId){
                        $isExist = true;
                    }
                }
                if(!$isExist){
                    $value.=",".$nodeId;
                    $meta->meta_value = $value;
                    $meta->save();
                    $ids = explode(",",$value);
                    if(count($ids)>=3){
                        //整个文件冗余备份成功
                        PluginMiniStoreVersion::getInstance()->replicateSuccess($signature);
                    }
                }
            }else{
                $meta = new FileVersionMeta();
                $meta->version_id = $version["id"];
                $meta->meta_value = $nodeId;
                $meta->save();
            }
        }
    }
    
}