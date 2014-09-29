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
        $userId = $this->user['id'];

        //获得绝对路径
        $filePath = MiniUtil::getAbsolutePath($userId,$filePath);
        $userIdInfos = array();
        $master = MiniUser::getInstance()->getUser( $userId);
        $masterName = $master['user_name'];
        foreach($privileges as $privilege){
            $privilegeArray = array();
            $userIdInfo = array();
            //解析权限开始
            $privilegeDetail = $privilege['privilege'];
            $keys = array('resource.read', 'folder.create', 'folder.rename', 'folder.delete', 'file.create', 'file.modify', 'file.rename', 'file.delete', 'permission.grant');
            for($i=0;$i<strlen($privilegeDetail);$i++){//将数组$privilege，string转换成int
                $privilegeArray[] = $privilegeDetail[$i];
            }
//            var_dump($privilegeArray);
            $permission = array_combine($keys, $privilegeArray);
            $permission = serialize($permission);
            //解析权限结束

            if($privilege['info']['type']=='user'){
                $userIdInfo['id'] = $privilege['info']['id'];
                $userIdInfo['permission'] = $permission;
                $userIdInfo['type'] = 'user';
                $userIdInfos[] = $userIdInfo;
                foreach($userIdInfos as $key=>$val){
                    if($userIdInfo['id']== $val['id']){
                        $userIdInfo['id'] = $privilege['info']['id'];
                        $userIdInfo['permission'] = $permission;
                        $userIdInfo['type'] = 'user';
                        $data[] = $userIdInfo;
                       array_splice($userIdInfos,$key,$data);
                    }
                }
            }

            if($privilege['info']['type']=='department'||$privilege['info']['type']=='group'){
                //查询出privilege中的数据
                $departmentPrivilege['department_id'] = $privilege['info']['id'];
                MiniGroupPrivilege::getInstance()->create($departmentPrivilege['department_id'],$filePath,$permission);
                //根据department_id 查询出user_id
                $userGroupRelations = MiniUserGroupRelation::getInstance()->getByGroupId($departmentPrivilege['department_id']);
                foreach($userGroupRelations as $key=>$userGroupRelation){
                    $isDouble = false;
                    foreach($userIdInfos as $val){
                        if($userGroupRelation['user_id'] == $val['id']){
                            if($val['type']=='department'&&$privilege['info']['type']=='group'){
                                $isDouble = false;
                            }else{
                                $isDouble = true;
                            }
                             break;
                        }
                    }
                    if($isDouble==false){
                        $userIdInfo['id'] = $userGroupRelation['user_id'];
                        $userIdInfo['permission'] = $permission;
                        $userIdInfo['type'] = $privilege['info']['type'];
                        $userIdInfos[] = $userIdInfo;
                    }
                }

            }

        }
        //delete privilege
        $oldGroupPrivileges = MiniGroupPrivilege::getInstance()->getPrivilegeList($filePath);
        $oldPrivileges = MiniUserPrivilege::getInstance()->getPrivilegeList($filePath);
        //删除权限
        if(!empty($oldGroupPrivileges)){
            foreach($oldGroupPrivileges as $oldGroupPrivilege){
                $oldGroupId = $oldGroupPrivilege['group_id'];
                $groupExisted = false;
                    foreach($privileges as $privilege){
                        $type = $privilege['info']['type'];
                        if($type=='group'||$type=='department'){
                            $newGroupId =$privilege['info']['slave_id'];
                            if($newGroupId==$oldGroupId){
                                $groupExisted = true;
                            }
                        }
                    }
                if($groupExisted==false){
                    MiniGroupPrivilege::getInstance()->deletePrivilege($oldGroupId,$filePath);
                }
            }
        }
        if (!empty($oldPrivileges)) {
            foreach ($oldPrivileges as $item) {
                $userId = $item['user_id']; //数据表中的userId
                $existed = false;
                foreach ($userIdInfos as $userIdInfo) {
                    if ($userIdInfo["id"] == $userId) {
                        $existed = true;
                    }
                }
                echo  $existed;
                if ($existed === false) {
                    MiniUserPrivilege::getInstance()->deletePrivilege($userId, $filePath);
                    //删除对应file_meta
                    $key = MConst::SHARED_FOLDERS;
                    $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key); //根据共享文件路径查到file_meta信息
                    $metaValue = unserialize($fileMeta['meta_value']); //得到metaValue 下一步根据value查得被共享者文件路径
                    $slaves = $metaValue['slaves']; //得到被共享者文件路径集合
                    foreach ($slaves as $slaveId => $slaveFilePath) { //删除被共享这file file_meta
                        if ($slaveId == $userId) {
                            $file = MiniFile::getInstance()->getByPath($slaveFilePath);
                            print_r($file);
                            $fileId = $file['id'];
                            $userId = $file['user_id'];
                            //删除文件夹
                            MiniFile::getInstance()->deleteFile($fileId);
                            //创建slaves取消共享事件
                            MiniUserPrivilege::getInstance()->createEvent($userId, 1, MConst::DELETE, $slaveFilePath, $slaveFilePath);
                            //删除slaves的file_meta信息
                            MiniFileMeta::getInstance()->deleteFileMetaByPath($slaveFilePath);
                        }
                    }
                }
            }
        }

