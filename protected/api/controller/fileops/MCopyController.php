<?php
/**
 * Miniyun copy服务主要入口地址, 复制文件/夹
 * 文件目前关联2张数据表：miniyun_files, miniyun_events
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MCopyController extends MApplicationComponent implements MIController{
	
    public $_from_path        = null;
    public $_to_path          = null;
    public $_from_shared_path   = null;
    public $_to_shared_path      = null;
    public $_user_id          = null;
    public $_user_device_id   = null;
    public $_user_device_name = null;
    public $master            = null;
    public $isOutput          = true;
    public $result            = array('data'=>array());
    public $fromId            = 0;
    public $versions          = array();
    public $owner             = NULL; // 所有者，默认ow为操作用户id
    public $user_nick;
    /**
     * 
     * 执行invoke之前的操作
     * @since 1.1.1
     */
    protected function beforeInvoke() {
        // 获取用户数据，如user_id
        $user                       = MUserManager::getInstance()->getCurrentUser();
        $device                     = MUserManager::getInstance()->getCurrentDevice();
        $this->owner                = $user["id"];
        $this->_user_id             = $user["user_id"];
        $this->master               = $this->owner;
        $this->user_nick            = $user["user_name"];
        $this->_user_device_id      = $device["device_id"];
        $this->_user_device_name    = $device["user_device_name"];
    }
    /**
     * 执行空间检查
     * @since 1.1.1
     * @throws MFileopsException
     */
    protected function beforecheck() {
    	$user      = MUserManager::getInstance()->getCurrentUser();
        $space     = $user["space"];
        $usedSpace = $user["usedSpace"];
        if ($space<=$usedSpace) {
            throw new MFileopsException(
                                        Yii::t('api','User is over storage quota.'),
                                        MConst::HTTP_CODE_507);
        }
    }
    
    /**
     * 控制器执行主逻辑函数, 复制文件或者文件夹
     */
    public function invoke($uri=null)
    {
        $this->setAction(MConst::COPY);
        $this->beforeInvoke();
        $this->beforecheck();
        $user                     = MUserManager::getInstance()->getCurrentUser();
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        
        $params = $_REQUEST;
        // 检查参数
        if (isset($params) === false) {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request 11'),
                                        MConst::HTTP_CODE_400);
        }

        // 文件大小格式化参数
        $locale = "bytes";
        if (isset($params["root"]) === false || 
                isset($params["from_path"]) === false || 
                isset($params["to_path"]) === false) {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request 12'),
                                        MConst::HTTP_CODE_400);
        }
        if (isset($params["locale"])) {
            $locale = $params["locale"];
        }
        $root               = $params["root"];
        $this->_from_path   = $params["from_path"];
        $this->_to_path     = $params["to_path"];
        if($params['is_root']){
            $this->_to_path     = '/'.$user['id'].$this->_to_path;
        }
        //
        // 检查文件名是否有效
        //
        $isInvalid = MUtils::checkNameInvalid(
                            MUtils::get_basename($this->_to_path));
        if ($isInvalid)
        {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request 13'),
                                        MConst::HTTP_CODE_400);
        }
        //
        // 转换路径分隔符，便于以后跨平台，如：将 "\"=>"/"
        //
        $this->_from_path = MUtils::convertStandardPath($this->_from_path);
        $this->_to_path   = MUtils::convertStandardPath($this->_to_path);
        if ($this->_from_path == "/" || 
            $this->_to_path == "/" || 
            $this->_from_path === false || 
            $this->_to_path === false)
        {
            throw new MFileopsException(
                        Yii::t('api','Bad Request 14'),
                        MConst::HTTP_CODE_400);
        }
        if ($this->_to_path[strlen($this->_to_path)-1] == "/")
        {
            // 目标文件无效,403 error
            throw new Exception(
                        Yii::t('api','The file or folder name is invalid'),
                        MConst::HTTP_CODE_403);
        }
        
        //
        // 检查共享
        //
        $from_share_filter = MSharesFilter::init();
        $this->to_share_filter   = MSharesFilter::init();
        // 当从共享目录拷贝到其他目录时，源目录用户id设置为共享用户id
