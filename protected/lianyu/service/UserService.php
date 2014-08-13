<?php
/**
 * 用户信息服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserService extends MiniService{
    /**
     * 获取好友列表
     * @return array
     */
    public function getList() {
        $pageSize  = MiniHttp::getParam("page_size",10);
        $page      = MiniHttp::getParam("page",1);
        $friend    = new UserBiz();
        return $friend->getFriends($pageSize,$page);
    }
    /**
     * 搜索好友
     */
    public function search(){
        $key  = MiniHttp::getParam("key",'');
        $friend    = new UserBiz();
        return $friend->searchFriends($key);
    }

    /**
     * 获取用户组
     * @return array
     */
    public function getGroupList() {

    }
}