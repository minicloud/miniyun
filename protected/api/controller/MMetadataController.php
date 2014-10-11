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
     *
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
        
        $urlManager = new MUrlManager();
        $path = MUtils::convertStandardPath($urlManager->parsePathFromUrl($uri));

        $this->root = $urlManager->parseRootFromUrl($path);
        if($path===false){
        	$path = "/";
        }
        // 根目录
        if ($path == "/"){
            $response = $this->handleRootPath($includeDeleted);
        }else{
            $path               = "/{$this->userId}{$path}";
            $isShared = false;
            $fileInfo = MiniFile::getInstance()->getByFilePath($path);
            if(!isset($fileInfo)){
                $isShared = true;
                $path               = $absolutePath;
            }


            $response = $this->handleNotRootPath(
                                                $path,
                                                $includeDeleted,$isShared);
        }
      echo json_encode($response) ;
    }
    private function getGroupIds($groupId,$ids){
        $group = MiniGroupRelation::getInstance()->getByGroupId($groupId);
        if(isset($group)){
            if($group['parent_group_id']!=-1){
                array_push($ids,$group['parent_group_id']);
                return $this->getGroupIds($group['parent_group_id'],$ids);
            }else{
                return $ids;
            }
        }
    }
    /**
     * 处理根目录下文件查询
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
        $response["event"]                  = "0";
        //获得最大的事件ID
        $lastEvent = MiniEvent::getInstance()->getAll($this->userId, 0, 1);
        if ($lastEvent && count($lastEvent) > 0) {
            $response["event"]              = $lastEvent[0]['id'];
        }
        // 共享检查
        $contents = array();
        $user = MUserManager::getInstance()->getCurrentUser();
        $userId = $this->userId;
        $userPrivileges = MiniUserPrivilege::getInstance()->getByUserId($userId);
        $filePaths = array();
        foreach($userPrivileges as $userPrivilege){
            array_push($filePaths,$userPrivilege['file_path']);
        }
        $groupPrivileges = MiniGroupPrivilege::getInstance()->getPublic();
        $groupIds = array();
        foreach($groupPrivileges as $groupPrivilege){
            array_push($groupIds,$groupPrivilege['group_id']);
        }
        $groupIdsArr = array();
        $userGroupRelations =MiniUserGroupRelation::getInstance()->findUserGroup($userId);
        if(isset($userGroupRelations)){
            foreach($userGroupRelations as $userRelation){
                $groupId = $userRelation['id'];
                $arr = array();
                array_push($arr,$groupId);
                $result = MiniGroup::getInstance()->findById($groupId);
                if($result['user_id']>0){
                    array_push($groupIdsArr,$groupId);
                }else{
                    $ids = $this->getGroupIds($groupId,$arr);
                }
            }
            array_splice($groupIdsArr,0,0,$ids);
                $commonGroupIds = array_intersect($groupIdsArr,$groupIds);
                foreach($commonGroupIds as $commonGroupId){
                    $groupInfos = MiniGroupPrivilege::getInstance()->getByGroupId($commonGroupId);
                        foreach($groupInfos as $groupInfo){
                            $paths[] = $groupInfo['file_path'];
                        }
                    }
                    if($paths){
                        array_splice($filePaths,0,0,$paths);
                    }

        }
        $filePaths = array_unique($filePaths);
        $files = MiniFile::getInstance()->getChildrenByFileID(
            $parentFileId=0,
            $includeDeleted,
            $user,
            $this->userId,$filePaths);
        $response["contents"] = $contents;
        if (empty($files)){
            return $response;
        }
        // 组装子文件数据
        foreach ($files as $file){
            $item = array();
            $version = MiniVersion::getInstance()->getVersion($file["version_id"]);
            $mimeType = null;
            $signature = null;
            if ($version != NULL)
            {
                $mimeType = $version["mime_type"];
                $signature = $version["file_signature"];
            }
            $file["signature"] = $signature;
            $isShared = null;
            $item = $this->assembleResponse($item, $file, $mimeType,$isShared);
            array_push($contents, $item);
        }
        $response["contents"] = $contents;
        return $response;
    }
    
    /**
     * 处理非根目录下文件查询
     */
    private function handleNotRootPath($path, $includeDeleted,$isShared)
    {
        // 查询其是否存在 信息
        $currentFile = MiniFile::getInstance()->getByPath($path);
        if($isShared){
              $currentFile['file_type'] = 3;
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
        $response                   = array();
        $response = $this->assembleResponse($response, $currentFile, $mimeType,$isShared);
        //获取当前目录的权限
        $shareKeyPrivilege = MiniFile::getInstance()->getFolderExtendProperty($currentFile,MUserManager::getInstance()->getCurrentUser());
        $response['share_key']=$shareKeyPrivilege['share_key'];
        $response['privilege']=$shareKeyPrivilege['privilege'];
        if ($currentFile["file_type"] != MConst::OBJECT_TYPE_FILE)
        {
            $user = MUserManager::getInstance()->getCurrentUser();
            // 需要返回请求目录的这一层子文件
            $childrenFiles = MiniFile::getInstance()->getChildrenByFileID(
                $currentFile['id'],
                $includeDeleted,
                $user);
            // 组装子文件数据
            $contents = array();
            $response["contents"] = $contents;
            foreach ($childrenFiles as $file){
                $content = array();
                $version = MiniVersion::getInstance()->getVersion($file["version_id"]);
                $mimeType = null;
                if ($version != NULL){
                    $mimeType = $version["mime_type"];
                    $file["signature"] = $version["file_signature"];
                }
                if($isShared){
                    $file['privilege'] = $response['privilege'];
                }
                $content = $this->assembleResponse($content, $file, $mimeType,$isShared);
                array_push($contents, $content);
            }
            $response["contents"] = $contents;
        }
        return $response;
    }
    
    /**
     * 处理组装请求元数据
     */
    private function assembleResponse($response, $file, $mimeType,$isShared)
    {
        $filePath                          = $file["file_path"];
        if($file['file_type'] == 3||$isShared){
            $response['shared_path']  = $filePath;
        }
        $filePath  = CUtils::removeUserFromPath($filePath);
        $response["size"]                   = MUtils::getSizeByLocale($this->locale, $file["file_size"]);
        $response["bytes"]                  = (int)$file["file_size"];
        $response["path"]                   = $filePath;
        $response["modified"]               = MUtils::formatIntTime($file["file_update_time"]);
        $response["create_time"]            = $file["file_create_time"];
        $response["update_time"]            = $file["file_update_time"];
        $response["revision"]               = intval($file["version_id"]);
        $response["rev"]                    = strval($file["version_id"]);
        $response["root"]                   = $this->root;
        $response["hash"]                   = isset($file["signature"])? $file["signature"] : "";
        $response["event"]                  = $file["event_uuid"];
        $response["sort"]                   = (int)$file["sort"];
        //外链Key
        $response["share_key"]              = $file["share_key"];
        $response["privilege"]              = $file["privilege"];
        
        $isFolder = true;
        if ($file["file_type"] == MConst::OBJECT_TYPE_FILE){
            //支持类s3数据源的文件下载
            $data = array("hash" => $file["signature"]);
            $downloadParam = apply_filters("event_params", $data);
            if ($downloadParam !== $data){
                if (is_array($downloadParam)){
                    $response = array_merge($response, $downloadParam);
                }
            }
            $mimeType = CUtils::mime_content_type($file['file_path']);
            $response["thumb_exists"]       = MUtils::isExistThumbnail($mimeType, (int)$file["file_size"]);
            $isFolder = false;
        }
        if ($file["file_type"] > MConst::OBJECT_TYPE_FILE) {
            $response["type"] = (int)$file["file_type"];
        }
        $response["is_dir"]            = $isFolder;
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