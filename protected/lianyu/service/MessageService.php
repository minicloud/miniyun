<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Mac
 * Date: 14-9-26
 * Time: 下午3:13
 * To change this template use File | Settings | File Templates.
 */

class MessageService extends MiniService{

    public function getList(){
        $pageSize = MiniHttp::getParam('pageSize','');
        $page= MiniHttp::getParam('page','');
        $value=new MessageBiz();
        return $value->getList($pageSize,$page);
    }

    public function getStatusList(){
        $pageSize = MiniHttp::getParam('pageSize','');
        $page= MiniHttp::getParam('page','');
        $value=new MessageBiz();
        return $value->getList($pageSize,$page);
    }

    public function getStatusCount(){
        $value=new MessageBiz();
        return $value->getStatusListCount();
    }

    public function updatedAllStatus(){
        $value=new MessageBiz();
        return $value->updateAllStatus();
    }

    public function updatedStatus(){
        $id=MiniHttp::getParam("id","");
        $value=new MessageBiz();
        return $value->updateStatus($id);
    }

}