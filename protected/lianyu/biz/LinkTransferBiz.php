<?php
/**
 * 外链转存业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class LinkTransferBiz extends MiniBiz{
    private $key;
    /**
     * @param $key
     */
    public function LinkTransferBiz($key){
        parent::MiniBiz();
        $this->key = $key;
    }
    public function transfer(){
        $link = MiniLink::getInstance()->getByKey($this->key);
        //TODO link not existed
        $file = MiniFile::getInstance()->getById($link["file_id"]);
        //TODO file not exited
        $userId = $this->user["id"];
        $deviceId = $this->device["id"];
        MiniFile::getInstance()->copy($file["id"],$userId,$deviceId,0);
        return true;
    }

    /**
     * 分享用户指定分享
     * @param $userNames
     * @return bool
     */
    public function sendToTransfer($userNames){
        $link = MiniLink::getInstance()->getByKey($this->key);
        if($link===NULL){
            return;
        }
        $file = MiniFile::getInstance()->getById($link["file_id"]);
        if($file===NULL){
            return;
        }
        $deviceId = $this->device["id"];
        if(count($userNames)>0){
            foreach($userNames as $name){
                $user = MiniUser::getInstance()->getUserByName($name);
                if($user===NULL){
                    continue;
                }
                MiniFile::getInstance()->copy($file["id"],$user['id'],$deviceId,0);
            }
            return true;
        }
        return false;
    }
}