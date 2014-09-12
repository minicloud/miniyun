<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-9-10
 * Time: 上午10:07
 */
class GroupService extends MiniService{
    /**
     * 群组列表
     */
    public function getList(){
        $biz = new GroupBiz();
        return $biz->getList();
    }
    /**
     * 新建群组
     */
    public function create(){
        $groupName = MiniHttp::getParam("group_name","");
        $biz = new GroupBiz();
        return $biz->create($groupName);
    }
    /**
     * 删除群组
     */
    public function delete(){
        $groupName = MiniHttp::getParam("group_name","");
        $biz = new GroupBiz();
        return $biz->delete($groupName);
    }
    /**
     * 群组更名
     */
    public function rename(){
        $oldGroupName = MiniHttp::getParam("old_group_name","");
        $newGroupName = MiniHttp::getParam("new_group_name","");
        $biz = new GroupBiz();
        return $biz->rename($oldGroupName,$newGroupName);
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