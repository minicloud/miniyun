<?php

/**
 * 缓存miniyun_user_privileges表的记录，V1.2.0该类接管miniyun_user_privileges的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniUserPrivilege extends MiniCache
{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.UserPrivileges";

    /**
     *  静态成品变量 保存全局实例
     * @access private
     */
    static private $_instance = null;

    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        parent::MiniCache();
    }

    /**
     * 静态方法, 单例统一访问入口
     * @return object  返回对象的唯一实例
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 按照id逐一放入内存
     */
    private function getCacheKey($id)
    {
        return MiniUserPrivilege::$CACHE_KEY . "_" . $id;
    }

    /**
     * 把数据库值序列化
     */
    private function db2list($items)
    {
        $data = array();
        foreach ($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }

    private function db2Item($item)
    {
        if (empty($item)) return NULL;
        $value = array();
        $value["id"] = $item->id;
        $value["user_id"] = $item->user_id;
        $value["file_path"] = $item->file_path;
        $value["permission"] = $item->permission;
        $value["created_at"] = $item->created_at;
        $value["updated_at"] = $item->updated_at;
        return $value;
    }
    public  function create($userId,$filePath,$permission){
        //存储权限
        $criteria = UserPrivilege::model()->find("user_id=:user_id and file_path=:file_path", array(":user_id" => $userId, ":file_path" => $filePath));
        if (empty($criteria)) {
            $criteria = new UserPrivilege();
        }
        $criteria["user_id"] = $userId;
        $criteria["file_path"] = $filePath;
        $criteria["permission"] = $permission;
        $criteria["updated_at"] = time();
        $criteria->save();
    }
    public function updateByPath($oldPath,$newPath){
        $privilege = $this->getByPath($oldPath);
        if(empty($privilege)){
           return null;
        }
        $permission = UserPrivilege::model()->findByPk($privilege['id']);
        $permission['file_path'] = $newPath;
        $permission->save();
    }
    public function getByPath($path){
        $criteria = new CDbCriteria();
        $criteria->condition = "file_path=:file_path";
        $criteria->params = array(
            "file_path" => $path
        );
        $item = UserPrivilege::model()->find($criteria);
        return $this->db2Item($item);
    }
    public function getByUserId($userId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id";
        $criteria->params = array(
            "user_id" => $userId
        );
        $list = UserPrivilege::model()->findAll($criteria);
        return $this->db2list($list);
    }
//    /**
//     * 创建用户权限
//     * @param $filePath
//     * @param $privileges
//     * @return bool
//     */
//    public function createPrivilege($filePath, $privileges)
//    {
//        //查出对应filePath的权限
//        $oldPrivileges = MiniUserPrivilege::getInstance()->getPrivilegeList($filePath);
//        //第一步 、首先根据filePath查表，将查出的数据中$names遍历出来的$name的没有的user_id删除。
//        $users = array();
//        foreach ($privileges as $user) {
//            $user = MiniUser::getInstance()->getUserByName($user['name']); //前端穿进来的username用来查询用户user_id。
//            array_push($users, $user);
//        }
//        //删除权限
//        if (!empty($oldPrivileges)) {
//            foreach ($oldPrivileges as $item) {
//                $userId = $item['user_id']; //数据表中的userId
//                $existed = false;
//                foreach ($users as $user) {
//                    if ($user["id"] == $userId) {
//                        $existed = true;
//                    }
//                }
//                if ($existed === false) {
//                    MiniUserPrivilege::getInstance()->deletePrivilege($userId, $filePath);
//                    //删除对应file_meta
//                    $key = MConst::SHARED_FOLDERS;
//                    $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key); //根据共享文件路径查到file_meta信息
//                    $metaValue = unserialize($fileMeta['meta_value']); //得到metaValue 下一步根据value查得被共享者文件路径
//                    $slaves = $metaValue['slaves']; //得到被共享者文件路径集合
//                    foreach ($slaves as $slaveId => $slaveFilePath) { //删除被共享这file file_meta
//                        if ($slaveId == $userId) {
//                            $file = MiniFile::getInstance()->getByPath($slaveFilePath);
//                            $fileId = $file['id'];
//                            $userId = $file['user_id'];
//                            //删除文件夹
//                            MiniFile::getInstance()->deleteFile($fileId);
//                            //创建slaves取消共享事件
//                            $this->createEvent($userId, 1, MConst::DELETE, $slaveFilePath, $slaveFilePath);
//                            //删除slaves的file_meta信息
//                            MiniFileMeta::getInstance()->deleteFileMetaByPath($slaveFilePath);
//                        }
//                    }
//                }
//            }
//        }
//        /**
//         * 创建被共享者的共享文件夹，meta值。
//         */
//        $newPath = explode('/', $filePath);
//        $fileName = end($newPath); //获取文件名
//        $master = MiniUser::getInstance()->getUser($newPath[1]);
//        $masterName = $master['user_name'];
//        $userIds = array();
//        $currentPaths = array();
//        $loop = 0;
//        $privileges2 = $this->getPrivilegeList($filePath); //查出已被共享的人
//        $privileges2 = $this->db2list($privileges2);
//        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, MConst::SHARED_FOLDERS);
//        $metaValue = unserialize($fileMeta['meta_value']);
//        $slaves = $metaValue['slaves'];
//
//        $privilegeIds = array();
//        foreach ($privileges2 as $privilege2) {
//            array_push($privilegeIds, $privilege2['user_id']);
//        }
//        foreach ($users as $user) { //遍历传进来的用户，为其创建文件元数据
//            $userId = $user['id'];
//            if (!empty($privileges2)) {
//                if (in_array($userId, $privilegeIds)) { //如果该用户已经被分享过
//                    $currentPath = $slaves[$userId];
//                    array_push($userIds, $userId);
//                    array_push($currentPaths, $currentPath);
//                } else {
//                    $name = $fileName . "(" . $masterName . "的共享)";
//                    $name = $this->nameUnique($name, $name, 0, $userId);
//                    $currentPath = '/' . $userId . '/' . $name;
//                    array_push($userIds, $userId);
//                    array_push($currentPaths, $currentPath);
//                }
//            } else {
//                $name = $fileName . "(" . $masterName . "的共享)";
//                $name = $this->nameUnique($name, $name, 0, $userId);
//                $currentPath = '/' . $userId . '/' . $name;
//                array_push($userIds, $userId);
//                array_push($currentPaths, $currentPath);
//            }
//        }
//        //存储或者更新权限
//        for ($i = 0; $i < count($privileges); $i++) {
//            //序列化权限
//            $privilege = $privileges[$i];
//            $user = $users[$i];
//            $privilegeDetail = $privilege['privilege'];
//            $userId = $user["id"];
//            $keys = array('resource.read', 'folder.create', 'folder.rename', 'folder.delete', 'file.create', 'file.modify', 'file.rename', 'file.delete', 'permission.grant');
//            $privilegeArray = array_map('intval', $privilegeDetail); //将数组$privilege，string转换成int
//            $permission = array_combine($keys, $privilegeArray);
//            $permission = serialize($permission);
//            //存储权限
//            $criteria = UserPrivilege::model()->find("user_id=:user_id and file_path=:file_path", array(":user_id" => $userId, ":file_path" => $filePath));
//            if (empty($criteria)) {
//                $criteria = new UserPrivilege();
//            }
//            $criteria["user_id"] = $userId;
//            $criteria["file_path"] = $filePath;
//            $criteria["permission"] = $permission;
//            $criteria->save();
//        }
//
//        foreach ($users as $user) { //遍历传进来的用户，为其创建文件元数据
//            $loop++;
//            $userId = $user['id'];
//            $currentPath = '/' . $userId . '/' . $fileName . "(" . $masterName . "的共享)";
//            $item = MiniFile::getInstance()->getByPath($currentPath);
//            if (empty($item)) { //如果文件名未被共享过，则共享
//                $file = array();
//                $file["file_type"] = MConst::OBJECT_TYPE_BESHARED;
//                $file["parent_file_id"] = 0;
//                $file["file_create_time"] = time();
//                $file["file_update_time"] = time();
//                $file["file_name"] = $fileName . "(" . $masterName . "的共享)";
//                $file["version_id"] = 0;
//                $file["file_size"] = 0;
//                $file["file_path"] = '/' . $userId . '/' . $file["file_name"];
//                $file["mime_type"]; //有存在NULL的情况
//                MiniFile::getInstance()->create($file, $userId);
//
//                //被分享者 path
//                $meta_value = array();
//                $meta_value['master'] = intval($newPath[1]); //分享者ID
//                $meta_value['slaves'] = array_combine($userIds, $currentPaths);
//                $meta_value['path'] = $filePath;
//                $meta_value['send_msg'] = "000";
//                $meta_value = serialize($meta_value);
//                $meta_key = MConst::SHARED_FOLDERS;
//                MiniFileMeta::getInstance()->createFileMeta($file["file_path"], $meta_key, $meta_value);
//                //创建事件
//                $this->createEvent($userId, 1, MConst::SHARE_FOLDER, $file["file_path"], $file["file_path"]);
//
//                //分享者 path
//                if ($loop == count($users)) {
//                    $meta_value = array();
//                    $meta_value['master'] = intval($newPath[1]); //分享者ID
//                    $meta_value['slaves'] = array_combine($userIds, $currentPaths);
//                    $meta_value['path'] = $filePath;
//                    $meta_value['send_msg'] = "000";
//                    $meta_value = serialize($meta_value);
//                    $meta_key = MConst::SHARED_FOLDERS;
//                    MiniFileMeta::getInstance()->createFileMeta($filePath, $meta_key, $meta_value);
//                    $this->createEvent(intval($newPath[1]), 1, MConst::SHARE_FOLDER, $filePath, $filePath);
//
//                }
//            } else { //如果文件名已被共享过，则需判断是否同一路径传入。不是则新创建
//                $key = MConst::SHARED_FOLDERS;
//                $file_meta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key);
//                $meta_value = unserialize($file_meta['meta_value']);
//                $path = $meta_value['path'];
//                if ($path != $filePath) { //共享文件名相同，但路径不同,则创建。同路径同名文件不二次创建
//                    $file = array();
//                    $file["file_type"] = MConst::OBJECT_TYPE_BESHARED;
//                    $file["parent_file_id"] = 0;
//                    $file["file_create_time"] = time();
//                    $file["file_update_time"] = time();
//                    $num = 0;
//                    $name = $fileName . "(" . $masterName . "的共享)";
//                    $file["file_name"] = $this->nameUnique($name, $name, $num, $userId);
//                    $file["version_id"] = 0;
//                    $file["file_size"] = 0;
//                    $file["file_path"] = '/' . $userId . '/' . $file["file_name"];
//                    $file["mime_type"]; //有存在NULL的情况
//                    MiniFile::getInstance()->create($file, $userId);
//
//                    //被分享者 path
//                    $meta_value = array();
//                    $meta_value['master'] = intval($newPath[1]); //分享者ID
//                    $meta_value['slaves'] = array_combine($userIds, $currentPaths);
//                    $meta_value['path'] = $filePath;
//                    $meta_value['send_msg'] = "000";
//                    $meta_value = serialize($meta_value);
//                    $meta_key = MConst::SHARED_FOLDERS;
//                    MiniFileMeta::getInstance()->createFileMeta($file["file_path"], $meta_key, $meta_value);
//
//                    //创建事件
//                    $this->createEvent($userId, 1, MConst::SHARE_FOLDER, $file["file_path"], $file["file_path"]);
//
//                    //分享者 path
//                    if ($loop == count($users)) {
//                        $meta_value = array();
//                        $meta_value['master'] = intval($newPath[1]); //分享者ID
//                        $meta_value['slaves'] = array_combine($userIds, $currentPaths);
//                        $meta_value['path'] = $filePath;
//                        $meta_value['send_msg'] = "000";
//                        $meta_value = serialize($meta_value);
//                        $meta_key = MConst::SHARED_FOLDERS;
//                        MiniFileMeta::getInstance()->createFileMeta($filePath, $meta_key, $meta_value);
//                        //创建事件
//                        $this->createEvent(intval($newPath[1]), 1, MConst::SHARE_FOLDER, $filePath, $filePath);
//
//                    }
//                } else { //同路径同名文件不二次创建,但是存在修改问题。
//                    $key = MConst::SHARED_FOLDERS;
//                    $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key);
//                    $metaValue = unserialize($fileMeta['meta_value']);
//                    $slaves = $metaValue['slaves'];
//                    $slavePath = $slaves[$userId]; //得到用户path，
//
//                    //被分享者 path
//                    $meta_value = array();
//                    $meta_value['master'] = intval($newPath[1]); //分享者ID
//                    $meta_value['slaves'] = array_combine($userIds, $currentPaths);
//                    $meta_value['path'] = $filePath;
//                    $meta_value['send_msg'] = "000";
//                    $meta_value = serialize($meta_value);
//                    $meta_key = MConst::SHARED_FOLDERS;
//                    MiniFileMeta::getInstance()->createFileMeta($slavePath, $meta_key, $meta_value);
//
//                    //分享者 path
//                    if ($loop == count($users)) {
//                        $meta_value = array();
//                        $meta_value['master'] = intval($newPath[1]); //分享者ID
//                        $meta_value['slaves'] = array_combine($userIds, $currentPaths);
//                        $meta_value['path'] = $filePath;
//                        $meta_value['send_msg'] = "000";
//                        $meta_value = serialize($meta_value);
//                        $meta_key = MConst::SHARED_FOLDERS;
//                        MiniFileMeta::getInstance()->createFileMeta($filePath, $meta_key, $meta_value);
//                        //创建事件
//                    }
//                }
//            }
//        }
//        /**
//         * 存储权限之后更新被分享文件的file_type = 2，出现分享图标
//         */
//        $beSharedFile = MiniFile::getInstance()->getByPath($filePath);
//        $beSharedFile['file_type'] = MConst::OBJECT_TYPE_SHARED;
//        MiniFile::getInstance()->updateByPath($filePath, $beSharedFile);
//        return true;
//    }

    /**
     * 获得权限列表
     * @param $filePath
     * @return array
     */
    public function getPrivilegeList($filePath)
    {
        $criteria = new CDbCriteria();
        $criteria->condition = "file_path=:file_path";
        $criteria->params = array(
            "file_path" => $filePath
        );
        $criteria->order = "id ASC";
        $list = UserPrivilege::model()->findAll($criteria);
        return $this->db2list($list);
    }

    /**
     * 获得特定用户的权限
     */
    public function getSpecifyPrivilege($userId, $filePath)
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'user_id=:user_id and file_path=:file_path';
        $criteria->params = array(':user_id' => $userId, ':file_path' => $filePath);
        $item = UserPrivilege::model()->find($criteria);
        return ($this->db2Item($item));
    }

    /**
     * 删除权限
     */
    public function deletePrivilege($userId, $filePath)
    {
        $modal = UserPrivilege::model()->find("user_id=:user_id and file_path=:file_path", array(":user_id" => $userId, ":file_path" => $filePath));
        if (!empty($modal)) {
            $modal->delete();
        }
        return true;
    }
    /**
     * 删除用户的时候删除其对应user_privilege表权限
     */
    public function deletePrivilegeWhenKillUser($userId){
        $modal = UserPrivilege::model()->find("user_id=:user_id", array(":user_id" => $userId));
        if (!empty($modal)) {
            $modal->delete();
        }
        return true;
    }

    /**
     * 根据路径删除记录
     * @param $path
     * @return bool
     */
    public function deleteByFilePath($path)
    {
        $criteria = new CDbCriteria;
        $criteria->condition = "file_path=:file_path";
        $criteria->params = array("file_path" => $path);
        UserPrivilege::model()->deleteAll($criteria);
        return true;
    }

    /**
     * 递归查询文件夹创建唯一文件夹名
     */
    public function nameUnique($name, $path, $num, $userId)
    {
        //$path和$name相同，为了保存原始路径
        $item = MiniFile::getInstance()->getFileByName($userId, $name);
        if (empty($item)) {
            return $name;
        } else {
            $num = $num + 1;
            $name = $path;
            return $this->nameUnique($name . "-" . $num, $path, $num, $userId);
        }
    }

    /**
     * 创建事件
     */
    public function createEvent($userId, $userDeviceId, $action, $path, $context)
    {
        $eventUuid = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
        MiniEvent::getInstance()->createEvent($userId, $userDeviceId, $action, $path, $context, $eventUuid, $extends = NULL);
    }

    /**
     * 判断目录是否可发起共享
     * 递归查询父目录file_type情况，file_type=1时返回false，file_type==2||3时返回true
     */
    public function getFolderPrivilege($currentUserId,$file){
        $filePath = $file['file_path'];
        $fileType = (int)$file['file_type'];
        //被共享目录本身可以修改和删除
        $privilege = Array('resource.read' => 1, 'folder.create' => 1, 'folder.rename' => 1, 'folder.delete' => 1, 'file.create' => 1, 'file.modify' => 1, 'file.rename' => 1, 'file.delete' => 1, 'permission.grant' => 1,'can_set_share' => 1);
        if($fileType == 3 ){
            $parentPath = $file['file_path'];
            //当用户，群组与部门中的用户权限出现重复时，获取最小部门的权限，顺序为用户，群组，部门
            $userPrivilege = MiniUserPrivilege::getInstance()->getSpecifyPrivilege($currentUserId,$parentPath);
            if(empty($userPrivilege)){
                $userGroupRelations = MiniUserGroupRelation::getInstance()->getByUserId($currentUserId);
                if(count($userGroupRelations)>1){//说明用户对应了群组和部门,
                    $groupIdsArr = array();
                    //获取群组id
                    foreach($userGroupRelations as $userGroupRelation){
                        $group = MiniGroup::getInstance()->findById($userGroupRelation['group_id']);
                        if($group['user_id']!=-1){
                            array_push($groupIdsArr,$userGroupRelation['group_id']);
                        }else{
                            $departmentId = $userGroupRelation['group_id'];
                        }
                    }
                    //将所有群组的权限放入数组
                    $permissionArr = array();
                    foreach($groupIdsArr as $groupId){
                        $privilege_0 = MiniGroupPrivilege::getInstance()->getSpecifyPrivilege($groupId,$parentPath);
                        if(!empty($privilege_0)){
                            array_push($permissionArr,$privilege_0['permission']);
                        }
                    }
                    //拼接群组中权限的最大值，如果为空则为空字符串
                    $permission = "";
                    if(count($permissionArr)>0){
                        for($j=0;$j<10;$j++){
                           $isHighestAuthority = false;
                           foreach($permissionArr as $per){
                               if($per[$j]==1){
                                   $isHighestAuthority = true;
                                   break;
                               }
                           }
                            if($isHighestAuthority){
                                $permission .="1";
                            }else{
                                $permission .= "0";
                            }

                       }
                    }
                    if($permission==""){
                        $groupPrivilege = MiniGroupPrivilege::getInstance()->getSpecifyPrivilege($departmentId,$parentPath);
                        if(empty($groupPrivilege)){
                            $groupPrivilege =  MiniGroupPrivilege::getInstance()->getGroupPrivilege($filePath,$departmentId);
                        }
                        $permission= $groupPrivilege['permission'];
                    }
                }else{
                    $groupId = $userGroupRelations[0]['group_id'];
                    $groupPrivilege = MiniGroupPrivilege::getInstance()->getSpecifyPrivilege($groupId,$parentPath);
                    if(empty($groupPrivilege)){
                        $groupPrivilege =  MiniGroupPrivilege::getInstance()->getGroupPrivilege($filePath,$groupId);
                    }
                    $permission= $groupPrivilege['permission'];
                }
            }else{
                $permission = $userPrivilege['permission'];
            }
            for($i=0;$i<strlen($permission);$i++){
                $privilege['resource.read'] = (int)$permission[0];
                $privilege['folder.create'] = (int)$permission[1];
                $privilege['folder.rename'] = (int)$permission[2];
                $privilege['folder.delete'] = (int)$permission[3];
                $privilege['file.create'] = (int)$permission[4];
                $privilege['file.modify'] = (int)$permission[5];
                $privilege['file.rename'] = (int)$permission[6];
                $privilege['file.delete'] = (int)$permission[7];
                $privilege['permission.grant'] = (int)$permission[8];
                $privilege["can_set_share"] = 0;
            }
        }
        if($fileType == 1){
            $isShared = false;
            $userId = $file['user_id'];
            //判断下级目录是否有共享目录
            $filePath = $file['file_path'];
            $children = MiniFile::getInstance()->getShowChildrenByPath($filePath);
            $userGroupRelation = MiniUserGroupRelation::getInstance()->getByUserId($userId);
            $groupId = $userGroupRelation['group_id'];
            $arr = array();
            array_push($arr,$groupId);
            $groupIds = MiniGroupPrivilege::getInstance()->getGroupIds($groupId,$arr);
            foreach($children as $child){
                $childFilePath = $child['file_path'];
                if($childFilePath==$filePath){
                    continue;
                }
                $file = MiniFile::getInstance()->getByFilePath($childFilePath);
                if($file['file_type']==2){
                    $isShared = true;
                    break;
                }
            }
            if($isShared){
                //子目录已经共享则不能二次共享
                $privilege["can_set_share"] = 0;
            }else{
                //判断上级目录是否有共享目录
                $arr = explode('/',$filePath);
                $parentPath = "/".$userId;
                for($i=2;$i<count($arr);$i++){
                    $parentPath = $parentPath."/".$arr[$i];
                    $file = MiniFile::getInstance()->getByFilePath($parentPath);
                    if($file['file_type']==2){
                        $privilege["can_set_share"] = 0;
                    }
                }
            }
        }
        return $privilege;
    }

   /**
     * 取消共享，删除权限
     */
    public function cancelPrivilege($filePath){

    }
