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
    public function invoke($uri=null)
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
        $this->root = $urlManager->parseRootFromUrl($uri);
        
        if($path===false){
        	$path = "/";
        }
        $this->share_filter = MSharesFilter::init();
        if ($this->share_filter->handlerCheck($this->userId, $path, true)) {
            $this->userId = $this->share_filter->master;
            $path = $this->share_filter->_path;
        }

        //判断需要进行列表的文件夹是否具有读权限，没权限则抛出异常
        if ($this->share_filter->is_shared) {
            $this->share_filter->hasPermissionExecute("/".$this->share_filter->master.$path, MPrivilege::RESOURCE_READ);
        }

        // 根目录
        if ($path == "/"){
            $response = $this->handleRootPath($includeDeleted);
        }else{
            $path               = "/{$this->userId}{$path}";
            $response = $this->handleNotRootPath(
                                                $path,
                                                $includeDeleted);
        }
        echo json_encode($response);
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
        $files = MiniFile::getInstance()->getChildrenByFileID(
            $parentFileId=0,
            $includeDeleted,
            $user,
            $this->userId);
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
            //列表权限，如果没有列表权限，则不进行显示
            if (MUtils::isShareFolder($file['file_type'])) {
                try {
                    $this->share_filter->hasPermissionExecute($file['file_path'], MPrivilege::RESOURCE_READ);
                } catch (Exception $e) {
                    continue;
                }
            }
            $file["signature"] = $signature;
            $item = $this->assembleResponse($item, $file, $mimeType);
            array_push($contents, $item);
        }
        $response["contents"] = $contents;
        return $response;
    }
    
    /**
     * 处理非根目录下文件查询
     */
    private function handleNotRootPath($path, $includeDeleted)
    {
        // 查询其是否存在 信息
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
        if ($this->share_filter->_is_shared_path) {
            $currentFile['file_type'] = $this->share_filter->file_type;
        }
        $response = $this->assembleResponse($response, $currentFile, $mimeType);
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
                //列表权限，如果没有列表权限，则不进行显示
                if ($this->share_filter->is_shared) {
                    try {
                        $this->share_filter->hasPermissionExecute($file['file_path'], MPrivilege::RESOURCE_READ);
                    } catch (Exception $e) {
                        continue;
                    }
                }
                $content = $this->assembleResponse($content, $file, $mimeType);
                array_push($contents, $content);
            }
            $response["contents"] = $contents;
        }
        return $response;
    }
    
    /**
     * 处理组装请求元数据
     */
    private function assembleResponse($response, $file, $mimeType)
    {
        $filePath                          = $file["file_path"];
        if ($this->share_filter->is_shared && $this->share_filter->operator != $file['user_id']
            &&$this->share_filter->type == 0) {
            $path                           = $this->share_filter->slaves[$this->share_filter->operator];
            $index                          = strlen($this->share_filter->_shared_path);
            $filePath                       = substr_replace($filePath, $path, 0, $index);
            $index                          = strlen("/{$this->share_filter->operator}");
            $filePath                       = substr_replace($filePath,"",0, $index);
        } else {
            $filePath  = CUtils::removeUserFromPath($filePath);
        }
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