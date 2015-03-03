<?php
/**
 * 缓存miniyun_search_build_tasks表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniSearchBuildTask extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniSearchBuildTask";

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
        $value                    = array();
        $value["id"]              = $item->id;
        $value["file_signature"]  = $item->file_signature;
        $value["node_id"]         = $item->node_id;
        $value["status"]          = $item->status;
        $value["created_at"]      = $item->created_at;
        $value["updated_at"]      = $item->updated_at;
        return $value;
    }

    /**
     * 删除记录
     * @param string $signature
     * @param int $nodeId
     */
    public function delete($signature,$nodeId){
        $item = SearchBuildTask::model()->find("file_signature=:file_signature and node_id=:node_id",
            array(
                "file_signature"=>$signature,
                "node_id"=>$nodeId,
            ));
        if(isset($item)){
            $item->delete();
        }
    }

    /**
     * 后台检查所有的searchFile记录，为其生成索引文件
     * 定时每日凌晨做此工作
     */
    public function backupCreateTask(){
        $files = SearchFile::model()->findAll();
        foreach($files as $file){
            $this->createTask($file["node_ids"],$file["file_signature"]);
        }
        //向迷你搜索服务器发送请求
        PluginMiniSearchBuildTask::getInstance()->pushTask();
    }
    /**
     * 向迷你搜索服务器推送消息
     * 选出status=1的前30条记录，为其迷你搜索节点发送请求，发送请求成功后修改状态status=1
     */
    public function pushTask(){
        $miniHost = PluginMiniSearchOption::getInstance()->getMiniyunHost();
        $siteId   = MiniSiteUtils::getSiteID();
        //
        $criteria                = new CDbCriteria();
        $criteria->condition     = "status=0";
        $criteria->limit         = 30;
        $criteria->offset        = 0;
        $tasks = SearchBuildTask::model()->findAll($criteria);
        if(count($tasks)>0){
            foreach($tasks as $task){
                $nodeId = $task->node_id;
                $signature = $task->file_signature;
                $node = PluginMiniSearchNode::getInstance()->getNodeById($nodeId);
                if(!empty($node)){
                    $url = $node["host"].'/api.php?route=file/build';
                    $downloadUrl =$miniHost."api.php?route=module/miniSearch/downloadTxt&signature=".$signature;
                    $callbackUrl =$miniHost."api.php?route=module/miniSearch/report&node_id=".$node["id"]."&signature=".$signature;
                    $data = array (
                        'signature'=>$signature,
                        'site_id'=>$siteId,//站点ID
                        'downloadUrl' =>$downloadUrl,//文件内容下载地址
                        "callbackUrl"=>$callbackUrl//文档转换成功后的回调地址
                    );
                    $http = new HttpClient();
                    $http->post($url,$data);
                    $result =  $http->get_body();
                    $result = json_decode($result,true);
                    if($result['status']==1){
                        //修改task状态
                        $task->status=1;
                        $task->save();
                    }
                }

            }

        }
    }
    /**
     * 为searchFile对象生成索引编制对象
     * @param string $nodeIds
     * @param string $signature
     */
    public function createTask($nodeIds,$signature){
        $ids = explode(",",$nodeIds);
        //为索引服务器生成索引记录记录
        $nodes = PluginMiniSearchNode::getInstance()->getValidNodeList();
        foreach($nodes as $node){
            $existed = false;
            $nodeId = $node["id"];
            foreach($ids as $id){
                if($id==$nodeId){
                    $existed = true;
                }
            }
            if(!$existed){
                $task = new SearchBuildTask();
                $task->file_signature = $signature;
                $task->node_id = $node["id"];
                $task->status = 0;
                $task->save();
            }

        }
    }

}