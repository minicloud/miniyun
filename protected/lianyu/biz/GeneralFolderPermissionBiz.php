<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-10-15
 * Time: 上午9:56
 */
class GeneralFolderPermissionBiz extends  MiniBiz{
    public function __construct($path){
         if($this->isChildrenShared($path)||$this->isParentShared($path)){
             return true;
         }
        return false;
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
            if($file['file_type']==2){
                return true;
            }
        }
        return false;
    }
}