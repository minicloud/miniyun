<?php
/**
 * Miniyun delete服务主要入口地址, 删除文件/夹
 * 文件目前关联2张数据表：miniyun_files, miniyun_events
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MDeleteController extends MApplicationComponent implements MIController
{
    private $_user_id = null;
    private $_user_device_name = null;
    public  $isOutput = true;
    public  $completely_remove  = false; // 是否放入回收箱
    public  $result   = array();
    /**
     * 控制器执行主逻辑函数, 处理删除文件或者文件夹
     *
     * @return mixed $value 返回最终需要执行完的结果
     * 
     * @since 1.0.7
     */
    public function invoke($uri=null)
    {
        $this->setAction(MConst::DELETE);
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $params = $_REQUEST;
        // 检查参数
        if (isset($params) === false) {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request 8'),
                                        MConst::HTTP_CODE_400);
        }
        //
        // 获取用户数据，如user_id
        $user   = MUserManager::getInstance()->getCurrentUser();
        $device = MUserManager::getInstance()->getCurrentDevice();
        $this->_user_id             = $user["user_id"];
        $user_nick                  = $user["user_name"];
        $user_device_id             = $device["device_id"];
        $this->_user_device_name    = $device["user_device_name"];

        // 文件大小格式化参数
        $locale = "bytes";
        if (isset($params["locale"])) {
            $locale = $params["locale"];
        }
        if (isset($params["root"]) === false || isset($params["path"]) === false) {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request 9'),
                                        MConst::HTTP_CODE_400);
        }
        $root = $params["root"];
        $path = $params["path"];
        $isDir= $params["is_dir"];
        $pathArr = explode('/',$path);
        if($path == '/' || empty($pathArr[2]) || empty($pathArr[1])){
            return;
        }
        if($isDir){//避免人为添加删除目录
            $arr = explode('/',$path);
            $isRoot = false;
            $isMine = false;
            if(count($arr)==3){
                $isRoot = true;
            }
            $fileOwnerId = $arr[1];
            $currentUserId = $this->_user_id;
            if($fileOwnerId==$currentUserId ){
                $isMine = true;
            }
            if($isRoot&&!$isMine){//如果是在根目录下且不是自己的目录 则后台控制不准取消共享
                throw new MFileopsException(
                    Yii::t('api','Internal Server Error'),
                    MConst::HTTP_CODE_409);
            }
        }

        //
        // 转换路径分隔符，便于以后跨平台，如：将 "\"=>"/"
        //
        $path = MUtils::convertStandardPath($path);
        if ($path == "" || $path == "/" || $path === false)
        {
            throw new MFileopsException(
                        Yii::t('api','Bad request 10'),
                                MConst::HTTP_CODE_400);
        }

        // 检查是否是共享目录
        $share_filter = MSharesFilter::init();
        if ($share_filter->handlerCheck($this->_user_id, $path)) {
            $this->_user_id = $share_filter->master;
            $path           = $share_filter->_path;
        }
        //
        // 如果删除的是共享目录，则转到ShareManager处理
        //
        if ($share_filter->_is_shared_path
            && $share_filter->operator != $share_filter->master) {
            $file = MFiles::queryFilesByPath("/".$share_filter->operator . $share_filter->src_path);
            if (!$file) {
                throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
            }
            $id   = $file[0]["id"];
            $handler = new ShareManager();
            $handler->_userId = $share_filter->operator;
            $handler->_id     = $id;
            try {
                $handler->invoke(ShareManager::CANCEL_SHARED);
            } catch (Exception $e) {
                throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
            }

            // 输出返回值
            $path = MUtils::convertStandardPath($share_filter->src_path);
            $this->buildResult(
                                $root, $path, $handler->_file["version_id"],
                                $handler->_file["file_update_time"], true);
            return ;
        }
        if ($share_filter->_is_shared_path
            && $share_filter->operator != $share_filter->master) {
            throw new MException(
                    Yii::t('api','You do not have permission to perform the delete operation.'),
                    MConst::HTTP_CODE_409);
        }

        //
        // 组装对象信息
        //
        $file_name   = MUtils::get_basename($path);
        $file_detail = new MFiles();
        $file_detail->file_name               = $file_name;
        $file_detail->file_path               = $path;
        //
        // 查询其目录信息,是否存在
        //
        $query_db_file = MFiles::queryFilesByPath($file_detail->file_path);
        //数据已不存在
        if (count($query_db_file) <= 0)
        {
            throw new MFileopsException(
                        Yii::t('api','Not found the source files of the specified path'),
                                MConst::HTTP_CODE_404);
        }

        $data = array("obj"=>$this, "share_filter"=>$share_filter, "query_db_file"=>$query_db_file[0]);
        //进行删除之前的检测
        do_action("before_delete_check", $data);

        //在共享文件夹中进行删除权限判断
        if ($share_filter->is_shared && $query_db_file[0]["file_type"] != MConst::OBJECT_TYPE_BESHARED) {
            if ($query_db_file[0]["file_type"] == 0){  //文件删除
                $share_filter->hasPermissionExecute($query_db_file[0]["file_path"], MPrivilege::FILE_DELETE);
            } else {                                   //文件夹删除
                $share_filter->hasPermissionExecute($query_db_file[0]["file_path"], MPrivilege::FOLDER_DELETE);
            }
        }
        
        //
        // 可以删除包含子文件的目录
        // 检查其是否为文件夹
        //
        $files                = array();
        $file_detail->is_dir  = false;
        $file_detail->id      = $query_db_file[0]["id"];
        $file_detail->file_size      = $query_db_file[0]["file_size"];
        $file_detail->file_type = $query_db_file[0]["file_type"];
        if ($query_db_file[0]["file_type"] > MConst::OBJECT_TYPE_FILE )
        {
            $file_detail->is_dir  = true;
            $files = $this->handleChildrenFile($file_detail->file_path, $files);
        }
        else
        {
            // 处理加入版本历史
            $this->handleFileMeta(
                                  $file_detail->file_path, 
                                  $query_db_file[0]["version_id"], 
                                  $this->_user_id, 
                                  $user_nick,
                                  $this->_user_device_name,
                                  $file_detail->file_size
                                  );
        }
        $isSharedPath = false;
        $pathArr = explode('/',$file_detail->file_path);
        $masterId = $pathArr[1];
        if($masterId!=$this->_user_id){
            $isSharedPath = true;
        }else{
            $model = new GeneralFolderPermissionBiz($file_detail->file_path);
            if($model->isParentShared($file_detail->file_path)){//如果是父目录被共享
                $isSharedPath = true;
            }
        }
        if($isSharedPath){
            $authority = new UserPermissionBiz($file_detail->file_path,$user["user_id"]);
            $permissionArr = $authority->authority;
            $permission = $permissionArr['permission'];
            if(!empty($permission)){
                $privilegeModel = new PrivilegeBiz();
                $share_filter->slaves =$privilegeModel->getSlaveIdsByPath($permissionArr['share_root_path']);
                $share_filter->is_shared = true;
                if($file_detail->file_type==0){//删除文件
                    $can_file_delete = substr($permission,7,1);
                    if($can_file_delete==0){
                        throw new MFileopsException(
                            Yii::t('api','no permission'),
                            MConst::HTTP_CODE_409);

                    }
                }
                if($file_detail->file_type==1||$file_detail->file_type==2||$file_detail->file_type==4){
                    $can_folder_delete = substr($permission,3,1);
                    if($can_folder_delete==0){
                        throw new MFileopsException(
                            Yii::t('api','no permission'),
                            MConst::HTTP_CODE_409);
                    }
                }
            }

        }

        //
        // 更新文件元数据的为删除数据
        //
        $this->assembleFileDetail($file_detail, $query_db_file[0]);
        $ret_value = MFiles::updateRemoveFileDetail($file_detail);
        if ($ret_value === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        
        //
        // 将删除目录加入数组
        //
        array_push($files, $file_detail);
        //
        // 保存事件
        //
        $ret_value = MiniEvent::getInstance()->createEvents($this->_user_id, $user_device_id, $files, $share_filter->type);
        if ($ret_value === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        
        //
        // 
        //
        if ($share_filter->is_shared) {
            foreach ($files as  $file) {
                $share_filter->handlerAction($file->event_action, $user_device_id, $file->from_path, $file->context);
            }
        }
        
        //
        // 删除共享目录(删除共享目录，对应的权限也一起删除)
        //
        //首先判断用户有无删除权限

        $userPrivilegeList = MiniUserPrivilege::getInstance()->getPrivilegeList($file_detail->file_path);
        $groupPrivilegeList = MiniGroupPrivilege::getInstance()->getPrivilegeList($file_detail->file_path);
        if(!empty($userPrivilegeList)){
            MiniUserPrivilege::getInstance()->deleteByFilePath($file_detail->file_path);
        }
        if(!empty($groupPrivilegeList)){
            MiniGroupPrivilege::getInstance()->deleteByFilePath($file_detail->file_path);
        }
        //并且将file_type改为1
        if($file_detail->file_type==0){
            MiniFile::getInstance()->togetherShareFile($file_detail->file_path,Mconst::OBJECT_TYPE_FILE);
        }else{
            MiniFile::getInstance()->togetherShareFile($file_detail->file_path,Mconst::OBJECT_TYPE_DIRECTORY);
        }
        if ($filter !== true && $share_filter->_is_shared_path
            && $share_filter->operator == $share_filter->master) {
            $file = MFiles::queryFilesByPath("/".$share_filter->operator . $path, true);
            if (!$file) {
                throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
            }
            $id   = $file[0]["id"];
            $handler = new ShareManager();
            $handler->_userId = $share_filter->operator;
            $handler->_id     = $id;
            try {
                $handler->invoke(ShareManager::CANCEL_SHARED);
            } catch (Exception $e) {
                throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
            }

        }
        // 如果彻底删除，则调用回收站
        if ($this->completely_remove) {
            $trash = new Trash();
            $trash->_userId = $this->_user_id;
            $trash->fromIds = $file_detail->id;
            try {
                $trash->invoke(Trash::DELETE);
            } catch (Exception $e) {
                throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
            }
            //执行的额外操作
            $this->extend($share_filter, $query_db_file, $file_detail);
            return;
        }
        
        $path                       = CUtils::removeUserFromPath($query_db_file[0]["file_path"]);
        $path_info                  = MUtils::pathinfo_utf($path);
        $path_info_out = MUtils::pathinfo_utf($share_filter->src_path);
        $path = MUtils::convertStandardPath($path_info_out['dirname'] . "/" . $path_info['basename']);

        //执行的额外操作
        $this->extend($share_filter, $query_db_file, $file_detail);

        $this->buildResult(
                            $root, $path, $query_db_file[0]["version_id"], 
                            $query_db_file[0]["file_update_time"], 
                            $file_detail->is_dir);
    }

    /**
     * 处理返回值组装
     * @param string $root
     * @param string $path
     * @param integer $version_id
     * @param string $modified
     * @param boolean $is_dir
     */
    public function buildResult($root, $path, $version_id, $modified, $is_dir)
    {
        // 处理不同端，不同返回值
        if ( MUserManager::getInstance()->isWeb() === true)
        {
            $this->buildWebResponse();
            return ;
        }
        $response                   = array();
        $response["size"]           = "0";
        $response["is_deleted"]     = true;
        $response["bytes"]          = 0;
        $response["thumb_exists"]   = false;
        $response["path"]           = $path;
        $response["root"]           = $root;
        $response["is_dir"]         = $is_dir;
        $response["rev"]            = strval($version_id);
        $response["revision"]       = intval($version_id);
        $response["modified"]       = MUtils::formatIntTime($modified);
        if ($is_dir === false)
        {
            $response["mime_type"]      = "text/plain";
        }
        if (!$this->isOutput) {
            return $response;
        }
        echo json_encode($response);
    }
    
    /**
     * web端返回值
     */
    public function buildWebResponse()
    {
        $this->result["state"]    = true;
        $this->result["code"]     = 0;
        $this->result["msg"]      = Yii::t('api_message', 'delete_success');
        $this->result["msg_code"] = "";
        $this->result["data"]     = array("d" => true);
    }
    
    /**
     * 处理删除子文件
     * @param int $user_id
     * @param string $parent_path  文件路径，传入路径中已经加入用户id："/".$this->_user_id.$parent_path
     * @param array $files
     * @throws MFileopsException
     * @return mixed $value 返回最终需要执行完的结果
     * 
     * @since 1.0.7
     */
    private function handleChildrenFile($parent_path, $files)
    {
        //
        // 查询所有子文件, 不包含已删除的
        //
        $db_children_files = MFiles::queryChildrenFilesByPath($parent_path, true, false);
        if ($db_children_files === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        //
        // 检查文件数量，复制数量限制在10000条内
        //
        if (count($db_children_files) > MConst::MAX_FILES_COUNT)
        {
            throw new MFileopsException(
                                        Yii::t('api','Too many files or folders need to be deleted'),
                                        MConst::HTTP_CODE_406);
        }
        //
        // 转换数据
        //
        foreach ($db_children_files as $db_file)
        {
            $file_detail = new MFiles();
            //
            // 排除已被删除的对象
            //
            if ($db_file["is_deleted"] == true)
            {
                continue;
            }
            $this->assembleFileDetail(
                                        $file_detail, 
                                        $db_file);
            array_push($files, $file_detail);
            //
            // 处理共享文件夹
            //
            if ($db_file['file_type'] >= MConst::OBJECT_TYPE_SHARED) {
                $handler          = new ShareManager();
                $handler->_userId = $db_file['user_id'];
                $handler->_id     = $db_file['id'];
                try {
                    $handler->invoke(ShareManager::CANCEL_SHARED);
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        //
        // 更新其为删除状态
        //
        $ret_value = MFiles::updateRemoveChildrenFile($parent_path);
        if ($ret_value === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        return $files;
    }
    
    /**
     * 处理添加当前文件记录的版本
     * @param string $file_path
     * @param int $version_id
     * @param string $user_nick
     */
    private function handleFileMeta($file_path, $version_id, $user_id, $user_nick, $deviceName, $fileSize)
    {
        //
        // 查询之前的版本
        //
        $file_meta = MFileMetas::queryFileMeta($file_path, MConst::VERSION);
        if ($file_meta)
        {
            $meta_value = MUtils::getFileVersions(
                                                    $deviceName,
                                                    $fileSize,
                                                    $version_id, 
                                                    MConst::DELETE, 
                                                    $user_id, 
                                                    $user_nick,
                                                    $file_meta[0]["meta_value"]);
            $ret = MFileMetas::updateFileMeta(
                                        $file_meta[0]["file_path"], 
                                        MConst::VERSION, 
                                        $meta_value);
        }
        else 
        {
            $meta_value = MUtils::getFileVersions(
                                                    $deviceName,
                                                    $fileSize,
                                                    $version_id, 
                                                    MConst::DELETE, 
                                                    $user_id, 
                                                    $user_nick);
            $ret = MFileMetas::createFileMeta(
                                        $file_path, 
                                        MConst::VERSION, 
                                        $meta_value);
        }
        return $ret;
    }
    
    /**
     * 处理删除文件，数据组装
     * @param $file_detail    文件对象
     * @param $query_db_file  数据库查询对象
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function assembleFileDetail( $file_detail, $query_db_file )
    {
        $file_detail->event_action = MConst::DELETE;
        $file_detail->file_name    = $query_db_file["file_name"];
        $file_detail->from_path    = $query_db_file["file_path"];
        $file_detail->context      = $query_db_file["file_path"];
        $file_detail->event_uuid   = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
    }
    
    /**
     * 
     * 执行的额外操作
     * 
     * @since 1.0.7
     */
    public function extend($share_filter, $query_db_file, $file_detail){
        //执行完删除操作后执行的额外事物
        $after                = new MDeleteAfter();
        $after->share_filter  = $share_filter;
        $after->query_db_file = $query_db_file;
        $after->file_detail   = $file_detail;
        $after->execute();
    }
}