<?php
/**
 * 普通目录权限
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
class GeneralFolderPermissionBiz extends  MiniBiz{
    public $permission;
    public $isShared;
    public $shareRootPath;
    public function __construct($path){
         if($this->isChildrenShared($path)||$this->isParentShared($path)){
             $this->isShared = true;
         }else{
             $this->isShared =  false;
         }
    }
    /**
     * 判断文件夹内是否有子文件被共享
     * @param $path
     * @return bool
     */
    public function isChildrenShared($path){
        $groupPermissions = MiniGroupPrivilege::getInstance()->getByFilePath($path);
        $userPermissions = MiniUserPrivilege::getInstance()->searchFilePath($path);
        if(count($groupPermissions)>0||count($userPermissions)>0){
            return true;
        }
        return false;
    }
    /**
     * 判断文件夹内是否有父目录被共享
     */
    public function isParentShared($path){
        $arr = explode('/',$path);
        $parentPath = "/".$arr[1];
        for($i=2;$i<count($arr);$i++){
            $parentPath = $parentPath."/".$arr[$i];
            $file = MiniFile::getInstance()->getByFilePath($parentPath);
            if($file['file_type']==2||$file['file_type']==4){
                $user     = MUserManager::getInstance()->getCurrentUser();
                $userId =   $user['user_id'];
                $this->permission = $this->getPermission($userId,$file['file_path']);
                $this->shareRootPath = $file['file_path'];
                return true;
            }
        }
        return false;
    }

    /**
     * 获的共享父目录的权限
     */
    public function getPermission($userId,$path){
        $publicPrivilege = MiniGroupPrivilege::getInstance()->getSpecifyPrivilege(-1, $path);
        if(!empty($publicPrivilege)){
            $permission = $publicPrivilege['permission'];
            return $permission;
        }
        $privilegeLength = 9;
        $userPrivilege = MiniUserPrivilege::getInstance()->getSpecifyPrivilege($userId,$path);
        if(empty($userPrivilege)){//如果不存在user_privilege，则向上查找group_privilege和department_privilege
            $groupPrivilege = new GroupPermissionBiz($path,$userId);
            $groupPermission = $groupPrivilege->authority;
            $departmentPrivilege = new DepartmentPermissionBiz();
            $departmentPermission = $departmentPrivilege->getPermission($userId,$path);
            if(empty($groupPermission)){
                $permission = $departmentPermission;
            }
            if(empty($departmentPermission)){
                $permission = $groupPermission;
            }
            if(!empty($groupPermission)&&!empty($departmentPermission)){
                $permission = '';
                $total = $groupPermission+$departmentPermission;
                for($i=0;$i<$privilegeLength;$i++){
                    $value = substr($total,$i,1);
                    if($value == '1'||$value == '2'){
                        $permission .='1';
                    }else{
                        $permission .='0';
                    }
                }
            }
        }else{
            $permission = $userPrivilege['permission'];
        }
        return $permission;
    }
}