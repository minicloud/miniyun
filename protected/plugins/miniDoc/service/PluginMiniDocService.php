<?php

/**
 * 文档在线预览列表
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class PluginMiniDocService extends MiniService{
    /**
     * 根据文件hash值进行下载文件
     */
    public function  download(){
        //TODO 这里要进行IP的安全过滤，否则将会导致文件匿名下载并外泄
        $fileHash = MiniHttp::getParam('hash',"");
        $biz = new PluginMiniDocBiz();
        $biz->download($fileHash);
    }
    /**
     * 根据文件hash值报告文档转换情况
     */
    public function  report(){
        //TODO 这里要进行IP的安全过滤，否则将会导致文件匿名下载并外泄
        $fileHash = MiniHttp::getParam('hash',"");
        $status = MiniHttp::getParam('status',"");
        $biz = new PluginMiniDocBiz();
        $result = $biz->report($fileHash,$status);
        return $result;
    }
    /**
     * 文档在线预览列表
     */
    public function getList() {
        $pageSize = MiniHttp::getParam("page_size","16");
        $page = MiniHttp::getParam("page","1");
        $type = MiniHttp::getParam('type','');
        $biz = new PluginMiniDocBiz();
        $list=$biz->getList($page,$pageSize,$type);
        return $list;
    }
    /**
     * 在线浏览文件获得内容
     * path文件当前路径
     * type文件类型，可选择pdf/png
     */
    public function previewContent(){
        $path = MiniHttp::getParam("path","");
        $type = MiniHttp::getParam("type","pdf");
        $biz = new PluginMiniDocBiz();
        return $biz->previewContent($path,$type);
    }
}