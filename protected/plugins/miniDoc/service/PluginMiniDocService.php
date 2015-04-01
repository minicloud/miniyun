<?php
/**
 * 迷你文档接口
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class PluginMiniDocService extends MiniService{
    protected function anonymousActionList(){
        return array(
            "download",
            "report",
            "previewContent"
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
        $biz = new PluginMiniDocBiz();
        $biz->download($signature);
    }
    /**
     * 根据文件hash值报告文档转换情况
     */
    public function  report(){
        //TODO 这里要进行IP的安全过滤，否则将会导致文件匿名下载并外泄
        $nodeId = MiniHttp::getParam('node_id',"");
        $signature = MiniHttp::getParam('signature',"");
        $status = MiniHttp::getParam('status',"");
        $biz = new PluginMiniDocBiz();
        $result = $biz->report($nodeId,$signature,$status);
        return $result;
    }
    /**
     * 文档在线预览列表
     */
    public function getList() {
        $pageSize = MiniHttp::getParam("page_size","16");
        $page = MiniHttp::getParam("page","1");
        $mimeType = MiniHttp::getParam('mime_type','');
        $biz = new PluginMiniDocBiz();
        $list=$biz->getList($page,$pageSize,$mimeType);
        return $list;
    }
    /**
     * 在线浏览文件获得内容
     * path文件当前路径
     * type文件类型，可选择pdf/png
     */
    public function previewContent(){
        $path = MiniHttp::getParam("path","");
        $path = rawurldecode($path);
        $type = MiniHttp::getParam("type","pdf");
        $biz = new PluginMiniDocBiz();
        return $biz->previewContent($path,$type);
    }
    /**
     * 获得迷你文档节点列表
     */
    public function nodeList() {
        $biz = new PluginMiniDocBiz();
        return $biz->getNodeList();
    }
    /**
     * 创建或修改新的迷你文档节点
     */
    public function node(){
        $name = MiniHttp::getParam("name","");
        $id = MiniHttp::getParam("id","");
        $host = MiniHttp::getParam("host","");
        $safeCode = MiniHttp::getParam("safe_code","");
        if(empty($name)||empty($host)||empty($safeCode)){
            throw new MiniException(100201);
        }
        $biz = new PluginMiniDocBiz();
        return $biz->createOrModifyNode($id,$name,$host,$safeCode);
    }
    /**
     * 修改迷你文档节点的状态
     */
    public function nodeStatus(){
        $name = MiniHttp::getParam("name","");
        $status = MiniHttp::getParam("status","0");
        if(empty($name)){
            throw new MiniException(100202);
        }
        $biz = new PluginMiniDocBiz();
        return $biz->modifyNodeStatus($name,$status);
    }
    public function convertStatus(){
        $path = MiniHttp::getParam("path","");
        $path = rawurldecode($path);
        $biz = new PluginMiniDocBiz();
        return $biz->convertStatus($path);
    }
}