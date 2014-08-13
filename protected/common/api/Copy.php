<?php
/**
 * Miniyun web文件(夹)copy
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Copy extends CApiComponent
{
    private $_userNick;
    public $toId; // 目标路径id
    public $fromId; // 原路径id
    public $fromPath; // 原路径  包含user_id
    public $toPath; // 目标路径，包含user_id
    public $from;
    public $toParent;
    public $master;
    private $to_share_filter;
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
        $this->result["msg"] = "复制失败";
        $this->result["data"] = array();
    }
    /**
     * 
     * 根据id初始化参数
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
        
        
        // 原路径必须是存在的
        $from = UserFile::model()->findByPk($this->fromId, "is_deleted=0");
        if (is_null($from) || is_null($toParent)) {
            throw new ApiException("Not found");
        }
        
        $from_share_filter = MSharesFilter::init();
        if ($from_share_filter->handlerCheckByFile($this->_userId, $from)) {
            $this->master = $from_share_filter->master;
            $from_path = '/'. $this->master . $from_share_filter->_path;
            $from = UserFile::model()->findByAttributes(array('is_deleted'=>0, 'file_path' => $from_path));
            if (!$from) {
                throw new ApiException("Not found");
            }
        };
        
        $this->to_share_filter = MSharesFilter::init();
        if ($this->to_share_filter->handlerCheckByFile($this->_userId, $toParent)) {
            $this->_userId = $this->to_share_filter->master;
            $to_path  = '/' . $this->_userId . $this->to_share_filter->_path;
            $toParent = UserFile::model()->findByAttributes(array('is_deleted'=>0, 'file_path' => $to_path));
            if (!$toParent) {
                throw new ApiException("Not found");
            }
        }
        
        $this->fromPath = $from["file_path"];
        $pathInfo = CUtils::pathinfo_utf($this->fromPath);
        $this->toPath = $toParent['file_path'] . "/" . $pathInfo["basename"];
        $this->fromPath = CUtils::convertStandardPath($this->fromPath);
        $this->toPath = CUtils::convertStandardPath($this->toPath);
        $this->from = $from;
        $this->toParent = $toParent;
    }
    
    /**
     * 
     * 根据路径初始参数
     */
    private function initByPath ()
    {
        // 移动目标目录 
        $pathInfo = CUtils::pathinfo_utf($this->toPath);
        $dirname = $pathInfo["dirname"];
        if ($dirname == "/{$this->_userId}") {
            $toParent = $toParent = new UserFile();
            $toParent["id"] = 0;
            $toParent["file_path"] = "/{$this->_userId}";
        } else {
            $toParent = UserFile::model()->findByAttributes(
            array("file_path" => $dirname));
        }
        // 原路径必须存在
        $from = UserFile::model()->findByAttributes(
        array("file_path" => $this->fromPath, "is_deleted" => 0));
        if (is_null($from))
            throw new ApiException("Not found");
            $from_share_filter = MSharesFilter::init();
            
        if ($from_share_filter->handlerCheckByFile($this->_userId, $from)) {
            $this->master = $from_share_filter->master;
        }
        
        $this->to_share_filter = MSharesFilter::init();
        if ($this->to_share_filter->handlerCheckByFile($this->_userId, $toParent)) {
            $this->_userId = $this->to_share_filter->master;
            $this->toPath  = '/' . $this->_userId . $this->to_share_filter->_path;;
        }
        
        $this->from = $from;
        $this->toParent = $toParent;
    }
    /**
     * 
     * Copy 执行入口
     * @param bool $isPath Use path to move if true ,or use id. 
     */
    public function invoke ($isPath = true)
    {
        // 初始化入口
        $device = new UserDevice();
        $device = $device->findByUserIdAndType($this->_userId, CConst::DEVICE_WEB);
        $this->_deviceId = $device["id"];
        $this->_deviceName = $device["user_device_name"];
        $user = User::model()->findByPk($this->_userId);
        $this->_userNick = $user["user_name"];
        //
        // 空间检查
        //
        $this->handleSpace();
        if ($isPath) {
            $this->fromPath = CUtils::convertStandardPath($this->fromPath);
            $this->toPath = CUtils::convertStandardPath($this->toPath);
            $this->initByPath();
        } else {
            $this->initById();
        }
        //
        // 检查复制原路径与目标路径是否一致，一致则返回失败
        //
        if ($this->fromPath === $this->toPath) {
            $this->result["data"][$this->fromId]["state"] = false;
            $this->handleResult(false, 0, "已存在同名文件");
            return;
        }
        //
        // 检查是否移动到其子目录下
        //
        if (strpos($this->toPath, $this->fromPath . "/") === 0) {
            $this->result["msg"] = "不能移动到子目录";
            return;
        }
        
        if ($this->toPath == "/{$this->_userId}" ||
         $this->toPath == "/{$this->_userId}/") {
            $this->result["msg"] = "目标目录不存在";
            return;
        }
        
        //
        // 存在同名的则，拒绝
        //
        $target = UserFile::model()->findByAttributes(array("user_id"=> $this->_userId, 
                                                            "file_path"=>$this->toPath, "is_deleted" => 0));
        if ($target) {
            $this->handleResult(false, 0, "已存在同名的文件");
            return;
        }
        
        // 文件直接进行移动
        if ($this->from["file_type"] == 0) {
            $this->handleCopyFile($this->fromPath, $this->toPath);
        } else { // 文件夹涉及子对象
            $this->handleCopyFolder($this->fromPath, $this->toPath);
        }
        // 成功
        $this->handleResult(true, 0, "复制成功");
        $this->result["data"][$this->fromId]["state"] = true;
    }
    /**
     * 
     * 复制文件
     * @param string $fromPath
     * @param string $toPath
     */
    private function handleCopyFile ($fromPath, $toPath)
    {
        $createFolder = new CreateFolder();
        $createFolder->_deviceId = $this->_deviceId;
        $createFolder->_userId = $this->_userId;
        $pathInfo = CUtils::pathinfo_utf($toPath);
        $parentId = $createFolder->handleCreateByPath($pathInfo["dirname"]);
        // 重命名
        $name = $this->handleRename($parentId, $pathInfo["basename"]);
        $toPath = $pathInfo["dirname"] . "/" . $name;
        $version = FileVersion::model()->findByPk($this->from["version_id"]);
        $hash = $version["file_signature"];
        //
        // 保存事件
        //
        $context = array( "hash"  => $hash,
                          "rev"   => (int)$this->from["version_id"],
                          "bytes" => (int)$this->from["file_size"]);
        // 
        // 如果是存在同名的已经删除的记录，则还原之
        //
        $target = UserFile::model()->findByAttributes(array("user_id"=> $this->_userId, 
                                                            "file_path"=>$this->toPath, 
                                                            "is_deleted" => 1));
        if (!$target) {
            $target = new UserFile();
            $target["user_id"]          = $this->_userId;
            $target["parent_file_id"]   = $parentId;
            $target["file_create_time"] = time();
        }
        $target["file_type"]        = 0;
        $target["is_deleted"]       = 0;
        $target["version_id"]       = $this->from["version_id"];
        $target["file_size"]        = $this->from["file_size"];
        $target["file_path"]        = $toPath;
        $target["file_name"]        = $name;
        $target["file_update_time"] = time();
        $target["event_uuid"]       = MiniUtil::getEventRandomString(46);
        $target->save();
        
        // 创建meta
        $this->handleFileMeta($toPath, $this->from["version_id"], $this->_userId, $this->_userNick, CConst::CREATE_FILE, $this->_deviceName, $this->from["file_size"]);
        // 创建事件
        MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, CConst::CREATE_FILE, $toPath, serialize($context), $target["event_uuid"]);
        $this->to_share_filter->handlerAction(3, $this->_deviceId, $toPath, $context);
    }
    /**
     * 
     * 复制文件夹
     * @param string $fromPath
     * @param string $toPath
     */
    public function handleCopyFolder ($fromPath, $toPath)
    {
        $createFolder = new CreateFolder();
        $createFolder->_deviceId = $this->_deviceId;
        $createFolder->_userId = $this->_userId;
        $createFolder->share_filter = $this->to_share_filter;
        $pathInfo = CUtils::pathinfo_utf($toPath);
        $parentId = $createFolder->handleCreateByPath($pathInfo["dirname"]);
        // 重命名
        $name = $this->handleRename($parentId, $pathInfo["basename"]);
        $this->toPath = $pathInfo["dirname"] . "/" . $name;
        
        // 
        // 如果是存在同名的已经删除的记录，则还原之
        //
        $target = UserFile::model()->findByAttributes(array("user_id"=> $this->_userId, 
                                                            "file_path"=>$toPath, 
                                                            "is_deleted" => 1));
        if (!$target) {
            $target = new UserFile();
            $target["user_id"]          = $this->_userId;
            $target["parent_file_id"]   = $parentId;
            $target["file_create_time"] = time();
        }
        $target["file_type"]        = 1;
        $target["is_deleted"]       = 0;
        $target["version_id"]       = $this->from["version_id"];
        $target["file_size"]        = $this->from["file_size"];
        $target["file_path"]        = $toPath;
        $target["file_name"]        = $name;
        $target["file_update_time"] = time();
        $target["event_uuid"]       = MiniUtil::getEventRandomString(46);
        $target->save();
        // 创建事件
        MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, 0, $toPath, $toPath, $target["event_uuid"]);
        $this->to_share_filter->handlerAction(0, $this->_deviceId, $toPath, $toPath);
        // copy 所有的文件,创建文件的时候会将父目录创建
        $handler = new UserFile();
        $children = $handler->getChildrenFileByPath($fromPath);
        foreach ($children as $child) {
            $this->from = $child;
            $childFromPath = $child["file_path"];
            $index         = strlen($this->fromPath);
            $childtoPath   = substr_replace($childFromPath,$this->toPath, 0, $index);
            $this->handleCopyFile($childFromPath, $childtoPath);
        }
        
        // copy 所有的文件夹，补偿没有子文件的 路径
        $folders = $handler->getChildrenFileByPath($fromPath,1);
        $createFolder = new CreateFolder();
        $createFolder->_deviceId = $this->_deviceId;
        $createFolder->_userId = $this->_userId;
        $createFolder->share_filter = $this->to_share_filter;
        foreach ($folders as $folder) {
            $childFromPath = $folder["file_path"];
            $index         = strlen($this->fromPath);
            $childtoPath   = substr_replace($childFromPath,$this->toPath, 0, $index);
            $parentId = $createFolder->handleCreateByPath($childtoPath);
        }
    }
    
    
    /**
     * 
     * 创建meta信息
     * @param string $filePath
     * @param int $versionId
     * @param int $userId
     * @param string $userNick
     * @param int $action
     */
    public function handleFileMeta ($filePath, $versionId, $userId, $userNick, $action, $deviceName, $fileSize)
    {
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
            $meta["file_path"] = $filePath;
            $meta["meta_key"] = "version";
            $meta["meta_value"] = $value;
        }
        return $meta->save();
    }
    /**
     * 
     * 重命名冲突的文件名
     * @param int $parentId
     * @param string $name
     */
    private function handleRename ($parentId, $name)
    {
        $children = UserFile::model()->findAllByAttributes(
        array("parent_file_id" => $parentId, "user_id" => $this->_userId, 
        "is_deleted" => 0));
        $names = array();
        foreach ($children as $child) {
            $names[$child["file_name"]] = $child["file_name"];
        }
        // 重命名
        $name = CUtils::getConflictName($name, $names);
        return $name;
    }
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException ($exception)
    {
        echo CJSON::encode($this->result);
    }
}
?>