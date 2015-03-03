<?php
/**
 * 迷你搜索接口
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginMiniSearchService extends MiniService{
    protected function anonymousActionList(){
        return array(
            "report",
            "download",
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
        $biz = new PluginMiniSearchBiz();
        $biz->download($signature);
    }
    /**
     * 迷你存储文件上传成功报俊
     */
    public function report(){
        //TODO 要进行安全校验
        $nodeId = MiniHttp::getParam("node_id","");
        $signature = MiniHttp::getParam("signature","");
        $biz = new PluginMiniSearchBiz();
        return $biz->report($signature,$nodeId);
    }
    /**
     * 根据关键词搜索全文内容
     */
    public function  search(){
        $key = MiniHttp::getParam("key","");
        $biz = new PluginMiniSearchBiz();
        return $biz->search($key);
    }
    /**
     * 获得迷你搜索节点列表
     */
    public function nodeList() {
        $biz = new PluginMiniSearchBiz();
        return $biz->getNodeList();
    }
    /**
     * 创建或修改新的迷你搜索节点
     */
    public function node(){
        $name = MiniHttp::getParam("name","");
        $id = MiniHttp::getParam("id","");
        $host = MiniHttp::getParam("host","");
        $safeCode = MiniHttp::getParam("safe_code","");
        if(empty($name)||empty($host)||empty($safeCode)){
            throw new MiniException(100301);
        }
        $biz = new PluginMiniSearchBiz();
        return $biz->createOrModifyNode($id,$name,$host,$safeCode);
    }
    /**
     * 修改迷你搜索节点的状态
     */
    public function nodeStatus(){
        $name = MiniHttp::getParam("name","");
        $status = MiniHttp::getParam("status","0");
        if(empty($name)){
            throw new MiniException(100302);
        }
        $biz = new PluginMiniSearchBiz();
        return $biz->modifyNodeStatus($name,$status);
    }
}