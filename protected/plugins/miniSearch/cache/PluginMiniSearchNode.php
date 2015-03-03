<?php
/**
 * 缓存miniyun_search_nodes表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniSearchNode extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniSearchNode";

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
        $value["build_file_count"]    = $item->build_file_count;
        $value["search_count"]        = $item->search_count;
        $value["status"]              = $item->status;
        $value["created_at"]          = $item->created_at;
        $value["updated_at"]          = $item->updated_at;
        return $value;
    } 
    /**
    * 根据ID获得迷你搜索节点
     * @param int $id 迷你搜索节点ID
     * @return array
    */
    public function getNodeById($id){
        $item = SearchNode::model()->find("id=:id",array("id"=>$id));
        if(isset($item)){
            return $this->db2Item($item);
        }
        return null;
    }

    /**
     * 获得迷你搜索所有节点列表
     */
    public function getNodeList(){
        $items = SearchNode::model()->findAll();
        return $this->db2list($items);
    }
    /**
     * 获得迷你搜索有效的节点列表
     */
    public function getValidNodeList(){
        $retVal = array();
        $nodes = $this->getNodeList();
        foreach($nodes as $node){
            if($node["status"]==1){
                array_push($retVal,$node);
            }
        }
        return $retVal;
    }
    /**
     * 检查所有节点状态
     */
    public function checkNodesStatus(){
        $items = SearchNode::model()->findAll();
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
     * 检查搜索节点状态
     * @param string $host
     * @return int
     */
    public function checkNodeStatus($host){
        $url = $host."/api.php?route=search/status";
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
     * 创建迷你搜索节点
     * @param int $id 节点id
     * @param string $name 节点名称
     * @param string $host 节点域名
     * @param string $safeCode 节点访问的安全码
     * @return array
     */
    public function createOrModifyNode($id,$name,$host,$safeCode){
        if(!empty($id)){
            //修改节点信息
            $item = SearchNode::model()->find("id=:id",array("id"=>$id));
            //防止节点名称修改为了其它节点的名称
            if($item->name!=$name){
                $node = SearchNode::model()->find("name=:name",array("name"=>$name));
                if(isset($node)){
                    return null;
                }
            }
        }else{
            $item = SearchNode::model()->find("name=:name",array("name"=>$name));
            if(isset($item)){
                return null;
            }
        }
        if(!isset($item)){
            $item = new SearchNode();
            $item->build_file_count=0;
            $item->search_count=0;
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
        return $this->db2Item(SearchNode::model()->find("name=:name",array("name"=>$name)));
    }
    /**
     * 修改迷你搜索节点状态
     * @param string $name 节点名称
     * @param int $status 节点状态
     * @return array
     */
    public function modifyNodeStatus($name,$status){
        //迷你搜索节点状态只保留2个
        //1表示迷你搜索节点生效,-1表示迷你搜索节点无效
        if($status!=="1"){
            $status = "-1";
        }
        $item = SearchNode::model()->find("name=:name",array("name"=>$name));
        if(isset($item)){
            $item->status = $status;
            $item->save();
        }
        return $this->db2Item($item);
    }
    /**
     * 节点新编制索引成功了一个文件
     * @param int $nodeId
     */
    public function newBuildFile($nodeId){
        $item = SearchNode::model()->find("id=:id",array("id"=>$nodeId));
        if(isset($item)){
            $item->build_file_count+=1;
            $item->save();
        }
    }
    /**
     * 节点新搜索了一次服务器
     * @param int $nodeId
     */
    public function newSearch($nodeId){
        $item = SearchNode::model()->find("id=:id",array("id"=>$nodeId));
        if(isset($item)){
            $item->search_count+=1;
            $item->save();
        }
    }

    /**
     * 获得最好的的迷你搜索节点
     * 先找出build_file_count最大的节点
     * 如果build_file_count相同，找出search_count最小的节点
     * @return array
     */
    public function getBestNode(){
        $nodes = $this->getValidNodeList();
        if(count($nodes)>0){
            $sortNodes = MiniUtil::arraySort($nodes,"build_file_count",SORT_DESC);
            $bestNode  = $sortNodes[0];
            $buildFileCount = $sortNodes[0]["build_file_count"];
            $searchCount = $sortNodes[0]["search_count"];
            foreach($sortNodes as $node){
                if($node["build_file_count"]==$buildFileCount){
                    if($node["search_count"]<$searchCount){
                        $bestNode = $node;
                    }
                }
            }
            return $bestNode;
        }
        return null;
    }
}