//        if ($from_share_filter->handlerCheck($this->owner, $this->_from_path)) {
//            $this->master = $from_share_filter->master;
//            $this->_from_path = $from_share_filter->_path;
//        }
//
//        // 当拷贝到共享目录的时候，目标目录的用户id设置为共享用户id
//        if ($this->to_share_filter->handlerCheck($this->_user_id, $this->_to_path)) {
//            $this->_user_id = $this->to_share_filter->master;
//            $this->user_nick      = $this->to_share_filter->master_nick;
//            $this->_to_path = $this->to_share_filter->_path;
//        }
//        if($this->_from_shared_path){
//            $this->_from_path =  $this->_from_shared_path;
//        }else{
//            $this->_from_path = "/".$this->master.$this->_from_path;
//        }
//        if($this->_to_shared_path){
//            $this->_to_path =  $this->_to_shared_path;
//        }else{
//            $this->_to_path   = "/".$this->_user_id.$this->_to_path;
//        }
        //
        // 检查目标路径是否在复制目录下
        //
        if (strpos($this->_to_path, $this->_from_path."/") === 0)
        {
            throw new MFileopsException(
                            Yii::t('api','Can not be copied to the subdirectory'),
                            MConst::HTTP_CODE_403);
        }
        $check = CUtils::removeUserFromPath($this->_to_path);
        if (empty($check) || $check == '/')
        {
            throw new MFileopsException(
                            Yii::t('api','Can not be copied to the error directory'),
                            MConst::HTTP_CODE_403);
        }
        //
        // 检查目标路径文件是否存在
        //
        $queryToPathDbFile = MFiles::queryAllFilesByPath($this->_to_path);
        $isUpdate = false;
        if ($queryToPathDbFile)
        {
            if ($queryToPathDbFile[0]["is_deleted"] == false)
            {
                // 已经存在,403 error
                throw new MFileopsException(
                            Yii::t('api','There is already a item at the given destination'),
                            MConst::HTTP_CODE_403);
            }
            $isUpdate = true;
        }
        //
        // 查询其信息
        //
        $fileName   = MUtils::get_basename($this->_to_path);
        $queryFromPathDbFile = MFiles::queryFilesByPath($this->_from_path);
        $queryToPathDbFile = MFiles::queryFilesByPath(dirname($this->_to_path));
        if ($queryFromPathDbFile === false || empty($queryFromPathDbFile))
        {
            throw new MFileopsException(
                Yii::t('api','Not found the source files of the specified path'),
                MConst::HTTP_CODE_404);
        }
        $fromArr = explode('/',$this->_from_path);
        $fromId = $fromArr[1];
        if($params['root']){
            $toArr = explode('/',$this->_to_path);
            $toId = $toArr[1];
        }else{
            $toId = $user['id'];
       }
        //权限判断
        //当属于共享目录时才进行权限控制(源路径)
        $fromFile = MiniFile::getInstance()->getByFilePath($this->_from_path);
        if ($fromId!=$user['id']){
            //判断文件重命名是否有权限操作
            $permissionArr =  UserPermissionBiz::getInstance()->getPermission($this->_from_path,$user['id']);
            if(!isset($permissionArr)){
                $permission = MConst::SUPREME_PERMISSION;
            }else{
                $permission = $permissionArr['permission'];
            }
            $miniPermission = new MiniPermission($permission);
            $canCopy = $miniPermission->canCopy($fromFile['file_type']);
            if(!$canCopy){
                throw new MFileopsException(
                    Yii::t('api','no permission'),
                    MConst::HTTP_CODE_409);
            }
        }
        $isSharedPath = false;//主要用于判断是否为被共享文件
        //目标路径
        if ($toId!=$user['id']){
            $isSharedPath = true;
            //拷贝到 （目标路径的创建权限）  的判断
//            if ($query_from_path_db_file[0]["file_type"] == 0){  //文件
//                $this->to_share_filter->hasPermissionExecute($this->_to_path, MPrivilege::FILE_CREATE);
//            } else {                                           //文件夹
//                $this->to_share_filter->hasPermissionExecute($this->_to_path, MPrivilege::FOLDER_CREATE);
//            }
        }else{
            $model = new GeneralFolderPermissionBiz($this->_to_path);
            if($model->isParentShared($this->_to_path)){//如果是父目录被共享
                $isSharedPath = true;
            }
        }
        if($isSharedPath){
            $permissionArr = UserPermissionBiz::getInstance()->getPermission(dirname($this->_to_path),$user['id']);
            if(!isset($permissionArr)){
                $permission = MConst::SUPREME_PERMISSION;
            }else{
                $permission = $permissionArr['permission'];
                $privilegeModel = new PrivilegeBiz();
                $this->to_share_filter ->slaves =$privilegeModel->getSlaveIdsByPath($permissionArr['share_root_path']);
                $this->to_share_filter ->is_shared = true;
            }
            $miniPermission = new MiniPermission($permission);
            $toFile = MiniFile::getInstance()->getByFilePath(dirname($this->_to_path));
            $canCopy = $miniPermission->canCopy($toFile['file_type']);
            if(!$canCopy){
                throw new MFileopsException(
                    Yii::t('api','no permission'),
                    MConst::HTTP_CODE_409);
            }
        }
        //
        // 查询目标路径父目录信息
        //
        $parentPath                      = dirname($this->_to_path);
        $createFolder                    = new MCreateFolderController();
        $createFolder->_user_device_id   = $this->_user_device_id;
        $createFolder->_user_id          = $this->_user_id;
        $createFolder->share_filter      = $this->to_share_filter;
        $parentFileId                   = $createFolder->handlerParentFolder($parentPath);
        //
        // 组装对象信息
        //
        $fileDetail                      = new MFiles();
        $fileDetail->file_name           = $fileName;
        $fileDetail->file_path           = $this->_to_path;
        $this->assembleFileDetail( 
                                    $fileName,
                                    $parentFileId,
                                    $fileDetail,
                                    $queryFromPathDbFile[0]);
        //
        // 首先处理复制根目录操作
        //
        if ($isUpdate)
        {
            $fileDetail->event_uuid = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
            $updates = array();
            $updates["file_update_time"]    = time();
            $updates["is_deleted"]          = intval(false);
            $updates["event_uuid"]          = $fileDetail->event_uuid;
            $updates["file_type"]           = $fileDetail->file_type;
            $retValue = MFiles::updateFileDetailByPath($this->_to_path, $updates);
        }
        else
        {
            $retValue = MFiles::CreateFileDetail($fileDetail, $this->_user_id);
        }
        if ($retValue === false)
        {
        throw new MFileopsException(
                                    Yii::t('api','Internal Server Error'),
                                    MConst::HTTP_CODE_500);
        }
        //
        // 更新版本信息
        //
        $this->updateVerRef(array($fileDetail));
        $retValue = MiniEvent::getInstance()->createEvent($this->_user_id,
            $this->_user_device_id,
            $fileDetail->event_action,
            $fileDetail->file_path,
            $fileDetail->context,
            $fileDetail->event_uuid,
            $this->to_share_filter->type);
        if ($retValue === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','There is already a item at the given destination'),
                                        MConst::HTTP_CODE_500);
        }
        $context = $fileDetail->context;
        if ($fileDetail->file_type == 0) {
            $context = unserialize($context);
        }
        $this->to_share_filter->handlerAction($fileDetail->event_action, $this->_user_device_id,
                                              $fileDetail->file_path,$context);
        //
        // 判断操作的是文件夹，还是文件
        //
        $createArray = array();
        $queryDbFile = MFiles::queryFilesByPath($this->_to_path);
        //
        // 查询其复制目录路径id
        //
        if ($queryDbFile === false || empty($queryDbFile))
        {
            throw new MFileopsException(
                Yii::t('api','Not found the source files of the specified path'),
                MConst::HTTP_CODE_404);
        }
        if ($fileDetail->file_type != MConst::OBJECT_TYPE_FILE)
        {
            $fileDetail->id = $queryDbFile[0]["id"];
            $fileDetail->file_size = $queryDbFile[0]["file_size"];
            $this->handlerChildrenFile($fileDetail);
            //
            // 处理版本信息
            //
            $moveController = new MMoveController();
            $moveController->versions = array();
            $createArray = $moveController->handleChildrenVersions(
                                            $createArray,
                                            $this->_user_id, 
                                            $this->user_nick, 
                                            $this->_from_path, 
                                            $this->_to_path,
                                            $queryToPathDbFile[0]["id"],
                                            $this->_user_device_name,
                                            $queryFromPathDbFile[0]["file_size"]
                                            );
            $this->versions = $moveController->versions;
        }
        else
        {
            $fileMeta = new MFileMetas();
            $fileMeta->version_id = $queryFromPathDbFile[0]["version_id"];
            //
            // 查询其版本
            //
            $fileVersion = MFileMetas::queryFileMeta(
                                                        $this->_to_path, 
                                                        MConst::VERSION);
            $fileMeta->is_add     = false;
            if ($fileVersion)
            {
                $metaValue = MUtils::getFileVersions(
                                                $this->_user_device_name,
                                                $fileDetail->file_size,
                                                $fileMeta->version_id,
                                                MConst::CREATE_FILE, 
                                                $this->_user_id, 
                                                $this->user_nick,
                                                $fileVersion[0]["meta_value"]);
            }
            else 
            {
                $metaValue = MUtils::getFileVersions(
                                                $this->_user_device_name,
                                                $fileDetail->file_size,
                                                $fileMeta->version_id,
                                                MConst::CREATE_FILE, 
                                                $this->_user_id, 
                                                $this->user_nick);
                $fileMeta->is_add     = true; // 不存在记录，需要添加
            }
            $fileMeta->meta_value = $metaValue;
            $fileMeta->file_path  = $this->_to_path;
            $createArray[$queryFromPathDbFile[0]["file_path"]] = $fileMeta;
            //
            // 添加到需要更新的版本ref
            //
            array_push($this->versions, $fileMeta->version_id);
        }
        //
        // 创建版本信息
        //
        MFileMetas::batchCreateFileMetas($createArray, MConst::VERSION);
