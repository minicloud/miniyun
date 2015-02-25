<?php
/**
 * 迷你存储业务层
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreBiz extends MiniBiz{
    /**
     *根据文件的Hash值下载内容
     * @param $signature 文件hash值
     * @throws 404错误
     */
    public function download($signature){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(!empty($version)){
            //根据文件内容输出文件内容
            MiniFile::getInstance()->getContentBySignature($signature,$signature,$version["mime_type"]);
        }else{
            throw new MFileopsException(
                Yii::t('api','File Not Found'),
                404);
        }

    }
    /**
     * 冗余备份报俊
     * @param $signature 文件hash值
     * @param $nodeId 迷你存储ID
     * @return array
     */
    public function replicateReport($signature,$nodeId){
        //冗余备份成功,为miniyun_file_version_metas.meta_value新增冗余的节点
        PluginMiniStoreVersionMeta::getInstance()->addReplicateNode($signature,$nodeId);
        //修改存储节点的miniyun_store_node.save_file_count+=1
        PluginMiniStoreNode::getInstance()->newUploadFile($nodeId);
        //删除冗余备份的任务
        PluginMiniReplicateTask::getInstance()->delete($signature,$nodeId);
        return array("success"=>true);
    }
    /**
     * 迷你存储报俊
     * @param string $path 用户文件的存储路径
     * @param string $signature 文件sha1
     * @param int $size 文件大小，单位字节
     * @param int $nodeId 迷你存储节点值 
     */
    public function report($path,$signature,$size,$nodeId){
        //防止重复文件通过网页上传，生成多条记录
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(empty($version)){
            //创建version/versionMeta数据
            $pathParts = pathinfo($path);
            $type = CUtils::mime_content_type($pathParts["filename"]);
            $version = MiniVersion::getInstance()->create($signature, $size, $type);
            MiniVersionMeta::getInstance()->create($version["id"],"store_id",$nodeId);
            //更新迷你存储节点状态，把新上传的文件数+1
            PluginMiniStoreNode::getInstance()->newUploadFile($nodeId);
            //文档转换
            do_action('file_upload_after', $signature);
            //清理垃圾数据
            PluginMiniBreakFile::getInstance()->deleteBySignature($signature);
        }
        //执行文件秒传逻辑
        $filesController = new MFileSecondsController();
        $filesController->invoke();
    }
    /**
     *获得迷你存储节点信息列表
     */
    public function getNodeList(){
        return PluginMiniStoreNode::getInstance()->getNodeList();
    }
    /**
     * 创建迷你存储节点
     * @param $name 节点名称
     * @param $host 节点域名
     * @param $safeCode 节点访问的accessToken
     */
    public function createOrModifyNode($name,$host,$safeCode){

        return PluginMiniStoreNode::getInstance()->createOrModifyNode($name,$host,$safeCode);
    }
    /**
     * 修改迷你存储节点状态
     * @param $name 节点名称
     * @param $status 节点状态
     * @throws MiniException
     */
    public function modifyNodeStatus($name,$status){
        $item = StoreNode::model()->find("name=:name",array("name"=>$name));
        if(!isset($item)){
            throw new MiniException(100103);
        }
        //TODO 检查服务器状态，看看是否可以连接迷你存储服务器
        //throw new MiniException(100104);
        return PluginMiniStoreNode::getInstance()->modifyNodeStatus($name,$status);
    }

}