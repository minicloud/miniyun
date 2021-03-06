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
     * 用户与群组关联关系列表
     */
    public function usersGroupRelation(){
        $currentPage = MiniHttp::getParam("current_page","");
        $pageSize = MiniHttp::getParam("page_size","");
        $biz = new GroupBiz();
        return $biz->usersGroupRelation($currentPage,$pageSize);
    }
    /**
     * 新建群组
     */
    public function create(){
        $groupName = MiniHttp::getParam("group_name","");
        $biz = new GroupBiz();
        $result =  $biz->create($groupName);
        if(is_null($result)){
            return array('success'=>false,'msg'=>'name existed');
        }else{
            return array('success'=>true,'msg'=>'success');
        }
    }
    /**
     * 删除群组
     */
    public function delete(){
        $groupId = MiniHttp::getParam("group_id","");
        $biz = new GroupBiz();
        return $biz->delete($groupId);
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
        $groupId = MiniHttp::getParam("group_id","");
        $biz = new GroupBiz();
        return $biz->userList($groupId);
    }
    /**
     * 绑定用户到群组
     */
    public function bind(){
        $userId = MiniHttp::getParam("user_id","");
        $groupId = MiniHttp::getParam("group_id","");
        $biz = new GroupBiz();
        return $biz->bind($userId,$groupId);
    }
    /**
     * 用户与群组解绑
     */
    public function unbind(){
        $userId = MiniHttp::getParam("user_id","");
        $groupId = MiniHttp::getParam("group_id","");
        $biz = new GroupBiz();
        return $biz->unbind($userId,$groupId);
    }
    /**
     * 搜索群组
     */
    public function search(){

    }
}