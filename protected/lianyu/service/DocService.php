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
class DocService extends MiniService{
    /**
     * 文档在线预览列表
     */
    public function getList() {
        $pageSize = MiniHttp::getParam("page_size","16");
        $page = MiniHttp::getParam("page","1");
        $type = MiniHttp::getParam('type','');
        $biz = new DocBiz();
        $list=$biz->getList($page,$pageSize,$type);
        return $list;
    } 
}