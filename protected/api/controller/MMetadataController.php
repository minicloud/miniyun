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
            $response = $this->handleNotRootPath($path,$includeDeleted);
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
        $contents = array();
        $user = MUserManager::getInstance()->getCurrentUser();
        $publicFiles = MiniFile::getInstance()->getPublics();
        $groupShareFiles  = MiniGroupPrivilege::getInstance()->getAllGroups();
        $userShareFiles   = MiniUserPrivilege::getInstance()->getAllUserPrivilege();
        $filePaths  = array();
        $shareFiles = array_merge($publicFiles,$groupShareFiles,$userShareFiles);
        $userFiles = MiniFile::getInstance()->getChildrenByFileID(
            $parentFileId=0,
            $includeDeleted,
            $user,
            $this->userId);
        $fileData = array_merge($shareFiles,$userFiles);
        //如果没有文件记录
        if (empty($publicFiles) && empty($shareFiles)){
            $response["contents"] = $contents;
            return $response;
        }
        foreach($fileData as $file){
            $file = MiniFile::getInstance()->getByPath($file['file_path']);
            if(!empty($file)){
                if($file['parent_file_id'] == 0 && $file['is_deleted'] == 0){
                    $filePaths[] = $file['file_path'];
                }
            }
        }
        $filePaths = array_unique($filePaths);
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
            }
            $file["signature"] = $signature;
            $item = $this->assembleResponse($item, $file, $mimeType);
//            if(!empty($item)){
                array_push($contents, $item);
//            }
        }
        $response["contents"] = $contents;
        return $response;
    }
    
    /**
     * 处理非根目录下文件查询
     */
    private function handleNotRootPath($path, $includeDeleted)
    {
        // 查询其是否存在信息
        $currentFile = MiniFile::getInstance()->getByPath($path);
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
        $shareKeyPrivilege = MiniFile::getInstance()->getFolderExtendProperty($currentFile,MUserManager::getInstance()->getCurrentUser());
        $response['share_key']=$shareKeyPrivilege['share_key'];
        $response = $this->assembleResponse($response, $currentFile, $mimeType);
        $user = MUserManager::getInstance()->getCurrentUser();
        // 组装子文件数据
        $childrenFiles = MiniFile::getInstance()->getChildrenByFileID(
            $parentFileId=$currentFile['id'],
            $includeDeleted,
            $user,
            $this->userId);
        $contents = array();
        if(!empty($childrenFiles)){
            foreach($childrenFiles as $childrenFile){
                $content = array();
                $version = MiniVersion::getInstance()->getVersion($childrenFile["version_id"]);
                $mimeType = null;
                if ($version != NULL){
                    $mimeType = $version["mime_type"];
                    $file["signature"] = $version["file_signature"];
                }
                $content = $this->assembleResponse($content, $childrenFile, $mimeType);
                if(!empty($item) && $childrenFile['is_deleted'] == 0){
                    array_push($contents, $content);
                }
            }
        }
        $response['contents'] = $contents;
        return $response;
    }
    
    /**
     * 处理组装请求元数据
     */
    private function assembleResponse($response, $file, $mimeType)
    {
        $filePath                           = $file["file_path"];
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
        $response['is_dir'] = false;
        if($file['file_type'] != 0){
            $response['is_dir'] = true;
            $permissionModel = new UserPermissionBiz($filePath,$this->userId);
            $permission = $permissionModel->getPermission($filePath,$this->userId);
            if(!empty($permission)){
                if(isset($permission['children_shared'])){
                    $response['children_shared'] = true;
                }else{
                    $response['share'] = $permission;
                }
            }else{
                return null;
            }
        }
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