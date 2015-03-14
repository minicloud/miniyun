<?php
/**
 * 缓存miniyun_search_files表的记录
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniSearchFile extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.searchFile";

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
     * 把数据库值序列化
     * @param $items
     * @return array
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }
    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                     = array();
        $value["id"]               = $item->id;
        $value["file_signature"]   = $item->file_signature;
        $value["node_ids"]         = $item->node_ids;
        $value["created_at"]       = $item->created_at;
        $value["updated_at"]       = $item->updated_at;
        return $value;
    }
    /**
     * 将文本内容存入数据库
     * @param $signature 文件hash值
     * @return bool
     */
    public function  create($signature){
        $item = SearchFile::model()->find("file_signature=:file_signature",
            array("file_signature"=>$signature));
        if(!isset($item)){
            $item = new SearchFile();
            $item['file_signature']=$signature;
            $item['node_ids']="";
            $item->save();
            //生成索引编制任务
            PluginMiniSearchBuildTask::getInstance()->createTask($item->node_ids,$signature);
        }
        return true;
    }
    /**
     * 根据signature获得search_file的记录
     * @param $signature 文件的signature
     * @return array
     */
    public function  getBySignature($signature){
        $criteria = new CDbCriteria();
        $criteria->condition = "file_signature=:file_signature";
        $criteria->params = array(
            "file_signature"=>$signature
        );
        $item = SearchFile::model()->find($criteria);
        return $this->db2Item($item);
    }
    /**
     * 根据signature列表获得searchFile对象
     * @param array $signatures 文件sha1值列表
     * @return array
     */
    public function getBySignatures($signatures){
        $criteria = new CDbCriteria();
        $criteria->addInCondition('file_signature', $signatures);
        $items = SearchFile::model()->findAll($criteria);
        return $this->db2list($items);
    }
    /**
     * 迷你搜索节点编制索引成功
     * @param $signature
     * @param $nodeId
     * @return boolean
     */
    public function buildSuccess($signature,$nodeId){
        $criteria = new CDbCriteria();
        $criteria->condition = "file_signature=:file_signature";
        $criteria->params = array(
            "file_signature"=>$signature
        );
        $item = SearchFile::model()->find($criteria);
        if(isset($item)){
            //迷你搜索编制索引成功后，node_ids将追加
            $nodeIds = $item->node_ids;
            if(empty($nodeIds)){
                $item->node_ids = $nodeId;
            }else{
                $item->node_ids .= ",".$nodeId;
            }
            $item->save();
            return true;
        }
        return false;
    }
}