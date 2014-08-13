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
class Delete extends CApiComponent
{
    // 删除文件（夹）事件
    private $_action = 1;
    private $_userNick;
    /**
     * 
     * 构造函数，初始化一些参数
     */
    public function __construct ()
    {
        parent::init ();
        $this->result = array();
        $this->result["state"] = false;
        $this->result["code"]  = 0;
        $this->result["msg"] = "删除失败";
        $this->result["msg_code"] = "";
        $this->result["data"] = array("d" => false);
    }
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleResult()
     */
    public function handleResult($status, $code, $message) {
        $this->result["state"] = true;
        $this->result["code"]  = 0;
        $this->result["msg"] = $message;
        $this->result["msg_code"] = "";
        $this->result["data"] = array("d" => $status); // TODO 变化
    }
    /**
     * 外部调用删除文件（夹）入口
     * @param string $ids - 1,2,3逗号分隔的字符串
     */
    public function invoke($ids) {
        $device = new UserDevice();
        $device = $device->findByUserIdAndType($this->_userId, CConst::DEVICE_WEB);
        $this->_deviceId = $device["id"];
        $this->_deviceName = $device["user_device_name"];
        $user  = User::model()->findByPk($this->_userId);
        $this->_userNick = $user["user_name"];
        $files = UserFile::model()->findAll("id in ( ".$ids." )");
        // 更新每个元素以及子元素
        foreach ($files as $file) {
            if ($file["is_deleted"]) continue;   // 已经删除的文件不做操作
            if ($file["file_type"] == 0) {
                $file["is_deleted"] = 1;
                $file["event_uuid"] = MiniUtil::getEventRandomString(46);
                $file->save();
                // 创建版本信息
                $this->handleFileMeta($file["file_path"], $file["version_id"], $file['user_id'], $this->_userNick, $this->_deviceName, $file['file_size']);
                // 创建事件
                MiniEvent::getInstance()->createEvent(
                                    $file['user_id'], 
                                    $this->_deviceId, 
                                    $this->_action, 
                                    $file["file_path"], 
                                    $file["file_path"], 
                                    $file["event_uuid"]
                                    );
                $share_filter = MSharesFilter::init();
                if ($share_filter->handlerCheckByFile($this->_userId, $file)){
                    $share_filter->handlerAction($this->_action, $this->_deviceId, $file["file_path"],$file["file_path"]);
                }
            } else {
                $this->delete($file);
            }
        }
        $this->handleResult(true, 0, "删除成功");
    }
    
    /**
     * 
     * 执行文件夹删除
     * @param UserFile $file
     */
    public function delete($parenFile) {
        $share_filter = MSharesFilter::init();
        $share_filter->handlerCheckByFile($this->_userId, $parenFile);
        //
        // 取消共享
        //
        if (($parenFile['file_type'] == 2 && $parenFile['user_id'] != $this->_userId) || $parenFile['file_type'] == 3 ) {
            $parenFile = UserFile::model()->findByAttributes(array('file_path'=>$share_filter->slaves[$this->_userId],'is_deleted' => 0));
            if (!$parenFile) {
                throw new ApiException("Not found");
            }
            $handler = new ShareManager();
            $handler->_userId = $share_filter->operator;
            $handler->_id     = $parenFile["id"];
            try {
                $handler->invoke(ShareManager::CANCEL_SHARED);
            } catch (Exception $e) {
                throw new ApiException("Not found");
            }
            
            return;
        }
        
        // 更新每个元素以及子元素
        $parentPath = $parenFile["file_path"];
        $handler = new UserFile();
        $files = $handler->getFilesByPath($parentPath);
        // 轮询删除
        foreach ($files as $file) {
            if ($file["is_deleted"]) continue;   // 已经删除的文件不做操作
            $file["event_uuid"] = MiniUtil::getEventRandomString(46);
            if ($file["file_type"] == 0) {
                if ($file["is_deleted"]) continue;   // 已经删除的文件不做操作
                $file["is_deleted"] = 1;
                $file->save();
                // 创建版本信息
                $this->handleFileMeta($file["file_path"], $file["version_id"], $file['user_id'], $this->_userNick, $this->_deviceName, $file['file_size']);
                // 创建事件
                MiniEvent::getInstance()->createEvent(
                                    $file['user_id'], 
                                    $this->_deviceId, 
                                    $this->_action, 
                                    $file["file_path"], 
                                    $file["file_path"], 
                                    $file["event_uuid"]
                                    );
                $share_filter->handlerAction($this->_action, $this->_deviceId, $file["file_path"],$file["file_path"]);
                continue;
            }
            $this->delete($file);
            $file["is_deleted"] = 1;
            $file->save();
            // 创建事件
            MiniEvent::getInstance()->createEvent(
                                $file['user_id'],
                                $this->_deviceId, 
                                $this->_action, 
                                $file["file_path"], 
                                $file["file_path"], 
                                $file["event_uuid"]
                                );
            $share_filter->handlerAction($this->_action, $this->_deviceId, $file["file_path"],$file["file_path"]);
        }
        
        $parenFile["event_uuid"] = MiniUtil::getEventRandomString(46);
        $parenFile["is_deleted"] = 1;
        $parenFile->save();
        // 创建事件
        MiniEvent::getInstance()->createEvent(
                            $parenFile['user_id'],
                            $this->_deviceId, 
                            $this->_action, 
                            $parenFile["file_path"], 
                            $parenFile["file_path"], 
                            $parenFile["event_uuid"]
                            );
        $share_filter->handlerAction($this->_action, $this->_deviceId, $parenFile["file_path"],$parenFile["file_path"]);
        //
        // 删除共享目录
        //
        if ($share_filter->_is_shared_path && $share_filter->operator == $share_filter->master) {
            $id   = $parenFile["id"];
            $handler = new ShareManager();
            $handler->_userId = $share_filter->operator;
            $handler->_id     = $id;
            try {
                $handler->invoke(ShareManager::CANCEL_SHARED);
            } catch (Exception $e) {
                throw new ApiException('Internal Server Error');
            }
            
        }
    }
    
    /**
     * 
     * 更新文件meta信息，删除时添加版本
     * @param UserFile $file
     */
    public function handleFileMeta($filePath, $versionId, $userId, $userNick, $deviceName, $fileSize) {
        //
        // 查询之前的版本
        //
        $meta = new FileMeta();
        $meta = $meta->getFileMeta($filePath, "version");
        if ($meta) {
            $value = CUtils::getFileVersions($deviceName, $fileSize, $versionId, $this->_action, $userId, $userNick, $meta["meta_value"]);
            $meta["meta_value"] = $value;
        } else {
            $value = CUtils::getFileVersions($deviceName, $fileSize, $versionId, $this->_action, $userId, $userNick);
            $meta["file_path"]  = $filePath;
            $meta["meta_key"]   = "version";
            $meta["meta_value"] = $value;
        }
        return $meta->save();
    }
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException($exception) {
        echo CJSON::encode ( $this->result );
    }
}
?>