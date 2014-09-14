<?php
/**
 * 迷你文档接口
 */
class MiniDocService{
    /**
     * 获得迷你文档文档大小控制策略
     */
    public function limitPolicy(){
        $model = new MiniDocBiz();
        $data  = $model->limitPolicy();
        return $data;
    }
    /**
     * 修改迷你文档文档大小控制策略
     */
    public function saveLimitPolicy(){
        $wordSize = MiniHttp::getParam('wordSize','1');
        $pptSize = MiniHttp::getParam('pptSize','1');
        $excelSize = MiniHttp::getParam('excelSize','1');
        $zip_rarSize = MiniHttp::getParam('zip_rarSize','1');
        $model = new MiniDocBiz();
        $data  = $model->saveLimitPolicy($wordSize,$pptSize,$excelSize,$zip_rarSize);
        return $data;
    }
    /**
     * 新建迷你文档节点
     */
    public function createNode(){
        $ip = MiniHttp::getParam('ip','');
        $port = MiniHttp::getParam('port','');
        $model = new MiniDocBiz();
        $data  = $model->createNode($ip,$port);
        return $data;
    }
    /**
     * 获得所有的文档节点信息
     */
    public function listNode(){
        $model = new MiniDocBiz();
        $data  = $model->listNode();
        return $data;
    }
    /**
     * 修改迷你文档节点
     */
    public function modifyNode(){
        $id = MiniHttp::getParam('id','');
        $ip = MiniHttp::getParam('ip','');
        $port = MiniHttp::getParam('port','');
        $model = new MiniDocBiz();
        $data  = $model->modifyNode($id,$ip,$port);
        return $data;
    }
    /**
     * 更改迷你文档节点状态
     */
    public function changeNodeStatus(){
        $id = MiniHttp::getParam('id','');
        $runStatus = MiniHttp::getParam('run_status','');
        $model = new MiniDocBiz();
        $data  = $model->changeNodeStatus($id,$runStatus);
        return $data;
    }
}