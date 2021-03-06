<?php
/**
 * 缓存miniyun_replicate_tasks表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniReplicateTask extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniReplicateTask";

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
        $value["id"]              = $item["id"];
        $value["file_signature"]  = $item["file_signature"];
        $value["node_id"]         = $item["node_id"];
        $value["status"]          = $item["status"];
        $value["created_at"]      = $item["created_at"];
        $value["updated_at"]      = $item["updated_at"];
        return $value;
    }

    /**
     * 删除记录
     * @param string $signature
     * @param int $nodeId
     */
    public function delete($signature,$nodeId){
        $item = ReplicateTask::model()->find("file_signature=:file_signature and node_id=:node_id",
            array(
                "file_signature"=>$signature,
                "node_id"=>$nodeId,
            ));
        if(isset($item)){
            $item->delete();
        }
    }

    /**
     * 推送任务到文件冗余备份服务器
     * @param $miniHost
     * @param $task
     */
    private function pushReplicateTask($miniHost,$task){
        $node = PluginMiniStoreNode::getInstance()->getNodeById($task->node_id);
        //如目标服务器不可用，则不用发送请求
        if($node["status"]==1){
            $signature = $task->file_signature;
            $version   = MiniVersion::getInstance()->getBySignature($signature);

            $downloadUrl = $miniHost."api.php?route=module/miniStore/download&signature=".$signature;
            $callbackUrl = $miniHost."api.php?route=module/miniStore/replicateReport&signature=".$signature."&node_id=".$node["id"];
            //向迷你存储发送冗余备份请求
            $data = array(
                'route'        => "file/replicate",
                'size'         => $version["file_size"],
                'signature'    => $signature,
                'download_url' => $downloadUrl,
                "callback_url" => $callbackUrl
            );
            $http = new HttpClient();
            $http->post($node["host"]."/api.php",$data);
            $content = $http->get_body();
            if(!empty($content)){
                $status = @json_decode($content)->{"status"};
                if($status==1){
                    //目标服务器接受请求后，更改任务状态
                    $task->status = 1;
                    $task->save();
                }
            }

        }
    }
    /**
     * 把所有超时的任务重新推送
     */
    public function pushTimeoutTask(){
        $fileCount = 0;
        $miniHost                = PluginMiniStoreOption::getInstance()->getMiniyunHost();
        $tasks = ReplicateTask::model()->findAll();
        foreach($tasks as $task){
            $this->pushReplicateTask($miniHost,$task);
            $fileCount++;
        }
        return $fileCount;
    }
    /**
     * 为前30个文件生成冗余备份任务
     * 并把任务推送到备份服务器
     * @param int $limit
     * @return int
     */
    public function replicateFile($limit=30){
        $miniHost                = PluginMiniStoreOption::getInstance()->getMiniyunHost();
        $criteria                = new CDbCriteria();
        $criteria->condition     = "replicate_status=0";
        $criteria->limit         = $limit;
        $criteria->offset        = 0;
        $versions = FileVersion::model()->findAll($criteria);
        $fileCount = 0;
        foreach($versions as $version){
            //设置replicate_status=1
            $signature = $version->file_signature;
            $version->replicate_status=1;
            $version->save();
            //为其它节点生成冗余备份记录
            $nodes = PluginMiniStoreNode::getInstance()->getReplicateNodes($signature);
            foreach($nodes as $node){
                $task = ReplicateTask::model()->find("file_signature=:file_signature and node_id=:node_id",
                    array(
                        "file_signature"=>$signature,
                        "node_id"=>$node["id"]
                    ));
                if(!isset($task)){
                    $task                 = new ReplicateTask();
                    $task->file_signature = $signature;
                    $task->node_id        = $node["id"];
                    $task->status         = 0;
                    $task->save();
                    $this->pushReplicateTask($miniHost,$task);
                    $fileCount++;
                }
            }
        }
        $this->replicateBreakFile();
        $this->overtime();
        return $fileCount;
    }
    //补偿在某个节点拉下的情况下，开启后文件不能备份的情况
    private function replicateBreakFile(){
        $miniHost                = PluginMiniStoreOption::getInstance()->getMiniyunHost();
        $criteria                = new CDbCriteria();
        $criteria->condition     = "meta_key='store_id' and meta_value not like '%%,%%' and TIME_TO_SEC(timediff(now(),created_at))>300";
        $metas = FileVersionMeta::model()->findAll($criteria);
        foreach($metas as $meta){
            $versionId = $meta->version_id;
            $version = FileVersion::model()->find("id=:id",array("id"=>$versionId));
            $signature = $version->file_signature;
             //为其它节点生成冗余备份记录
            $nodes = PluginMiniStoreNode::getInstance()->getReplicateNodes($signature);
            foreach($nodes as $node){
                $task = ReplicateTask::model()->find("file_signature=:file_signature and node_id=:node_id",
                    array(
                        "file_signature"=>$signature,
                        "node_id"=>$node["id"]
                    ));
                if(!isset($task)){
                    $task                 = new ReplicateTask();
                    $task->file_signature = $signature;
                    $task->node_id        = $node["id"];
                    $task->status         = 0;
                    $task->save();
                    $this->pushReplicateTask($miniHost,$task);
                }
            }
        }
    }
    //补偿一直都未处理的任务
    private function overtime(){
        $miniHost                = PluginMiniStoreOption::getInstance()->getMiniyunHost();
        $items = ReplicateTask::model()->findAll("TIME_TO_SEC(timediff(now(),created_at))>300");
        foreach($items as $task){
            $this->pushReplicateTask($miniHost,$task);
        }
    }
}