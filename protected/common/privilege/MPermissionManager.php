<?php
/**
 * 操作权限 
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MPermissionManager extends CApiComponent
{
    public $_master;         // 发起者用户id
    public $_slaves;         // 拥有直接权限的用户
    public $_permissions;    // 逗号分割的共享属性 权限
    public $_id;             // 目录id
    public $_from;
    public $_to;

    public $_file;          // 文件信息
    private $_update_meta = array();

    const LIST_DETAILS    = 1; // 获取用户权限列表
    const SET_PERMISSION  = 2; // 设置用户权限

    /**
     *
     * 构造函数，初始化一些参数
     */
    public function __construct() {
        parent::init ();
        $this->result = array ();
        $this->result["state"] = false;
        $this->result["code"]  = 0;
        $this->result["msg"] = Yii::t('front_common', 'common_permission_operate_fail');
        $this->result['dir_pickcode'] = array();
        $this->result['data'] = array("d"=>1);
    }

    /**
     *
     * 公共目录入口
     * @param int $action
     *
     * @since 1.0.7
     */
    public function invoke ($action = 0)
    {
        $this->result["msg"] = Yii::t('front_common', 'common_permission_operate_success');

        switch ($action) {
            case self::LIST_DETAILS:
                $this->handlerListDetails();
                break;
            case self::SET_PERMISSION:
                $this->handlerSetPermission();
                $this->result["msg"] = Yii::t('front_common', 'common_permission_setting_privilege');
                break;
            default:
                break;
        }
        $this->result["state"] = true;

    }

    /**
     *
     * 列举共享信息
     *
     * @since 1.1.0
     */
    public function handlerListDetails() {
        $this->_slaves = array();
        $this->_file = UserFile::model()->findByAttributes(array('id'=>$this->_id, 'is_deleted'=>0));
        if (is_null($this->_file)) {
            throw new ApiException('Not Found');
        }

        //判断此用户是否有 分配 权限
        $file_path = $this->_file["file_path"];
        $hasPermissionAllot = Yii::app()->privilege->hasPermission($file_path, MPrivilege::PERMISSION_GRANT);
        if (!$hasPermissionAllot){
            $this->result["msg"] = Yii::t('front_common', 'Can not grant permission');
            throw new ApiException('Can not grant permission');
        }
        //获取此文件的拥有者
        $master    = CUtils::getUserFromPath($file_path);

        $privileges = MUserPrivilege::model()->findAll('file_path=:file_path', array(':file_path'=>$this->_file['file_path']));
        foreach ($privileges as $pri) {
            //文件属于拥有者则不能进行权限分配
            if ($pri["user_id"] == $master){
                continue;
            }
            //文件属于自己是不进行权限分配
            $currentUser = MUserManager::getInstance()->getCurrentUser();
            if ($pri["user_id"] == $currentUser["id"]){
                continue;
            }
            $user = User::model()->findByPk($pri["user_id"]);
            if (!$user) {
                continue;
            }
            $mate = UserMeta::model()->findByAttributes(array("user_id" => $user->id, "meta_key" => "nick"));
            if($mate){
                $nick = $mate->meta_value;
            } else {
                $nick = $user['user_name'];
            }
            //没有设置分配权限则默认为不分配
            $permission = unserialize($pri["permission"]);
            if (!isset($permission[MPrivilege::PERMISSION_GRANT])){
                $permission[MPrivilege::PERMISSION_GRANT] = 0;
            }
            array_push($this->_slaves, array('user_id'=>$pri["user_id"],'user_name'=>$user['user_name'],'nick'=>$nick,'permission'=>$permission));
        }
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
     *
     * 列举共享信息
     *
     * @since 1.1.0
     */
    public function handlerSetPermission() {
        $this->_file = UserFile::model()->findByAttributes(array('id'=>$this->_id,'is_deleted'=>0));
        if (is_null($this->_file)) {
            throw new ApiException('Not Found');
        }
        //判断此用户是否有 分配 权限
        $file_path = $this->_file["file_path"];
        $hasPermissionAllot = Yii::app()->privilege->hasPermission($file_path, MPrivilege::PERMISSION_GRANT);
        if (!$hasPermissionAllot){
            $this->result["msg"] = Yii::t('front_common', 'Can not grant permission');
            throw new ApiException('Can not grant permission');
        }

        //
        // 将逗号分割的id组装成数组
        //
        if (!empty($this->_slaves)){
            $this->_slaves = array_slice(explode(',', $this->_slaves), 0);
        } else {
            $this->_slaves = array();
        }

        $this->_permissions  = array_slice(explode(',', $this->_permissions), 0);

        $tmpUser = $this->_slaves;

        //查询出此路径下的直接权限的所有用户
        $privileges = MUserPrivilege::model()->findAll('file_path=:file_path', array(':file_path'=>$this->_file["file_path"]));
        $currentUser = MUserManager::getInstance()->getCurrentUser();
        foreach ($privileges as $pri) {
            //分配权限时如果权限表中的权限的所有者是自己， 则不进行处理
            if ($pri["user_id"] == $currentUser["id"]){
                continue;
            }
            //如果此用户不存在则进行添加权限，已经存在则进行修改权限，否则进行删除权限操作
            if (in_array($pri["user_id"], $tmpUser)){
                $newPermission = $this->getUserPermission($this->_slaves, $pri["user_id"]);
                //进行是否事件生成判断
                $this->updatePrivelegeEvent($pri, $newPermission);
                //更新权限
                Yii::app()->privilege->updatedPrivilege($pri["user_id"], $this->_file["file_path"], $newPermission);
                $tmpUser = CUtils::arrayRemove($tmpUser, $pri["user_id"]);
            } else {
                $pri->delete();
                $this->deletePrivelegeEvent($pri["user_id"], $pri["file_path"], unserialize($pri["permission"]));
            }
        }

        //创建权限
        foreach ($tmpUser as $index=>$userId) {
            $permission = $this->getUserPermission($this->_slaves, $userId);
            $this->createPrivelegeEvent($userId, $this->_file["file_path"], $permission);
            Yii::app()->privilege->createPrivilege($userId, $this->_file["file_path"], $permission);
        }
    }

    /**
     * 
     * 删除用户权限时的处理逻辑
     * @param unknown_type $oldPri
     * @param unknown_type $newPermission
     * 
     * @since 1.0.7
     */
    public function deletePrivelegeEvent($user_id, $file_path, $permission){
        //如果是自己则排除
        $own_user_id = CUtils::getUserFromPath($file_path);
        if ($own_user_id == $user_id){
            return;
        }

        //判断删除权限后用户是否还对此文件有读取权限
        $hasRead = Yii::app()->privilege->hasPermissionUser($user_id, $file_path, MPrivilege::RESOURCE_READ);
        $file_path = '/'.$user_id . CUtils::removeUserFromPath($file_path);
        $content   = $file_path;
        if ($permission[MPrivilege::RESOURCE_READ] && !$hasRead) { //原来有读权限，现在没有了  --- 创建删除事件
            $this->createEvent($user_id, MConst::CAN_NOT_READ, $file_path, $content);
        } elseif (!$permission[MPrivilege::RESOURCE_READ] && $hasRead){ //原来没有读权限，现在有了  --- 创建读取事件
            $this->createEvent($user_id, MConst::CAN_READ, $file_path, $content);
        }
    }
    

    /**
     *
     * 添加事件
     * @param object $oldPri
     * @param object $newPri
     *
     * @since 1.0.7
     */
    public function updatePrivelegeEvent($oldPri, $newPermission){
        $user_id   = $oldPri["user_id"];
        //如果是自己则排除
        $own_user_id = CUtils::getUserFromPath($oldPri["file_path"]);
        if ($own_user_id == $user_id){
            return;
        }

        $oldPermission = unserialize($oldPri["permission"]);
        $file_path = '/'.$user_id.CUtils::removeUserFromPath($oldPri["file_path"]);
        $content   = $file_path;
        if ($oldPermission[MPrivilege::RESOURCE_READ] && !$newPermission[MPrivilege::RESOURCE_READ]){  //从有权限到没有权限
            $this->createEvent($user_id, MConst::CAN_NOT_READ, $file_path, $content);
        } elseif (!$oldPermission[MPrivilege::RESOURCE_READ] && $newPermission[MPrivilege::RESOURCE_READ]) { //从没有权限到有权限
            $this->createEvent($user_id, MConst::CAN_READ, $file_path, $content);
        }
    }

    /**
     *
     * 添加事件
     *
     * @since 1.0.7
     */
    public function createPrivelegeEvent($user_id, $file_path, $permission){
        //如果是自己则排除
        $own_user_id = CUtils::getUserFromPath($file_path);
        if ($own_user_id == $user_id){
            return;
        }

        //默认的权限
        $defaultPermission = Yii::app()->privilege->getFilePrivilegeDefault($file_path);

        $file_path = '/'.$user_id . CUtils::removeUserFromPath($file_path);
        $content   = $file_path;
        if ($permission[MPrivilege::RESOURCE_READ] && !$defaultPermission[MPrivilege::RESOURCE_READ]){  //当默认权限为不能读，现在变更为能读
            $this->createEvent($user_id, MConst::CAN_READ, $file_path, $content);
        } elseif (!$permission[MPrivilege::RESOURCE_READ] && $defaultPermission[MPrivilege::RESOURCE_READ]) { //当默认权限为能读  现在变更为不能读
            $this->createEvent($user_id, MConst::CAN_NOT_READ, $file_path, $content);
        }
    }

    /**
     *
     * 创建权限
     *
     * @since 1.0.7
     */
    public function createEvent($userId, $action, $file_path, $content){
        $device = UserDevice::model()->findByUserIdAndType($userId, 1);
        $deviceId = $device["id"];
        $event_uuid = MiniUtil::getEventRandomString(46);
        MiniEvent::getInstance()->createEvent($userId, $deviceId, $action, $file_path, $content, $event_uuid);
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