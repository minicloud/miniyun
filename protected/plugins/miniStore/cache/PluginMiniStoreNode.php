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
        $value["running"]             = $item->running;
        $value["plugin_info"]         = $item->plugin_info;
        $value["version"]             = $item->version;
        $value["saved_file_count"]    = $item->saved_file_count;
        $value["downloaded_file_count"] = $item->downloaded_file_count;
        $value["time_diff"]             = $item->time_diff;
        $value["disk_size"]             = $item->disk_size;
        $value["created_at"]            = $item->created_at;
        $value["updated_at"]            = $item->updated_at;
        $host = '//'.$value['ip'];
        if(!($value['port']==80 || $value['port']==443)){
            $host.=':'.$value['port'].'/';
        }
        $value['host'] = $host;
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
    * 新上传文件 
    */
    public function newUploadFile($node){
        $item = StoreNode::model()->find("id=:id",array("id"=>$node['id']));
        if(isset($item)){
            $item->saved_file_count++;
            $item->save();
        }
    }
    /**
    * 新下载文件 
    */
    private function newDownloadFile($node){
        $item = StoreNode::model()->find("id=:id",array("id"=>$node['id']));
        if(isset($item)){
            $item->downloaded_file_count++;
            $item->save();
        }
    }
    public function getNodeList(){
         //在该公司列表下查询有效节点
        $items = StoreNode::model()->findAll();
        $nodes = $this->db2list($items);
        $validNodes = array();
        foreach($nodes as $node){
            if($node['status']==1 && $node['running']==1){                
                array_push($validNodes, $node);
            }
        }
        return $validNodes;
    }
    /**
     * 根据Key查询节点
     */
    public function getByKey($key){  
        $items = StoreNode::model()->findAll();
        $nodes = $this->db2list($items);
        foreach($nodes as $node){
            if($node['key']===$key){
                return $node;
            }
        }
        return null;
    }
    /**
     * 获得有效上传节点
     */
    public function getUploadNode(){
        $user = MUserManager::getInstance()->getCurrentUser();
        if(empty($user)) return null;
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
            $validNodes = $this->getNodeList();
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
     * 签名URL地址
     */
    private function signatureUrl($url,$node,$bucketPath,$params){ 
        $now = time()+$node['time_diff']/1000;
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;         
        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition; 
        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$bucketPath,3=>$end);
        $conditions[] = $start; 
        $arr = array('conditions'=>$conditions);  
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $node['secret'], true));
        $url .='policy='.urlencode($base64_policy).'&signature='.urlencode($signature);
        foreach($params as $key=>$value){
            $url .="&".$key."=".urlencode($value);
        } 
        return $url;
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
                "file_name"=>$fileName, 
                "force_download"=>$forceDownload
            );
            $url = $node["host"]."api/v1/file/download?";
            $url = $this->signatureUrl($url,$node,$meta['meta_value'],$data); 
            //更新迷你存储节点状态，把新上传的文件数+1
            $this->newDownloadFile($node["id"]);   
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
            );
            $url = $node["host"]."api/v1/doc/cover?";
            $url = $this->signatureUrl($url,$node,$meta['meta_value'],$data);  
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
            );
            $url = $node["host"]."api/v1/doc/pdf?";
            $url = $this->signatureUrl($url,$node,$meta['meta_value'],$data); 
            return $url;
        }
        return null;
    }
    /**
     * 获得视频封面图片
     * @param Object $version 
     * @return string
     */
    public function getVideoCoverPngUrl($version){ 
        $node = $this->getDownloadNode($version); 
        if(!empty($node)){ 
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],'bucket_path');
            $data = array(  
            );
            $url = $node["host"]."api/v1/video/cover?";
            $url = $this->signatureUrl($url,$node,$meta['meta_value'],$data);  
            return $url;
        }
        return null;
    }
    /**
     * 获得视频mp4文档地址
     * @param Object $version 
     * @return string
     */
    public function getVideoContentUrl($version){ 
        $node = $this->getDownloadNode($version); 
        if(!empty($node)){ 
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],'bucket_path');
            $data = array(  
            );
            $url = $node["host"]."api/v1/video/content?";
            $url = $this->signatureUrl($url,$node,$meta['meta_value'],$data); 
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
                "w"=>$w,
                "h"=>$h
            );
            $url = $node["host"]."api/v1/img/thumbnail?";
            $url = $this->signatureUrl($url,$node,$meta['meta_value'],$data);
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
    private function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
    /**
    *把/1/xxx 替换为 /xxx
    */ 
    private function getBucketPath($user,$path){ 
        $prefix = '/'.$user['id'].'/';
        $path = str_replace($prefix,'/', $path);
        $bucketPath = 'minicloud/'.$user['user_name'].$path;
        return $bucketPath;
    }
    /**
     * 文档或视频第二次转换地址
     */
    public function getConvertUrl($file,$version){
        $user = MUserManager::getInstance()->getCurrentUser(); 
        $bucketPath = $this->getBucketPath($user,$file['file_path']);
        $hash = $version['file_signature'];
        $storeNode = $this->getDownloadNode($version);
        //回调地址是阿里云接收文件成功后，反向调用迷你云的地址报竣        
        $now = time()+$storeNode['time_diff']/1000;
        $expire = 60; //设置该policy超时时间是60秒. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);
        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition; 

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$bucketPath,3=>$end);
        $conditions[] = $start; 


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);  
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $storeNode['secret'], true));

        $context = array();
        $token = MiniHttp::getParam('access_token','');
        $callbackUrl = MiniHttp::getMiniHost()."api.php"; 
        $context['policy'] = $base64_policy;
        $context['signature'] = $signature;
        $context['expire'] = $end;
        $context['hash'] = $hash;
        //添加文档转换回掉地址
        $isDoc = MiniUtil::isDoc($bucketPath);
        if($isDoc){
            $callbackParam = array('callbackUrl'=>$callbackUrl, 
                     'callbackBody'=>'access_token='.$token.'&route=convert/docStart&node_key='.$storeNode['key'].'&signature='.$signature.'&policy='.$base64_policy.'&hash=${etag}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded");
            $callbackParamString = json_encode($callbackParam); 
            $context['doc_convert_start_callback'] = base64_encode($callbackParamString);

            $callbackParam = array('callbackUrl'=>$callbackUrl, 
                     'callbackBody'=>'access_token='.$token.'&route=convert/docEnd&node_key='.$storeNode['key'].'&signature='.$signature.'&policy='.$base64_policy.'&success=${success}&hash=${etag}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded"); 
            $callbackParamString = json_encode($callbackParam); 
            $context['doc_convert_end_callback'] = base64_encode($callbackParamString);
        }else{
            //添加视频转换回掉地址
            $isVideo = MiniUtil::isVideo($bucketPath);
            if($isVideo){
                $callbackParam = array('callbackUrl'=>$callbackUrl, 
                     'callbackBody'=>'access_token='.$token.'&route=convert/videoStart&node_key='.$storeNode['key'].'&signature='.$signature.'&policy='.$base64_policy.'&hash=${etag}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded");
                $callbackParamString = json_encode($callbackParam); 
                $context['video_convert_start_callback'] = base64_encode($callbackParamString);

                $callbackParam = array('callbackUrl'=>$callbackUrl, 
                         'callbackBody'=>'access_token='.$token.'&route=convert/videoEnd&node_key='.$storeNode['key'].'&signature='.$signature.'&policy='.$base64_policy.'&hash=${etag}&success=${success}', 
                         'callbackBodyType'=>"application/x-www-form-urlencoded"); 
                $callbackParamString = json_encode($callbackParam); 
                $context['video_convert_end_callback'] = base64_encode($callbackParamString);
            }
        } 
        if($isDoc){
            $url = $storeNode["host"]."api/v1/doc/convert?";
        }else{
            if($isVideo){
                $url = $storeNode["host"]."api/v1/video/convert?";
            }
        }
        foreach($context as $key=>$value){
            $url .="&".$key."=".urlencode($value);
        } 
        return $url;
    }
}