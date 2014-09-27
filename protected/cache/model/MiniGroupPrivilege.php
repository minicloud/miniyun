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
class MiniGroupPrivilege extends MiniCache
{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.GroupPrivilege";

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
        return MiniGroupPrivilege::$CACHE_KEY . "_" . $id;
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
        $value["group_id"] = $item->group_id;
        $value["file_path"] = $item->file_path;
        $value["permission"] = $item->permission;
        $value["created_at"] = $item->created_at;
        $value["updated_at"] = $item->updated_at;
        return $value;
    }
    public function create($groupId,$filePath,$permission){
        $criteria = GroupPrivilege::model()->find("group_id=:group_id and file_path=:file_path", array(":group_id" => $groupId, ":file_path" => $filePath));
        if (empty($criteria)) {
            $criteria = new GroupPrivilege();
        }
        $criteria["group_id"] = $groupId;
        $criteria["file_path"] = $filePath;
        $criteria["permission"] = $permission;
        $criteria->save();
        return true;
    }
    /**
     * 创建用户权限
     */
    public function createDepartmentPrivilege($privileges,$filePath){
        $newPath = explode('/', $filePath);
        $fileName = end($newPath); //获取文件名
        $master = MiniUser::getInstance()->getUser($newPath[1]);
        $masterName = $master['user_name'];

        $userIds = array();
        $currentPaths = array();
        $loop = 0;
        $privileges2 = $this->getPrivilegeList($filePath); //查出已被共享的人
        $privileges2 = $this->db2list($privileges2);

        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, MConst::SHARED_FOLDERS);
        $metaValue = unserialize($fileMeta['meta_value']);
        $slaves = $metaValue['slaves'];
        $privilegeIds = array();
        foreach ($privileges2 as $privilege2) {
            array_push($privilegeIds, $privilege2['user_id']);
        }

        foreach ($privileges as $privilege) {
            $userGroups = MiniUserGroupRelation::getInstance()->getByGroupId($privilege['slave_id']);

            //存储或者更新权限
            $groupId = $privilege['slave_id'];
            $privilegeDetail = $privilege['privilege'];
            $keys = array('resource.read', 'folder.create', 'folder.rename', 'folder.delete', 'file.create', 'file.modify', 'file.rename', 'file.delete', 'permission.grant');
            for($i=0;$i<strlen($privilegeDetail);$i++){//将数组$privilege，string转换成int
                $privilegeArray[] = $privilegeDetail[$i];
            }
            $permission = array_combine($keys, $privilegeArray);
            $permission = serialize($permission);
            //存储权限
            $this->create($groupId,$filePath,$permission);
            foreach($userGroups as $key=>$userGroup){
                $userId = $userGroup['user_id'];
                if (!empty($privileges2)) {
                    if (in_array($userId, $privilegeIds)) { //如果该用户已经被分享过
                        $currentPath = $slaves[$userId];
                        array_push($userIds, $userId);
                        array_push($currentPaths, $currentPath);
                    } else {
                        $name = $fileName . "(" . $masterName . "的共享)";
                        $name = MiniUserPrivilege::getInstance()->nameUnique($name, $name, 0, $userId);
                        $currentPath = '/' . $userId . '/' . $name;
                        array_push($userIds, $userId);
                        array_push($currentPaths, $currentPath);
                    }
                } else {
                    $name = $fileName . "(" . $masterName . "的共享)";
                    $name = MiniUserPrivilege::getInstance()->nameUnique($name, $name, 0, $userId);
                    $currentPath = '/' . $userId . '/' . $name;
                    array_push($userIds, $userId);
                    array_push($currentPaths, $currentPath);
                }


        //创建元数据
        $currentPath = '/' . $userId . '/' . $fileName . "(" . $masterName . "的共享)";
        $item = MiniFile::getInstance()->getByPath($currentPath);
        if (empty($item)) { //如果文件名未被共享过，则共享
            $file = array();
            $file["file_type"] = MConst::OBJECT_TYPE_BESHARED;
            $file["parent_file_id"] = 0;
            $file["file_create_time"] = time();
            $file["file_update_time"] = time();
            $file["file_name"] = $fileName . "(" . $masterName . "的共享)";
            $file["version_id"] = 0;
            $file["file_size"] = 0;
            $file["file_path"] = '/' . $userId . '/' . $file["file_name"];
            $file["mime_type"]; //有存在NULL的情况
            MiniFile::getInstance()->create($file, $userId);

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
            $this->createEvent($userId, 1, MConst::SHARE_FOLDER, $file["file_path"], $file["file_path"]);

            //分享者 path
            if ($key == count($userGroups)-1) {
                $meta_value = array();
                $meta_value['master'] = intval($newPath[1]); //分享者ID
                $meta_value['slaves'] = array_combine($userIds, $currentPaths);
                $meta_value['path'] = $filePath;
                $meta_value['send_msg'] = "000";
                $meta_value = serialize($meta_value);
                $meta_key = MConst::SHARED_FOLDERS;
                MiniFileMeta::getInstance()->createFileMeta($filePath, $meta_key, $meta_value);
//                $this->createEvent(intval($newPath[1]), 1, MConst::SHARE_FOLDER, $filePath, $filePath);

            }
        } else { //如果文件名已被共享过，则需判断是否同一路径传入。不是则新创建
            $key = MConst::SHARED_FOLDERS;

            $file_meta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key);
            $meta_value = unserialize($file_meta['meta_value']);
            $path = $meta_value['path'];

            if ($path != $filePath) { //共享文件名相同，但路径不同,则创建。同路径同名文件不二次创建
                $file = array();
                $file["file_type"] = MConst::OBJECT_TYPE_BESHARED;
                $file["parent_file_id"] = 0;
                $file["file_create_time"] = time();
                $file["file_update_time"] = time();
                $num = 0;
                $name = $fileName . "(" . $masterName . "的共享)";
                $file["file_name"] = MiniUserPrivilege::getInstance()->nameUnique($name, $name, $num, $userId);
                $file["version_id"] = 0;
                $file["file_size"] = 0;
                $file["file_path"] = '/' . $userId . '/' . $file["file_name"];
                $file["mime_type"]; //有存在NULL的情况

                MiniFile::getInstance()->create($file, $userId);

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
//                $this->createEvent($userId, 1, MConst::SHARE_FOLDER, $file["file_path"], $file["file_path"]);

                //分享者 path
                if ($key == count($userGroups)-1) {
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
                $slavePath = $slaves[$userId]; //得到用户path，

                //被分享者 path
                $meta_value = array();
                $meta_value['master'] = intval($newPath[1]); //分享者ID
                $meta_value['slaves'] = array_combine($userIds, $currentPaths);
                $meta_value['path'] = $filePath;
                $meta_value['send_msg'] = "000";
                $meta_value = serialize($meta_value);
                $meta_key = MConst::SHARED_FOLDERS;
                MiniFileMeta::getInstance()->createFileMeta($slavePath, $meta_key, $meta_value);

                //分享者 path
                if ($key == count($userGroups)-1) {
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

        }

        }
        /**
         * 存储权限之后更新被分享文件的file_type = 2，出现分享图标
         */
        MiniFile::getInstance()->togetherShareFile($filePath, MConst::OBJECT_TYPE_SHARED);
        return true;
    }
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
        $list = GroupPrivilege::model()->findAll($criteria);
        return $list;
    }

    /**
     * 获得特定用户组的权限
     */
    public function getSpecifyPrivilege($groupId, $filePath)
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'group_id=:group_id and file_path=:file_path';
        $criteria->params = array(':group_id' => $groupId, ':file_path' => $filePath);
        $item = GroupPrivilege::model()->find($criteria);
        return ($this->db2Item($item));
    }

    /**
     * 删除权限
     */
    public function deletePrivilege($groupId, $filePath)
    {
        $modal = GroupPrivilege::model()->find("group_id=:group_id and file_path=:file_path", array(":group__id" => $groupId, ":file_path" => $filePath));
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

//    /**
//     * 创建事件
//     */
//    public function createEvent($userId, $userDeviceId, $action, $path, $context)
//    {
//        $eventUuid = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
//        MiniEvent::getInstance()->createEvent($userId, $userDeviceId, $action, $path, $context, $eventUuid, $extends = NULL);
//    }
    /**
     * 判断目录是否可发起共享
     * 递归查询父目录file_type情况，file_type=1时返回false，file_type==2||3时返回true
     */
    public function getFolderPrivilege($currentUserId,$file){
        $fileType = intval($file['file_type']);
        //被共享目录本身可以修改和删除
        $privilege = Array('resource.read' => 1, 'folder.create' => 1, 'folder.rename' => 1, 'folder.delete' => 1, 'file.create' => 1, 'file.modify' => 1, 'file.rename' => 1, 'file.delete' => 1, 'permission.grant' => 1,'can_set_share' => 1);
        if($fileType === 3){
            $fileMeta = MiniFileMeta::getInstance()->getFileMeta($file['file_path'],MConst::SHARED_FOLDERS);
            $metaValue = unserialize($fileMeta['meta_value']);
            $masterPath = $metaValue['path'];
            $item = $this->getSpecifyPrivilege($currentUserId,$masterPath);
            $privilege = unserialize($item['permission']);
            //被共享着不能发起共享
            $privilege["folder.delete"] = 1;
            $privilege["can_set_share"] = 0;
        }
        if($fileType === 1){
            //判断下级目录是否有共享目录
            $filePath = $file['file_path'];
            $key = MConst::SHARED_FOLDERS;
            $childrenMeta = MiniFileMeta::getInstance()->getChildrenFileMetaByPath($filePath,$key);
            if(!empty($childrenMeta)){
                //子目录已经共享则不能二次共享
                $privilege["can_set_share"] = 0;
            }else{
                //判断上级目录是否有共享目录,获得父目录权限
                $arr = explode('/',$filePath);
                $userId = $file['user_id'];
                $parentPath = "/".$userId;
                for($i=2;$i<count($arr);$i++){
                    $parentPath = $parentPath."/".$arr[$i];
                    $fileMeta = MiniFileMeta::getInstance()->getFileMeta($parentPath,MConst::SHARED_FOLDERS);
                    if(!empty($fileMeta)){
                        $metaValue = unserialize($fileMeta['meta_value']);
                        $masterPath = $metaValue['path'];
                        $item = $this->getSpecifyPrivilege($currentUserId,$masterPath);
                        if(!empty($item)){//权限列表只有被共享目录的信息，所以必须判断(非空则为被共享目录)
                            $privilege = unserialize($item['permission']);
                        }else{
                            $privilege = Array('resource.read' => 1, 'folder.create' => 1, 'folder.rename' => 1, 'folder.delete' => 1, 'file.create' => 1, 'file.modify' => 1, 'file.rename' => 1, 'file.delete' => 1, 'permission.grant' => 1);
                        }
                        $privilege["can_set_share"] = 0;
                        break;
                    }
                }
            }
        }
        return $privilege;
    }

    /**
     * 取消共享，删除权限
     */
    public function cancelPrivilege($filePath)
    {
        $temp = explode("/", $filePath);
        $masterId = $temp[1];
        $key = MConst::SHARED_FOLDERS;
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath, $key); //根据共享文件路径查到file_meta信息
        $metaValue = unserialize($fileMeta['meta_value']); //得到metaValue 下一步根据value查得被共享者文件路径
        $slaves = $metaValue['slaves']; //得到被共享者文件路径集合
        foreach ($slaves as $slavePath) {
            $file = MiniFile::getInstance()->getByPath($slavePath);
            $fileId = $file['id'];
            $userId = $file['user_id'];
            //删除文件夹
            MiniFile::getInstance()->deleteFile($fileId);
            //创建slaves取消共享事件
            $this->createEvent($userId, 1, MConst::DELETE, $slavePath, $slavePath);
            //删除slaves的file_meta信息
            MiniFileMeta::getInstance()->deleteFileMetaByPath($slavePath);
            //删除privilege信息
            $this->deletePrivilege($userId, $filePath);
        }
        //删除master的file_meta信息
        MiniFileMeta::getInstance()->deleteFileMetaByPath($filePath);
        //删除master的privilege信息
        $this->deletePrivilege($masterId, $filePath);
        //创建master取消共享事件
        $this->createEvent($masterId, 1, MConst::CANCEL_SHARED, $filePath, $filePath);
        // 取消共享后被共享文件file_type = 2，出现分享图标
        $beSharedFile = MiniFile::getInstance()->getByPath($filePath);
        $beSharedFile['file_type'] = MConst::OBJECT_TYPE_DIRECTORY;
        MiniFile::getInstance()->updateByPath($filePath, $beSharedFile);
        return true;
    }

}