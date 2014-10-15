<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-10-15
 * Time: 上午9:56
 */
class GeneralFolderPermissionBiz extends  MiniBiz{
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
}