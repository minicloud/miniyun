<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-9-10
 * Time: 上午10:08
 */
class GroupBiz extends MiniBiz{
    /**
     * 群组列表
     */
    public function getList(){
        $user = $this->user;
        $userId = $user['user_id'];
        return MiniGroup::getInstance()->getList($userId);
    }
    /**
     * 新建群组
     */
    public function create($groupName){
        $user = $this->user;
        $userId = $user['user_id'];
        return MiniGroup::getInstance()->create($groupName,$userId);
    }
    /**
     * 删除群组
     */
    public function delete($groupName){
        $user = $this->user;
        $userId = $user['user_id'];
        return MiniGroup::getInstance()->delete($groupName,$userId);
    }
    /**
     * 群组更名
     */
    public function rename($oldGroupName,$newGroupName){
        $user = $this->user;
        $userId = $user['user_id'];
        return MiniGroup::getInstance()->rename($oldGroupName,$newGroupName,$userId);
    }
    /**
     * 群组下的用户列表
     */
    public function userList(){

    }
    /**
     * 绑定用户到群组
     */
    public function bind(){

    }
    /**
     * 用户与群组解绑
     */
    public function unbind(){

    }
    /**
     * 搜索群组
     */
    public function search(){

    }
}