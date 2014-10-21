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
                                        Yii::t('api','Bad Request'),
                                        MConst::HTTP_CODE_400);
        }

        // 文件大小格式化参数
        $locale = "bytes";
        if (isset($params["root"]) === false || 
                isset($params["from_path"]) === false || 
                isset($params["to_path"]) === false) {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request'),
                                        MConst::HTTP_CODE_400);
        }
        if (isset($params["locale"])) {
            $locale = $params["locale"];
        }
        $root               = $params["root"];
        $this->_from_path   = $params["from_path"];
        $this->_to_path     = $params["to_path"];

        //
        // 检查文件名是否有效
        //
        $is_invalid = MUtils::checkNameInvalid(
                            MUtils::get_basename($this->_to_path));
        if ($is_invalid)
        {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request'),
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
                        Yii::t('api','Bad Request'),
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
        if($params['is_root']){
            $this->_to_path = "/".$user['id'].$this->_to_path;
        }
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
        $query_to_path_db_file = MFiles::queryAllFilesByPath($this->_to_path);
        $isUpdate = false;
        if ($query_to_path_db_file)
        {
            if ($query_to_path_db_file[0]["is_deleted"] == false)
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
        $file_name   = MUtils::get_basename($this->_to_path);
        $query_from_path_db_file = MFiles::queryFilesByPath($this->_from_path);
        if ($query_from_path_db_file === false || empty($query_from_path_db_file))
        {
            throw new MFileopsException(
                Yii::t('api','Not found the source files of the specified path'),
                MConst::HTTP_CODE_404);
        }
        $fromArr = explode('/',$this->_from_path);
        $fromId = $fromArr[1];
        if($params['is_root']){
            $toArr = explode('/',$this->_to_path);
            $toId = $toArr[1];
        }else{
            $toId = $user['id'];
       }
        //权限判断
        //当属于共享目录时才进行权限控制(源路径)
        if ($fromId!=$user['id']){
            //判断文件重命名是否有权限操作
//            $from_share_filter->hasPermissionExecute($this->_from_path, MPrivilege::RESOURCE_READ);
            $permissionModel = new UserPermissionBiz($this->_from_path,$user['id']);
            $permissionArr = $permissionModel->getPermission($this->_from_path,$user['id']);
            if(!isset($permissionArr)){
                $permission = "111111111";
            }else{
                $permission = $permissionArr['permission'];
            }
            $miniPermission = new MiniPermission($permission);
            $canCopy = $miniPermission->canCopy();
            if(!$canCopy){
                throw new MFileopsException(MConst::HTTP_CODE_1132);
            }
        }
        //目标路径
        if ($toId!=$user['id']){
            //拷贝到 （目标路径的创建权限）  的判断
//            if ($query_from_path_db_file[0]["file_type"] == 0){  //文件
//                $this->to_share_filter->hasPermissionExecute($this->_to_path, MPrivilege::FILE_CREATE);
//            } else {                                           //文件夹
//                $this->to_share_filter->hasPermissionExecute($this->_to_path, MPrivilege::FOLDER_CREATE);
//            }
            $permissionModel = new UserPermissionBiz(dirname($this->_to_path),$user['id']);
            $permissionArr = $permissionModel->getPermission(dirname($this->_to_path),$user['id']);
            if(!isset($permissionArr)){
                $permission = "111111111";
            }else{
                $permission = $permissionArr['permission'];
            }
            $miniPermission = new MiniPermission($permission);
            $canCopy = $miniPermission->canCopy();
            if(!$canCopy){
                throw new MFileopsException(MConst::HTTP_CODE_1132);
            }
        }

        //
        // 查询目标路径父目录信息
        //
        $parent_path                      = dirname($this->_to_path);
        $create_folder                    = new MCreateFolderController();
        $create_folder->_user_device_id   = $this->_user_device_id;
        $create_folder->_user_id          = $this->_user_id;
        $create_folder->share_filter      = $this->to_share_filter;
        $parent_file_id                   = $create_folder->handlerParentFolder($parent_path);
        //
        // 组装对象信息
        //
        $file_detail                      = new MFiles();
        $file_detail->file_name           = $file_name;
        $file_detail->file_path           = $this->_to_path;
        $this->assembleFileDetail( 
                                    $file_name,
                                    $parent_file_id,
                                    $file_detail, 
                                    $query_from_path_db_file[0]);
        //
        // 首先处理复制根目录操作
        //
        if ($isUpdate)
        {
            $file_detail->event_uuid = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
            $updates = array();
            $updates["file_update_time"]    = time();
            $updates["is_deleted"]          = intval(false);
            $updates["event_uuid"]          = $file_detail->event_uuid;
            $updates["file_type"]           = $file_detail->file_type;
            $ret_value = MFiles::updateFileDetailByPath($this->_to_path, $updates);
        }
        else
        {
            $ret_value = MFiles::CreateFileDetail($file_detail, $this->_user_id);
        }
        if ($ret_value === false)
        {
        throw new MFileopsException(
                                    Yii::t('api','Internal Server Error'),
                                    MConst::HTTP_CODE_500);
        }
        //
        // 更新版本信息
        //
        $this->updateVerRef(array($file_detail));
        $ret_value = MiniEvent::getInstance()->createEvent($this->_user_id,
            $this->_user_device_id,
            $file_detail->event_action,
            $file_detail->file_path,
            $file_detail->context,
            $file_detail->event_uuid,
            $this->to_share_filter->type);
        if ($ret_value === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','There is already a item at the given destination'),
                                        MConst::HTTP_CODE_500);
        }
        $context = $file_detail->context;
        if ($file_detail->file_type == 0) {
            $context = unserialize($context);
        }
        $this->to_share_filter->handlerAction($file_detail->event_action, $this->_user_device_id,
                                              $file_detail->file_path,$context);
        //
        // 判断操作的是文件夹，还是文件
        //
        $create_array = array();
        $query_db_file = MFiles::queryFilesByPath($this->_to_path);
        //
        // 查询其复制目录路径id
        //
        if ($query_db_file === false || empty($query_db_file))
        {
            throw new MFileopsException(
                Yii::t('api','Not found the source files of the specified path'),
                MConst::HTTP_CODE_404);
        }
        if ($file_detail->file_type != MConst::OBJECT_TYPE_FILE)
        {
            $file_detail->id = $query_db_file[0]["id"];
            $file_detail->file_size = $query_db_file[0]["file_size"];
            $this->handlerChildrenFile($file_detail);
            //
            // 处理版本信息
            //
            $move_controller = new MMoveController();
            $move_controller->versions = array();
            $create_array = $move_controller->handleChildrenVersions(
                                            $create_array, 
                                            $this->_user_id, 
                                            $this->user_nick, 
                                            $this->_from_path, 
                                            $this->_to_path, 
                                            $query_from_path_db_file[0]["id"],
                                            $this->_user_device_name,
                                            $query_from_path_db_file[0]["file_size"]
                                            );
            $this->versions = $move_controller->versions;
        }
        else
        {
            $file_meta = new MFileMetas();
            $file_meta->version_id = $query_from_path_db_file[0]["version_id"];
            //
            // 查询其版本
            //
            $file_version = MFileMetas::queryFileMeta(
                                                        $this->_to_path, 
                                                        MConst::VERSION);
            $file_meta->is_add     = false;
            if ($file_version)
            {
                $meta_value = MUtils::getFileVersions(
                                                $this->_user_device_name,
                                                $file_detail->file_size,
                                                $file_meta->version_id,
                                                MConst::CREATE_FILE, 
                                                $this->_user_id, 
                                                $this->user_nick,
                                                $file_version[0]["meta_value"]);
            }
            else 
            {
                $meta_value = MUtils::getFileVersions(
                                                $this->_user_device_name,
                                                $file_detail->file_size,
                                                $file_meta->version_id, 
                                                MConst::CREATE_FILE, 
                                                $this->_user_id, 
                                                $this->user_nick);
                $file_meta->is_add     = true; // 不存在记录，需要添加
            }
            $file_meta->meta_value = $meta_value;
            $file_meta->file_path  = $this->_to_path;
            $create_array[$query_from_path_db_file[0]["file_path"]] = $file_meta;
            //
            // 添加到需要更新的版本ref
            //
            array_push($this->versions, $file_meta->version_id);
        }
        //
        // 创建版本信息
        //
        $ret = MFileMetas::batchCreateFileMetas($create_array, MConst::VERSION);
        if ($ret === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        //
        // 更新版本
        //
        foreach ($create_array as $key => $file_meta)
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
        // 处理不同端，不同返回值
        //
        if ( MUserManager::getInstance()->isWeb() === true)
        {
            $this->buildWebResponse();
            return ;
        }
        
        $response                   = array();
        $is_dir                     = true;
        if ($query_db_file[0]["file_type"] == MConst::OBJECT_TYPE_FILE)
        {
            // TODO
            $mime_type                = "text/plain";
            $response["mime_type"]    = $mime_type;
            $is_dir                   = false;
            $response["thumb_exists"] = MUtils::isExistThumbnail($mime_type, (int)$query_db_file[0]["file_size"]);
        }
        
        $size                       = $query_db_file[0]["file_size"];
        $response["size"]           = MUtils::getSizeByLocale($locale, $size);
        $response["bytes"]          = intval($size);
        
        
        $path_info                  = MUtils::pathinfo_utf($this->_to_path);
        $path_info_out = MUtils::pathinfo_utf($this->to_share_filter->src_path);
        
        $path = MUtils::convertStandardPath($path_info_out['dirname'] . "/" . $path_info['basename']);
        $response["path"]           = $path;
        $response["root"]           = $root;
        $response["is_dir"]         = $is_dir;
        $response["rev"]            = strval($query_db_file[0]["version_id"]);
        $response["revision"]       = intval($query_db_file[0]["version_id"]);
        $response["modified"]       = MUtils::formatIntTime($query_db_file[0]["file_update_time"]);
        
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
    
    public function handlerChildrenFile($file_detail)
    {
        $directories = array();   // 记录这层中文件夹的对象
        $files       = array();   // 记录需要处理的文件对象（包括文件夹）
        //
        // 文件夹，查询其子文件这一层数据
        //
        $db_children_files = MFiles::queryChildrenFilesByParentFileID($file_detail->from_id);
        if ($db_children_files === false)
        {
            throw new MFileopsException(
                        Yii::t('api','Internal Server Error'),
                        MConst::HTTP_CODE_500);
        }
        if (empty($db_children_files))
        {
            $p = $file_detail->file_path;
            return ; // 没有子文件，返回
        }
        //
        // 检查文件数量，复制数量限制在10000条内
        //
        if (count($db_children_files) > MConst::MAX_FILES_COUNT)
        {
            throw new MFileopsException(
                        Yii::t('api','Too many files or folders need to be copied'),
                        MConst::HTTP_CODE_406);
        }
        //
        // 转换数据
        //
        foreach ($db_children_files as $db_file)
        {
            $new_file_detail = new MFiles();
            //
            // 排除已被删除的对象
            //
            if ($db_file["is_deleted"] == true)
            {
                continue;
            }
            $this->assembleFileDetail(
                                        $db_file['file_name'],
                                        $file_detail->id,
                                        $new_file_detail, 
                                        $db_file);
            array_push($files, $new_file_detail);
            if ($db_file["file_type"] == MConst::OBJECT_TYPE_DIRECTORY)
            {
                array_push($directories, $new_file_detail);
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
            $query_db_directory = MFiles::queryFilesByPath(
                                                        $file->file_path);
            if ($query_db_directory === false || empty($query_db_directory))
            {
                throw new MFileopsException(
                    Yii::t('api','Not found the source files of the specified path'),
                    MConst::HTTP_CODE_404);
            }
            $file->id        = $query_db_directory[0]["id"];
            $this->handlerChildrenFile($file);
        }
    }
    
    /**
     * 处理组装复制文件需要的对象
     * @param string $file_name 文件名
     * @param object $file_detail     文件对象
     * @param array $query_db_file  数据库查询对象
     */
    public function assembleFileDetail(
                                        $file_name,
                                        $parent_file_id,
                                        $file_detail, 
                                        $query_db_file)
    {
        $file_path                          = $query_db_file["file_path"];
        $file_detail->file_type             = $query_db_file["file_type"];
        $event_action = MConst::CREATE_DIRECTORY;
        $file_detail->context               = $file_path;
        $file_detail->mime_type             = NULL;
        if ($file_detail->file_type == MConst::OBJECT_TYPE_FILE)
        {
            $file_detail->mime_type = CUtils::mime_content_type($file_name);
            $event_action = MConst::CREATE_FILE;
            $version_id  = $query_db_file["version_id"];
            $version     = MiniVersion::getInstance()->getVersion($version_id);
            if ($version != null) {
                $context = array( "hash"  => $version["file_signature"],
                                               "rev"   => (int)$version_id,
                                               "bytes" => (int)$query_db_file["file_size"],
                                               "update_time" => (int)$query_db_file["file_update_time"],
                                               "create_time" => (int)$query_db_file["file_create_time"] );
                $file_detail->context = serialize($context);
            }
        } elseif ($file_detail->file_type > MConst::OBJECT_TYPE_DIRECTORY) {
            $file_detail->file_type = 1;
        }
        
        $file_detail->from_id               = $query_db_file['id'];
        $file_detail->parent_file_id        = $parent_file_id;
        $file_detail->event_action          = $event_action;
        $file_detail->file_name             = $file_name;
        $file_detail->version_id            = $query_db_file["version_id"];
        $file_detail->file_size             = $query_db_file["file_size"];
        $file_detail->event_uuid            = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
        $index      = strlen($this->_from_path);
        $file_path  = substr_replace($file_path, $this->_to_path, 0, $index);
        $file_detail->from_path             = $file_path;
        $file_detail->file_path             = $file_path;
        $file_detail->file_create_time      = time();
        $file_detail->file_update_time      = time();
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