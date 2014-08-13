<?php
/**
 * Miniyun web文件(夹)移动or重命名
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Move extends CApiComponent
{
    // 删除文件（夹）事件
    private $_action = 2;
    private $_userNick;
    public $toId;        // 目标目录id
    public $fromId;      // 原路径id
    public $fromPath;   // 原路径  包含user_id
    public $toPath;     // 目标路径，包含user_id
    public $from;
    public $toParent;
    public $target;
    public $master;
    private $to_share_filter;
    private $rename = false;
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
        $this->result["msg"] = "移动失败";
        $this->result["data"] = array();
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
        // 原路径必须是存在的
        $from = UserFile::model()->findByPk($this->fromId, "is_deleted=0");
        if (is_null($from) || is_null($toParent)) {
            throw new ApiException("Not found");
        }
        
        $this->to_share_filter = MSharesFilter::init();
        if ($this->to_share_filter->handlerCheckByFile($this->_userId, $toParent)) {
            $this->_userId = $this->to_share_filter->master;
            $this->toPath  = '/' . $this->_userId . $this->to_share_filter->_path . '/' . $this->from['file_name'];
            $this->toParent = UserFile::model()->findByAttributes(array('is_deleted'=>0, 'file_path' => '/' . $this->_userId . $this->to_share_filter->_path));
            if (!$this->toParent) {
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
     * 如果使用path，则使用path为关键字初始化
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
            $toParent['user_id']   = $this->_userId;
        } else {
            $toParent = UserFile::model()->findByAttributes(
            array("file_path" => $dirname));
        }
        // 原路径必须存在
        $from = UserFile::model()->findByAttributes(
        array("file_path" => $this->fromPath, "is_deleted" => 0));
        if (is_null($from))
            throw new ApiException("Not found");
            
            
        $this->to_share_filter = MSharesFilter::init();
        $path = substr_replace($this->toPath, '', 0,strlen("/{$this->_userId}"));
        if ($this->to_share_filter->handlerCheck($this->_userId, $path)) {
            $this->_userId = $this->to_share_filter->master;
            $this->toPath  = '/' . $this->_userId . $this->to_share_filter->_path;
            $path_info = CUtils::pathinfo_utf($this->toPath);
            $this->toParent = UserFile::model()->findByAttributes(array('is_deleted'=>0, 'file_path' => $path_info['dirname']));
            if (!$this->toParent) {
                throw new ApiException("Not found");
            }
        }
        
        $this->from = $from;
        $this->toParent = $toParent;
    }
    /**
     * move 执行入口
     * @param bool $isPath - Use path to move if true ,or use id. 
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
        $this->master    = $this->_userId;
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
        // 判断是否是共享
        //
        $from_share_filter = MSharesFilter::init();
        $from_share_filter->handlerCheckByFile($this->_userId, $this->from);
        
        $this->rename = false;
        if ($from_share_filter->_is_shared_path && $this->toParent['id'] == 0) {
            $this->rename = true;
        } elseif ($from_share_filter->is_shared) {
            $this->master = $from_share_filter->master;
            $this->fromPath = '/'. $this->master . $from_share_filter->_path;
            $this->from = UserFile::model()->findByAttributes(array('is_deleted'=>0, 'file_path' => $this->fromPath));
            if (!$this->from) {
                throw new ApiException("Not found");
            }
        }
        //
        // 检查移动原路径与目标路径是否一致，一致则返回成功
        //
        if ($this->fromPath === $this->toPath) {
            $this->handleResult(false, 0, "已存在同名的文件");
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
        // 命名检查
        //
        if (CUtils::checkNameInvalid($this->toPath) != 0 || CUtils::checkNameInvalid($this->toPath) != 0) {
             $this->result["msg"] = "命名不能包含下列字符串： ^|?*\\<\":>";
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
        
        $index     = strlen ( "/{$this->from['user_id']}" );
        $fpath     = substr_replace($this->fromPath, "", 0, $index);
        
        $index     = strlen ( "/{$this->toParent['user_id']}" );
        $tpath     = substr_replace($this->toPath, "", 0, $index);
        
        //
        // 检查移动方式
        //
        
        if ($isPath == false &&$this->rename == false && ($from_share_filter->handlerCheckMove($from_share_filter->master, 
                                                 $this->to_share_filter->master, 
                                                 $fpath, 
                                                 $tpath) || $this->to_share_filter->is_shared )) {
             //
             // 先copy再删除,如果是移动共享文件夹则只copy，再执行shareManager取消共享
             //
             $copy = new Copy();
             $copy->_userId = $this->_userId;
             $copy->toId = $this->toParent['id'];
             $copy->fromId = $this->from['id'];
             try {
                 $copy->invoke(false);
             } catch (Exception $e) {
                 $this->result["msg"] = "操作失败";
                 return;
             }
             
             if ($this->from['file_type'] == 2 && $this->from['user_id'] != $this->to_share_filter->operator) {
                $file_meta = FileMeta::model()->findByAttributes(array('meta_key' => 'shared_folders', 'file_path' => $this->from['file_path']));
                if (! $file_meta) {
                    $this->result["msg"] = "操作失败";
                     return;
                }
                $meta_value = unserialize($file_meta['meta_value']);
                $slaves = $meta_value['slaves'];
                $this->from = UserFile::model()->findByAttributes(array('file_path'=>$slaves[$this->to_share_filter->operator],'is_deleted' => 0));
                if (!$this->from) {
                    $this->result["msg"] = "操作失败";
                     return;
                }
             }
             $del = new Delete();
             $del->_userId = $this->from['user_id'];
             try {
                 $del->invoke($this->from['id']);
                 $trash = new Trash();
                 $trash->_userId = $this->master;
                 $trash->fromIds = $this->from['id'];
                 $trash->invoke(Trash::DELETE);
             } catch (Exception $e) {
                 $this->result["msg"] = "操作失败";
                 return;
             }
             
             if ($copy->result['state'] && $del->result['state']) {
                 $this->handleResult(true, 0, "操作成功");
                 $this->result["data"][$this->fromId]["state"] = true;
             }
             return;
         }
         
        // 文件直接进行移动
        if ($this->from["file_type"] == 0) {
            $this->handleMoveFile($this->fromPath, $this->toPath);
        } else { // 文件夹涉及子对象
            $this->handleMoveFolder($this->fromPath, $this->toPath);
        }
        
        $this->handleResult(true, 0, "操作成功");
        $this->result["data"][$this->fromId]["state"] = true;
    }
    
    /**
     * 
     * 移动目录
     * @param string $fromPath
     * @param string $toPath
     */
    private function handleMoveFolder($fromPath, $toPath) {
        $createFolder = new CreateFolder();
        $createFolder->_deviceId = $this->_deviceId;
        $createFolder->_userId   = $this->to_share_filter->master;
        $createFolder->share_filter = $this->to_share_filter;
        $pathInfo = CUtils::pathinfo_utf($this->toPath);
        $parentId  = $createFolder->handleCreateByPath($pathInfo["dirname"]);
        
        // 重命名
        $name = $this->handleRename($parentId, $pathInfo["basename"]);
        $this->toPath = $pathInfo["dirname"] . "/" .$name;
        
        // 
        // 如果是存在同名的已经删除的记录，则删除之
        //
        $deleted = UserFile::model()->findByAttributes(array("user_id"=> $this->_userId, "file_path"=>$this->toPath, "is_deleted" => 1));
        
        
        // 为子文件创建版本
        $children = new UserFile();
        $children = $children->getChildrenFileByPath($this->fromPath);
        foreach ($children as $child) {
            $filePath = $child["file_path"];
            $index    = strlen($this->fromPath);
            $filePath = substr_replace($filePath,$this->toPath, 0, $index);
            $action = CConst::CREATE_FILE; // 创建
            if ($child["is_deleted"]) $action = 1; // 保持原来的删除状态
            $this->handleFileMeta($filePath, $child["version_id"], $this->_userId, $this->_userNick, $action, $this->_deviceName, $child['file_size']);
        }
        
        $handler = new UserFile();
        if ($deleted) {
            $handler->updateAll(array('parent_file_id' => $this->from['id']), 
                                array('condition' => 'user_id=:user_id and parent_file_id=:parent_file_id',
                                      'params'    => array(':user_id'=>$this->_userId, ":parent_file_id" => $deleted["id"])));
            $deleted->delete();
        }
        // 更新所有子 对象
        $handler->updateAllByParentPath($this->fromPath, $this->toPath, $this->master,$this->to_share_filter->master);
        $this->from["file_path"]      = $this->toPath;
        $this->from["file_name"]      = $name;
        $this->from["event_uuid"]     = MiniUtil::getEventRandomString(46);
        $this->from["parent_file_id"] = $parentId;
        $this->from["user_id"]        = $this->to_share_filter->master;
        $this->from->save();
        // 创建事件
        MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, $this->_action, $this->fromPath, $this->toPath, $this->from["event_uuid"]);
        
        if ($this->rename) {
            $this->to_share_filter->handlerRenameShared($this->fromPath, $this->toPath);
        } else
        {
            $this->to_share_filter->handlerAction($this->_action, $this->_deviceId, $this->fromPath,$this->toPath);
        }
    }
    
    /**
     * 
     * 移动文件
     * @param string $fromPath
     * @param string $toPath
     */
    private function handleMoveFile($fromPath, $toPath) {
        $createFolder = new CreateFolder();
        $createFolder->_deviceId = $this->to_share_filter->master;;
        $createFolder->_userId   = $this->_userId;
        $pathInfo = CUtils::pathinfo_utf($this->toPath);
        $parentId  = $createFolder->handleCreateByPath($pathInfo["dirname"]);
        // 重命名
        $name = $this->handleRename($parentId, $pathInfo["basename"]);
        $this->toPath = $pathInfo["dirname"] . "/" .$name;
        // 
        // 如果是存在同名的已经删除的记录，则删除之
        //
        $deleted = UserFile::model()->findByAttributes(array("user_id"=> $this->_userId, "file_path"=>$this->toPath, "is_deleted" => 1));
        if ($deleted) $deleted->delete();
        
        // 修改属性后 保存
        $this->from["file_path"] = $this->toPath;
        $this->from["file_name"] = $name;
        $this->from["event_uuid"] = MiniUtil::getEventRandomString(46);
        $this->from["parent_file_id"] = $parentId;
        $this->from["user_id"]    = $this->to_share_filter->master;
        $this->from->save();
        // 更新meta
        $this->handleFileMeta($this->toPath, $this->from["version_id"], $this->_userId, $this->_userNick, CConst::CREATE_FILE, $this->_deviceName, $this->from['file_size']);
        // 创建事件
        MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, $this->_action, $this->fromPath, $this->toPath, $this->from["event_uuid"]);
        $this->to_share_filter->handlerAction($this->_action, $this->_deviceId, $this->fromPath,$this->toPath);
    }
    
    
    
    /**
     * 
     * 创建meta信息
     */
    public function handleFileMeta($filePath, $versionId, $userId, $userNick, $action, $deviceName, $fileSize) {
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
     * 
     * 重命名冲突的文件名
     * @param int $parentId
     * @param string $name
     */
    private function handleRename($parentId, $name) {
        $children = UserFile::model()->findAllByAttributes(array("parent_file_id" => $parentId, "user_id" => $this->_userId, "is_deleted" => 0));
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
        Yii::app()->end();
    }
    
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleError()
     */
    public function handleError($code, $message, $file, $line) {
        echo CJSON::encode($this->result);
        Yii::app()->end();
    }
}
?>