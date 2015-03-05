<?php
/**
 * Miniyun web文件（夹）删除
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class CreateFile extends CApiComponent
{
    private $_userNick;
    public $path; // meta路径
    public $toId; // 目录id
    public $cname; // 文件名
    public $tmpName;
    public $size;
    public $versionId;
    public $type;
    public $hash;
    public $fileId;
    private $share_filter;
    /**
     * 
     * 构造函数，初始化一些参数
     */
    public function __construct ()
    {
        parent::init();
        $this->result = array();
        $this->result["state"] = false;
        $this->result["code"] = 0;
        $this->result["msg"] = "上传文件失败";
        $this->share_filter = MSharesFilter::init();
    }
    /**
     * 
     * 如果是使用id的话，先将id转换为path
     */
    private function initById ()
    {
        // 移动到根目录
        if ($this->toId == 0) {
            $toParent = new UserFile();
            $toParent["id"] = 0;
            $toParent["file_path"] = "/{$this->_userId}";
            $toParent['user_id'] = $this->_userId;
        } else {
            $toParent = UserFile::model()->findByPk($this->toId);
        }
        if (empty($this->cname))
            throw new ApiException("File name is NULL.");
        //
        // 共享检查
        //
        if ($this->share_filter->handlerCheckByFile($this->_userId, $toParent)) {
            $this->_userId = $this->share_filter->master;
            $this->_userNick = $this->share_filter->master_nick;
            $toParent['file_path'] = '/' . $this->_userId . $this->share_filter->_path;
        }
        $this->path = $toParent["file_path"] . "/" . $this->cname;
        $this->path = CUtils::convertStandardPath($this->path);
    }
    /**
     * 
     * 创建文件入口
     */
    public function invoke ($isPath = true)
    {
        // 初始化入口
        $device = new UserDevice();
        $device = $device->findByUserIdAndType($this->_userId, 1);
        $this->_deviceId = $device["id"];
        $this->_deviceName = $device["user_device_name"];
        $user = User::model()->findByPk($this->_userId);
        $this->_userNick = $user["user_name"];
        //
        // 空间检查
        //
        $this->handleSpace();
        if ($this->totalSpace < $this->usedSpace + $this->size) {
            $this->handleResult(false, 0, "空间不足");
            throw new ApiException("Params Error.");
        }
        
        
        if (! $isPath) {
            $this->initById();
        }
        
        //
        // 验证文件是否已经上传成功
        //
        if (file_exists($this->tmpName) === false) {
            $this->handleResult(false, 0, "传入参数错误");
            throw new ApiException("Params Error.");
        }
        //
        // 检查文件上传错误
        //
        if (filesize($this->tmpName) != $this->size) {
            $this->handleResult(false, 0, "传入参数错误");
            throw new ApiException("Params Error.");
        }
        $this->hash = MiniUtil::getFileHash($this->tmpName);
        $this->type = MiniUtil::getMimeType($this->cname);
        $this->handleVersion();
        $this->handleCreateFile();
        
        $this->handleResult(true, 0, "上传文件成功");
    }
    
    /**
     * 
     * 创建文件，同名的执行覆盖处理
     */
    public function handleCreateFile() 
    {
        $createFolder = new CreateFolder();
        $createFolder->_deviceId = $this->_deviceId;
        $createFolder->_userId   = $this->_userId;
        $pathInfo = CUtils::pathinfo_utf($this->path);
        $parentId  = $createFolder->handleCreateByPath($pathInfo["dirname"]);
        // 重命名
        $this->cname = $this->handleRename($parentId, $pathInfo["basename"]);
        $this->path = $pathInfo["dirname"] . "/" .$this->cname;
        
        //
        // 保存事件
        //
        $context = array( "hash"  => $this->hash,
                          "rev"   => (int)$this->versionId,
                          "bytes" => (int)$this->size);
        // 
        // 如果是存在同名的已经删除的记录，则删除之
        //
        $target = UserFile::model()->findByAttributes(array("user_id"=> $this->_userId, 
                                                            "file_path"=>$this->path, 
                                                            "is_deleted" => 1));
        
        if (!$target) {
            $target = new UserFile();
            $target["user_id"]          = $this->_userId;
            $target["parent_file_id"]   = $parentId;
            $target["file_create_time"] = time();
        }
        $target["file_type"]        = 0;
        $target["is_deleted"]       = 0;
        $target["version_id"]       = $this->versionId;
        $target["file_size"]        = $this->size;
        $target["file_path"]        = $this->path;
        $target["file_name"]        = $this->cname;
        $target["file_update_time"] = time();
        $target["event_uuid"]       = MiniUtil::getEventRandomString(46);
        $target->save();
        $this->fileId = $target["id"];
        
        // 创建meta
        $this->handleFileMeta($this->path, $this->versionId, $this->_userId, $this->_userNick, CConst::CREATE_FILE, $this->_deviceName, $this->size);
        // 创建事件
        MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, CConst::CREATE_FILE, $this->path, serialize($context), $target["event_uuid"]);
        $this->share_filter->handlerAction(3, $this->_deviceId, $this->path, $context);
    }
    
    
    /**
     * 
     * 重命名冲突的文件名
     */
    private function handleRename($parentId, $name) {
        $children = UserFile::model()->findAllByAttributes(array("parent_file_id" => $parentId, "user_id" => $this->_userId, "is_deleted" => 0));
        $names = array();
        foreach ($children as $child) {
            $names[strtolower($child["file_name"])] = $child["file_name"];
        }
        // 重命名
        $name = CUtils::getConflictName($name, $names);
        return $name;
    }
    
    /**
     * 
     * 创建meta信息
     */
    private function handleFileMeta($filePath, $versionId, $userId, $userNick, $action, $deviceName, $fileSize) {
        //
        // 查询之前的版本
        //
        $handler = new FileMeta();
        $meta = $handler->getFileMeta($filePath, "version");
        if ($meta) {
            $value = CUtils::getFileVersions($deviceName, $fileSize, $versionId, $action, $userId, $userNick, $meta["meta_value"]);
            $meta["meta_value"] = $value;
        } else {
            $meta = new FileMeta();
            $value = CUtils::getFileVersions($deviceName, $fileSize, $versionId, $action, $userId, $userNick);
            $meta["file_path"]  = $filePath;
            $meta["meta_key"]   = "version";
            $meta["meta_value"] = $value;
        }
        return $meta->save();
    }
    /**
     * 保存文件版本
     */
    public function handleVersion ()
    {
        //
        // 文件内容保存路径
        //
        $storePath = DOCUMENT_ROOT_BLOCK . MiniUtil::getPathBySplitStr($this->hash);
        if (file_exists(dirname($storePath)) === false) {
            CUtils::MkDirs(dirname($storePath));
        }
        $version = FileVersion::model()->queryFileVersionBySignatrue($this->hash);
        //
        // 文件版本id
        //
        if ($version) {
            $this->versionId = $version["id"];
            if (file_exists($storePath) == false) {
                if (move_uploaded_file($this->tmpName, $storePath) === false) {
                    throw new ApiException("Move temp file failure.");
                }
            }
            unlink($this->tmpName);
            return;
        }

        //
        // 移动临时文件到保存路径中
        //
        if (move_uploaded_file($this->tmpName, $storePath) === false) {
            throw new ApiException("Move temp file failure.");
        }
        
        //
        // 创建metadata
        //
        $fileVersion = FileVersion::model()->createFileVersion ( $this->hash, $this->size, $this->type );
        if (!$fileVersion) {
            throw new ApiException("Create Version failure.");
        }
        
        $this->versionId = $fileVersion["id"];
    }
    
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException($exception) {
        $this->handleEnd();
    }
}