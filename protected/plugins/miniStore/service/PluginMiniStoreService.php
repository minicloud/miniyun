<?php
/**
 * 迷你存储接口
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreService extends MiniService{
    protected function anonymousActionList(){
        return array( 
            "report",
            "download"
        );
    }
    protected function adminActionList(){
        return array( 
            "nodeList",
            "node",
            "nodeStatus",
        );
    }
    /**
     * 根据文件hash值进行下载文件
     */
    public function  download(){
        //TODO 这里要进行IP的安全过滤，否则将会导致文件匿名下载并外泄
        $signature = MiniHttp::getParam('signature',"");
        $biz = new PluginMiniStoreBiz();
        $biz->download($signature);
    }
    /**
     * 冗余备份报俊
     */
    public function  replicateReport(){
        //TODO 这里要进行IP的安全过滤，否则将会导致文件匿名下载并外泄
        $signature = MiniHttp::getParam('signature',"");
        $nodeId = MiniHttp::getParam('node_id',"");
        $biz = new PluginMiniStoreBiz();
        $biz->replicateReport($signature,$nodeId);
    }
    /**
     * 迷你存储文件上传成功报俊
     */
    public function report(){
        //TODO 要进行安全校验
        $path = MiniHttp::getParam("path","");
        $size = MiniHttp::getParam("size","");
        $nodeId = MiniHttp::getParam("node_id","");
        $signature = MiniHttp::getParam("signature","");
        $biz = new PluginMiniStoreBiz();
        return $biz->report($path,$signature,$size,$nodeId);
    }
    /**
     * 获得迷你存储节点列表
     */
    public function nodeList() {
        $biz = new PluginMiniStoreBiz();
        return $biz->getNodeList();
    }
    /**
     * 创建或修改新的迷你存储节点
     */
    public function node(){
        $name = MiniHttp::getParam("name","");
        $host = MiniHttp::getParam("host","");
        $accessToken = MiniHttp::getParam("node_access_token","");
        if(empty($name)||empty($host)||empty($accessToken)){
            throw new MiniException(100101);
        }
        $biz = new PluginMiniStoreBiz();
        return $biz->createOrModifyNode($name,$host,$accessToken);
    }
    /**
     * 修改迷你存储节点的状态
     */
    public function nodeStatus(){
        $name = MiniHttp::getParam("name","");
        $status = MiniHttp::getParam("status","0");
        if(empty($name)){
            throw new MiniException(100102);
        }
        $biz = new PluginMiniStoreBiz();
        return $biz->modifyNodeStatus($name,$status);
    }
}