//        if ($ret === false)
//        {
//            throw new MFileopsException(
//                                        Yii::t('api','Internal Server Error'),
//                                        MConst::HTTP_CODE_500);
//        }
        //
        // 更新版本
        //
        foreach ($createArray as $key => $fileMeta)
        {
            if ($fileMeta->is_add === true)
            {
                // 不存在记录，不需要更新
                continue;
            }
            MFileMetas::updateFileMeta(
                                        $fileMeta->file_path,
                                        MConst::VERSION, 
                                        $fileMeta->meta_value);
        }
        
        //
        // 处理不同端，不同返回值
        //
        if ( MUserManager::getInstance()->isWeb() === true)
        {
            $this->buildWebResponse();
            return ;
        }
        
        $response                   = array();
        $isDir                     = true;
        if ($queryDbFile[0]["file_type"] == MConst::OBJECT_TYPE_FILE)
        {
            // TODO
            $mimeType                = "text/plain";
            $response["mime_type"]    = $mimeType;
            $isDir                   = false;
            $response["thumb_exists"] = MUtils::isExistThumbnail($mimeType, (int)$queryDbFile[0]["file_size"]);
        }
        
        $size                       = $queryDbFile[0]["file_size"];
        $response["size"]           = MUtils::getSizeByLocale($locale, $size);
        $response["bytes"]          = intval($size);
        
        
        $pathInfo                  = MUtils::pathinfo_utf($this->_to_path);
        $pathInfoOut = MUtils::pathinfo_utf($this->to_share_filter->src_path);
        
        $path = MUtils::convertStandardPath($pathInfoOut['dirname'] . "/" . $pathInfo['basename']);
        $response["path"]           = $path;
        $response["root"]           = $root;
        $response["is_dir"]         = $isDir;
        $response["rev"]            = strval($queryDbFile[0]["version_id"]);
        $response["revision"]       = intval($queryDbFile[0]["version_id"]);
        $response["modified"]       = MUtils::formatIntTime($queryDbFile[0]["file_update_time"]);
        
        //
        // 如果标记为不输出结果的话，直接返回$response
        //
        if (!$this->isOutput) {
            return $response;
        }
        echo json_encode($response);
    }
    
    public function buildWebResponse()
    {
        $this->result["state"]   = true;
        $this->result["code"]    = 0;
        $this->result["msg"]     = Yii::t('api_message', 'copy_success');
        $this->result["data"][$this->fromId]["state"] = true;
        return ;
    }
    
    public function handlerChildrenFile($fileDetail)
    {
        $directories = array();   // 记录这层中文件夹的对象
        $files       = array();   // 记录需要处理的文件对象（包括文件夹）
        //
        // 文件夹，查询其子文件这一层数据
        //
        $dbChildrenFiles = MFiles::queryChildrenFilesByParentFileID($fileDetail->from_id);
        if ($dbChildrenFiles === false)
        {
            throw new MFileopsException(
                        Yii::t('api','Internal Server Error'),
                        MConst::HTTP_CODE_500);
        }
        if (empty($dbChildrenFiles))
        {
            $p = $fileDetail->file_path;
            return ; // 没有子文件，返回
        }
        //
        // 检查文件数量，复制数量限制在10000条内
        //
        if (count($dbChildrenFiles) > MConst::MAX_FILES_COUNT)
        {
            throw new MFileopsException(
                        Yii::t('api','Too many files or folders need to be copied'),
                        MConst::HTTP_CODE_406);
        }
        //
        // 转换数据
        //
        foreach ($dbChildrenFiles as $dbFile)
        {
            $newFileDetail = new MFiles();
            //
            // 排除已被删除的对象
            //
            if ($dbFile["is_deleted"] == true)
            {
                continue;
            }
            $this->assembleFileDetail(
                                        $dbFile['file_name'],
                                        $fileDetail->id,
                                        $newFileDetail,
                                        $dbFile);
            array_push($files, $newFileDetail);
            if ($dbFile["file_type"] == MConst::OBJECT_TYPE_DIRECTORY)
            {
                array_push($directories, $newFileDetail);
            }
        }
        if (empty($files))
        {
            return ;
        }
        //
        // 批量处理这批数据
        //
        $ret = MFiles::batchCreateFileDetails(
                                        $this->_user_id, 
                                        $files);
        if ($ret === false || empty($ret))
        {
            throw new MFileopsException(
                Yii::t('api','Not found the source files of the specified path'),
                MConst::HTTP_CODE_404);
        }
        
        $this->updateVerRef($files);


        $ret = MiniEvent::getInstance()->createEvents(
                                    $this->_user_id, 
                                    $this->_user_device_id, 
                                    $files,
                                    $this->to_share_filter->type);
        if ($ret === false || empty($ret))
        {
            throw new MFileopsException(
                Yii::t('api','Not found the source files of the specified path'),
                MConst::HTTP_CODE_404);
        }
        
        //
        // 为共享创建事件
        //
        if ($this->to_share_filter->is_shared) {
            foreach ($files as $v) {
                $context = $v->context;
                if ($v->event_action == MConst::CREATE_FILE || $v->event_action == MConst::MODIFY_FILE) {
                    $context = unserialize($context);
                }
                $this->to_share_filter->handlerAction($v->event_action, $this->_user_device_id, $v->from_path, $context);
            }
        }
        
        //
        // 处理这层中的文件夹
        //
        foreach ($directories as $file)
        {
            //
            // 查询其复制目录路径id
            //
            $queryDbDirectory = MFiles::queryFilesByPath(
                                                        $file->file_path);
            if ($queryDbDirectory === false || empty($queryDbDirectory))
            {
                throw new MFileopsException(
                    Yii::t('api','Not found the source files of the specified path'),
                    MConst::HTTP_CODE_404);
            }
            $file->id        = $queryDbDirectory[0]["id"];
            $this->handlerChildrenFile($file);
        }
    }

    /**
     * 处理组装复制文件需要的对象
     * @param $fileName
     * @param $parentFileId
     * @param $fileDetail
     * @param $queryDbFile
     */
    public function assembleFileDetail(
                                        $fileName,
                                        $parentFileId,
                                        $fileDetail,
                                        $queryDbFile)
    {
        $filePath                          = $queryDbFile["file_path"];
        $fileDetail->file_type             = $queryDbFile["file_type"];
        $eventAction = MConst::CREATE_DIRECTORY;
        $fileDetail->context               = $filePath;
        $fileDetail->mime_type             = NULL;
        if ($fileDetail->file_type == MConst::OBJECT_TYPE_FILE)
        {
            $fileDetail->mime_type = MiniUtil::getMimeType($fileName);
            $eventAction = MConst::CREATE_FILE;
            $versionId  = $queryDbFile["version_id"];
            $version     = MiniVersion::getInstance()->getVersion($versionId);
            if ($version != null) {
                $context = array( "hash"  => $version["file_signature"],
                                               "rev"   => (int)$versionId,
                                               "bytes" => (int)$queryDbFile["file_size"],
                                               "update_time" => (int)$queryDbFile["file_update_time"],
                                               "create_time" => (int)$queryDbFile["file_create_time"] );
                $fileDetail->context = serialize($context);
            }
        } elseif ($fileDetail->file_type > MConst::OBJECT_TYPE_DIRECTORY) {
            $fileDetail->file_type = 1;
        }
        
        $fileDetail->from_id               = $queryDbFile['id'];
        $fileDetail->parent_file_id        = $parentFileId;
        $fileDetail->event_action          = $eventAction;
        $fileDetail->file_name             = $fileName;
        $fileDetail->version_id            = $queryDbFile["version_id"];
        $fileDetail->file_size             = $queryDbFile["file_size"];
        $fileDetail->event_uuid            = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
        $index      = strlen($this->_from_path);
        $filePath  = substr_replace($filePath, $this->_to_path, 0, $index);
        $fileDetail->from_path             = $filePath;
        $fileDetail->file_path             = $filePath;
        $fileDetail->file_create_time      = time();
        $fileDetail->file_update_time      = time();
    }
    
    /**
     * 
     * 更新文件版本引用次数
     * @since 0.9.6
     * @param array $files
     */
    private function updateVerRef($files) {
        foreach ($files as $file) {
            if ($file->file_type != 0)
                continue;
            MiniVersion::getInstance()->updateRefCount($file->version_id);
        }
    }
}