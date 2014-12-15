<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-9-10
 * Time: 上午9:26
 */
class DepartmentBiz extends MiniBiz{
    private  $userId = -1;//当为-1时，所有的用户均有群组
    /**
     * 添加部门
     */
    public function create($departmentName,$parentDepartmentId){
        $userId = $this->userId;
        $result = MiniGroup::getInstance()->create($departmentName,$userId,$parentDepartmentId);
        return $result;
    }
    /**
     * 删除部门
     */
    public function delete($departmentId){
        $userId = $this->userId;
        $result = MiniGroup::getInstance()->deleteByDepartmentId($departmentId,$userId);
        if($result['success']==true){
            $result = MiniGroupRelation::getInstance()->getByGroupId($departmentId);
            if(!empty($result)){
                MiniGroupPrivilege::getInstance()->deleteRelatedPrivilegeById($departmentId);
                $result = MiniGroupRelation::getInstance()->delete($departmentId);
            }
        }
        return $result;
    }
    /**
     * 更改部门名称
     */
    public function rename($departmentId,$newDepartmentName){
        $userId = $this->userId;
        $result = MiniGroup::getInstance()->getById($departmentId);
        $oldDepartmentName = $result['group_name'];
        $result = MiniGroup::getInstance()->rename($oldDepartmentName,$newDepartmentName,$userId);
        return $result;
    }
    /**
     * 部门列表
     */
    public function getList(){
        $data = MiniGroup::getInstance()->getTreeNodes(-1);
        return $data;
    }
    /**
     * 移动部门
     */
    public function move($parentDepartmentId,$sourceId,$sourceType){
        if($sourceType == "group"){
            $data = MiniGroupRelation::getInstance()->update($parentDepartmentId,$sourceId);
        }
        if($sourceType == "user"){
            $data = MiniUserGroupRelation::getInstance()->update($sourceId,$parentDepartmentId);
        }
        return $data;
    }
    /**
     * 未绑定用户列表
     */
    public function unBindUserList(){
        $users = MiniUser::getInstance()->unbindUsers();
        $data = array();
        foreach($users as $user){
            $item['id'] = $user['id'];
            $item['user_name'] = $user['user_name'];
            $data[] = $item;
        }
        return array('success'=>true,'msg'=>'success','unbindUsers'=>$data);
    }

    /**
     * 将用户绑定到部门
     */
    public function bind($userId,$groupId){
        $userGroup = MiniUserGroupRelation::getInstance()->create($userId,$groupId);
        return $userGroup;
    }
    /**
     * 从部门中的用户解除绑定
     */
    public function unbind($userId,$groupId){
        $userGroup = MiniUserGroupRelation::getInstance()->delete($userId,$groupId);
        return $userGroup;
    }
    /**
     *修改用户绑定的用户组
     */
    public  function modifyBind($userId,$groupId){
        $result = MiniUserGroupRelation::getInstance()->update($userId,$groupId);
        return $result;
    }
    /**
     * 搜索部门
     */
    public function search(){

    }
}