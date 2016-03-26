<?php
/**
 * Miniyun 处理获取数据
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MMetadataController extends MApplicationComponent implements MIController{
	
    private $root      = null;
    private $userId   = null;
    private $locale    = null;

    /**
     * 控制器执行主逻辑函数
     * @param null $uri
     * @param null $absolutePath
     * @throws MAuthorizationException
     * @throws MFileopsException
     */
    public function invoke($uri=null,$absolutePath=null)
    {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $params = $_REQUEST;
        // 检查参数
        if (isset($params) === false || $params == null) {
            throw new MAuthorizationException(Yii::t('api','Invalid parameters'));
        }
        // 获取用户数据，如user_id
        $user             = MUserManager::getInstance()->getCurrentUser();
        $this->userId     = $user["user_id"];

        $includeDeleted    = false; // 处理删除的同样也需要返回
        if (isset($params["include_deleted"])) {
            $includeDeleted = $params["include_deleted"];
        }
        $this->locale      = "bytes";
        if (isset($params["locale"])) {
            $this->locale  = $params["locale"];
        }
        $includeDeleted    = MUtils::convertToBool($includeDeleted);
        $urlManager        = new MUrlManager();
        $path              = MUtils::convertStandardPath($urlManager->parsePathFromUrl($uri));

        $this->root = $urlManager->parseRootFromUrl($path);
        if($path===false){
        	$path = "/";
        }
        $pathPart = explode('/',$path);  
        // 根目录
        if (count($pathPart) <= 2){
            //个人空间根目录
            $response = $this->handleRootPath($includeDeleted);
            echo json_encode($response);exit;
        }else{
            if(sizeof($pathPart)>2){
                $key = $pathPart[2];            
                if($key==='公共空间'){
                    // 公共空间
                    $response = $this->handlePublicSpace($includeDeleted);
                    echo json_encode($response);exit;
                }else if($key==='共享空间'){
                    // 共享空间
                    $response = $this->handleShareSpace($path,$includeDeleted);
                    echo json_encode($response);exit;
                }else if($key==='群空间'){
                    // 群空间
                    $response = $this->handleGroupSpace($includeDeleted);
                    echo json_encode($response);exit;
                }
            }
            //个人空间非根目录
            $response = $this->handleNotRootPath($path,$includeDeleted);
            echo json_encode($response);exit;
        }        
    }
    /**
     * 处理公共空间
     * @param $includeDeleted 
     */
    private function handlePublicSpace($includeDeleted)
    {
        $response                           = array();
        $response["size"]                   = MUtils::getSizeByLocale($this->locale, 0);
        $response["bytes"]                  = 0;
        $response["path"]                   = "/";
        $response["modified"]               = MUtils::formatIntTime(time());
        $response["revision"]               = 0;
        $response["rev"]                    = "0";
        $response["root"]                   = $this->root;
        $response["is_deleted"]             = false;
        $response["is_dir"]                 = true;
        $response["hash"]                   = "";
        $contents         = array();
        $user             = MUserManager::getInstance()->getCurrentUser();
        $publicFiles      = MiniFile::getInstance()->getPublics(); 
        $filePaths        = array(); 
        $fileData       = array_merge($publicFiles); 

        //如果没有文件记录
        if (empty($fileData)){
            $response["contents"] = $contents;
            return $response;
        }
        foreach($fileData as $file){
            $file = MiniFile::getInstance()->getByPath($file['file_path']); 
            if(!empty($file)){
                if((($file['parent_file_id'] == 0) && $file['is_deleted'] == 0) || (($file['file_type'] == 2)&&($file['user_id'] != $this->userId))){
                    $filePaths[] = $file['file_path'];
                }
            }
        }
        $filePaths    = array_unique($filePaths);
        $userMetaData = MiniUserMeta::getInstance()->getUserMetas($this->userId);
        $userHidePaths = '';
        if(!empty($userMetaData['user_hide_path'])){
            $userHidePaths = unserialize($userMetaData['user_hide_path']);
        }
        // 组装子文件数据
        foreach($filePaths as $filePath){
            $file = MiniFile::getInstance()->getByFilePath($filePath);
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
                if(in_array($filePath,$userHidePaths)){
                    $item['is_hide_path'] = true;
                }else{
                    $item['is_hide_path'] = false;
                }
                array_push($contents, $item);
            }
        }
        $response["contents"] = $contents;
        return $response;
    }
    /**
     * 处理共享空间
     * @param $includeDeleted 
     */
    private function handleShareSpace($path,$includeDeleted)
    {
        $pathPart = explode('/',$path); 
        $contents         = array(); 
        if(sizeof($pathPart)===3){
            //共享空间第一层目录：用户列表
            $user = MUserManager::getInstance()->getCurrentUser();
            $shareFiles   = MiniUserPrivilege::getInstance()->getAllUserPrivilege($user["id"]);
            $ids = array();
            foreach($shareFiles as $file){
                $filePath = $file['file_path'];
                //剔除群空间列表
                $isInGroupSpace = MiniFileMeta::getInstance()->isInGroupSpace($filePath); 
                if($isInGroupSpace){
                    continue;
                }
                $pathArr = explode('/',$filePath); 
                $userId = $pathArr[1];
                $ids[$userId] = $userId; 
            }   
            //把用户名换成目录名称
            foreach($ids as $userId){ 
                $user = MiniUser::getInstance()->getById($userId); 
                $item['lock']                   = false;
                $item["size"]                   = MUtils::getSizeByLocale($this->locale, 0);
                $item["bytes"]                  = 0;
                $item["path"]                   = $path.'/'.$user["user_name"]; 
                $item["file_name_pinyin"]       = '';
                $item["modified"]               = MUtils::formatIntTime(strtotime($user["created_at"]));
                $item["create_time"]            = strtotime($user["created_at"]).'';
                $item["update_time"]            = strtotime($user["updated_at"]).'';
                $item["revision"]               = 0;
                $item["rev"]                    = '0';
                $item["root"]                   = $this->root;
                $item["hash"]                   = "";
                $item["event"]                  = '';
                $item["sort"]                   = 0;
                $item["share_key"]              = '';
                $item["is_dir"]                 = true;
                
                $item['share']['permission']='111111111';
                $item['share']['share_user_nick']=$user["nick"];
                $item['share']['is_public_folder']=false;
                $item['share']['can_set_share']=0;
                $item['share']['share_model']='normal';
                $item['canDelete']=false;
                $item['type']=2;
                $item['is_hide_path']=false;
                array_push($contents, $item);
            }
        }
        if(sizeof($pathPart)===4)
        {
            $userName = $pathPart[3]; 
            $currentUser = MiniUser::getInstance()->getUserByName($userName);
            //共享空间第二层目录：共享目录
            $user = MUserManager::getInstance()->getCurrentUser();
            $shareFiles   = MiniUserPrivilege::getInstance()->getAllUserPrivilege($user["id"]);
            $fileData = array(); 
            foreach($shareFiles as $file){
                $filePath = $file['file_path'];
                //剔除群空间列表
                $isInGroupSpace = MiniFileMeta::getInstance()->isInGroupSpace($filePath); 
                if($isInGroupSpace){
                    continue;
                }
                $pathArr = explode('/',$filePath); 
                $userId = $pathArr[1];
                if($currentUser['id']==$userId){
                    array_push($fileData,$file);
                }
            }    
            foreach($fileData as $file){
                $file = MiniFile::getInstance()->getByPath($file['file_path']); 
                if(!empty($file)){
                    if((($file['parent_file_id'] == 0) && $file['is_deleted'] == 0) || (($file['file_type'] == 2)&&($file['user_id'] != $this->userId))){
                        $filePaths[] = $file['file_path'];
                    }
                }
            }
            $filePaths    = array_unique($filePaths);
            $userMetaData = MiniUserMeta::getInstance()->getUserMetas($this->userId);
            $userHidePaths = '';
            if(!empty($userMetaData['user_hide_path'])){
                $userHidePaths = unserialize($userMetaData['user_hide_path']);
            }
            // 组装子文件数据
            foreach($filePaths as $filePath){
                $file = MiniFile::getInstance()->getByFilePath($filePath);
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
                    if(in_array($filePath,$userHidePaths)){
                        $item['is_hide_path'] = true;
                    }else{
                        $item['is_hide_path'] = false;
                    }
                    array_push($contents, $item);
                }
            }
        } 
        $response                           = array();
        $response["size"]                   = MUtils::getSizeByLocale($this->locale, 0);
        $response["bytes"]                  = 0;
        $response["path"]                   = "/";
        $response["modified"]               = MUtils::formatIntTime(time());
        $response["revision"]               = 0;
        $response["rev"]                    = "0";
        $response["root"]                   = $this->root;
        $response["is_deleted"]             = false;
        $response["is_dir"]                 = true;
        $response["hash"]                   = "";        
        $response["contents"] = $contents;
        return $response;
    }
    /**
     * 处理群空间
     * @param $includeDeleted 
     */
    private function handleGroupSpace($path,$includeDeleted)
    {
        $contents         = array();     
        $user = MUserManager::getInstance()->getCurrentUser(); 
        //共享者身份获得群空间列表
        $fileData = MiniFile::getInstance()->getGroupSpaceList($user['id']);
        //被共享者身份获得群空间列表
        $shareFiles   = MiniUserPrivilege::getInstance()->getAllUserPrivilege($user["id"]);        
        foreach($shareFiles as $file){
            $filePath = $file['file_path']; 
            //判断该文件是否是群空间的文件
            $isInGroupSpace = MiniFileMeta::getInstance()->isInGroupSpace($filePath); 
            if($isInGroupSpace){
                array_push($fileData, $file);
            }
        }   

        foreach($fileData as $file){
            $file = MiniFile::getInstance()->getByPath($file['file_path']); 
            if(!empty($file)){
                if((($file['parent_file_id'] == 0) && $file['is_deleted'] == 0) || (($file['file_type'] == 2)&&($file['user_id'] != $this->userId))){
                    $filePaths[] = $file['file_path'];
                }
            }
        }
        $filePaths    = array_unique($filePaths);
        $userMetaData = MiniUserMeta::getInstance()->getUserMetas($this->userId);
        $userHidePaths = '';
        if(!empty($userMetaData['user_hide_path'])){
            $userHidePaths = unserialize($userMetaData['user_hide_path']);
        }
        // 组装子文件数据
        foreach($filePaths as $filePath){
            $file = MiniFile::getInstance()->getByFilePath($filePath);
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
                if(in_array($filePath,$userHidePaths)){
                    $item['is_hide_path'] = true;
                }else{
                    $item['is_hide_path'] = false;
                }
                array_push($contents, $item);
            }
        } 
        $response                           = array();
        $response["size"]                   = MUtils::getSizeByLocale($this->locale, 0);
        $response["bytes"]                  = 0;
        $response["path"]                   = "/";
        $response["modified"]               = MUtils::formatIntTime(time());
        $response["revision"]               = 0;
        $response["rev"]                    = "0";
        $response["root"]                   = $this->root;
        $response["is_deleted"]             = false;
        $response["is_dir"]                 = true;
        $response["hash"]                   = "";        
        $response["contents"] = $contents;
        return $response;
    }
    /**
     * 处理根目录下文件查询
     * @param $includeDeleted
     * @return array
     */
    private  function  handleRootPath($includeDeleted)
    {
        $response                           = array();
        $response["size"]                   = MUtils::getSizeByLocale($this->locale, 0);
        $response["bytes"]                  = 0;
        $response["path"]                   = "/";
        $response["modified"]               = MUtils::formatIntTime(time());
        $response["revision"]               = 0;
        $response["rev"]                    = "0";
        $response["root"]                   = $this->root;
        $response["is_deleted"]             = false;
        $response["is_dir"]                 = true;
        $response["hash"]                   = "";
        $contents         = array();
        $user             = MUserManager::getInstance()->getCurrentUser(); 
        $filePaths        = array();  
        $userFiles        = MiniFile::getInstance()->getChildrenByFileID(
                                                                    $parentFileId=0,
                                                                    $includeDeleted,
                                                                    $user,
                                                                    $this->userId);  
        foreach($userFiles as $file){ 
            //把隐藏空间目录直接隐藏
            // if($file['file_name']==='隐藏空间' || $file['file_name']==='共享空间' || $file['file_name']==='群空间' || $file['file_name']==='公共空间'){
            //     continue;
            // }
            if(!empty($file)){
                if((($file['parent_file_id'] == 0) && $file['is_deleted'] == 0) || (($file['file_type'] == 2)&&($file['user_id'] != $this->userId))){
                    $filePaths[] = $file['file_path'];
                }
            }
        }
        $filePaths    = array_unique($filePaths); 
        $userMetaData = MiniUserMeta::getInstance()->getUserMetas($this->userId);
        $userHidePaths = '';
        if(!empty($userMetaData['user_hide_path'])){
            $userHidePaths = unserialize($userMetaData['user_hide_path']);
        }
        // 组装子文件数据
        foreach($filePaths as $filePath){
            $file = MiniFile::getInstance()->getByFilePath($filePath);
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
                if(in_array($filePath,$userHidePaths)){
                    $item['is_hide_path'] = true;
                }else{
                    $item['is_hide_path'] = false;
                }
                array_push($contents, $item);
            }
        }
        $response["contents"] = $contents;
        return $response;
    }
    /**
     * 获得共享模式，普通模式or高级模式
     * @param $filePath 
     */
    private function getShareModel($filePath){
        //读取共享模式，默认是普通模式，否则是高级模式
        //高级模式下有多重权限控制
        $shareModel = "normal";
        $shareModelItem = MiniFileMeta::getInstance()->getFileMeta($filePath,'share_model');
        if(!empty($shareModelItem)){
            if($shareModelItem["meta_value"]=="advance"){
                $shareModel = "advance";
            }                    
        }
        return $shareModel;
    }
    /**
     * 处理非根目录下文件查询
     * @param $path
     * @param $includeDeleted
     * @return array
     * @throws MFileopsException
     */
    private function handleNotRootPath($path, $includeDeleted)
    {
        // 查询其是否存在信息
        $currentFile = MiniFile::getInstance()->getByPath($path);
        $pathParts = pathinfo($path);  
        //判断隐藏空间子目录访问是否合法
        if($pathParts['basename']==='隐藏空间' && $currentFile['parent_file_id']==='0'){
            if(!array_key_exists('valid_hide_space_passwd', $_SESSION)){
                throw new MFileopsException(Yii::t('api','not permission'),MConst::HTTP_CODE_404);
            }
        }
        if (empty($currentFile)){
            throw new MFileopsException(Yii::t('api','not existed'),MConst::HTTP_CODE_404);
        }
        //查询文件类型
        $version = MiniVersion::getInstance()->getVersion($currentFile["version_id"]);
        $mimeType = null;
        if ($version != NULL)
        {
            $currentFile["signature"] = $version["file_signature"];
            $mimeType = $version["mime_type"];
        }
        $response              = array();
        $shareKeyPrivilege     = MiniFile::getInstance()->getFileExtendProperty($currentFile);
        $response['share_key'] = $shareKeyPrivilege['share_key'];
        $response = $this->assembleResponse($response, $currentFile, $mimeType);
        // 组装子文件数据
        $childrenFiles = MiniFile::getInstance()->getChildrenByFileID(
        $parentFileId  = $currentFile['id'],
        $includeDeleted);
        $contents = array();
        if(!empty($childrenFiles)){
            foreach($childrenFiles as $childrenFile){
                $childrenFileMeta = MiniFileMeta::getInstance()->getFileMeta($childrenFile['file_path'],'create_id');
                if(!empty($childrenFileMeta)){
                    $filePathArr  = explode('/',$childrenFile['file_path']);
                    $fileOwnerId  = $filePathArr[1];
                    $childrenFileCreateId = $childrenFileMeta['meta_value'];
                    $currentUser     = Yii::app()->session["user"];
                    if((int)$fileOwnerId !== (int)$currentUser['user_id']){//当前目录不为当前用户所有（共享目录/公共目录）
                        if($response['share']['permission']==='000000000'){
                            continue;
                        }
                        $readPrivilege = substr($response['share']['permission'],0,1);
                        if($readPrivilege === '0'){//如果父目录没有只读权限
                            if($childrenFileCreateId!=$currentUser['user_id']){//当没有只读权限时，过滤(用户只能看见共享目录中自己的文件)
                                continue;
                            }
                        } 
                    }
                }
                $content = array();
                $version = MiniVersion::getInstance()->getVersion($childrenFile["version_id"]);
                $mimeType = null;
                if ($version != NULL){
                    $mimeType = $version["mime_type"];
                    $childrenFile["signature"] = $version["file_signature"];
                }
                $content = $this->assembleResponse($content, $childrenFile, $mimeType);
                if(!empty($content) && $childrenFile['is_deleted'] == 0){
                    array_push($contents, $content);
                }
            }
        }
        $response['contents'] = $contents;
        return $response;
    }
    /**
     * 处理组装请求元数据
     * @param $response
     * @param $file
     * @param $mimeType
     * @return mixed
     */
    private function assembleResponse($response, $file, $mimeType)
    {
        $filePath                           = $file["file_path"];
        $result                             = LockBiz::getInstance()->status($filePath);
        $response['lock']                   = $result["success"];
        $response["size"]                   = MUtils::getSizeByLocale($this->locale, $file["file_size"]);
        $response["bytes"]                  = (int)$file["file_size"];
        $response["path"]                   = $filePath;
        $response["file_name_pinyin"]       = $file['file_name_pinyin'];
        $response["modified"]               = MUtils::formatIntTime($file["file_update_time"]);
        $response["create_time"]            = $file["file_create_time"];
        $response["update_time"]            = $file["file_update_time"];
        $response["revision"]               = intval($file["version_id"]);
        $response["rev"]                    = strval($file["version_id"]);
        $response["root"]                   = $this->root;
        $response["hash"]                   = !empty($file["signature"])? $file["signature"] : "";
        $response["event"]                  = $file["event_uuid"];
        $response["sort"]                   = (int)$file["sort"];
        //外链Key

        $link = MiniLink::getInstance()->getByFileId($file['id']);
        if(empty($link['share_key'])){
            $response["share_key"] = '';
        }else{
            $response["share_key"] = $link['share_key'];
        }
        $response['is_dir'] = false;
        $permission = UserPermissionBiz::getInstance()->getPermission($filePath,$this->userId);
        if(!empty($permission)){
            if(isset($permission['children_shared'])){
                $response['children_shared'] = true;
            }else{
                $permission["share_model"] = $this->getShareModel($filePath);
                $response['share'] = $permission;
            }
            $filePermission = new MiniPermission($permission['permission']);
            $response['canDelete'] = $filePermission->canDeleteFile();
            if(empty($permission['permission'])){
                return null;
            }
        }
        if ($file["file_type"] == MConst::OBJECT_TYPE_FILE){
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
        return $response;
    }
}