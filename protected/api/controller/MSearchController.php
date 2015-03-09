<?php
/**
 * Miniyun 处理查找数据
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MSearchController extends MApplicationComponent implements MIController {
    private $_root = null;
    private $_user_id = null;
    private $_locale = null;
    /**
     * 控制器执行主逻辑函数
     */
    public function invoke($uri = null) {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $params = $_REQUEST;
        // 检查参数
        if(isset($params) === false || $params == null) {
            throw new MAuthorizationException(Yii::t('api','Invalid parameters'));
        }
        $url_manager = new MUrlManager();
        $path = MUtils::convertStandardPath($url_manager->parsePathFromUrl($uri));
        $root = $url_manager->parseRootFromUrl($uri);
        // 去掉问号后面参数
        if($pos = strpos($this->_root, '?')) {
            $this->_root = substr($this->_root, 0, $pos);
        }
        if($pos = strpos($path, '?')) {
            $path = substr($path, 0, $pos);
        }
        // 获取用户数据，如user_id
        $user           = MUserManager::getInstance()->getCurrentUser();
        $this->_user_id = $user["user_id"];
        $query = "";
        if(isset($params["query"])) {
            $query = $params["query"];
        }
        if($query === "") {
            throw new MFileopsException(Yii::t('api','bad request 15'), MConst::HTTP_CODE_400);
        }
        $file_limit = 10000;
        if(isset($params["file_limit"])) {
            $file_limit = $params["file_limit"];
        }
        $include_deleted = false; // 处理删除的同样也需要返回
//        if(isset($params["include_deleted"])) {
//            $include_deleted = $params["include_deleted"];
//            if(is_string($include_deleted) === true) {
//                if(strtolower($include_deleted) === "false") {
//                    $include_deleted = false;
//                } elseif(strtolower($include_deleted) === "true") {
//                    $include_deleted = true;
//                }
//            }
//        }
        $this->_locale = "bytes";
        if(isset($params["locale"])) {
            $this->_locale = $params["locale"];
        }
        $callback     = null;
        if(isset($params["callback"])) {
            $callback = $params["callback"];
        }
        if(empty($path)){
            $path = '/'.$this->_user_id;
            $userFiles = array();
            $query_db_file = MFiles::searchFilesByPath($path, $query, $this->_user_id, $include_deleted);
            foreach($query_db_file as $db_file){
                if(($db_file['parent_file_id'] == 0) and ($db_file['file_type'] != 4) and ($db_file['file_type'] != 2)){
                    $userFiles[] = $db_file;
                }
            }
            $retval = $this->handleSearchRoot($path, $query);
            $files  = array_merge($retval,$userFiles);
        }else{
//            $files = array();
            $includeDeleted = false;
            $currentFile = MiniFile::getInstance()->getByPath($path);
//            if (empty($currentFile)){
//                throw new MFileopsException(Yii::t('api','not existed'),MConst::HTTP_CODE_404);
//            }
//            //查询文件类型
//            $version = MiniVersion::getInstance()->getVersion($currentFile["version_id"]);
//            $mimeType = null;
//            if ($version != NULL)
//            {
//                $currentFile["signature"] = $version["file_signature"];
//                $mimeType = $version["mime_type"];
//            }
//            // 组装子文件数据
            $childrenFiles = MiniFile::getInstance()->getChildrenByFileID(
                $parentFileId  = $currentFile['id'],
                $includeDeleted);
            $currentFileParts  = explode('/',$path);
            $currentFileUserId = $currentFileParts[1];
            $files = $childrenFiles;
//            $query = str_replace("%", "\\%", $query);
//            $sql = ' file_name like "%' . $query . '%"';
//            $sql = '';
//            $files = array();
//            foreach($childrenFiles as $childrenFile){
//                $condition = $sql . 'file_path="' . $childrenFile['file_path'] . '" ';
//                $file = MFiles::findAll($condition);
//                $files = array_merge($files,$file);
//            }

//            $contents = array();
//            if(!empty($childrenFiles)){
//                foreach($childrenFiles as $childrenFile){
//                    $content = array();
//                    $version = MiniVersion::getInstance()->getVersion($childrenFile["version_id"]);
//                    $mimeType = null;
//                    if ($version != NULL){
//                        $mimeType = $version["mime_type"];
//                        $childrenFile["signature"] = $version["file_signature"];
//                    }
//                    $content = $this->assembleResponse($content, $childrenFile, $mimeType);
//                    if(!empty($content) && $childrenFile['is_deleted'] == 0){
//                        array_push($contents, $content);
//                    }
//                }
//            }
//            $response['contents'] = $contents;
        }
        $result = array();
        $query = str_replace("%", "\\%", $query);
        $sql = ' file_name like "%' . $query . '%"';
        foreach($files as $file) {
            $condition = $sql . 'and file_path like"' . $file['file_path'] . '%" ';
            $file = MFiles::findAll($condition);
            if(empty($file)) {
                continue;
            }
            $result = array_merge($result, $file);
        }
//        $path        = "/{$this->_user_id}{$path}";
//        $path        = MUtils::convertStandardPath($path) . "/";

        // 查询其 信息
//        $operator = $this->_user_id;
//
//        $sharefilter = MSharesFilter::init();
//        $sharefilter->handlerCheck($this->_user_id, CUtils::removeUserFromPath($path));
//        if($sharefilter->is_shared) {
//            $operator = $sharefilter->master;
//            $qpath = '/' . $sharefilter->master . $sharefilter->_path;
//            $query_db_file = MFiles::searchFilesByPath($qpath, $query, $sharefilter->master, $include_deleted);
//
//            // 判断搜索出来的文件是否有权限访问
//            foreach($query_db_file as $index => $file) {
//                // 列表权限，如果没有列表权限，则不进行显示
//                try {
//                    $sharefilter->hasPermissionExecute($file['file_path'], MPrivilege::RESOURCE_READ);
//                }catch(Exception $e) {
//                    unset($query_db_file[$index]);
//                    continue;
//                }
//            }
//        } else {
//            $query_db_file = MFiles::searchFilesByPath($path, $query, $this->_user_id, $include_deleted);
//        }
        //
        // 查询根目录
        //
//        $retval = $this->handleSearchRoot($path, $query);
//        $query_db_file = array_merge($query_db_file, $retval);

        // if (count($query_db_file) > $file_limit)
        // {
        // throw new MFileopsException(
        // Yii::t('api','Too many file entries to return'),
        // MConst::HTTP_CODE_406);
        // }
//        $keys = array();
        $response = array();
        $filePaths = array();
        if(!empty($result)){
            foreach($result as $file){
                if(in_array($file['file_path'],$filePaths)){
                    continue;
                }
                array_push($filePaths,$file['file_path']);
                $item = array();
                $version = MiniVersion::getInstance()->getVersion($file["version_id"]);
                $mimeType = null;
                $signature = null;
                if ($version != NULL)
                {
                    $mimeType = $version["mime_type"];
                    $signature = $version["file_signature"];
                    $file["signature"] = $signature;
                }

                $item = $this->assembleResponse($item, $file, $mimeType);
                if(!empty($item)){
                    array_push($response, $item);
                }
            }
        }
//        foreach($query_db_file as $key => $db_file) {
//            if ($key >= $file_limit)
//                break;
//            $file_array = array();
//            $mime_type = null;
//            if($db_file["file_type"] == MConst::OBJECT_TYPE_FILE) {
//                $version = MiniVersion::getInstance()->getVersion($db_file["version_id"]);
//                if($version) {
//                    $mime_type = $version["mime_type"];
//                }
//            }
//            $file_array = $this->assembleResponse($file_array, $db_file, $mime_type);
//            #这里对数据进行了二次过滤，如果路径是一致的，则过滤掉
//            #TODO 这里的冗余数据初步分析是权限导致的，二次重构要去掉这个代码
//            $path = $file_array["path"];
//            if(!array_key_exists($path, $keys)){
//                $keys[$path] = $file_array;
//                array_push($response, $file_array);
//            }
//        }
        echo json_encode($response);
    }
    
    /**
     * 搜索公共目录，共享目录
     */
    public function  handleSearchRoot($path, $query) {
        $user = MUserManager::getInstance()->getCurrentUser();
        $sharedpaths = array();
        $publicFiles = MiniFile::getInstance()->getPublics();
        $groupShareFiles  = MiniGroupPrivilege::getInstance()->getAllGroups();
        $userShareFiles   = MiniUserPrivilege::getInstance()->getAllUserPrivilege($user["id"]);
        $shareFiles = array_merge($publicFiles,$groupShareFiles,$userShareFiles);
        foreach($shareFiles as $shareFile){
                $sharedpaths[] = $shareFile['file_path'];
        }
        $sharedpaths = array_unique($sharedpaths);
//        $path = MUtils::convertStandardPath($path);
        //
        // 搜索共享目录,根目录查询
        //
        if($path != '/' . $this->_user_id) {
            return array();
        }
//        $access = new SharesAccessFilter();
//        $sharedpaths = $access->handleGetAllSharesFolder($this->_user_id);
        $query = str_replace("%", "\\%", $query);
//        $sql = ' file_name like "%' . $query . '%"';
        $sql = '';
        $retval = array();
        foreach($sharedpaths as $sharedpath) {
            $condition = $sql . 'parent_file_id=0 and file_path="' . $sharedpath . '" ';
            $files = MFiles::findAll($condition);
            if(empty($files)) {
                continue;
            }
            $retval = array_merge($retval, $files);
        }
//        $var = apply_filters('documents_filter', null);
//        if(is_array($var) && !empty($var)) {
//            $ids = join(',', $var);
//            $sql = ' file_name like "%' . $query . '%"';
//            $sql .= ' and id in (' . $ids . ')';
//            $files = MFiles::findAll($sql);
//            $retval = array_merge($retval, $files);
//        }
//
//        // 判断搜索出来的文件是否有权限访问
//        $share_filter = MSharesFilter::init();
//        foreach($retval as $index => $ret) {
//            // 列表权限，如果没有列表权限，则不进行显示
//            if(MUtils::isShareFolder($ret['file_type'])) {
//                try {
//                    $share_filter->hasPermissionExecute($ret['file_path'], MPrivilege::RESOURCE_READ);
//                } catch(Exception $e) {
//                    unset($query_db_file[$index]);
//                    continue;
//                }
//            }
//        }
        
        return $retval;
    }
    
    /**
     * 处理组装请求元数据
     *
     */
    private function assembleResponse($response, $file, $mimeType) {
        $filePath                           = $file["file_path"];
        $response["size"]                   = MUtils::getSizeByLocale($this->_locale, $file["file_size"]);
        $response["bytes"]                  = (int)$file["file_size"];
        $response["path"]                   = $filePath;
        $response["modified"]               = MUtils::formatIntTime($file["file_update_time"]);
        $response["create_time"]            = $file["file_create_time"];
        $response["update_time"]            = $file["file_update_time"];
        $response["revision"]               = intval($file["version_id"]);
        $response["rev"]                    = strval($file["version_id"]);
        $response["root"]                   = $this->_root;
        $response["hash"]                   = !empty($file["signature"])? $file["signature"] : "";
        $response["event"]                  = $file["event_uuid"];
        $response["sort"]                   = (int)$file["sort"];
        //外链Key
        $response["share_key"]              = $file["share_key"];
        $response['is_dir'] = false;
//        if($file['file_type'] != 0){
        $permissionModel = new UserPermissionBiz($filePath,$this->_user_id);
        $permission = $permissionModel->getPermission($filePath,$this->_user_id);
        if(!empty($permission)){
            if(isset($permission['children_shared'])){
                $response['children_shared'] = true;
            }else{
                $response['share'] = $permission;
            }
            if(empty($permission['permission'])){
                return null;
            }
        }
//        }
        if ($file["file_type"] == MConst::OBJECT_TYPE_FILE){
            //支持类s3数据源的文件下载
            $data = array("hash" => $file["signature"]);
            $downloadParam = apply_filters("event_params", $data);
            if ($downloadParam !== $data){
                if (is_array($downloadParam)){
                    $response = array_merge($response, $downloadParam);
                }
            }
            $mimeType = MiniUtil::getMimeType($file['file_path']);
            $response["thumb_exists"]       = MUtils::isExistThumbnail($mimeType, (int)$file["file_size"]);
        }else{
            $response['is_dir'] = true;
        }
        if ($file["file_type"] > MConst::OBJECT_TYPE_FILE) {
            $response["type"] = (int)$file["file_type"];
        }
        if (!empty($mimeType)){
            $response["mime_type"]     = $mimeType;
        }
        if ($file["is_deleted"] == true){
            $response["is_deleted"]    = true;
        }
        // 添加hook，修改meta值
        $response = apply_filters('meta_add', $response);
        return $response;
    }
} 