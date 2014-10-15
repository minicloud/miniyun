<?php
/**
 * Created by JetBrains PhpStorm.
 * User: cl
 * Date: 14-10-15
 * Time: 上午10:14
 * 权限类
 */
class MiniPermission extends MiniCache{
    const    CREATE_FOLDER  = 1;//定义创建文件夹权限在字符串里的位置
    const    FOLDER_RENAME  = 2;
    const    FOLDER__DELETE = 3;
    const    FILE__CREATE   = 4;
    const    FILE__MODIFY   = 5;
    const    FILE__RENAME   = 6;
    const    FILE__DELETE   = 7;
    public   $permission;
    //构造函数
    public   function __construct($permission){
        $this->serialize($permission);
    }
    //
    //判断创建目录权限
    public function canCreateFolder(){
        if($this->permission[self::CREATE_FOLDER] == 1){
            return true;
        }else{
            return false;
        }
    }
    //判断修改目录名称权限
    public function canModifyFolderName(){
        if($this->permission[self::FOLDER_RENAME] == 1){
            return true;
        }else{
            return false;
        }
    }
    //判断删除目录权限
    public function canDeleteFolder(){
        if($this->permission[self::FOLDER__DELETE] == 1){
            return true;
        }else{
            return false;
        }
    }
    //判断创建文件权限
    public function canCreateFile(){
        if($this->permission[self::FILE__CREATE] == 1){
            return true;
        }else{
            return false;
        }
    }
    //判修改文件名权限
    public function canModifyFileName(){
        if($this->permission[self::FILE__RENAME] == 1){
            return true;
        }else{
            return false;
        }
    }
    //判断修改文件权限
    public function canModifyFile(){
        if($this->permission[self::FILE__MODIFY] == 1){
            return true;
        }else{
            return false;
        }
    }
    //判断是否删除文件权限
    public function canDeleteFile(){
        if($this->permission[self::FILE__DELETE] == 1){
            return true;
        }else{
            return false;
        }
    }
    //能否复制权限
    public function canCopy(){
        return true;
    }
    //序列化
    public function serialize($permission){
        $this->permission = $permission;
    }
}

