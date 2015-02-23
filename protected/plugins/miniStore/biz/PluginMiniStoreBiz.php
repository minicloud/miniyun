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
     * @param $accessToken 节点访问的accessToken
     */
    public function createOrModifyNode($name,$host,$accessToken){

        return PluginMiniStoreNode::getInstance()->createOrModifyNode($name,$host,$accessToken);
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