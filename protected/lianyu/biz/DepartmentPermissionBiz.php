<?php
/**
 * 部门权限
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
class DepartmentPermissionBiz  extends MiniBiz{
    public $ids = array();
    /**
     * 获取部门权限
     */
    public function getPermission($userId,$path){
        $department = MiniUserGroupRelation::getInstance()->getDepartment($userId);
        $departmentPermission = MiniGroupPrivilege::getInstance()->getSpecifyPrivilege($department['id'],$path);
        if(empty($departmentPermission)){
            $departmentPermission =  $this->searchPermission($path,$department['id']);
        }
        if(empty($departmentPermission)){
            return NULL;
        }
        $permission= $departmentPermission['permission'];
        return $permission;
    }
    /**
     * 根据groupId,filePath一级一级往上查，查询groupId最小数据
     * @param $filePath
     * @param $groupId
     * @return null
     */
    private  function searchPermission($filePath,$groupId){
        $relation = MiniGroupRelation::getInstance()->getByGroupId($groupId);
        if(empty($relation)){
            return NULL;
        }
        if($relation['parent_group_id']!=-1){
            $permission =  MiniGroupPrivilege::getInstance()->getSpecifyPrivilege($relation['parent_group_id'], $filePath);
            if(empty($permission)){
                return  $this->searchPermission($filePath,$relation['parent_group_id']);
            }else{
                return  $permission;
            }
        }else{
            return NULL;
        }
    }
    private function getGroups($departmentId){
        $departments = MiniGroupRelation::getInstance()->getByParentId($departmentId);
        if(count($departments)>0){
            foreach($departments as $department){
                $userGroups = MiniUserGroupRelation::getInstance()->getByGroupId($department['group_id']);
                if(count($userGroups)>0){
                    foreach($userGroups as $userGroup){
                        $this->ids[] = $userGroup['user_id'];
                    }
                }
                $this->getGroups($department['id']);
            }

        }else{
            return;
        }
    }
    public function getUserByDepartmentId($departmentId){
        $userGroups = MiniUserGroupRelation::getInstance()->getByGroupId($departmentId);
        if(count($userGroups)>0){
            foreach($userGroups as $userGroup){
                $this->ids[] = $userGroup['user_id'];
            }
        }
        $this->getGroups($departmentId);
    }
}