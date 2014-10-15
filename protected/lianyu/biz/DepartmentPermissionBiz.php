<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-10-15
 * Time: 上午9:14
 */
class DepartmentPermissionBiz  extends MiniBiz{
    /**
     * 获取部门权限
     */
    public function getPermission($userId,$path){
        $department = MiniUserGroupRelation::getInstance()->getDepartment($userId);
        $departmentPermission = MiniGroupPrivilege::getInstance()->getSpecifyPrivilege($department['group_id'],$path);
        if(empty($departmentPermission)){
            $departmentPermission =  MiniGroupPrivilege::getInstance()->searchPermission($path,$department['group_id']);
        }
        if(empty($departmentPermission)){
            return NULL;
        }
        $permission= $departmentPermission['permission'];
        return MiniPermission($permission);
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
}