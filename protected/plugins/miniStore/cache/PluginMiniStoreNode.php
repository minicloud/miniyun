<?php
/**
 * 缓存miniyun_store_nodes表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreNode extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniStoreNode";

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
     * @param array $item 数据库对象
     * @return array
     */
    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                        = array();
        $value["id"]                  = $item->id;
        $value["ip"]                  = $item->ip;
        $value["port"]                = $item->port;       
        $value["key"]                 = $item->key;
        $value["secret"]              = $item->secret;
        $value["status"]              = $item->status;
        $value["running"]              = $item->running;
        $value["version"]             = $item->version;
        $value["saved_file_count"]    = $item->saved_file_count;
        $value["downloaded_file_count"] = $item->downloaded_file_count;
        $value["time_diff"]             = $item->time_diff;
        $value["disk_size"]             = $item->disk_size;
        $value["created_at"]            = $item->created_at;
        $value["updated_at"]            = $item->updated_at;
        return $value;
    } 
    /**
    * 根据ID获得迷你存储节点
    * 找到min(saved_file_count) and status=1的记录分配
     * @param int $id 迷你存储节点ID
     * @return array
    */
    public function getNodeById($id){
        $item = StoreNode::model()->find("id=:id",array("id"=>$id));
        if(isset($item)){
            return $this->db2Item($item);
        }
        return null;
    } 
    /**
     * 获得有效上传节点
     */
    public function getUploadNode(){
        $user = MUserManager::getInstance()->getCurrentUser();
        $meta = MiniUserMeta::getInstance()->getUserMeta($user['id'],'store_id');
        if(!empty($meta)){
            //如为该用户指定了存储点，则直接用该存储节点
            $node = $this->getNodeById($meta['meta_value']);
            if(!empty($node)){
                if($node['status']==1 && $node['running']==1){
                    return $node;
                }
            }
        }else{
            //在该公司列表下查询有效节点
            $items = StoreNode::model()->findAll();
            $nodes = $this->db2list($items);
            $validNodes = array();
            foreach($nodes as $node){
                if($node['status']==1 && $node['running']==1){
                    array_push($validNodes, $node);
                }
            }
            //选出saved_file_count最小的个节点
            $validNodes = MiniUtil::arraySort($validNodes,"saved_file_count",SORT_ASC);
            $nodes = MiniUtil::getFistArray($validNodes,1);
            if(count($nodes)>0){ 
                return $nodes[0];
            }
        }        
        return null;
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
        $version = MiniVersion::getInstance()->getBySignature($signature);
        $node = $this->getDownloadNode($version); 
        if(!empty($node)){ 
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],'bucket_path');
            //迷你存储服务器下载文件地址
            //对网页的处理分为2种逻辑，-1种是直接显示内容，1种是文件直接下载
            $data = array( 
                "path"=>urlencode($meta['meta_value']), 
                "file_name"=>urlencode($fileName), 
                "force_download"=>$forceDownload
            );
            $url = $node["host"];
            if(substr($url, strlen($url)-1,1)!="/"){
                $url .="/";
            }
            $url.="api/v1/file/download?";
            foreach($data as $key=>$value){
                $url .=$key."=".$value."&";
            } 
            //更新迷你存储节点状态，把新上传的文件数+1
            PluginMiniStoreNode::getInstance()->newDownloadFile($node["id"]);  
            return $url;
        }
        return null;
    }
    /**
     * 获得文档封面图片
     * @param Object $version 
     * @return string
     */
    public function getDocCoverPngUrl($version){ 
        $node = $this->getDownloadNode($version); 
        if(!empty($node)){ 
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],'bucket_path');
            $data = array( 
                "path"=>urlencode($meta['meta_value'])
            );
            $url = $node["host"];
            if(substr($url, strlen($url)-1,1)!="/"){
                $url .="/";
            }
            $url.="api/v1/doc/cover?";
            foreach($data as $key=>$value){
                $url .=$key."=".$value."&";
            } 
            return $url;
        }
        return null;
    }
    /**
     * 获得文档PDF文档地址
     * @param Object $version 
     * @return string
     */
    public function getDocPdfUrl($version){ 
        $node = $this->getDownloadNode($version); 
        if(!empty($node)){ 
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],'bucket_path');
            $data = array( 
                "path"=>urlencode($meta['meta_value'])
            );
            $url = $node["host"];
            if(substr($url, strlen($url)-1,1)!="/"){
                $url .="/";
            }
            $url.="api/v1/doc/pdf?";
            foreach($data as $key=>$value){
                $url .=$key."=".$value."&";
            } 
            return $url;
        }
        return null;
    }
     /**
     * 获得图片缩略图
     * @param string $signature 文件内容hash 
     * @return string
     */
    public function getThumbnailUrl($params){
        $signature = $params['signature'];
        $w = $params['w'];
        $h = $params['h'];
        $version = MiniVersion::getInstance()->getBySignature($signature);
        $node = $this->getDownloadNode($version); 
        if(!empty($node)){ 
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],'bucket_path');
            //迷你存储服务器下载文件地址
            //对网页的处理分为2种逻辑，-1种是直接显示内容，1种是文件直接下载
            $data = array( 
                "path"=>urlencode($meta['meta_value']),
                "w"=>$w,
                "h"=>$h
            );
            $url = $node["host"];
            if(substr($url, strlen($url)-1,1)!="/"){
                $url .="/";
            }
            $url.="api/v1/img/thumbnail?";
            foreach($data as $key=>$value){
                $url .=$key."=".$value."&";
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
    private function getDownloadNode($version){  
        if(!empty($version)){
            $metaKey = "store_id";
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],$metaKey);
            if(!empty($meta)){
                $id = $meta["meta_value"];
                $node =$this->getNodeById($id); 
                return $node;
            }
        }
        return null;
    } 
}