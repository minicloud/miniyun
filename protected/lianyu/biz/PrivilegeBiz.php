<?php
/**
 * 权限业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class PrivilegeBiz  extends MiniBiz{
    /**
     * 获得拥有权限的用户列表
     */
    public function getPrivilegeList($filePath){
        //获得绝对路径
        $filePath = MiniUtil::getAbsolutePath($this->user["id"],$filePath);
        $privileges = MiniUserPrivilege::getInstance()->getPrivilegeList($filePath);
        $data = array();
        foreach($privileges as $item){
            $user = MiniUser::getInstance()->getUser($item['user_id']);
            $privilege = array();
            $privilege['nick'] = $user['nick'];
            $privilege['name'] = $user['user_name'];
            $privilege['privilege'] = unserialize($item['permission']);
            array_push($data,$privilege);
        }
        return $data;
    }
    /**
     * 保存用户权限
     * @param $filePath
     * @param $privileges
     * @return bool
     */
    public function save($filePath,$privileges){
        //获得绝对路径
        $filePath = MiniUtil::getAbsolutePath($this->user["id"],$filePath);
        MiniUserPrivilege::getInstance()->createPrivilege($filePath,$privileges);
        return true;
    }
    /**
     * 根据file_path查询文件权限
     */
    public function get($filePath){
        $filePath = MiniUtil::getAbsolutePath($this->user["id"],$filePath);
        $privilege = MiniUserPrivilege::getInstance()->getFolderPrivilege($filePath);
        return $privilege;
    }
    /**
     * 取消共享，删除权限
     */
    public function delete($filePath){
        $filePath = MiniUtil::getAbsolutePath($this->user["id"],$filePath);
        MiniUserPrivilege::getInstance()->cancelPrivilege($filePath);
        return true;
    }
}