//        print_r($userIdInfos);

        //TODO 还没有进行过滤

            //group user common Operation
            $privilegeUseds = MiniUserPrivilege::getInstance()->getPrivilegeList($filePath);//查出已被共享的人
            $privilegeIds = array();
            if(isset($privilegeUseds)){
                foreach ($privilegeUseds as $privilegeUsed) {
                    array_push($privilegeIds, $privilegeUsed['user_id']);
                }
            }
            $currentPaths = array();
            //查询出MiniFileMeta中的数据，将被共享的数据添加到slave数据中
            $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, MConst::SHARED_FOLDERS);
            $metaValue = unserialize($fileMeta['meta_value']);
            $slaves = $metaValue['slaves'];
            //获取文件名
            $newPath = explode('/', $filePath);
            $fileName = end($newPath);
            $userIds = array();
            foreach($userIdInfos as $key=>$userIdInfo){
                //1.存储userPrivilege
                MiniUserPrivilege::getInstance()->create($userIdInfo['id'],$filePath,$userIdInfo['permission']);

                if (!empty($privilegeUseds)) {
                    if (in_array($userIdInfo['id'], $privilegeIds)) { //如果该用户已经被gong享过
                        $currentPath = $slaves[$userIdInfo['id']];
                        array_push($userIds, $userIdInfo['id']);
                        array_push($currentPaths, $currentPath);
                    } else {
                        $name = $fileName . "(" . $masterName . "的共享)";
                        $name = MiniUserPrivilege::getInstance()->nameUnique($name, $name, 0, $userIdInfo['id']);
                        $currentPath = '/' . $userIdInfo['id'] . '/' . $name;
                        array_push($userIds, $userIdInfo['id']);
                        array_push($currentPaths, $currentPath);
                    }
                } else {
                    $name = $fileName . "(" . $masterName . "的共享)";
                    $name = MiniUserPrivilege::getInstance()->nameUnique($name, $name, 0, $userIdInfo['id']);
                    $currentPath = '/' . $userIdInfo['id'] . '/' . $name;
                    array_push($userIds, $userIdInfo['id']);
                    array_push($currentPaths, $currentPath);
                }
            }
        foreach($userIdInfos as $number=>$userIdInfo){
            //创建元数据
                    if(in_array($userIdInfo['id'], $privilegeIds)){
                            $shareUsed = false;
                        }else{
                            $shareUsed = true;
                    }
                    if ($shareUsed) { //共享文件名相同，但路径不同,则创建。同路径同名文件不二次创建
                        $file = array();
                        $file["file_type"] = MConst::OBJECT_TYPE_BESHARED;
                        $file["parent_file_id"] = 0;
                        $file["file_create_time"] = time();
                        $file["file_update_time"] = time();
                        $num = 0;
                        $name = $fileName . "(" . $masterName . "的共享)";
                        $file["file_name"] = MiniUserPrivilege::getInstance()->nameUnique($name, $name, $num, $userIdInfo['id']);
                        $file["version_id"] = 0;
                        $file["file_size"] = 0;
                        $file["file_path"] = '/' . $userIdInfo['id'] . '/' . $file["file_name"];
                        $file["mime_type"]; //有存在NULL的情况

                        MiniFile::getInstance()->create($file, $userIdInfo['id']);

                        //被分享者 path
                        $meta_value = array();
                        $meta_value['master'] = intval($newPath[1]); //分享者ID
                        $meta_value['slaves'] = array_combine($userIds, $currentPaths);
                        $meta_value['path'] = $filePath;
                        $meta_value['send_msg'] = "000";
                        $meta_value = serialize($meta_value);
                        $meta_key = MConst::SHARED_FOLDERS;
                        MiniFileMeta::getInstance()->createFileMeta($file["file_path"], $meta_key, $meta_value);
                        //创建事件
//                $this->createEvent($userIdInfo['id'], 1, MConst::SHARE_FOLDER, $file["file_path"], $file["file_path"]);

                        //分享者 path
                        if ($number == count($userIdInfos)-1) {
                            $meta_value = array();

                            $meta_value['master'] = intval($newPath[1]); //分享者ID
                            $meta_value['slaves'] = array_combine($userIds, $currentPaths);
                            $meta_value['path'] = $filePath;
                            $meta_value['send_msg'] = "000";
                            $meta_value = serialize($meta_value);
                            $meta_key = MConst::SHARED_FOLDERS;
                            MiniFileMeta::getInstance()->createFileMeta($filePath, $meta_key, $meta_value);
                            //创建事件
//                    $this->createEvent(intval($newPath[1]), 1, MConst::SHARE_FOLDER, $filePath, $filePath);

                        }
                    } else { //同路径同名文件不二次创建,但是存在修改问题。
                        $key = MConst::SHARED_FOLDERS;
                        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key);
                        $metaValue = unserialize($fileMeta['meta_value']);
                        $slaves = $metaValue['slaves'];
                        $slavePath = $slaves[$userIdInfo['id']]; //得到用户path，

                        //被分享者 path
                        $meta_value = array();
                        $meta_value['master'] = intval($newPath[1]); //分享者ID
                        $meta_value['slaves'] = array_combine($userIds, $currentPaths);//todo
                        $meta_value['path'] = $filePath;
                        $meta_value['send_msg'] = "000";
                        $meta_value = serialize($meta_value);
                        $meta_key = MConst::SHARED_FOLDERS;
                        MiniFileMeta::getInstance()->createFileMeta($slavePath, $meta_key, $meta_value);
                        //分享者 path
                        if ($number== count($userIdInfos)-1) {
                            $meta_value = array();
                            $meta_value['master'] = intval($newPath[1]); //分享者ID
                            $meta_value['slaves'] = array_combine($userIds, $currentPaths);
                            $meta_value['path'] = $filePath;
                            $meta_value['send_msg'] = "000";
                            $meta_value = serialize($meta_value);
                            $meta_key = MConst::SHARED_FOLDERS;
                            MiniFileMeta::getInstance()->createFileMeta($filePath, $meta_key, $meta_value);
                            //创建事件
                        }
                    }
                }
        /**
         * 存储权限之后更新被分享文件的file_type = 2，出现分享图标
         */
        MiniFile::getInstance()->togetherShareFile($filePath, MConst::OBJECT_TYPE_SHARED);
        return true;
//        $result = MiniUserPrivilege::getInstance()->createPrivilege($filePath, $privileges);

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