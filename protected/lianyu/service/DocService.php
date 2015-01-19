<?php

class DocService extends MiniService{
    /**
     * download file
     */
    public function getList() {
        $pageSize = MiniHttp::getParam("pageSize","15");
        $page = MiniHttp::getParam("page","1");
        $mimeType = MiniHttp::getParam('mime_type','');
        $biz = new DocBiz();
        $list=$biz->getList($page,$pageSize,$mimeType);
        return $list;
    }

    public function convert(){
        $signature = MiniHttp::getParam("signature","");
        $filePath = MiniHttp::getParam("file_path","");
        $biz = new DocBiz();
        $result = $biz->convert($signature,$filePath);
        return $result;
    }
}