<?php
/**
 * 缓存miniyun_file_version_metas表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniFileVersionMeta extends MiniCache{
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
        $value["id"]                = $item->id;
        $value["version_id"]        = $item->version_id;
        $value["meta_key"]          = $item->meta_key;
        $value["meta_value"]        = $item->meta_value; 
        $value["created_at"]        = $item->created_at;
        $value["updated_at"]        = $item->updated_at;
        return $value;
    }
    /**
     * 获得有效文件下载地址
     * @param string $signature 文件内容hash
     * @param string $fileName 文件名
     * @param string $mimeType 文件的mimeType
     * @param int $forceDownload 是否要文件下载
     * @return string
     */
    public function getDownloadUrl($signature,$fileName,$mimeType,$forceDownload){
        $node = $this->getDownloadNode($signature);
        if(!empty($node)){
            //迷你存储服务器下载文件地址
            //对网页的处理分为2种逻辑，-1种是直接显示内容，1种是文件直接下载
            $data = array(
                "route"=>"file/download",
                "signature"=>$signature,
                "node_id"=>$node["id"],
                "file_name"=>$fileName,
                "mime_type"=>$mimeType,
                "force_download"=>$forceDownload
            );
            $url = $node["host"]."/api.php?";
            foreach($data as $key=>$value){
                $url .="&".$key."=".$value;
            }
            return $url;
        }
        return null;
    }
    /**
    * 获得有效文件下载服务器节点
    * 找到min(downloaded_file_count) and status=1的记录分配
    * @param string $signature 文件内容hash
    * @return array
    */
    public function getDownloadNode($signature){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(!empty($version)){
            $metaKey = "store_id";
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],$metaKey);
            if(!empty($meta)){
                $value = $meta["meta_value"];
                $ids = explode(",",$value); 
                $downloadFileCount = 0;
                $downloadNode = null;
                $nodes = PluginMiniStoreNode::getInstance()->getNodeList();
                foreach ($nodes as $node) {
                    //先找到当前文件存储的节点
                    $isValidNode = false;
                    foreach ($ids as $validNodeId) {
                        if($validNodeId==$node[id]){
                            $isValidNode = true;
                        }
                    }
                    if(!$isValidNode) break;
                    //然后判断节点是否有效，并在有效的节点找到下载次数最小的节点
                    if($node["status"]==1){
                        $currentFileCount = $node["downloaded_file_count"];
                        //初始化第一次
                        if($downloadFileCount===0){
                            $downloadFileCount = $currentFileCount;
                            $downloadNode = $node;
                        }
                        //轮训最小上传文件数的节点
                        if($downloadFileCount>$currentFileCount){
                            $downloadFileCount = $currentFileCount;
                            $downloadNode = $node;
                        }
                    }
                }
                return $downloadNode;
            }
        }
        return null;
    }
    
}