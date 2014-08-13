<?php
/**
 * 相册服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class AlbumService extends MiniService{
    /**
     * download file
     */
    public function getList() {
        $pageSize = MiniHttp::getParam("pageSize","");
        $page = MiniHttp::getParam("page","");
        $biz = new AlbumBiz();
        $list=$biz->getPage($page,$pageSize);
        return $list;
    }

    public function timeLine() {
        $biz = new AlbumBiz();
        $list=$biz->getTimeLine();
        return $list;
    }
}