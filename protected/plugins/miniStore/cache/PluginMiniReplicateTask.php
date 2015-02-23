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
     * @param $id
     */
    private function delete($id){
        $item = ReplicateTask::model()->find("id=:id",array("id"=>$id));
        if(isset($item)){
            $item->delete();
        }
    }
    /**
     *为miniyun_replicate_tasks.status=0前30条记录向对应服务器发送请求
     */
    public function replicate(){
        $criteria                = new CDbCriteria();
        $criteria->limit         = 30;
        $criteria->offset        = 0;
        $tasks = $this->db2list(ReplicateTask::model()->findAll($criteria));
        foreach($tasks as $task){
            $node = PluginMiniStoreNode::getInstance()->getNodeById($task["node_id"]);
            if($node["status"]==1){
                $signature = $task["file_signature"];
                //文件下载地址
                $miniHost = PluginMiniStoreOption::getInstance()->getMiniyunHost();
                $downloadUrl = $miniHost."api.php?route=module/miniStore/download&signature=".$signature;
                //向迷你存储发送冗余备份请求
                $data = array(
                    'route'=>"file/replicate",
                    'signature'=>$signature,
                    'downloadUrl'=>$downloadUrl,
                );
                $http = new HttpClient();
                $http->post($node["host"]."/api.php",$data);
                $content = $http->get_body();
                if(!empty($content))
                {
                    $status = @json_decode($content)->{"status"};
                    if($status==1)
                    {
                        //冗余备份成功,为miniyun_file_version_metas.meta_value新增冗余的节点
                        PluginMiniStoreVersionMeta::getInstance()->addReplicateNode($signature,$node["id"]);
                        //修改存储节点的miniyun_store_node.save_file_count+=1
                        PluginMiniStoreNode::getInstance()->newUploadFile($node["id"]);
                        //删除冗余备份的任务
                        $this->delete($task["id"]);
                    }
                }

            }
        }
    }
    /**
     *为miniyun_file_versions.replicate_status=0前10条记录生成冗余备份记录
     */
    public function createReplicateTask(){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "replicate_status=0";
        $criteria->limit         = 10;
        $criteria->offset        = 0;
        $versions = FileVersion::model()->findAll($criteria);
        foreach($versions as $version){
            //设置replicate_status=1
            $signature = $version->file_signature;
            $version->replicate_status=1;
            $version->save();
            //为其它节点生成冗余备份记录
            $nodes = PluginMiniStoreNode::getInstance()->getReplicateNodes($signature);
            foreach($nodes as $node){
                $task = new ReplicateTask();
                $task->file_signature = $signature;
                $task->node_id = $node["id"];
                $task->status = 0;
                $task->save();
            }
        }
    }
}