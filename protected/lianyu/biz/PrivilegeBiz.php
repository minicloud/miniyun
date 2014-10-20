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
        $privileges = MiniUserPrivilege::getInstance()->getPrivilegeList($filePath);
        $groupPrivileges = MiniGroupPrivilege::getInstance()->getPrivilegeList($filePath);
        $data = array();
        foreach($privileges as $item){
            $user = MiniUser::getInstance()->getUser($item['user_id']);
            $privilege = array();
            $privilege['id'] = $user['user_id'];
            $privilege['name'] = $user['user_name'];
            $privilege['avatar'] = $user['avatar'];
            $permission = $item['permission'];
            $readValue = substr($permission,0,1);
            $modifyValue = substr($permission,1);
            if($readValue=='1'){//read权限 与js格式转化为一致
                $privilege['read'] = true;
            }else{
                $privilege['read'] = false;
            }
            if($modifyValue=='11111111'){
                $privilege['modified'] = true;
            }else{
                $privilege['modified'] = false;
            }
            $privilege['type'] = "0";
            array_push($data,$privilege);
        }
        foreach($groupPrivileges as $item){
            $group = MiniGroup::getInstance()->getById($item['group_id']);
            $privilege = array();
            $privilege['id']=$item['group_id'];
            $privilege['name']=$group['group_name'];
            $privilege['avatar'] = MiniHttp::getMiniHost()."static/images/icon_group.png";
            $permission = $item['permission'];
            $readValue = substr($permission,0,1);
            $modifyValue = substr($permission,1);
            if($readValue=='1'){//read权限 与js格式转化为一致
                $privilege['read'] = true;
            }else{
                $privilege['read'] = false;
            }
            if($modifyValue=='11111111'){
                $privilege['modified'] = true;
            }else{
                $privilege['modified'] = false;
            }
            $privilege['type'] = "1";
            array_push($data,$privilege);
        }
        return $data;
    }
    /**
     * 保存用户权限
     * @param $filePath
     * @param $slaves
     * @return bool
     */
    public function save($filePath,$slaves){
        //delete privilege
        $oldGroupPrivileges = MiniGroupPrivilege::getInstance()->getPrivilegeList($filePath);
        $oldUserPrivileges = MiniUserPrivilege::getInstance()->getPrivilegeList($filePath);
        //删除权限
        if(!empty($oldGroupPrivileges)){
            foreach($oldGroupPrivileges as $oldGroupPrivilege){
                $oldGroupId = $oldGroupPrivilege['group_id'];
                $groupExisted = false;
                foreach($slaves as $groupPrivilege){
                    $type = $groupPrivilege['type'];
                    if($type=='1'||$type=='2'){
                        $newGroupId =$groupPrivilege['id'];//todo 原先是($privilege['info']['slave_id'])，已修改(2)
                        if($newGroupId==$oldGroupId){
                            $groupExisted = true;
                        }
                    }
                }
                if($groupExisted==false){//todo 判断的地方有误 导致无法存入数据库(1) 见上(2)
                    MiniGroupPrivilege::getInstance()->deletePrivilege($oldGroupId,$filePath);
                }
            }
        }
        if (!empty($oldUserPrivileges)) {
            foreach ($oldUserPrivileges as $item) {
                $userId = $item['user_id']; //数据表中的userId
                $existed = false;
                foreach($slaves as $userPrivilege){
                    $type = $userPrivilege['type'];
                    if($type=='0'){
                        $newUserId =$userPrivilege['id'];//todo 原先是($privilege['info']['slave_id'])，已修改(2)
                        if($newUserId==$userId){
                            $existed = true;
                        }
                    }
                }
                if ($existed == false) {
                    MiniUserPrivilege::getInstance()->deletePrivilege($userId, $filePath);
                }
            }
        }
        //创建权限
        foreach($slaves as $privilege){
            $permission = $privilege['privilege'];
            $privilegeType = $privilege['type'];
            if($privilegeType=='0'){
                MiniUserPrivilege::getInstance()->create($privilege['id'],$filePath,$permission);
            }
            if($privilegeType=='1'){
                MiniGroupPrivilege::getInstance()->create($privilege['id'],$filePath,$permission);
            }
            if($privilegeType=='2'){
                MiniGroupPrivilege::getInstance()->create($privilege['id'],$filePath,$permission);
            }
        }
//        $meta_key = MConst::SHARED_FOLDERS;
//        $meta_value = MConst::SHARE_FOLDER;
//        MiniFileMeta::getInstance()->createFileMeta($filePath, $meta_key, $meta_value);
        //todo创建共享事件
        /**
         * 存储权限之后更新被分享文件的file_type = 2，出现分享图标
         */
        MiniFile::getInstance()->togetherShareFile($filePath, MConst::OBJECT_TYPE_SHARED);
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
    /**
     * 获取共享文件的根目录文件
     */
    public function getSharedParentPath($sharedpath){
        $arr = explode('/',$sharedpath);
        $parentPath = "/".$arr[1];
        for($i=2;$i<count($arr);$i++){
            $parentPath = $parentPath."/".$arr[$i];
            $file = MiniFile::getInstance()->getByFilePath($parentPath);
            if($file['file_type']==2){
                return $parentPath;
            }
        }
        return null;
    }

    /**
     * 用户对应某个文件的权限
     * @param $sharedPath
     */
    public function getUserPermission($sharedPath){
        $userId = $this->user['id'];
    }
}