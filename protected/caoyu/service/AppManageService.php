<?php
/**
 * 迷你云app管理
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class AppManageService extends MiniService{

    /**
     * 获得所有情况
     */
    public function getAll(){
        $model = new AppManageBiz();
        $data  = $model->getAll();
        return $data;
    }
    /**
     * 获取单个app信息
     */
    public function getApp(){
        $id = MiniHttp::getParam('id',"");
        $model = new AppManageBiz();
        $data  = $model->getApp($id);
        return $data;
    }

    /**
     * @return mixed
     * 得到appList
     */
    public function getList(){
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new AppManageBiz();
        $data  = $model->getAppList($currentPage,$pageSize);
        return $data;
    }
    /**
     * 获取正常APp列表
     */
    public function getNormalList(){
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new AppManageBiz();
        $data  = $model->getNormalList($currentPage,$pageSize);
        return $data;
    }
    /**
     * 获取冻结用户列表
     */
    public function getDisabledList(){
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new AppManageBiz();
        $data  = $model->getDisabledList($currentPage,$pageSize);
        return $data;
    }
    /**
     * 解冻app
     */
    public function enableApp(){
        $id    = MiniHttp::getParam('id',"");
        $model = new AppManageBiz();
        $data  = $model->enableApp($id);
        return $data;
    }
    /**
     * 冻结app
     */
    public function disableApp(){
        $id    = MiniHttp::getParam('id',"");
        $model = new AppManageBiz();
        $data  = $model->disableApp($id);
        return $data;
    }
    /**
     * 添加app
     */
    public function createApp(){
        $name           = MiniHttp::getParam('name',"");
        $description    = MiniHttp::getParam('description',"");
        $client_id      = MiniHttp::getParam('client_id',"");
        $client_secret  = MiniHttp::getParam('client_secret',"");
        $model = new AppManageBiz();
        $data  = $model->createApp($name,$description,$client_id,$client_secret);
        return $data;
    }
    /**
     * 搜索用户
     */
    public function searchApp(){
        $name = MiniHttp::getParam('name','');
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new AppManageBiz();
        $data  = $model->searchApp($name,$currentPage,$pageSize);
        return $data;
    }
}