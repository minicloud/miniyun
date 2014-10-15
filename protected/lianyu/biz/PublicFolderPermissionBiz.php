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
        //先查询公共目录文件夹
        //select * from miniyun_group_priveleges where user_id=-1;
        //循环获得每一个path，根据path，获得MiniFile对象，同时组装权限数据
        //miniFile["permission"] = group_priveleges.permission;
        //miniFile["is_public_folder"] = true
//        return List<MiniFile>()
    }
}