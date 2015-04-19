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
        $value["name"]                = $item->name;
        $value["host"]                = $item->host;
        $value["safe_code"]           = $item->safe_code;
        $value["status"]              = $item->status;
        $value["saved_file_count"]    = $item->saved_file_count;
        $value["downloaded_file_count"] = $item->downloaded_file_count;
        $value["created_at"]          = $item->created_at;
        $value["updated_at"]          = $item->updated_at;
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
    * 获得有效文件上传服务器节点
    * 找到min(saved_file_count) and status=1的记录分配
    */
    public function getUploadNode(){
        //TODO 对用户进行分区文件管理，需要找到迷你存储节点与用户的关系，然后进行分配处理
        $nodes = $this->getNodeList();
        $validNodes = array();
        foreach ($nodes as $node) {
            if ($node["status"] == 1) {
                array_push($validNodes, $node);
            }
        }
        //选出saved_file_count最小的个节点
        $validNodes = MiniUtil::arraySort($validNodes,"saved_file_count",SORT_ASC);
        $nodes = MiniUtil::getFistArray($validNodes,1);
        if(count($nodes)>0){
            $node    = $nodes[0];
            $urlInfo = parse_url($node["host"]);
            if($urlInfo["host"]=="127.0.0.1"){
                //说明迷你存储在本机，直接把127.0.0.1替换为迷你存储端口
                $defaultHost  = MiniHttp::getMiniHost();
                $node['host'] = substr($defaultHost,0,strlen($defaultHost)-1).":".$urlInfo["port"];
            }
            return $node;
        }
        return null;
    }
    /**
     * 获得迷你存储所有节点列表
     */
    public function getNodeList(){
        $items = StoreNode::model()->findAll();
        return $this->db2list($items);
    }
    /**
     * 检查所有节点状态
     */
    public function checkNodesStatus(){
        $items = StoreNode::model()->findAll();
        foreach($items as $item){
            $host = $item->host;
            $oldStatus = $item->status;
            $status = $this->checkNodeStatus($host);
            if($status!=$oldStatus){
                $item->status = $status;
                $item->save();
            }
        }
    }
    /**
     * 检查存储节点状态
     * @param string $host
     * @return int
     */
    public function checkNodeStatus($host){
        $url      = $host.'/api.php';
        $data = array (
            'route'        => "store/status",
            'callback_url' => PluginMiniStoreOption::getInstance()->getMiniyunHost()."info.htm"
        );
        $http   = new HttpClient();
        $http->post($url,$data);
        $result = $http->get_body();
        $result = @json_decode($result,true);
        if($result["status"]=="1"){
            return 1;
        }
        return -1;
    }
    /**
     * 节点新上传文件
     * @param int $nodeId
     */
    public function newUploadFile($nodeId){
        $item = StoreNode::model()->find("id=:id",array("id"=>$nodeId));
        if(isset($item)){
            $item->saved_file_count+=1;
            $item->save();
        }
    }
    /**
     * 节点新新下载了文件
     * @param int $nodeId
     */
    public function newDownloadFile($nodeId){
        $item = StoreNode::model()->find("id=:id",array("id"=>$nodeId));
        if(isset($item)){
            $item->downloaded_file_count+=1;
            $item->save();
        }
    }
    /**
     * 创建迷你存储节点
     * @param int $id 节点id
     * @param string $name 节点名称
     * @param string $host 节点域名
     * @param string $safeCode 节点访问的安全码
     * @return array
     */
    public function createOrModifyNode($id,$name,$host,$safeCode){
        if(!empty($id)){
            //修改节点信息
            $item = StoreNode::model()->find("id=:id",array("id"=>$id));
            //防止节点名称修改为了其它节点的名称
            if($item->name!=$name){
                $node = StoreNode::model()->find("name=:name",array("name"=>$name));
                if(isset($node)){
                    return null;
                }
            }
        }else{
            $item = StoreNode::model()->find("name=:name",array("name"=>$name));
            if(isset($item)){
                return null;
            }
        }
        if(!isset($item)){
            $item = new StoreNode();
            $item->saved_file_count=0;
            $item->downloaded_file_count=0;
        }
        $item->name      = $name;
        $item->host      = $host;
        $item->safe_code = $safeCode;
        $item->status    = -1;//所有新建或修改节点状态都是无效的
        $item->save();
        return $this->db2Item($item);
    }
    /**
     * 根据名称查询节点
     * @param string $name
     * @return array
     */
    public function getNodeByName($name){
        return $this->db2Item(StoreNode::model()->find("name=:name",array("name"=>$name)));
    }
    /**
     * 修改迷你存储节点状态
     * @param string $name 节点名称
     * @param int $status 节点状态
     * @return array
     */
    public function modifyNodeStatus($name,$status){
        //迷你存储节点状态只保留2个
        //1表示迷你存储节点生效,-1表示迷你存储节点无效
        if($status!=="1"){
            $status = "-1";
        }
        $item = StoreNode::model()->find("name=:name",array("name"=>$name));
        if(isset($item)){
            $item->status = $status;
            $item->save();
        }
        return $this->db2Item($item);
    }
    /**
     * 为文件生成其它冗余备份节点
     * 找到不属当前迷你存储节点，且status=1，saved_file_count最小的记录
     * @param string $signature 文件内容hash
     * @return array
     */
    public function getReplicateNodes($signature){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(!empty($version)) {
            $metaKey = "store_id";
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"], $metaKey);
            if (!empty($meta)) {
                $value = $meta["meta_value"];
                $ids = explode(",",$value);
                $validNodes = array();
                $nodes = PluginMiniStoreNode::getInstance()->getNodeList();
                foreach ($nodes as $node) {
                    //排除当前节点的迷你存储服务器
                    $isValidNode = false;
                    foreach ($ids as $validNodeId) {
                        if($validNodeId!=$node["id"]){
                            $isValidNode = true;
                        }
                    }
                    if(!$isValidNode) continue;
                    //然后判断服务器是否有效
                    if($node["status"]==1){
                        array_push($validNodes,$node);
                    }
                }
                //选出save_file_count最小的2个节点
                $validNodes = MiniUtil::arraySort($validNodes,"saved_file_count",SORT_ASC);
                $nodes = MiniUtil::getFistArray($validNodes,2);
                if(count($nodes)==2){
                    return $nodes;
                }
                return $nodes;
            }
        }

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
                "file_name"=>urlencode($fileName),
                "mime_type"=>$mimeType,
                "force_download"=>$forceDownload
            );
            $url = $node["host"]."/api.php?";
            foreach($data as $key=>$value){
                $url .="&".$key."=".$value;
            }
            //更新迷你存储节点状态，把新上传的文件数+1
            PluginMiniStoreNode::getInstance()->newDownloadFile($node["id"]);

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
    private function getDownloadNode($signature){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(!empty($version)){
            $metaKey = "store_id";
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],$metaKey);
            if(!empty($meta)){
                $value = $meta["meta_value"];
                $ids = explode(",",$value);
                $nodes = $this->getNodeList();
                $validNodes = array();
                foreach ($nodes as $node) {
                    //先找到当前文件存储的节点
                    $isValidNode = false;
                    foreach ($ids as $validNodeId) {
                        if($validNodeId==$node["id"]){
                            $isValidNode = true;
                        }
                    }
                    if(!$isValidNode) continue;
                    //然后判断节点是否有效，并在有效的节点找到下载次数最小的节点
                    if($node["status"]==1){
                        array_push($validNodes,$node);
                    }
                }
                //选出downloaded_file_count最小的个节点
                $validNodes = MiniUtil::arraySort($validNodes,"downloaded_file_count",SORT_ASC);
                $nodes = MiniUtil::getFistArray($validNodes,1);
                if(count($nodes)>0){
                    $node    = $nodes[0];
                    $urlInfo = parse_url($node["host"]);
                    if($urlInfo["host"]=="127.0.0.1"){
                        //说明迷你存储在本机，直接把127.0.0.1替换为迷你存储端口
                        $defaultHost  = MiniHttp::getMiniHost();
                        $node['host'] = substr($defaultHost,0,strlen($defaultHost)-1).":".$urlInfo["port"];
                    }
                    return $node;
                }
                return null;
            }
        }
        return null;
    }

    /**
     * 创建默认站点
     */
    public function createDefault(){
        $nodes = $this->getNodeList();
        if(count($nodes)>2){
            return true;
        }
        if(count($nodes)==1){
            $node = StoreNode::model()->find("id=:id",array("id"=>$nodes[0]["id"]));
        }else{
            $node = new StoreNode();
            $node->saved_file_count=0;
            $node->downloaded_file_count=0;
            $node->safe_code = "uBEEAcKM2D7sxpJD7QQEapsxiCmzPCyS";
            $node->name      = "store1";
        }
        $host = MiniHttp::getMiniHost();
        $node->host = substr($host,0,strlen($host)-1).":6081";
        $node->status = 1;
        $node->save();
    }
}