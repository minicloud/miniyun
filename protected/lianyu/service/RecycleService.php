<?php
/**
 * 回收站服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class RecycleService extends MiniService
{

    /**获得假删除文件分页信息
     * @return mixed
     */
    public function getDeleteFile()
    {
        $page = MiniHttp::getParam("page", "");
        $pageSize = MiniHttp::getParam("page_size", "");
        $biz = new RecycleBiz();
        $deleteList = $biz->getFileList($page, $pageSize);
        return $deleteList;
    }

    /**
     * 回复删除文件
     */
    public function recover()
    {
        $path = MiniHttp::getParam("path", "");
        $arr = new RecycleBiz;
        $arr->recover($path);
    }

    /**永久删除文件
     * @return mixed
     */
    public function delete()
    {
        $path = MiniHttp::getParam("path", "");
        $arr  = new RecycleBiz;
        $data = $arr->delete($path);
        return $data;
    }

    /**按照文件名搜索已删除文件
     * @return mixed
     */
    public function search(){
        $fileName = MiniHttp::getParam("file_name", "");
        $arr      = new RecycleBiz;
        $data     = $arr->search($fileName);
        return $data;
    }
}