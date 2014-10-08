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
        $items = MiniGroup::getInstance()->getList($userId);
        if($items['success']==true){
            $list = $items['list'];
            $groupList = array();
            foreach($list as $item){
                $arr = array();
                $arr['id'] = $item['id'];
                $arr['user_id'] = $item['user_id'];
                $arr['group_name'] = $item['group_name'];
                $arr['count'] = MiniUserGroupRelation::getInstance()->count($item['id']);
                array_push($groupList,$arr);
            }
            $items['list'] = $groupList;
            return $items;
        }else{
            return $items;
        }
    }
    /**
     * 用户与群组关联关系列表
     */
    public function usersGroupRelation($currentPage,$pageSize){
        $user = $this->user;
        $userId = $user['user_id'];
        $result = MiniGroup::getInstance()->getList($userId);
        $groupList = $result['list'];
        foreach($groupList as $item){
            $groupId = $item['id'];
            $relatedList = MiniUserGroupRelation::getInstance()->getPageList($groupId,$currentPage,$pageSize);
            $relatedUserList = $relatedList['list'];
            var_dump($relatedUserList);
        }
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
     * 删除群组时，对应的group_privilege 和 user_group_relations的内容也一并删除
     */
    public function delete($groupId){
        MiniGroup::getInstance()->deleteByGroupId($groupId);//删除群组
        MiniGroupPrivilege::getInstance()->deleteRelatedPrivilegeById($groupId);//删除群组对应group_privilege的所有信息
        MiniUserGroupRelation::getInstance()->deleteRelatedRelations($groupId);
        return true;
//        return MiniGroup::getInstance()->delete($groupName,$userId);
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
    public function userList($groupId){
        $items = MiniUserGroupRelation::getInstance()->getList($groupId);
        if($items['success']==true){
            $list = $items['list'];
            $userList = array();
            foreach($list as $item){
                $arr = array();
                $user = MiniUser::getInstance()->getUser($item['user_id']);
                $arr['id']=$item['user_id'];
                $arr['name']=$user['user_name'];
                $arr['nick']=$user['nick'];
                $arr['avatar']=$user['avatar'];
                array_push($userList,$arr);
            }
            $items['list']=$userList;
            return $items;
        }else{
            return $items;
        }
    }
    /**
     * 绑定用户到群组
     */
    public function bind($userId,$groupId){
        return MiniUserGroupRelation::getInstance()->bind($userId,$groupId);
    }
    /**
     * 用户与群组解绑
     */
    public function unbind($userId,$groupId){
        return MiniUserGroupRelation::getInstance()->unbind($userId,$groupId);
    }
    /**
     * 搜索群组
     */
    public function search(){

    }
}