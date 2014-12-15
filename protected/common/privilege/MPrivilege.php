<?php
/**
 * 权限控制
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MPrivilege extends CApplicationComponent
{
    //定义权限的常量
    const RESOURCE_READ    = "resource.read";
    const FOLDER_CREATE    = "folder.create";
    const FILE_CREATE      = "file.create";
    const FILE_MODIFY      = "file.modify";
    const FOLDER_RENAME    = "folder.rename";
    const FILE_RENAME      = "file.rename";
    const FOLDER_DELETE    = "folder.delete";
    const FILE_DELETE      = "file.delete";
    const PERMISSION_GRANT = "permission.grant";

    //用户所拥有的所有权限
    public $allPrivileges;

    //用户所拥有的所有权限
    public $privileges = array();
    //用户对具体文件的权限
    public $permission = array();
    //用户id
    public $user_id;
    //检查权限的路径
    public $file_path;
    //操作对应的名称
    public $operateName;
    //缓存用户所拥有的所有权限
    public $userPrivilegeCache = array();

    public function init()
    {
        $currentUser = MUserManager::getInstance()->getCurrentUser();
        $user_id = $currentUser["id"];
        $this->user_id = $user_id;
    }

    /**
     *
     * 默认系统权限
     *
     * @since 1.1.0
     */
    public function getDefaultPermission(){
        $dPermission = Yii::app()->params["app"]["permission"];
        $permission = array(
        self::RESOURCE_READ      => $dPermission[MPrivilege::RESOURCE_READ],
        self::FOLDER_CREATE      => $dPermission[MPrivilege::FOLDER_CREATE],
        self::FOLDER_RENAME      => $dPermission[MPrivilege::FOLDER_RENAME],
        self::FOLDER_DELETE      => $dPermission[MPrivilege::FOLDER_DELETE],
        self::FILE_CREATE        => $dPermission[MPrivilege::FILE_CREATE],
        self::FILE_MODIFY        => $dPermission[MPrivilege::FILE_MODIFY],
        self::FILE_RENAME        => $dPermission[MPrivilege::FILE_RENAME],
        self::FILE_DELETE        => $dPermission[MPrivilege::FILE_DELETE],
        self::PERMISSION_GRANT   => isset($dPermission[MPrivilege::PERMISSION_GRANT])?$dPermission[MPrivilege::PERMISSION_GRANT]:0,
        );
        return $permission;
    }

    /**
     *
     * 默认系统权限
     *
     * @since 1.1.0
     */
    public function hasAllPermission(){
        $permission = array(
        self::RESOURCE_READ    => true,
        self::FOLDER_CREATE    => true,
        self::FOLDER_RENAME    => true,
        self::FOLDER_DELETE    => true,
        self::FILE_CREATE      => true,
        self::FILE_MODIFY      => true,
        self::FILE_RENAME      => true,
        self::FILE_DELETE      => true,
        self::PERMISSION_GRANT => true
        );
        return $permission;
    }

    /**
     *
     * 默认系统权限
     *
     * @since 1.1.0
     */
    public function generatePermission($resource_read, $folder_create, $folder_rename, $folder_delete, $file_create, $file_modify, $file_rename, $file_delete, $permission_allow = 0 ){
        $permission = array(
        self::RESOURCE_READ    => $resource_read,
        self::FOLDER_CREATE    => $folder_create,
        self::FOLDER_RENAME    => $folder_rename,
        self::FOLDER_DELETE    => $folder_delete,
        self::FILE_CREATE      => $file_create,
        self::FILE_MODIFY      => $file_modify,
        self::FILE_RENAME      => $file_rename,
        self::FILE_DELETE      => $file_delete,
        self::PERMISSION_GRANT => $permission_allow
        );
        return $permission;
    }

    /**
     *
     * 获取操作名字
     * @param string $type
     *
     * @since 1.0.7
     */
    public function getOperateName($type){
        $operateName = "";
        switch ($type) {
            case self::RESOURCE_READ:
                $operateName = Yii::t('api_message', 'resource.read');
                break;
            case self::FOLDER_CREATE:
                $operateName = Yii::t('api_message', 'folder.create');
                break;
            case self::FOLDER_RENAME:
                $operateName = Yii::t('api_message', 'folder.rename');
                break;
            case self::FOLDER_DELETE:
                $operateName = Yii::t('api_message', 'folder.delete');
                break;
            case self::FILE_CREATE:
                $operateName = Yii::t('api_message', 'file.create');
                break;
            case self::FILE_MODIFY:
                $operateName = Yii::t('api_message', 'file.modify');
                break;
            case self::FILE_RENAME:
                $operateName = Yii::t('api_message', 'file.rename');
                break;
            case self::FILE_DELETE:
                $operateName = Yii::t('api_message', 'file.delete');
                break;
            default:
                ;
                break;
        }
        return $operateName;
    }

    /**
     *
     * 获取指定用户所拥有的所有权限
     *
     * @since 1.0.7
     */
    public function getUserAllPrivilege($user_id){
        $privileges = UserPrivilege::model()->findAll(array(
                 'condition' => 'user_id=:user_id',
                 'params'    => array(':user_id'=>$user_id),
                 'order'     => "file_path desc",
        ));
        //查询用户所拥有权权限时添加权限
        $privileges = apply_filters("add_privilege", $privileges, $user_id);
        return $privileges;
    }

    /**
     *
     * 获取用户所拥有的所有权限
     *
     * @since 1.0.7
     */
    public function getAllPrivilege(){
        return $this->getUserAllPrivilege($this->user_id);
    }

    /**
     *
     * 判断文件针对用户是否具有权限
     *
     * @since 1.0.7
     */
    public function checkPrivilegeListRead($user_id, $files){
        if (empty($this->allPrivileges)){
            $this->allPrivileges = $this->getUserAllPrivilege($user_id);
        }
        $privileges = $this->allPrivileges;

        $hasPermission = array();
        foreach ($files as $file){
            if ($file["user_id"] == $user_id){
                $hasPermission[] = $file;
                continue;
            }
            $permission = $this->checkReadPrivilege($file["file_path"], $privileges);
            if ($permission) {
                $hasPermission[] = $file;
            }
        }
        return $hasPermission;
    }

    /**
     *
     * 判断用户是否具有读权限
     *
     * @since 1.0.7
     */
    public function checkReadPrivilege($file_path, $privileges){
        //查询出离所选择文件最近的权限
        $permission = false;
        $hasChildren = false;
        $file_path = $file_path."/";
        foreach ($privileges as $privi){
            $priPath     = $privi["file_path"]."/";
            // $priPath是传入的$file_path的父目录
            $result = strpos($file_path, $priPath);
            if ($result === 0){
                $permission = unserialize($privi["permission"]);
                break;
            } else {
                //进行子目录的判断。如果子目录是有可读权限， 则此目录自动具有只读权限，以方便list列表显示
                // $priPath是传入的$file_path的子目录
                $childResult = strpos($priPath, $file_path);
                if ($childResult === 0){
                    $pri = unserialize($privi["permission"]);
                    if (!$pri[MPrivilege::RESOURCE_READ]){    //如果没有权限则继续进行查找
                        continue;
                    }
                    $permission = $this->generatePermission(1, 0, 0, 0, 0, 0, 0, 0);
                    $hasChildren = true;
                }
            }
        }
        //如果某文件夹存在可读子目录
        if ($hasChildren){
            return true;
        }
        // 系统中未设置权限，使用默认权限
        if ($permission === false){
            $permission = $this->getFilePrivilegeDefault($file_path);
        }
        //有读取权限
        if ($permission && $permission[MPrivilege::RESOURCE_READ]){
            return true;
        }
        return false;
    }

    /**
     *
     * 根据文件路径创建文件的权限
     *
     * @since 1.0.7
     */
    public function createPrivilege($user_id, $file_path, $permission)
    {
        $privilege = UserPrivilege::model()->find('user_id=:user_id and file_path=:file_path', array(':user_id'=>$user_id,':file_path'=>$file_path));
        if (empty($privilege)){
            $privilege = new MUserPrivilege();
        }
        $permission = serialize($permission);
        if ($privilege["permission"] != $permission){
            $privilege->user_id    = $user_id;
            $privilege->file_path  = $file_path;
            $privilege->permission = $permission;
            $privilege->save();
        }
        return true;
    }

    /**
     *
     * 根据文件路径创建文件的权限
     *
     * @since 1.0.7
     */
    public function updatedPrivilege($user_id, $file_path, $permission)
    {
        $privilege =UserPrivilege::model()->find('user_id=:user_id and file_path=:file_path', array(':user_id'=>$user_id,':file_path'=>$file_path));
        if (empty($privilege)){
            return false;
        }
        $permission = serialize($permission);
        if ($privilege["permission"] != $permission){
            $privilege->permission = $permission;
            $privilege->save();
        }
        return true;
    }

    /**
     *
     * 修改用户权限的路径
     *
     * @since 1.0.7
     */
    public function updatedFilePath($user_id, $file_path, $new_file_path)
    {
        $privilege = UserPrivilege::model()->find('user_id=:user_id and file_path=:file_path', array(':user_id'=>$user_id,':file_path'=>$file_path));
        if (empty($privilege)){
            return false;
        }
        $privilege->file_path = $new_file_path;
        $privilege->save();
        return true;
    }

    /**
     *
     * 修改用户权限的路径
     *
     * @since 1.0.7
     */
    public function updatedUserAllFilePath($user_id, $file_path, $new_file_path)
    {
        $privileges =UserPrivilege::model()->findAll('user_id=:user_id and file_path like :file_path', array(':user_id'=>$user_id,':file_path'=>$file_path.'%'));
        if (empty($privileges)){
            return false;
        }
        //替换所有的path
        foreach ($privileges as $privilege){
            $privilege->file_path = CUtils::str_replace_once($file_path, $new_file_path, $privilege->file_path);
            $privilege->save();
        }
        return true;
    }

    /**
     *
     * 修改用户权限的路径
     *
     * @since 1.0.7
     */
    public function updatedAllFilePath($file_path, $new_file_path)
    {
        $privileges =UserPrivilege::model()->findAll('file_path like :file_path', array(':file_path'=>$file_path.'%'));
        if (empty($privileges)){
            return false;
        }
        //替换所有的path
        foreach ($privileges as $privilege){
            $privilege->file_path = CUtils::str_replace_once($file_path, $new_file_path, $privilege->file_path);
            $privilege->save();
        }
        return true;
    }

    /**
     *
     * 删除指定用户的所有权限
     *
     * @since 1.0.7
     */
    public function deleteUserPrivilege($user_id)
    {
        UserPrivilege::model()->deleteAll('user_id=:user_id', array(':user_id'=>$user_id));
    }

    /**
     *
     * 根据文件路径删除文件的权限
     *
     * @since 1.0.7
     */
    public function deletePrivilege($user_id, $file_path)
    {
        UserPrivilege::model()->deleteAll('user_id=:user_id and file_path=:file_path', array(':user_id'=>$user_id,':file_path'=>$file_path));
    }

    /**
     *
     * 根据文件路径删除用户的所有文件的权限
     *
     * @since 1.0.7
     */
    public function deleteAllUserPrivilege($user_id, $file_path)
    {
        UserPrivilege::model()->deleteAll('user_id=:user_id and file_path like :file_path', array(':user_id'=>$user_id,':file_path'=>$file_path.'%'));
    }

    /**
     *
     * 根据文件路径删除所有用户的文件的权限
     *
     * @since 1.0.7
     */
    public function deleteAllPrivilege($file_path)
    {
        UserPrivilege::model()->deleteAll('file_path like :file_path', array(':file_path'=>$file_path.'%'));
    }

    /**
     *
     * 根据path检查文件操作权限
     *
     * example
     * 1. 文件path： /public
     *    权限path：/public
     * 2. 文件path： /public/ccc
     *    权限path：/public
     * 3. 文件path： /ccccc
     *    权限path：/public
     *
     *    @since 1.0.7
     */
    public function checkPrivilege($file_path)
    {
        return $this->checkPrivilegeUser($this->user_id, $file_path);
    }

    /**
     *
     * 获取一个用户对一个path的权限
     * @param unknown_type $user_id
     * @param unknown_type $file_path
     *
     * @since 1.0.7
     */
    public function checkPrivilegeUser($user_id, $file_path){
        $this->file_path = $file_path;

        //判断文件是否是属于自己的,如果是自己的则拥有所有权限
        $owner_user_id = CUtils::getUserFromPath($file_path);
        if ($owner_user_id == $user_id){
            return $this->hasAllPermission();
        }

        //检测用户是否有权限
        $permission = $this->checkPrivilegeUserSelf( $user_id, $file_path);
        if ($permission){
            $this->permission = $permission;
            return $permission;
        }

        //添加hook为用户增加额外的文件的权限
        $curPrivileges = array("file_path"=>$file_path,"permission"=>$permission);
        $retPri = apply_filters("add_privilege_after_user", $curPrivileges);
        $permission = $retPri["permission"];
        if ($permission){
            $this->permission = $permission;
            return $permission;
        }

        //如果检测不到用户的权限,则获取默认权限
        $permission = $this->getFilePrivilegeDefault($file_path);

        $this->permission = $permission;
        return $permission;
    }

    /**
     *
     * 1. 查询用户权限进行按照路径倒序排序， 以最底层目录权限为准
     * 2. 最底层目录有读权限，则所有父目录自动拥有读权限(单一，只有直接父目录具有)
     * @param object $user_id
     * @param string $file_path
     *
     * @since 1.0.7
     */
    public function checkPrivilegeUserSelf($user_id, $file_path){
        //从单例缓存中获取用户的权限
        if (key_exists($user_id, $this->userPrivilegeCache)){
            $privileges = $this->userPrivilegeCache[$user_id];
        } else {
            $privileges = UserPrivilege::model()->findAll(array(
                 'condition' => 'user_id=:user_id',
                 'params'    => array(':user_id'=>$user_id),
                 'order'     => "file_path desc",
            ));
            $this->userPrivilegeCache[$user_id] = $privileges;
        }

        //查询出离所选择文件最近的权限
        $permission = false;
        $file_path = $file_path."/";
        foreach ($privileges as $privi){
            $priPath     = $privi["file_path"]."/";
            // $priPath是传入的$file_path的父目录
            $result = strpos($file_path, $priPath);
            if ($result === 0){
                $permission = unserialize($privi["permission"]);
                break;
            } else {
                //进行只目录的判断。如果子目录是有可读权限， 则此目录自动具有只读权限，以方便list列表显示
                // $priPath是传入的$file_path的子目录
                $childResult = strpos($priPath, $file_path);
                if ($childResult === 0){
                    $pri = unserialize($privi["permission"]);
                    if (!$pri[MPrivilege::RESOURCE_READ]){    //如果没有权限则继续进行查找
                        continue;
                    }
                    $permission = $this->generatePermission(1, 0, 0, 0, 0, 0, 0, 0);
                }
            }
        }
        return $permission;
    }

    /**
     *
     * 根据file对象检查用户权限
     *
     * @since 1.0.7
     */
    public function checkPrivilegeObj($file)
    {
        $file_path = $file["file_path"];
        return $this->checkPrivilege($file_path);
    }

    /**
     *
     * 判断路径是否存在指定的权限
     * @param string $filePath
     * @param string $permission
     *
     * @since 1.0.7
     */
    public function hasPermission($file_path, $permission){
        $this->operateName = $this->getOperateName($permission);
        $permissions = $this->checkPrivilege($file_path);
        if (!array_key_exists($permission, $permissions)) {
            return false;
        }

        //删除文件夹时需要考虑子目录是否有删除权限
        if ($permission == MPrivilege::FOLDER_DELETE){
            if (!$permissions[$permission]){
                return false;
            }

            //检查子目录是否有文件或者文件夹不能删除的权限
            $currentUser = MUserManager::getInstance()->getCurrentUser();
            $user_id = $currentUser["id"];
            if (!$this->isDeleteChildFolder($user_id, $file_path)){
                return false;
            }
        }

        return $permissions[$permission];
    }

    /**
     *
     * 判断路径是否存在指定的权限
     * @param string $filePath
     * @param string $permission
     *
     * @since 1.0.7
     */
    public function hasPermissionUser($user_id, $file_path, $permission){
        $this->operateName = $this->getOperateName($permission);
        $permissions = $this->checkPrivilegeUser($user_id, $file_path);
        if (!array_key_exists($permission, $permissions)) {
            return false;
        }

        //删除文件夹时需要考虑子目录是否有删除权限
        if ($permission == MPrivilege::FOLDER_DELETE){
            if (!$permissions[$permission]){
                return false;
            }

            //检查子目录是否有文件或者文件夹不能删除的权限
            if (!$this->isDeleteChildFolder($user_id, $file_path)){
                return false;
            }
        }

        return $permissions[$permission];
    }

    /**
     *
     * 根据文件路径, 判断路径是否共享，且存在指定权限
     * @param string $filePath
     * @param string $permission
     *
     * @since 1.1.2
     */
    public function hasSharePermissionUser($user_id, $file_path, $permission){
        //判断此文件是否属于共享文件
        $share_filter = MSharesFilter::init();
        if ($share_filter->handlerCheck($user_id, $file_path)) {
            $permissions = $this->hasPermissionUser($user_id, $file_path, $permission);
            if ($permissions){
                return $permissions[$permission];
            }
        }
        return false;
    }

    /**
     *
     * 根据文件对象， 判断路径是否共享，且存在指定权限
     * @param string $userId
     * @param string $file
     * @param string $permission
     * @return bool
     * @since 1.1.2
     */
    public function hasShareFilePermissionUser($userId, $file, $permission){
        //判断此文件是否属于共享文件
        $shareFilter = MSharesFilter::init();
        if ($shareFilter->handlerCheckByFile($userId, $file)){
            $permissions = $this->hasPermissionUser($userId, $file["file_path"], $permission);
            if ($permissions){
                return true;
            }
        }
        return false;
    }

    /**
     *
     * 是否具有分配权限的能力
     *
     * @since 1.1.0
     */
    public function hasPermissionAllot(){
        $this->operateName = $this->getOperateName(self::PERMISSION_GRANT);
        if (array_key_exists(self::PERMISSION_GRANT, $this->permission)){
            return $this->permission[self::PERMISSION_GRANT];
        }
        return false;
    }

    /**
     *
     * 是否具有读功能
     *
     * @since 1.0.7
     */
    public function hasResourceRead(){
        $this->operateName = $this->getOperateName(self::RESOURCE_READ);
        if (array_key_exists(self::RESOURCE_READ, $this->permission)){
            return $this->permission[self::RESOURCE_READ];
        }
        return false;
    }

    /**
     *
     * 是否具有创建文件夹功能
     *
     * @since 1.0.7
     */
    public function hasFolderCreate(){
        $this->operateName = $this->getOperateName(self::FOLDER_CREATE);
        if (array_key_exists(self::FOLDER_CREATE, $this->permission)){
            if (!$this->permission[self::RESOURCE_READ]){
                return false;
            }
            return $this->permission[self::FOLDER_CREATE];
        }
        return false;
    }

    /**
     *
     * 是否具有创建文件功能
     *
     * @since 1.0.7
     */
    public function hasFileCreate(){
        $this->operateName = $this->getOperateName(self::FILE_CREATE);
        if (array_key_exists(self::FILE_CREATE, $this->permission)){
            if (!$this->permission[self::RESOURCE_READ]){
                return false;
            }
            return $this->permission[self::FILE_CREATE];
        }
        return false;
    }

    /**
     *
     * 是否具有创建文件功能
     *
     * @since 1.0.7
     */
    public function hasFileModify(){
        $this->operateName = $this->getOperateName(self::FILE_MODIFY);
        if (array_key_exists(self::FILE_MODIFY, $this->permission)){
            if (!$this->permission[self::RESOURCE_READ]){
                return false;
            }
            return $this->permission[self::FILE_MODIFY];
        }
        return false;
    }

    /**
     *
     * 是否具有重命名文件夹功能
     *
     * @since 1.0.7
     */
    public function hasFolderRename(){
        $this->operateName = $this->getOperateName(self::FOLDER_RENAME);
        if (array_key_exists(self::FOLDER_RENAME, $this->permission)){
            if (!$this->permission[self::RESOURCE_READ]){
                return false;
            }
            return $this->permission[self::FOLDER_RENAME];
        }
        return false;
    }

    /**
     *
     * 是否具有修改文件功能
     *
     * @since 1.0.7
     */
    public function hasFileRename(){
        $this->operateName = $this->getOperateName(self::FILE_RENAME);
        if (array_key_exists(self::FILE_RENAME, $this->permission)){
            if (!$this->permission[self::RESOURCE_READ]){
                return false;
            }
            return $this->permission[self::FILE_RENAME];
        }
        return false;
    }

    /**
     *
     * 是否具有删除文件夹功能
     *
     * @since 1.0.7
     */
    public function hasFolderDelete(){
        $this->operateName = $this->getOperateName(self::FOLDER_DELETE);
        if (array_key_exists(self::FOLDER_DELETE, $this->permission)){
            if (!$this->permission[self::RESOURCE_READ]){
                return false;
            }

            if (!$permissions[$permission]){
                return false;
            }

            //检查子目录是否有文件或者文件夹不能删除的权限
            $currentUser = MUserManager::getInstance()->getCurrentUser();
            $user_id = $currentUser["id"];
            if (!$this->isDeleteChildFolder($user_id, $this->file_path)){
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     *
     * 检测子目录是否有删除的权限
     *
     * @since 1.0.7
     */
    private function isDeleteChildFolder($user_id, $file_path){
        $privileges =UserPrivilege::model()->findAll('user_id=:user_id and file_path like :file_path', array(':user_id'=>$user_id,':file_path'=>$file_path.'%'));
        foreach ($privileges as $privilege){
            $path = $privilege["file_path"];
            if ($path == $file_path){
                continue;
            }
            $permission = unserialize($privilege["permission"]);
            if (!$permission[MPrivilege::FOLDER_DELETE] || !$permission[MPrivilege::FILE_DELETE]){
                return false;
            }
        }
        return true;
    }

    /**
     *
     * 是否具有删除文件功能
     *
     * @since 1.0.7
     */
    public function isFileDelete(){
        $this->operateName = $this->getOperateName(self::FILE_DELETE);
        if (array_key_exists(self::FILE_DELETE, $this->permission)){
            if (!$this->permission[self::RESOURCE_READ]){
                return false;
            }
            return $this->permission[self::FILE_DELETE];
        }
        return false;
    }

    /**
     *
     * 初始化用户对文件所有的权限
     *
     * @since 1.0.7
     */
    private function initUserPrivilege()
    {
        //直接从数据库中查询出基于用户的权限
        $pri = new MUserPrivilegeDB();
        $privilege = $pri->getPirilege();
        return $privilege;
    }


    /**
     *
     * 获得文件自身默认的权限
     *
     * @since 1.0.7
     */
    public function getFilePrivilege($file_path)
    {
        $meta = false;
        $count = substr_count($file_path, "/");
        while ($count > 1) {
            $meta = FileMeta::model()->find('file_path=:file_path and meta_key=:permission', array(':file_path'=>$file_path,':permission'=>'permission'));
            if (!empty($meta)){
                break;
            } else {
                $fileInfo = pathinfo($file_path);
                $file_path = $fileInfo["dirname"];
                $count = substr_count($file_path, "/");
            }
        }

        if (!$meta){
            return false;
        }
        return unserialize($meta->meta_value);
    }

    /**
     *
     * 获得文件自身默认的权限
     *
     * @since 1.0.7
     */
    public function getUserFilePrivilegeOnly($user_id, $file_path)
    {
        $pri = UserPrivilege::model()->find('user_id=:user_id and file_path=:file_path', array(':user_id'=>$user_id,':file_path'=>$file_path));
        if (!empty($pri)){
            return unserialize($pri["permission"]);
        }

        //添加hook为用户增加额外的文件的权限
        $curPrivileges = array("file_path"=>$file_path, "user_id"=> $user_id);
        $retPri = apply_filters("get_user_privilege_file_only", $curPrivileges);
        if (!$retPri || $retPri == $curPrivileges){
            return false;
        }
        return $retPri;
    }

    /**
     *
     * 获得文件自身默认的权限
     *
     * @since 1.0.7
     */
    public function getFilePrivilegeDefault($file_path)
    {
        //如果检测不到用户的权限,则获取默认权限
        $permission = $this->getFilePrivilege($file_path);
        if ($permission){
            return $permission;
        }

        //添加hook为用户增加额外的文件的权限
        $curPrivileges = array("file_path"=>$file_path,"permission"=>$permission);
        $retPri = apply_filters("add_privilege_after_file", $curPrivileges);
        $permission = $retPri["permission"];

        //返回系统默认的权限
        if (!$permission){
            $permission = $this->getDefaultPermission($file_path);
        }
        return $permission;
    }

    /**
     *
     * 向数据库中插入权限
     *
     * @since 1.0.7
     */
    public function createUserPrivilegeDb($user_id, $file_path, $permission){
        $privilege = new UserPrivilege();
        $privilege["user_id"]    = $user_id;
        $privilege["file_path"]  = $file_path;
        $privilege["permission"] = serialize($permission);
        $privilege->save();
    }
}