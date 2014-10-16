<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-10-15
 * Time: 上午10:15
 */
class PublicFolderPermissionBiz extends MiniBiz{
    public $list;
    public function  PublicFolderPermissionBiz(){
        $this->getList();
    }
    /**
     * 获取公共目录列表
     * @return array
     */
    public function getList(){
        $publicFolderList = MiniGroupPrivilege::getInstance()->getPublic();
        $fileList = array();
        foreach($publicFolderList as $publicFolder){
            $filePath = $publicFolder['file_path'];
            $file = MiniFile::getInstance()->getByPath($filePath);
            $file['permission'] = $publicFolder['permission'];
            $file['is_public_folder'] = true;
            array_push($fileList,$file);
        }
        return $this->list = $fileList;
    }
    /**
     * 获取公共目录权限
     */
    public function getPublicPermission($path){
        return $permission = MiniGroupPrivilege::getInstance()->getPublicPermission($path);
    }
}