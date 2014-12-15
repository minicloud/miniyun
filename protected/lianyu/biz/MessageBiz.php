<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Mac
 * Date: 14-9-27
 * Time: 上午11:27
 * To change this template use File | Settings | File Templates.
 */


class MessageBiz  extends MiniBiz{
    public function getList(){
        $pageSize = MiniHttp::getParam('pageSize','');
        $page= MiniHttp::getParam('page','');
        $pageSet=($page-1)*$pageSize;
        $sessionUser = $this->user;
        $userId      = $sessionUser["id"];
        $value=MiniMessage::getInstance()->getMessageList($pageSize,$pageSet,$userId);
        $list["list"]=$this->createArray( $value);
        $list["total"]=MiniMessage::getInstance()->getMessageCount($userId);
        return $list;
    }

    public function getStatusList(){

        $pageSize = MiniHttp::getParam('pageSize','');
        $page= MiniHttp::getParam('page','');
        $pageSet=($page-1)*$pageSize;
        $sessionUser = $this->user;
        $userId      = $sessionUser["id"];
        $value=MiniMessage::getInstance()->getMessageStatus($pageSize,$pageSet,$userId);
        $list["list"]=$this->createArray( $value);
        $list["total"]=MiniMessage::getInstance()->getMessageStatusCount($userId);
        return $list;
    }

    public function createArray($value){
        $messageData=array();
        foreach($value as $list){
            $userList=MiniUser::getInstance()->getUser($list['uu_id']);
            $messageData['userName']     = $userList['user_name'];
            $messageData['content']      = $list["content"];
            $messageData['created_at']   = $list['created_at'];
            $messageData['updated_at']   = $list['updated_at'];
            $messageData['status']       = $list['status'];
            $messageData['id']       = $list['id'];
            $messageList[]=$messageData;
        }
        return $messageList;
    }

    public function getStatusListCount(){
        $sessionUser = $this->user;
        $userId      = $sessionUser["id"];
        $value=MiniMessage::getInstance()->getMessageStatusCount($userId);
        return $value;
    }
    public function updateAllStatus(){
        $sessionUser = $this->user;
        $userId      = $sessionUser["id"];
//        echo "aaaa";exit;
        $value=MiniMessage::getInstance()->updateAllStatus($userId);

        return $value;
    }
    public function updateStatus($id){
        $sessionUser = $this->user;
        $userId      = $sessionUser["id"];
        $value=MiniMessage::getInstance()->updateStatus($id,$userId);
        return $value;
    }


}