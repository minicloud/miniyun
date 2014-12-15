<?php
/**
 * 公共目录权限控制
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
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