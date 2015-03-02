<?php
/**
 * 缓存miniyun_doc_nodes表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniDocNode extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniDocNode";

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
        $value["converted_file_count"] = $item->converted_file_count;
        $value["created_at"]          = $item->created_at;
        $value["updated_at"]          = $item->updated_at;
        return $value;
    } 
    /**
    * 根据ID获得迷你文档节点
     * @param int $id 迷你文档节点ID
     * @return array
    */
    public function getNodeById($id){
        $item = DocNode::model()->find("id=:id",array("id"=>$id));
        if(isset($item)){
            return $this->db2Item($item);
        }
        return null;
    }

    /**
     * 获得迷你文档所有节点列表
     */
    public function getNodeList(){
        $items = DocNode::model()->findAll();
        return $this->db2list($items);
    }
    /**
     * 检查所有节点状态
     */
    public function checkNodesStatus(){
        $items = DocNode::model()->findAll();
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
     * 获得迷你文档转换服务器节点
     * @param string $signature
     * @return string
     */
    public function getConvertNode($signature){
        //寻找以前迷你文档节点
        $version = PluginMiniDocVersion::getInstance()->getBySignature($signature);
        if(!empty($version)){
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],"doc_id");
            if(!empty($meta)){
                $nodeId = $meta["meta_value"];
                $node = $this->getNodeById($nodeId);
                if($node["status"]==1){
                    return $node;
                }
            }
        }
        //返回随机converted_file_count最小的节点
        $nodes = $this->getNodeList();
        $validNodes = array();
        foreach($nodes as $itemNode){
            if($itemNode["status"]==1){
                array_push($validNodes,$itemNode);
            }
        }
        //选出converted_file_count最小的个节点
        $validNodes = MiniUtil::arraySort($validNodes,"converted_file_count",SORT_ASC);
        $nodes = MiniUtil::getFistArray($validNodes,1);
        if(count($nodes)>0){
            return $nodes[0];
        }
        return null;
    }
    /**
     * 检查文档节点状态
     * @param string $host
     * @return int
     */
    public function checkNodeStatus($host){
        $url = $host."/api.php?route=node/status";
        $content = @file_get_contents($url);
        if(!empty($content)){
            $nodeStatus = @json_decode($content);
            if($nodeStatus->{"status"}=="1"){
                return 1;
            }
        }
        return -1;
    }
    /**
     * 创建迷你文档节点
     * @param int $id 节点id
     * @param string $name 节点名称
     * @param string $host 节点域名
     * @param string $safeCode 节点访问的安全码
     * @return array
     */
    public function createOrModifyNode($id,$name,$host,$safeCode){
        if(!empty($id)){
            //修改节点信息
            $item = DocNode::model()->find("id=:id",array("id"=>$id));
            //防止节点名称修改为了其它节点的名称
            if($item->name!=$name){
                $node = DocNode::model()->find("name=:name",array("name"=>$name));
                if(isset($node)){
                    return null;
                }
            }
        }else{
            $item = DocNode::model()->find("name=:name",array("name"=>$name));
            if(isset($item)){
                return null;
            }
        }
        if(!isset($item)){
            $item = new DocNode();
            $item->converted_file_count=0;
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
        return $this->db2Item(DocNode::model()->find("name=:name",array("name"=>$name)));
    }
    /**
     * 修改迷你文档节点状态
     * @param string $name 节点名称
     * @param int $status 节点状态
     * @return array
     */
    public function modifyNodeStatus($name,$status){
        //迷你文档节点状态只保留2个
        //1表示迷你文档节点生效,-1表示迷你文档节点无效
        if($status!=="1"){
            $status = "-1";
        }
        $item = DocNode::model()->find("name=:name",array("name"=>$name));
        if(isset($item)){
            $item->status = $status;
            $item->save();
        }
        return $this->db2Item($item);
    }
    /**
     * 节点新转换成功了一个文件
     * @param int $nodeId
     */
    public function newConvertFile($nodeId){
        $item = DocNode::model()->find("id=:id",array("id"=>$nodeId));
        if(isset($item)){
            $item->converted_file_count+=1;
            $item->save();
        }
    }
}