//    public function cancelPrivilege($filePath)
//    {
//        $temp = explode("/", $filePath);
//        $masterId = $temp[1];
//        $key = MConst::SHARED_FOLDERS;
//        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key); //根据共享文件路径查到file_meta信息
//        $metaValue = unserialize($fileMeta['meta_value']); //得到metaValue 下一步根据value查得被共享者文件路径
//        $slaves = $metaValue['slaves']; //得到被共享者文件路径集合
//        foreach ($slaves as $slavePath) {
//            $file = MiniFile::getInstance()->getByPath($slavePath);
//            $fileId = $file['id'];
//            $userId = $file['user_id'];
//            //删除文件夹
//            MiniFile::getInstance()->deleteFile($fileId);
//            //创建slaves取消共享事件
//            $this->createEvent($userId, 1, MConst::DELETE, $slavePath, $slavePath);
//            //删除slaves的file_meta信息
//            MiniFileMeta::getInstance()->deleteFileMetaByPath($slavePath);
//            //删除privilege信息
//            $this->deletePrivilege($userId, $filePath);
//        }
//        //删除master的file_meta信息
//        MiniFileMeta::getInstance()->deleteFileMetaByPath($filePath);
//        //删除master的privilege信息
//        $this->deletePrivilege($masterId, $filePath);
//        //创建master取消共享事件
//        $this->createEvent($masterId, 1, MConst::CANCEL_SHARED, $filePath, $filePath);
//        // 取消共享后被共享文件file_type = 2，出现分享图标
//        $beSharedFile = MiniFile::getInstance()->getByPath($filePath);
//        $beSharedFile['file_type'] = MConst::OBJECT_TYPE_DIRECTORY;
//        MiniFile::getInstance()->updateByPath($filePath, $beSharedFile);
//        return true;
//    }
    public function searchFilePath($filePath){
        $criteria = new CDbCriteria();
        $criteria->condition = "file_path like :file_path";
        $criteria->params = array(':file_path'=>$filePath.'/%');
        $items = UserPrivilege::model()->findAll($criteria);
        return ($this->db2list($items));
    }
    /**
     * 获取所有记录
     * @return array
     */
    public function getAllUserPrivilege(){
        $criteria = new CDbCriteria();
        $items = UserPrivilege::model()->findAll($criteria);
        return ($this->db2list($items));
    }

}