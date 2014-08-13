<?php
/** 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ShareManager extends CApiComponent
{
    public $_master;         // 发起者用户id
    public $_slaves;         // 逗号分割的用户id
    public $_permissions;          // 逗号分割的共享属性 权限
    public $_id;             // 目录id
    public $_userNick;
    public $_from;
    public $_to;
    public $_file;          // 文件信息
    private $_send_msg;  //       是否发送消息通知
    private $_update_meta = array();

    //添加的共享的用户
    public $addShareUsers = array();
    //删除的共享的用户
    public $deleteShareUsers = array();
    //修改的共享的用户
    public $modifyShareUsers = array();

    const SHARE_FOLDERS = 1; // 创建共享
    const CANCEL_SHARED = 2; // 取消共享
    const ADD_SHARED    = 3; // 共享添加用户
    const RENAME_SHARED = 4; // 重命名
    const LIST_DETAILS  = 5; // 获取共享信息
    const SHARED_META_FLAG   = 'shared_folders';

    /**
     *
     * 构造函数，初始化一些参数
     */
    public function __construct() {
        parent::init ();
        $this->result = array ();
        $this->result["state"] = false;
        $this->result["code"]  = 0;
        $this->result["msg"] = "操作失败";
        $this->result['dir_pickcode'] = array();
        $this->result['data'] = array("d"=>1);
    }

    /**
     *
     * 共享文件夹入口
     */
    public function invoke ($action = 0, $send_msg = 0)
    {
        $this->_send_msg = $send_msg;
        $device = new UserDevice();
        $device = $device->findByUserIdAndType($this->_userId, 1);
        $this->_deviceId = $device["id"];
        $user = User::model()->findByPk($this->_userId);
        $this->_userNick = $user["user_name"];

        switch ($action) {
            case self::SHARE_FOLDERS:
                $userIds = array_slice(explode(',', $this->_slaves), 0);
                $this->handlerCreateShareFolder();
                //创建用户对资源操作的权限
                foreach ($this->addShareUsers as $userId){
                    $path = $this->_file['file_path'];
                    Yii::app()->privilege->createPrivilege($userId, $path, $this->getUserPermission($userIds, $userId));
                }
                //删除用户对资源操作的权限
                foreach ($this->deleteShareUsers as $userId){
                    $path = $this->_file['file_path'];
                    Yii::app()->privilege->deleteAllUserPrivilege($userId, $path);
                }
                break;
            case self::CANCEL_SHARED:
                $this->handlerCancelSharedFolder();
                //取消用户对资源操作的权限(包括层级关系)
                foreach ($this->deleteShareUsers as $userId){
                    $path = $this->_file['file_path'];
                    Yii::app()->privilege->deleteAllUserPrivilege($userId, $path);
                }
                break;
            case self::ADD_SHARED:
                $this->handlerAddUser2Shared();
                break;
            case self::RENAME_SHARED:
                $this->handlerRenameShared();
                //修改对资源操作的权限(包括层级关系)
                foreach ($this->modifyShareUsers as $userId){
                    $pathFrom = $this->_from;
                    $pathTo   = $this->_to;
                    Yii::app()->privilege->updatedFilePath($userId, $pathFrom, $pathTo);
                }
                $tttt = $this->modifyShareUsers;
                break;
            case self::LIST_DETAILS:
                $this->handlerListDetails();
                break;
            default:
                break;
        }
        $this->result["state"] = true;
        $this->result["msg"] = '操作成功';
    }

    /**
     * 获取用户的权限
     *
     * @since 1.0.7
     */
    public function getUserPermission($userIds, $userId){
        $indexUser = 0;
        foreach ($userIds as $index=>$id){
            $indexUser = $index;
            if ($userId == $id){
                break;
            }
        }
        return MUtils::getPermissionArray($this->_permissions[$indexUser]);
    }

    /**
     * 创建共享文件夹
     *
     * @since 1.0.7
     */
    private function handlerCreateShareFolder ()
    {
        $this->_file = UserFile::model()->findByAttributes(array('id'=>$this->_id,'user_id'=>$this->_userId,'is_deleted'=>0));
        if (is_null($this->_file)) {
            throw new ApiException('Not Found');
        }

        //
        // 将逗号分割的id组装成数组
        //
        $this->_slaves = array_slice(explode(',', $this->_slaves), 0);
        $this->_permissions  = array_slice(explode(',', $this->_permissions), 0);
        $this->_master = $this->_userId;

        //添加共享用户
        $this->addShareUsers = $this->_slaves;

        //
        // 如果已经共享的，则调用修改共享
        //
        if ($this->_file['file_type'] >= 2) {
            $this->handlerModifyShared();
            return ;
        }

        if ($this->_file['file_type'] != 1) {
            throw new ApiException('Not Found');
        }

        if (empty($this->_slaves) || $this->_slaves == '-1' || empty($this->_permissions) || $this->_permissions == '-1') {
            throw new ApiException("参数错误");
        }


        $file_name = $this->_file['file_name'];
        $this->_file['file_type'] = 2;
        //
        // 为每个用户创建事件，创建meta
        //
        $metas = $this->handlerCreateFolder();

        $meta = array();
        $meta['master'] = $this->_master;
        $meta['slaves'] = $metas;
        $meta['path']   = $this->_file['file_path'];
        $meta['send_msg'] = $this->_send_msg;

        $metas[$this->_master] = $this->_file['file_path'];
        $meta_value = serialize($meta);
        foreach ($metas as $k => $v) {
            $file_meta = FileMeta::model()->findByAttributes(array('file_path' => $v, 'meta_key' => self::SHARED_META_FLAG));
            if (!$file_meta) {
                $file_meta = new FileMeta();
            }
            $file_meta['meta_value'] = $meta_value;
            $file_meta['file_path']  = $v;
            $file_meta['meta_key']   = self::SHARED_META_FLAG;
            $file_meta->save();
        }

        $event_uuid = MiniUtil::getEventRandomString(46);
        MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, 5, $this->_file['file_path'], $this->_file['file_path'], $event_uuid);
        $this->_file['event_uuid'] = $event_uuid;
        $this->_file->save();
    }

    /**
     *
     * 增加或者删除共享
     *
     * @since 1.0.7
     */
    private function handlerModifyShared() {
        $fileMeta = FileMeta::model()->findByAttributes(array('file_path' => $this->_file['file_path'], 'meta_key' => self::SHARED_META_FLAG));
        if (!$fileMeta) {
            $file_meta = new FileMeta();
            $fileMeta['meta_key']   = self::SHARED_META_FLAG;
            $fileMeta['file_path']  = $this->_file['file_path'];
            $fileMeta['meta_value'] = serialize(array('master'=>$this->_userId, 'path'=>$this->_file['file_path'],'slaves'=>array(),'send_msg'=>$this->_send_msg));
        }
        $meta_value = unserialize($fileMeta['meta_value']);
        $master = $meta_value['master'];
        $path   = $meta_value['path'];
        $slaves = $meta_value['slaves'];
        $oldSend = $meta_value['send_msg'];
        if ($this->_userId != $master) {
            throw new ApiException('没有权限');;
        }
        $add     = array();  // 添加的用户
        $del     = array();
        $compare = array();
        foreach ($this->_slaves as $key=>$v) {
            if (isset($slaves[$v]) == false) {
                array_push($add, $v);
            } else {

                // 创建事件
                $event_uuid = MiniUtil::getEventRandomString(46);
                $action = 5;
                MiniEvent::getInstance()->createEvent($v, $this->_deviceId, $action, $slaves[$v], $slaves[$v], $event_uuid);
            }

            $compare[$v] = $v;
        }

        //
        // 比较得出删除用户
        //
        foreach ($slaves as $k => $v) {
            if (isset($compare[$k]) == false) {
                $del[$k] = $v;
                //删除共享用户计入全局变量 
                $this->deleteShareUsers[] = $k;
                unset($slaves[$k]);
            }
        }


        //
        // 删除被共享的
        //
        foreach ($del as $k => $v) {
            $event_uuid = MiniUtil::getEventRandomString(46);
            UserFile::model()->deleteAllByAttributes(array('file_path'=>$v, 'file_type'=> 3));
            FileMeta::model()->deleteAllByAttributes(array('file_path'=>$v, 'meta_key'=>self::SHARED_META_FLAG));
            MiniEvent::getInstance()->createEvent($k, $this->_deviceId, 1, $v, $v, $event_uuid);
            $this->handlerDeleteMyFavorate($k, $path);
        }

        //
        // 为每个增加的用户创建文件夹
        //
        $this->_slaves = $add;
        $metas = $this->handlerCreateFolder();
        $this->_slaves = $metas + $slaves;
        $meta_value['slaves'] = $this->_slaves;
        $meta_value['send_msg'] = $this->_send_msg;
        $meta_value = serialize($meta_value);


        if (count($add) == 0 && count($del) == 0 && $oldSend == $this->_send_msg) {
            return ;
        }

        //
        // 没有其他用户
        //
        if (count($this->_slaves) == 0) {
            $file_meta = FileMeta::model()->findByAttributes(array('file_path' => $v, 'meta_key' => self::SHARED_META_FLAG));
            $file_meta['meta_value'] = $meta_value;
            $file_meta['file_path']  = $v;
            $file_meta['meta_key']   = self::SHARED_META_FLAG;
            $file_meta->save();
        }

        //
        // 更新
        //
        $this->_slaves[$master] = $path;
        foreach ($this->_slaves as $k => $v) {
            $file_meta = FileMeta::model()->findByAttributes(array('file_path' => $v, 'meta_key' => self::SHARED_META_FLAG));
            if (!$file_meta) {
                $file_meta = new FileMeta();
            }
            $file_meta['meta_value'] = $meta_value;
            $file_meta['file_path']  = $v;
            $file_meta['meta_key']   = self::SHARED_META_FLAG;
            $file_meta->save();
        }
    }

    /**
     *
     * 取消共享文件夹
     *
     * @since 1.0.7
     */
    private function handlerCancelSharedFolder()
    {
        $this->_file = UserFile::model()->findByPk($this->_id);
        if (empty($this->_file)) {
            throw new ApiException("NOT FOUND");
        }

        if ($this->_file['file_type'] != 2 && $this->_file['file_type'] != 3 && $this->_file['file_type'] != 4) {
            throw new ApiException("NOT FOUND");
        }

        $file_meta = FileMeta::model()->findByAttributes(array('file_path'=>$this->_file['file_path'],
            'meta_key'=>self::SHARED_META_FLAG));
        if (empty($file_meta)) {
            $this->_file['file_type'] = 1;
            $this->_file->save();
            return;
        }

        $meta_value = unserialize($file_meta['meta_value']);
        $this->_master = $meta_value['master'];
        $this->_slaves = $meta_value['slaves'];
        $path = $meta_value['path'];
        //
        // 表示共享者删除共享，所有全部都删除,否则只删除自己的
        //
        if ($this->_master == $this->_userId) {
            $this->_file['file_type'] = 1;
            //
            // 删除共享信息
            //
            foreach ($this->_slaves as $k => $v) {
                //添加需要删除的共享用户到列表中
                $this->deleteShareUsers[] = $k;

                $event_uuid = MiniUtil::getEventRandomString(46);
                UserFile::model()->deleteAllByAttributes(array('file_path'=>$v, 'file_type'=> 3));
                FileMeta::model()->deleteAllByAttributes(array('file_path'=>$v, 'meta_key'=>self::SHARED_META_FLAG));
                MiniEvent::getInstance()->createEvent($k, $this->_deviceId, 1, $v, $v, $event_uuid);
                $this->handlerDeleteMyFavorate($k, $path);
            }
            FileMeta::model()->deleteAllByAttributes(array('file_path'=>$meta_value['path'], 'meta_key'=>self::SHARED_META_FLAG));
            $event_uuid = MiniUtil::getEventRandomString(46);
            MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, 6, $this->_file['file_path'], $this->_file['file_path'], $event_uuid);
            $this->_file['event_uuid'] = $event_uuid;
            $this->_file->save();
        } else {
            if (isset($this->_slaves[$this->_userId])) {
                UserFile::model()->deleteAllByAttributes(array('file_path'=>$this->_slaves[$this->_userId], 'file_type'=> 3));
                //
                // 只读共享
                // 如果 type发生改变
                // by kindac
                //
                UserFile::model()->deleteAllByAttributes(array('file_path'=>$this->_slaves[$this->_userId], 'file_type'=> 4));
                FileMeta::model()->deleteAllByAttributes(array('file_path'=>$this->_slaves[$this->_userId], 'meta_key'=>self::SHARED_META_FLAG));
            }
            $event_uuid = MiniUtil::getEventRandomString(46);
            MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, 1, $this->_slaves[$this->_userId], $this->_slaves[$this->_userId], $event_uuid);
            //
            // 删除
            //
            unset($this->_slaves[$this->_userId]);
            $meta_value['slaves'] = $this->_slaves;
            $meta_value['send_msg'] = $this->_send_msg;
            $this->_slaves[$meta_value['master']] = $meta_value['path'];
            $meta_value = serialize($meta_value);
            foreach ($this->_slaves as $k => $v) {
                FileMeta::model()->updateAll(array('meta_value'=>$meta_value), 'meta_key=:meta_key and file_path=:file_path',
                    array(':meta_key'=>self::SHARED_META_FLAG, ':file_path'=>$v));
            }

            $this->handlerDeleteMyFavorate($this->_userId, $path);
            //
            // 取消没有被共享者的共享
            //
            if (count($this->_slaves) <= 1) {
                FileMeta::model()->deleteAllByAttributes(array('file_path'=>$path, 'meta_key'=>self::SHARED_META_FLAG));
                $event_uuid = MiniUtil::getEventRandomString(46);
                MiniEvent::getInstance()->createEvent($this->_master, $this->_deviceId, 6, $path, $path, $event_uuid);
                UserFile::model()->updateAll(array('file_type'=>1, 'event_uuid' => $event_uuid),'file_path=:file_path', array(':file_path'=>$path));
            }
        }
    }

    /**
     *
     * 为已经共享的文件夹添加用户
     *
     * @since 1.0.7
     */
    private function handlerAddUser2Shared ()
    {
        //
        // 检查是否是共享目录
        //
        $this->handlerCheck(2);
        if (empty($this->_slaves) || $this->_slaves == '-1') {
            throw new ApiException("参数错误");
        }
        //
        // 将逗号分割的id组装成数组
        //
        $this->_slaves = array_slice(explode(',', $this->_slaves), 0);
        $this->_types  = array_slice(explode(',', $this->_types), 0);
        $this->_master = $this->_userId;

        $file_meta = FileMeta::model()->findByAttributes(array('file_path'=>$this->_file['file_path'],
            'meta_key'=>self::SHARED_META_FLAG));
        if (empty($file_meta)) {
            throw new ApiException('Not Found');
        }

        $meta_value = unserialize($file_meta['meta_value']);
        if ($this->_master != $meta_value['master']) {
            throw new ApiException('Forbidden');
        }

        $slaves = $meta_value['slaves'];

        //
        // 为新的用户创建文件夹
        //
        $metas = $this->handlerCreateFolder($slaves);
        // 一个用户都没添加
        if (count($metas) == 0 && $this->_send_msg == $meta_value['send_msg']) {
            return true;
        }
        $meta_value['slaves'] = $slaves + $metas;
        $meta_value['send_msg'] = $this->_send_msg;
        $meta_value = serialize($meta_value);
        $slaves[$this->_master] = $this->_file['file_path'];
        //
        // 更新老数据
        //
        foreach ($slaves as $k => $v) {
            FileMeta::model()->updateAll(array('meta_value'=>$meta_value), 'meta_key=:meta_key and file_path=:file_path',
                array(':meta_key'=>self::SHARED_META_FLAG, ':file_path'=>$v));
        }

        //
        // 创建新数据
        //
        foreach ($metas as $k => $v) {
            $meta = new FileMeta();
            $meta['meta_value'] = $meta_value;
            $meta['file_path']  = $v;
            $meta['meta_key']   = self::SHARED_META_FLAG;
            $meta->save();
        }
    }

    /**
     *
     * 预检查是否满足条件
     * @param int $type
     * @throws ApiException
     *
     * @since 1.0.7
     */
    private function handlerCheck ($type = 1)
    {
        $this->_file = UserFile::model()->findByAttributes(array('id'=>$this->_id,'user_id'=>$this->_userId,'is_deleted'=>0));
        if (is_null($this->_file)) {
            throw new ApiException('Not Found');
        }

        //
        // 必须是非共享的文件夹
        //
        if ($this->_file['file_type'] != $type) {
            throw new ApiException('Not Found');
        }
    }

    /**
     *
     * 为共享用户创建文件夹信息
     *
     * @since 1.0.7
     */
    private function handlerCreateFolder($slaves = array(), $types = array()) {
        $metas = array();
        foreach ($this->_slaves as $key=>$slave) {

            // 表示改用户已经被共享
            if (isset($slaves[$slave]) || $this->_master == $slave) {
                continue;
            }

            if (!$this->handlerCheckSlave($slave, $this->_file['file_name'])) {
                $name = $this->handlerRename($this->_file['file_name'], $slave);
                $path = '/' . $slave . '/' . $name;
                $metas[$slave] = $path;
                //
                // 创建文件夹
                //
                $handler = new CreateFolder();
                $handler->_userId = $slave;
                $handler->_deviceId = $this->_deviceId;
                $handler->_action   = 5;

                $file_id = $handler->handleCreateByPath($path);
                UserFile::model()->updateByPk($file_id, array('file_type'=> 3));
            } else {
                $metas[$slave] = $path = '/' . $slave . '/' . $this->_file['file_name'];
            }
        }
        return $metas;
    }

    /**
     *
     * 检查是否重复创建
     * @param int $slave
     * @param array $update_meta
     *
     * @since 1.0.7
     */
    private function handlerCheckSlave($slave, $name) {
        $path = '/' . $slave . '/' . $name;
        $check = UserFile::model()->findByAttributes(array('file_path'=>$path));
        if (!$check) {
            return false;
        }
        //
        // 如果不是被共享的，返回重命名
        //
        if ($check['file_type'] < 3) {
            return false;
        }

        $check_meta = FileMeta::model()->findByAttributes(array('file_path'=>$path, 'meta_key'=>self::SHARED_META_FLAG));
        if (!$check_meta) {
            return false;
        }
        $meta_value = unserialize($check_meta['meta_value']);
        //
        // 检查是否相同
        //
        if ($this->_master == $meta_value['master'] && $this->_file['file_path'] == $meta_value['path']) {
            $this->_update_meta[$slave] = $check_meta;
            return true;
        }
        return false;
    }
    /**
     *
     * 重命名重名的文件夹
     * @param string $name
     * @param int $user_id
     * @param int $parent
     *
     * @since 1.0.7
     */
    private function handlerRename($name, $user_id, $parent = 0) {
        $file = UserFile::model()->findByAttributes(array('file_name'=>$name, 'parent_file_id'=>$parent,
            'user_id'=>$user_id));
        if (!$file) {
            return $name;
        }

        $files =  UserFile::model()->getByParentID($user_id, $parent);
        $names = array ();
        foreach ( $files as $k => $v ) {
            $names[strtolower($v["file_name"])] = $v["file_name"];
        }
        $retval = $name . '(' . $this->_userNick . '的共享)';
        $tmp_name = strtolower($retval);
        $index = 1;
        while (isset($names[$tmp_name])) {
            $retval = $name . '-'.$index.'(' . $this->_userNick . '的共享)';
            $tmp_name = strtolower($retval);
            $index += 1;
            if ($index > 10000) {
                break;
            }
        }

        return $retval;
    }

    /**
     *
     * 删除我的最爱
     * @param int $slave
     * @param string $path
     */
    private function handlerDeleteMyFavorate($slave, $path) {
        $conditions = 'user_id = :user_id and file_id in ( SELECT id FROM '. UserFile::model()->tableName().' WHERE file_path like :file_path)';
        FileStar::model()->deleteAll($conditions, array(':user_id'=>$slave, ':file_path'=> $path.'/%'));
    }

    /**
     *
     * 列举共享信息
     */
    public function handlerListDetails() {
        $this->_slaves = array();
        $this->_file = UserFile::model()->findByAttributes(array('id'=>$this->_id,'user_id'=>$this->_userId,'is_deleted'=>0));
        if (is_null($this->_file)) {
            throw new ApiException('Not Found');
        }

        if ($this->_file['file_type'] != 2) {
            throw new ApiException('Not Found');
        }

        $meta = FileMeta::model()->findByAttributes(array('file_path' => $this->_file['file_path'], 'meta_key' => self::SHARED_META_FLAG));
        if (!$meta) {
            throw new ApiException('Not Found');
        }

        $meta_value = unserialize($meta['meta_value']);
        if ($meta_value['master'] != $this->_userId) {
            throw new ApiException('Not Found');
        }

        $slaves = $meta_value['slaves'];
        $send  = $meta_value['send_msg'];
        foreach ($slaves as $key => $value) {
            $user = User::model()->findByPk($key);
            if (!$user) {
                continue;
            }

            $mate = UserMeta::model()->findByAttributes(
                array("user_id" => $user->id, "meta_key" => "nick"));
            if($mate){
                $value = $mate->meta_value;
                if(!empty($value) && strlen(trim($value))>0){
                    $nick  = $value;
                }
            } else {
                $nick = $user['user_name'];
            }
            $shared_file = UserFile::model()->findByAttributes(array('user_id'=>$key,'file_path'=>$value,'is_deleted'=>0));
            $path = $this->_file['file_path'];
            $permission = Yii::app()->privilege->checkPrivilegeUser($key, $path);
            array_push($this->_slaves, array('user_id'=>$key, 'user_name'=>$user['user_name'], 'nick'=>$nick, 'type'=>$shared_file['file_type'],'permission'=>$permission,'send'=>$send));
        }
    }

    /**
     *
     * 发生重命名的时候，更新meta信息
     */
    private function handlerRenameShared() {
        $meta = FileMeta::model()->findByAttributes(array('file_path' => $this->_from, 'meta_key'=>self::SHARED_META_FLAG));
        if (!$meta) {
            return false;
        }
        $meta_value = unserialize($meta['meta_value']);
        $updates = $meta_value['slaves'];

        //添加需要修改的权限用户的列表
        $this->modifyShareUsers = array_keys($updates);

        if ($meta_value['master'] == $this->_userId) {
            $meta_value['path'] = $this->_to;
            $updates[$this->_userId] = $this->_to;
        } else {
            $meta_value['slaves'][$this->_userId] = $this->_to;
            $updates[$meta_value['master']] = $meta_value['path'];
        }
        $meta_value['send_msg'] = $this->_send_msg;
        $meta_value = serialize($meta_value);
        FileMeta::model()->updateAll(array('meta_value'=>$meta_value,'file_path' => $this->_to), 'meta_key=:meta_key and file_path=:file_path',
            array(':meta_key'=>self::SHARED_META_FLAG, ':file_path'=>$this->_from));

        foreach ($updates as $v) {
            FileMeta::model()->updateAll(array('meta_value'=>$meta_value), 'meta_key=:meta_key and file_path=:file_path',
                array(':meta_key'=>self::SHARED_META_FLAG, ':file_path'=>$v));
        }

        return true;
    }

    /**
     *
     * 输出结果，退出进程
     */
    public function handleEnd() {
        echo CJSON::encode ( $this->result );
        Yii::app ()->end ();
    }

    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException($exception) {
        echo CJSON::encode ( $this->result );
    }
}