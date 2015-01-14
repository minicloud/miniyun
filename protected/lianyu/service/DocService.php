<?php

class DocService extends MiniService{
    /**
     * download file
     */
    public function word() {
        $pageSize = MiniHttp::getParam("pageSize","");
        $page = MiniHttp::getParam("page","");
        $biz = new DocBiz();
        $list=$biz->word($page,$pageSize);
        return array('list'=>$list);
    }

    public function convert(){
        $signature = MiniHttp::getParam("signature","");
        $biz = new DocBiz();
        $url = $biz->convert($signature);
        return array('url'=>$url);
    }
}