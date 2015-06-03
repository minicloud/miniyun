<?php
/**
 * Miniyun move服务主要入口地址, 移动，重命名文件/夹
 * 文件目前关联2张数据表：miniyun_files, miniyun_events
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class MMoveController
    extends MApplicationComponent
    implements MIController
{
    public $_userId  = null;
    private $_locale = null;
    private $_root   = null;
    private $to_share_filter = null;
    private $from_share_filter = null;
    public $master   = null;
    private $_user_device_name = null;
    public  $result  = array();
    public  $fromId  = 0;
    public $versions = array();
    public $isSingle = true; // 移动用户 or 用户组
    public $isEcho   = true; // 输出返回
    public $isRename = false;
    /**
     * 控制器执行主逻辑函数, 处理移动文件或者文件夹
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null)
    {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $this->setAction(MConst::MOVE);
        $params = $_REQUEST;
        // 检查参数
        if (isset($params) === false) {
            throw new MException(Yii::t('api','Bad Request 7'));
        }
        //
        // 获取用户数据，如user_id
        $user                        = MUserManager::getInstance()->getCurrentUser();
        $device                      = MUserManager::getInstance()->getCurrentDevice();
        $this->_userId               = $user["user_id"];
        $this->master                = $user["user_id"];
        $user_nick                   = $user["user_name"];
        $user_device_id              = $device["device_id"];
        $this->_user_device_name     = $device["user_device_name"];
        // 文件大小格式化参数
        $this->_locale = "bytes";
        if (isset($params["locale"])) {
            $this->_locale = $params["locale"];
        }
        if (isset($params["root"]) === false ||
            isset($params["from_path"]) === false ||
            isset($params["to_path"]) === false) {
            throw new MFileopsException(
                Yii::t('api','Bad Request'),
                MConst::HTTP_CODE_400);
        }
        $this->_root        = $params["root"];
        $from_path          = $params["from_path"];
        $to_path            = $params["to_path"];
        $arr = explode('/',$from_path);
        $isRoot = false;
        $isMine = false;
        if(count($arr)==3){
            $isRoot = true;
        }
        $fileOwnerId = $arr[1];
        $currentUserId = $this->_userId;
        if($fileOwnerId==$currentUserId ){
            $isMine = true;
        }
        if($isRoot&&!$isMine){//如果是在根目录下且不是自己的目录 则后台控制不准取消共享
            throw new MFileopsException(
                Yii::t('api','Internal Server Error'),
                MConst::HTTP_CODE_409);
        }
        $to_parts   = explode('/', $to_path);
        $from_parts = explode('/', $from_path);
        if(count($to_parts) == 2){
            $to_path = '/'.$this->_userId.$to_path;
        }
        $to_parts   = explode('/', $to_path);
        $file                        = MiniFile::getInstance()->getByPath($from_path);
        $isSelfFile = false;
        if(!empty($file) && ($file['user_id'] == $this->_userId)){
            $isSelfFile = true;
        }
        // 转换路径分隔符，便于以后跨平台，如：将 "\"=>"/"
        $from_path = MUtils::convertStandardPath($from_path);
        $to_path   = MUtils::convertStandardPath($to_path);
        if ($to_path[strlen($to_path)-1] == "/")
        {
            // 目标文件无效,403 error
            throw new MFileopsException(
                Yii::t('api','The file or folder name is invalid'),
                MConst::HTTP_CODE_403);
        }

        // 检查共享
        $this->from_share_filter       = MSharesFilter::init();
        $this->to_share_filter   = MSharesFilter::init();

        $isSharedPath = true;
        $this->rename = false;
        // 检查移动方式
        if ($this->rename == true) {
            // 先copy再删除,如果是移动共享文件夹则只copy，再执行shareManager取消共享
            $copy_handler = new MCopyController();
            $copy_handler->isOutput = false;
            $response = $copy_handler->invoke();
            $_REQUEST['path'] = $params["from_path"];
            $delete_handler = new MDeleteController();
            $delete_handler->isOutput = false;
            $delete_handler->completely_remove = true;
            $delete_handler->invoke();
            if (MUserManager::getInstance()->isWeb() === true)
            {
                $this->buildWebResponse();exit;
                return ;
            }
            echo json_encode($response);
            return ;
        }

        $file_name = MUtils::get_basename($to_path);
        // 检查文件名是否有效
        $is_invalid = MUtils::checkNameInvalid($file_name);
        if ($is_invalid)
        {
            throw new MFileopsException(
                Yii::t('api','The file or folder name is invalid'),
                MConst::HTTP_CODE_400);
        }
        // 检查是否移动到其子目录下
        if (strpos($to_path, $from_path."/") === 0)
        {
            throw new MFileopsException(
                Yii::t('api','Can not be moved to the subdirectory'),
                MConst::HTTP_CODE_403);
        }
        if ($to_path == "/{$this->_userId}" || $to_path == "/{$this->_userId}/")
        {
            throw new MFileopsException(
                Yii::t('api','Can not be moved to the error directory'),
                MConst::HTTP_CODE_403);
        }
        $from_parent = CUtils::pathinfo_utf($from_path);
        $to_parent   = CUtils::pathinfo_utf($to_path);
        $privilegeModel = new PrivilegeBiz();
        if(!(count($to_parts)==3)){
            $isSharedPath = false;
            $toPathArr = explode('/',$to_path);
            $masterId =   $toPathArr[1];
            if($masterId!=$this->_userId){
                $isSharedPath = true;
            }else{
                $model = new GeneralFolderPermissionBiz($to_parent['dirname']);
                if($model->isParentShared($to_parent['dirname'])){//如果是父目录被共享
                    $isSharedPath = true;
                }
            }
            if($isSharedPath){
                $toPrivilege   = UserPermissionBiz::getInstance()->getPermission($to_parent['dirname'],$this->_userId);
                if(empty($toPrivilege)){
                    $toPrivilege['permission'] = MConst::SUPREME_PERMISSION;
                }else{

                    $this->to_share_filter->slaves =$privilegeModel->getSlaveIdsByPath($toPrivilege['share_root_path']);
                    $this->to_share_filter->is_shared = true;

                }
            }else{
                $toPrivilege['permission'] = MConst::SUPREME_PERMISSION;
            }

            $toFilter      = new MiniPermission($toPrivilege['permission']);
        }else{
            if ($to_parent['dirname'] == $from_parent['dirname']) {
                $isSharedPath = false;
                $fromPathArr = explode('/',$from_path);
                $masterId =  $fromPathArr[1];
                if($masterId!=$this->_userId){
                    $isSharedPath = true;
                }else{
                    $model = new GeneralFolderPermissionBiz($from_path);
                    if($model->isParentShared($from_path)){//如果是父目录被共享
                        $isSharedPath = true;
                    }
                }
                if($isSharedPath){
                    $toPrivilege   =  UserPermissionBiz::getInstance()->getPermission($from_path,$this->_userId);
                    if(!empty($toPrivilege)){
                        $this->to_share_filter->slaves =$privilegeModel->getSlaveIdsByPath($toPrivilege['share_root_path']);
                        $this->to_share_filter->is_shared = true;
                    }else{
                        $toPrivilege['permission'] = MConst::SUPREME_PERMISSION;
                    }
                }else{
                    $toPrivilege['permission'] = MConst::SUPREME_PERMISSION;
                }

            }
            $toFilter      = new MiniPermission(MConst::SUPREME_PERMISSION);
        }
        $isSharedPath = false;
        $fromPathArr = explode('/',$from_path);
        $masterId =  $fromPathArr[1];
        if($masterId!=$this->_userId){
            $isSharedPath = true;
        }else{
            $model = new GeneralFolderPermissionBiz($from_path);
            if($model->isParentShared($from_path)){//如果是父目录被共享
                $isSharedPath = true;
            }
        }
        if($isSharedPath){
            $fromPrivilege = UserPermissionBiz::getInstance()->getPermission($from_path,$this->_userId);
            if(empty($fromPrivilege)){
                $fromPrivilege['permission'] = MConst::SUPREME_PERMISSION;
            }else{
                $this->from_share_filter->slaves =$privilegeModel->getSlaveIdsByPath($fromPrivilege['share_root_path']);
                $this->from_share_filter->is_shared = true;
            }
        }else{
            $fromPrivilege['permission'] = MConst::SUPREME_PERMISSION;
        }

        $fromFilter    = new MiniPermission($fromPrivilege['permission']);
        if ($to_parent['dirname'] == $from_parent['dirname']) {
            $this->setAction(MConst::RENAME);
            $this->isRename = true;
            $canRenameFile = $fromFilter->canModifyFileName();
            $canRenameFolder = $fromFilter->canModifyFolderName();
            $canRenameFile2 = $toFilter->canModifyFileName();
            $canRenameFolder2 = $toFilter->canModifyFolderName();
            if( $file['file_type']>0 && !$canRenameFolder && !$isSelfFile){//如果目标是目录，则当其不为己文件，且无更名权限时 exception
                throw new MFileopsException(
                    Yii::t('api','have no permission to rename folder'),
                    MConst::HTTP_CODE_409);
            }
            if( $file['file_type']=0 && !$canRenameFile && !$isSelfFile){//如果目标是文件，则当其不为己文件，且无更名权限时 exception
                throw new MFileopsException(
                    Yii::t('api','have no permission to rename file'),
                    MConst::HTTP_CODE_409);
            }
            if( $file['file_type']>0 && !$canRenameFile2 && !$isSelfFile){//如果目标是目录，则当其不为己文件，且无更名权限时 exception
                throw new MFileopsException(
                    Yii::t('api','have no permission to rename folder'),
                    MConst::HTTP_CODE_409);
            }
            if( $file['file_type']=0 && !$canRenameFolder2 && !$isSelfFile){//如果目标是文件，则当其不为己文件，且无更名权限时 exception
                throw new MFileopsException(
                    Yii::t('api','have no permission to rename file'),
                    MConst::HTTP_CODE_409);
            }
        }else{
            $canModifyFile = $fromFilter->canModifyFile();
            $canModifyFile2 = $toFilter->canModifyFile();
            if((!$canModifyFile2) || (!$canModifyFile)){
                throw new MFileopsException(
                    Yii::t('api','have no permission to move file'),
                    MConst::HTTP_CODE_409);
            }
            if((!$canModifyFile2) && (!$isSelfFile)){
                throw new MFileopsException(
                    Yii::t('api','have no permission to move file'),
                    MConst::HTTP_CODE_409);
            }
        }
        // 先检查源目录是否存在，如果不存在抛出404错误
        //
        $query_db_file = MFiles::queryFilesByPath($from_path);
        if ($query_db_file === false || empty($query_db_file))
        {
            throw new MFileopsException(
                Yii::t('api','The source file was not found at the specified path'),
                MConst::HTTP_CODE_404);
        }
        //
        // 检查目标是否存在(包括已被删除的状态)
        //
        $deleted = null;
        $query_db_goal_file = MFiles::queryAllFilesByPath($to_path);
        if ($query_db_goal_file)
        {
            if ($from_path !== $to_path &&
                $query_db_goal_file[0]["is_deleted"] == false)
            {
                throw new MFileopsException(
                    Yii::t('api','There is already a item at the given destination'),
                    MConst::HTTP_CODE_403);
            }
            // 已删除文件的处理
            if ($query_db_goal_file[0]["is_deleted"] == 1) {
                MFiles::deleteById($query_db_goal_file[0]["id"]);
                if ($query_db_goal_file[0]["file_type"] != 0) {  // 文件则直接删除
                    $deleted = $query_db_goal_file[0]["id"];
                }

            }

        }
        $query_db_file = MFiles::queryFilesByPath($from_path);
        if ($query_db_file === false || empty($query_db_file))
        {
            throw new MFileopsException(
                Yii::t('api','Not found the source files of the specified path'),
                MConst::HTTP_CODE_404);
        }
        //
        // 检查移动原路径与目标路径是否一致，一致则则返回其文件信息
        //
        if ($from_path === $to_path)
        {
            $this->buildResult($query_db_file[0]);
            return ;
        }
        //
        // 查询目标路径父目录信息
        //

        $pathInfo                         = MUtils::pathinfo_utf($to_path);
        $parent_path                      = $pathInfo["dirname"];
        $create_folder                    = new MCreateFolderController();
        $create_folder->_user_device_id   = $user_device_id;
        $create_folder->_user_id          = $this->_userId;
        if(count(explode('/',$parent_path)) == 2){
            $parent_file_id = 0;
        }else{
            $parent_file_id                   = $create_folder->handlerParentFolder($parent_path);
        }
        $fromUserId = $from_parts[1];
        $toUserId   = $to_parts[1];
        //
        // 组装对象信息
        //
        $file_detail = new MFiles();
        $file_detail->file_name         = $file_name;
        $file_detail->file_path         = $to_path;
        $file_detail->file_type         = $query_db_file[0]["file_type"];
        $file_detail->id                = $query_db_file[0]["id"];
        $file_detail->from_path         = $from_path;
        $file_detail->parent_file_id    = $parent_file_id;
        $file_detail->user_id           = $toUserId;
        $file_detail->mime_type         = NULL;
        $create_array = array();
        //
        // 判断操作的是文件夹，还是文件
        //
        if ($file_detail->file_type > MConst::OBJECT_TYPE_FILE){
            if($file['user_id'] != ($this->_userId)){
                $updateUserId = $query_db_file[0]["user_id"];
            }else{
                $updateUserId = $this->master;
            }
            //
            // 文件夹，将会对其子文件做进一步处理
            //
            $ret_value = MFiles::updateMoveChildrenFileDetail($updateUserId, $file_detail);

            if ($ret_value === false)
            {
                throw new MFileopsException(
                    Yii::t('api','Not found the source files of the specified path'),
                    MConst::HTTP_CODE_404);
            }
            //
            // 针对文件夹下的文件，组装需要添加版本信息的文件
            //
            $create_array = $this->handleChildrenVersions($create_array,
                $this->_userId,
                $user_nick,
                $from_path,
                $to_path,
                $query_db_file[0]["id"],
                $this->_user_device_name,
                $query_db_file[0]["file_size"]);
        }else{
            $file_detail->mime_type = MiniUtil::getMimeType($file_name);
            $file_meta              = new MFileMetas();
            $file_meta->version_id  = $query_db_file[0]["version_id"];
            //
            // 查询之前是否包含其版本
            //
            $file_version = MFileMetas::queryFileMeta($to_path, MConst::VERSION);
            if ($file_version){
                $meta_value = MUtils::getFileVersions(
                    $this->_user_device_name,
                    $query_db_file[0]['file_size'],
                    $file_meta->version_id,
                    MConst::CREATE_FILE,
                    $this->_userId,
                    $user_nick,
                    $file_version[0]["meta_value"]
                );
                $file_meta->is_add      = false;
            }else{
                $meta_value = MUtils::getFileVersions(
                    $this->_user_device_name,
                    $query_db_file[0]['file_size'],
                    $file_meta->version_id,
                    MConst::CREATE_FILE,
                    $this->_userId,
                    $user_nick
                );
                $file_meta->is_add      = true;
            }
            $file_meta->meta_value = $meta_value;
            $file_meta->file_path  = $to_path;
            $create_array[$to_path] = $file_meta;
            //
            // 添加到需要更新的版本ref
            //
            array_push($this->versions, $file_meta->version_id);
        }
        if(($file['file_type'] == 2) || ($file['file_type'] == 4)){
            MiniUserPrivilege::getInstance()->updateByPath($from_path,$to_path);
            MiniGroupPrivilege::getInstance()->updateByPath($from_path,$to_path);
            if($to_parent['dirname'] != $from_parent['dirname']){
                MiniUserPrivilege::getInstance()->deleteByFilePath($to_path);
                MiniGroupPrivilege::getInstance()->deleteByFilePath($to_path);
            }
        }
        //
        // 创建版本信息
        //
        $ret = MFileMetas::batchCreateFileMetas($create_array, MConst::VERSION);
//        if ($ret === false)
//        {
//            throw new MFileopsException(
//            Yii::t('api','Internal Server Error'),
//            MConst::HTTP_CODE_500);
//        }

        //
        // 更新版本
        //
        foreach ($create_array as $file_meta)
        {
            if ($file_meta->is_add === true)
            {
                // 不存在记录，不需要更新
                continue;
            }
            MFileMetas::updateFileMeta(
                $file_meta->file_path,
                MConst::VERSION,
                $file_meta->meta_value);
        }

        //
        // 更新版本引用次数
        //
        foreach ($this->versions as $vid) {
            MiniVersion::getInstance()->updateRefCount($vid);
        }

        //
        // 更新该对象元数据
        //
        $file_detail->event_uuid = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
        $ret_value = MFiles::updateMoveFileDetail($file_detail); // 移动目录 or 文件
        if ($ret_value === false)
        {
            throw new MFileopsException(
                Yii::t('api','Internal Server Error'),
                MConst::HTTP_CODE_500);
        }
        $updates = array();
        if(($file['file_type'] == 2) || ($file['file_type'] == 4)){
            if($to_parent['dirname'] != $from_parent['dirname']){
                $updates['file_type'] = 1;
                $updates['user_id'] = $this->_userId;
            }
        }
        if($fromUserId != $toUserId){
            $updates['user_id'] = $toUserId;
        }
        MiniFile::getInstance()->updateByPath($to_path, $updates);

        //被共享者更改文件名更新file_meta记录
            MiniFileMeta::getInstance()->modifyFilePath($from_path,'create_id', $to_path,$file['file_type']);

        //
        // 保存移动事件 
        // by Kindac;
        //
        $event_action = $this->getGroupMove($file_detail->from_path, $file_detail->file_path);
        $ret_value = MiniEvent::getInstance()->createEvent($this->_userId,
            $user_device_id,
            $event_action,
            $file_detail->from_path,
            $file_detail->file_path,
            $file_detail->event_uuid,
            $this->to_share_filter->type
        );
        if ($ret_value === false)
        {
            throw new MFileopsException(
                Yii::t('api','Internal Server Error'),
                MConst::HTTP_CODE_500);
        }
        if($file['type']!=0){
            $eventAction = MConst::CREATE_DIRECTORY;
        }else{
            $eventAction = MConst::CREATE_FILE ;
        }
        if ($to_parent['dirname'] == $from_parent['dirname']) {
            $this->to_share_filter->handlerAction($event_action, $user_device_id,  $file_detail->from_path,$file_detail->file_path);
        }else{
            if ($this->to_share_filter->is_shared) {
                $this->to_share_filter->handlerAction($eventAction, $user_device_id, $file_detail->file_path, $file_detail->file_path);
            }
            if ($this->from_share_filter->is_shared) {
                $this->from_share_filter->handlerAction(MConst::DELETE, $user_device_id, $file_detail->from_path, $file_detail->from_path);
            }
        }

        $query_db_file[0]["file_path"]  = $file_detail->file_path;
        $query_db_file[0]["event_uuid"] = $file_detail->event_uuid;
        if (!empty($deleted)) {
            MFiles::updateParentId($deleted, $file_detail->id);
        }

        //进行扩展操作
        $this->extend($from_path, $to_path);

        //执行完删除操作后执行的额外事物
        $after = new MMoveAfter();
        $after->action            = self::$scene;
//        $after->from_share_filter = $from_share_filter;
        $after->to_share_filter   = $this->to_share_filter;
        $after->from_path         = $from_path;
        $after->to_path           = $to_path;
        $after->file_detail       = $file_detail;
        $after->execute();
        $this->buildResult($query_db_file[0], $to_path);
    }

    /**
     * 移动文件后的扩展操作
     */
    public function extend($from_path, $to_path){
        //删除分享的操作
        $pathFromInfo      = MUtils::pathinfo_utf($from_path);
        $pathToInfo        = MUtils::pathinfo_utf($to_path);
        if ($pathFromInfo["dirname"] != $pathToInfo["dirname"]){
            MiniLink::getInstance()->unlink($this->fromId);
        }
    }

    /**
     * 处理返回值
     */
    public function buildResult($query_db_file, $to_path=null)
    {
        // 处理不同端，不同返回值
        if (MUserManager::getInstance()->isWeb() === true)
        {
            $this->buildWebResponse();
            return ;
        }

        if ($this->isEcho === false) {
            return;
        }

        $is_dir                     = true;
        $size                       = $query_db_file["file_size"];
        $response                   = array();

        if ($query_db_file["file_type"] == MConst::OBJECT_TYPE_FILE)
        {
            // 根据文件名后缀判断mime type
            $mime_type                  = MiniUtil::getMimeType($query_db_file["file_name"]);
            $is_dir                     = false;
            $response["mime_type"]      = $mime_type;
            $response["thumb_exists"]   = MUtils::isExistThumbnail($mime_type, (int)$query_db_file["file_size"]);
        }
        // 去除/{user_id}
        $path                       = CUtils::removeUserFromPath($query_db_file["file_path"]);
        $response["size"]           = MUtils::getSizeByLocale($this->_locale, $size);
        $response["is_deleted"]     = false;
        $response["bytes"]          = intval($size);
        $response["modified"]       = MUtils::formatIntTime($query_db_file["file_update_time"]);
        if ($to_path)
        {
            $path                   = $to_path;
        }
        else
        {
            $path                   = $query_db_file["file_path"];
        }

        $path_info                  = MUtils::pathinfo_utf($path);
        $path_info_out = MUtils::pathinfo_utf($this->to_share_filter->src_path);
        $path = MUtils::convertStandardPath($path_info_out['dirname'] . "/" . $path_info['basename']);

        $response["path"]           = $path;
        $response["root"]           = $this->_root;
        $response["is_dir"]         = $is_dir;
        $response["rev"]            = strval($query_db_file["version_id"]);
        $response["revision"]       = intval($query_db_file["version_id"]);
        // 增加操作返回事件编码
        $response["event_uuid"]     = $query_db_file["event_uuid"];

        echo json_encode($response);
    }

    public function buildWebResponse()
    {
        $this->result["state"]   = true;
        $this->result["code"]    = 0;
        $this->result["msg"]     = Yii::t('api_message', 'action_success');
        $this->result["data"][$this->fromId]["state"] = true;
        return ;
    }

    /**
     * 处理子文件的版本
     * @param array $create_array
     * @param integer $user_id
     * @param string $user_nick
     * @param string $from_path
     * @param string $to_path
     * @param integer $parent_file_id
     * @param string $device_name
     * @param integer $file_size
     * @throws MFileopsException
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function handleChildrenVersions(
        $create_array,
        $user_id,
        $user_nick,
        $from_path,
        $to_path,
        $parent_file_id,
        $device_name,
        $file_size)
    {
        // 查找所有子文件
        $create_array = $this->findAllChildrenFiles(
            $parent_file_id,
            $user_nick,
            $from_path,
            $to_path,
            $create_array,
            $device_name,
            $file_size
        );
        if ($create_array === false) // 空不需处理
        {
            throw new MFileopsException(
                Yii::t('api','Internal Server Error'),
                MConst::HTTP_CODE_500);
        }
        // 查询目标文件是否包含之前对应的各自版本信息
        //
        $file_versions  = MFileMetas::batchQueryFileMeta(MConst::VERSION, $create_array);
        if ($file_versions === false) // 空不需处理
        {
            throw new MFileopsException(
                Yii::t('api','Internal Server Error'),
                MConst::HTTP_CODE_500);
        }
        foreach ($file_versions as $file_version)
        {
            // 取出路径对应对象
            $file_meta = $create_array[$file_version["file_path"]];
            // 针对原有的版本信息，附加新的
            $meta_value = MUtils::getFileVersions(
                $device_name,
                $file_size,
                $file_meta->version_id,
                MConst::CREATE_FILE,
                $user_id,
                $user_nick,
                $file_version["meta_value"]
            );
            $file_meta->meta_value  = $meta_value;
            // 修改标记，需要更新
            $file_meta->is_add      = false;
            //
            // 添加到需要更新的版本ref
            //
            array_push($this->versions, $file_meta->version_id);
        }
        return $create_array;
    }

    /**
     * 查找该路径下所有子文件：文件存入数组
     * @param string $parent_file_id 父目录id
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function findAllChildrenFiles(
        $parent_file_id,
        $user_nick,
        $from_path,
        $to_path,
        $file_array,
        $device_name,
        $file_size)
    {
        $query_db_files  = MFiles::queryChildrenFilesByParentFileID($parent_file_id);
        if ($query_db_files === false)
        {
            return false;
        }
        foreach ($query_db_files as $key => $db_file)
        {
            if ($db_file["file_type"] != MConst::OBJECT_TYPE_FILE)
            {
                $file_array = $this->findAllChildrenFiles(
                    $db_file["id"],
                    $user_nick,
                    $from_path,
                    $to_path,
                    $file_array,
                    $device_name,
                    $file_size);
                continue;
            }
            //
            // 转换路径
            //
            $file_path      = $db_file["file_path"];
            //
            // 文件存入数组
            //
            $file = new MFiles();
            $file->file_path        = $file_path;
            $file->version_id       = $db_file["version_id"];
            $meta_value = MUtils::getFileVersions(
                $device_name,
                $db_file['file_size'],
                $file->version_id,
                MConst::CREATE_FILE,
                $db_file["user_id"],
                $user_nick);
            $file->meta_value = $meta_value;
            $file->is_add     = true; // 记录是否需要添加
            $file_array[$file_path] = $file;
        }
        return $file_array;
    }
    /**
     * 移动事件id
     * @return number
     */
    private function getGroupMove($from, $to) {
        if ($this->isSingle) {
            return MConst::MOVE;
        }
        if (dirname($from) == dirname($to)) {
            return MConst::MOVE;
        }
        if (strpos($to, $from)) {
            return MConst::MOVE;
        }

        return MConst::GROUP_MOVE;

    }
}