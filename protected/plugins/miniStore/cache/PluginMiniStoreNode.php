<?php
/**
 * 缓存miniyun_store_nodes表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreNode extends MiniVersion{
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
     * @param $item 数据库对象
     * @return array
     */
    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                        = array();
        $value["id"]                  = $item->id;
        $value["name"]                = $item->name;
        $value["host"]                = $item->host;
        $value["access_token"]        = $item->access_token;
        $value["status"]              = $item->status;
        $value["saved_file_count"]    = $item->saved_file_count;
        $value["downloaded_file_count"] = $item->downloaded_file_count;
        $value["created_at"]          = $item->created_at;
        $value["updated_at"]          = $item->updated_at;
        return $value;
    }
    /**
     * 获得迷你存储所有节点列表
     */
    public function getNodeList(){
        $items = StoreNode::model()->findAll();
        return $this->db2list($items);
    }
    /**
     * 创建迷你存储节点
     * @param $name 节点名称
     * @param $host 节点域名
     * @param $accessToken 节点访问的accessToken
     * @return array
     */
    public function createOrModifyNode($name,$host,$accessToken){
        $item = StoreNode::model()->find("name=:name",array("name"=>$name));
        if(!isset($item)){
            $item = new StoreNode();
            $item->saved_file_count=0;
            $item->download_file_count=0;
        }
        $item->name = $name;
        $item->host = $host;
        $item->access_token = $accessToken;
        $item->status = -1;//所有新建或修改节点状态都是无效的
        $item->save();
        return $this->db2Item($item);
    }
    /**
     * 修改迷你存储节点状态
     * @param $name 节点名称
     * @param $status 节点状态
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
}