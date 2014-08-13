<?php
/** 
 * App 管理
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class AppManageBiz extends MiniBiz{

    public function getAll(){
        $data = MiniClient::getInstance()->getAll();
        return $data;
    }

    /**
     * 获得指定app
     * @param $id
     * @return mixed
     */
    public function getApp($id){
        $data = MiniClient::getInstance()->getApp($id);
        return $data;
    }
    /**
     * @param $currentPage
     * @param $pageSize
     * @return mixed
     * 获得appList
     */
    public function getAppList($currentPage,$pageSize){
        $data = MiniClient::getInstance()->getAppList($currentPage,$pageSize);
        return $data;
    }
    /**
     * 分页获取admin用户信息
     */
    public function getNormalList($currentPage,$pageSize){
        $list = MiniClient::getInstance()->getNormal($currentPage,$pageSize);
        return $list;
    }
    /**
     * 分页获取冻结用户信息
     */
    public function getDisabledList($currentPage,$pageSize){
        $list = MiniClient::getInstance()->getDisabled($currentPage,$pageSize);
        return $list;
    }
    /**
     * 解冻app
     */
    public function enableApp($id){
        MiniClient::getInstance()->enabledClient($id);
        return true;
    }
    /**
     * 冻结app
     */
    public function disableApp($id){
        MiniClient::getInstance()->diabledClient($id);
        return true;
    }
    /**
     * 删除app
     */
    public function deleteApp($id){
        MiniClient::getInstance()->deleteClient($id);
        return true;
    }
    /**
     * 新建app
     */
    public function createApp($name,$description,$client_id,$client_secret){
        MiniClient::getInstance()->createClient($name,$description,$client_id,$client_secret);
        return true;
    }

    /**
     * 搜索APP
     * @param $name
     * @param $currentPage
     * @param $pageSize
     * @return mixed
     */
    public function searchApp($name,$currentPage,$pageSize){
        $data = MiniClient::getInstance()->getAppByName($name,$currentPage,$pageSize);
        return $data;
    }
}