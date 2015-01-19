<?php

class DocService extends MiniService{
    /**
     * download file
     */
    public function getList() {
        $pageSize = MiniHttp::getParam("pageSize","14");
        $page = MiniHttp::getParam("page","1");
        $mimeType = MiniHttp::getParam('mime_type','');
        $biz = new DocBiz();
        $list=$biz->getList($page,$pageSize,$mimeType);
        return $list;
    } 
}