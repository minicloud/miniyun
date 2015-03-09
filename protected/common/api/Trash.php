<?php
/**
 * Miniyun web文件(夹)回收站，包括回收站list，revert，delete，clean
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Trash extends CApiComponent
{
    public $fromIds;  // ids 使用“,”分隔  eg. 1,2,3
    public $trashList;
    public $_userNick;
    const LIST_TRASH = 0;
    const REVERT = 1; // 还原删除的文件（夹）
    const DELETE = 2; // 彻底删除回收站的文件（夹）
    const CLEAN = 3; // 清空回收站所有内容
    const SUPERDELETE = 4; // 保留源数据的清楚, 回收站的加强模式
    const SUPERCLEAN = 5; // 保留源数据的清楚, 回收站的加强模式
    /**
     *
     * 构造函数，初始化一些必要参数
     */
    public function __construct ()
    {
        parent::init();
        $this->result = array();
        $this->result["state"] = false;
        $this->result["code"] = 0;
        $this->result["msg"] = Yii::t('api_message', 'action_failure');
    }
    /**
     *
     * 外部调用入口
     * @param int $action
     */
    public function invoke ($action = 0)
    {
        // 回收站操作之前做安全密码验证
        do_action('trash_entry');

        switch ($action) {
            case Trash::LIST_TRASH:
                $this->handleListTrash();
                break;
            case Trash::REVERT:
                $this->handleRevert();
                break;
            case Trash::DELETE:
                $this->handleDelete();
                break;
            case Trash::CLEAN:
                $this->handleClean();
                break;

            case Trash::SUPERDELETE:
                /* @since 1.0.0  by Kindac */
                $this->handleSuperDelete();
                break;
            case Trash::SUPERCLEAN:
                /* @since 1.0.0  by Kindac */
                $this->handleSuperClean();
                break;
            default:
                break;
        }
    }

    /**
     *
     * 获取回收站列表
     */
    private function handleListTrash ()
    {
        $fileHandle      = new TrashList();
        $this->trashList = $fileHandle->obtainTrashList($this->_userId, $this->limit, $this->offset);
    }

    /**
     *
     * 将删除的文件（夹）还原
     *
     * @since 1.0.7
     */
    private function handleRevert() {
        // 获取用户信息
        $device = new UserDevice();
        $device = $device->findByUserIdAndType($this->_userId, CConst::DEVICE_WEB);
        $this->_deviceId = $device["id"];
        $this->_deviceName = $device["user_device_name"];
        $user = User::model()->findByPk($this->_userId);
        $this->_userNick = $user["user_name"];

        $filter = MSharesFilter::init();
        $files = UserFile::model()->getFilesByIds($this->fromIds, 1);
        foreach ($files as $file) {
            $file_path = CUtils::removeUserFromPath($file["file_path"]);
            if ($filter->handlerCheck($this->_userId, $file_path)) {
                $this->_userId  = $filter->master;
                $path           = $filter->_path;
            }
            if ($filter->is_shared && $filter->master != $filter->operator){
                //如果没有读权限则不进行删除
                $permission = Yii::app()->privilege->checkPrivilege('/'.$filter->master.$path);
                if (!$permission[MPrivilege::RESOURCE_READ]){
                    continue;
                }
                //没有删除权限则不能进行还原
                if ($file["file_type"] == 0 && !$permission[MPrivilege::FILE_DELETE]){
                    continue;
                } elseif (!$permission[MPrivilege::FOLDER_DELETE]) {
                    continue;
                }
            }

            $path = $file["file_path"];
            $file["is_deleted"] = 0;
            $file["event_uuid"] = MiniUtil::getEventRandomString(46);
            $file['file_update_time'] = time();
            $file->save();
            // 创建事件
            $share_filter = MSharesFilter::init();
            $share_filter->handlerCheckByFile($this->_userId, $file);
            if ($file["file_type"] == 0) {
                $version = FileVersion::model()->findByPk($file["version_id"]);
                // 创建meta
                $this->handleFileMeta($path, $file["version_id"], $filter->operator, $this->_userNick, CConst::CREATE_FILE, $this->_deviceName, $file['file_size']);
                $hash = $version["file_signature"];
                $context = array( "hash"  => $hash,
                          "rev"   => (int)$file["version_id"],
                          "bytes" => (int)$file["file_size"],
                          "update_time" => (int)$file['file_update_time'],
                          "create_time" => (int)$file['file_create_time']);
                MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, 3, $path, serialize($context), $file["event_uuid"], $share_filter->type);
                $share_filter->handlerAction(3, $this->_deviceId, $path, $context);
                continue;
            }

            // 文件夹创建事件
            $event->CreateEvent($this->_userId, $this->_deviceId, 0, $path, $path, $file["event_uuid"], $share_filter->type);
            $share_filter->handlerAction(0, $this->_deviceId, $path, $path);
            // 处理子文件
            $children = UserFile::model()->getFilesByPath($path, 1);
            foreach ($children as $child) {
                $child["is_deleted"] = 0;
                $child["event_uuid"] = MiniUtil::getEventRandomString(46);
                $child['file_update_time'] = time();
                $child->save();
                $path = $childcontext = $child["file_path"];
                // 如果是文件则创建meta
                if ($child["file_type"] == 0) {
                    $version = FileVersion::model()->findByPk($child["version_id"]);
                    // 创建meta
                    $this->handleFileMeta($path, $child["version_id"], $filter->operator, $this->_userNick, CConst::CREATE_FILE, $this->_deviceName, $child['file_size']);
                    $hash = $version["file_signature"];
                    $childcontext = array( "hash"  => $hash,
                              "rev"   => (int)$child["version_id"],
                              "bytes" => (int)$child["file_size"],
                              "update_time" => (int)$child['file_update_time'],
                              "create_time" => (int)$child['file_create_time']);
                    // 文件夹创建事件
                    MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, 3, $path, serialize($childcontext), $child["event_uuid"],$share_filter->type);
                    $share_filter->handlerAction(3, $this->_deviceId, $path, $childcontext);
                    continue;
                }
                // 文件夹创建事件
                $event->CreateEvent($this->_userId, $this->_deviceId, 0, $path, $childcontext, $child["event_uuid"]);
                $share_filter->handlerAction(0, $this->_deviceId, $path, $childcontext);
            }
        }
        $this->handleResult(TRUE, 0, Yii::t('api_message', 'action_success'));
    }

    /**
     *
     * 删除回收站一条记录
     *
     * @since 1.0.7
     */
    private function handleDelete() {
        if ($this->fromIds == "-1") {
            throw new ApiException(Yii::t('common', 'param_error'));
        }

        // 获取文件（夹）信息
        $files = UserFile::model()->getFilesByIds($this->fromIds, 1);
        // 我的最爱和分享都删除
        //        FileStar::model()->deleteAllByFileIds($this->_userId, $this->fromIds);
        FileStar::model()->deleteAll('id in (:ids)', array(':ids'=>$this->fromIds));
        MiniLink::getInstance()->unlink($this->fromIds);

        $publicFilter = MSharesFilter::init();
        // 如果是文件，则直接删除,否则删除文件夹下子文件
        foreach ($files as $file) {
            //先进行回收站删除权限的判断
            $file_path = CUtils::removeUserFromPath($file["file_path"]);
            if ($publicFilter->handlerCheck($this->_userId, $file_path)) {
                $this->_userId  = $publicFilter->master;
                $path           = $publicFilter->_path;
            }
            if ($publicFilter->is_shared && $publicFilter->master != $publicFilter->operator){
                //如果没有读权限则不进行删除
                $permission = Yii::app()->privilege->checkPrivilege('/'.$publicFilter->master.$path);
                if (!$permission[MPrivilege::RESOURCE_READ]){
                    continue;
                }
                if ($file["file_type"] == 0 && !$permission[MPrivilege::FILE_DELETE]){
                    continue;
                } elseif(!$permission[MPrivilege::FOLDER_DELETE]) {
                    continue;
                }
            }

            if ($file["file_type"] == 0) {
                MiniVersion::getInstance()->updateRefCount($file['version_id'], FALSE);
                $file->delete();
                continue;
            }

            $parentPath = $file["file_path"];
            $children   = UserFile::model()->getFilesByPath($parentPath,1);
            foreach ($children as $child) {
                if ($child["file_type"] == 0) {
                    MiniVersion::getInstance()->updateRefCount($child['version_id'], FALSE);
                }
                $child->delete();
            }
            $file->delete();
        }
        $this->handleResult(TRUE, 0, Yii::t('api_message', 'action_success'));
    }

    /**
     *
     * 删除回收站所有记录
     */
    private function handleClean() {
        
        $this->fromIds = $this->getTrashList();
        if (empty($this->fromIds)) {
            $this->handleResult(TRUE, 0, Yii::t('api_message', 'action_success'));
            return;
        }
        $files = UserFile::model()->getFilesByIds($this->fromIds, 1);
//         $files = UserFile::model()->findAllByAttributes(array("user_id" => $this->_userId, "is_deleted" => 1));
        //为清空回收站文件添加文件
        $ids = array();
        foreach ($files as $file) {
            array_push($ids, $file["id"]);
            //
            // 将文件的版本引用次数减1
            //
            if ($file['file_type'] == 0) {
                MiniVersion::getInstance()->updateRefCount($file['version_id'], FALSE);
            }
        }
        $ids = join(",", $ids);
        // 我的最爱和分享都删除
        if (!empty($ids)) {
            FileStar::model()->deleteAll('id in ('.$ids.')');
            MiniLink::getInstance()->unlink($ids);
            $value = UserFile::model()->deleteAll('id in ('.$ids.')');
        }
        if ($value >= 0) {
            $this->handleResult(TRUE, 0, Yii::t('api_message', 'action_success'));
        }
    }

    /**
     * 删除回收站一条记录, 但保留源数据
     * @since 1.0.0
     * by Kindac
     */
    private function handleSuperDelete() {
        if ($this->fromIds == "-1") {
            throw new ApiException(Yii::t('common', 'param_error'));
        }
        // 获取文件（夹）信息
        $files = UserFile::model()->getFilesByIds($this->fromIds, 1);
        // 我的最爱和分享都删除
        //        FileStar::model()->deleteAllByFileIds($this->_userId, $this->fromIds);
        FileStar::model()->deleteAll('id in ('.$this->fromIds.')');
        MiniLink::getInstance()->unlink($this->fromIds);

        // 如果是文件，则直接删除,否则删除文件夹下子文件
        foreach ($files as $file) {
            if ($file["file_type"] == 0) {
                MiniVersion::getInstance()->updateRefCount($file['version_id'], FALSE);
                $file["is_deleted"] = -1;
                $file->save();
                continue;
            }

            $parentPath = $file["file_path"];
            $children   = UserFile::model()->getFilesByPath($parentPath,1);
            foreach ($children as $child) {
                if ($child["file_type"] == 0) {
                    MiniVersion::getInstance()->updateRefCount($child['version_id'], FALSE);
                }
                $child["is_deleted"] = -1;
                $child->save();
            }
            $file["is_deleted"] = -1;
            $file->save();
            continue;
        }
        $this->handleResult(TRUE, 0, Yii::t('api_message', 'action_success'));
    }

    /**
     * 删除回收站所有记录, 但保留源数据
     * @since 1.0.0
     * by Kindac
     */
    private function handleSuperClean() {
        
        $this->fromIds = $this->getTrashList();
        if (empty($this->fromIds)) {
            $this->handleResult(TRUE, 0, Yii::t('api_message', 'action_success'));
            Yii::app()->end();
        }
        $files = UserFile::model()->getFilesByIds($this->fromIds, 1);
//         $files = UserFile::model()->findAllByAttributes(array("user_id" => $this->_userId, "is_deleted" => 1));
        $ids = array();
        foreach ($files as $file) {
            array_push($ids, $file["id"]);
            //
            // 将文件的版本引用次数减1
            //
            if ($file['file_type'] == 0) {
                MiniVersion::getInstance()->updateRefCount($file['version_id'], FALSE);
            }
        }
        $ids = join(",", $ids);
        // 我的最爱和分享都删除
        $value = 0;
        if (!empty($ids)) {
            FileStar::model()->deleteAll('id in ('.$ids.')');
            MiniLink::getInstance()->unlink($ids);
            $value = UserFile::model()->updateAll(array("is_deleted" => -1), "id in ($ids)");
        }
        
        if ($value >= 0) {
            $this->handleResult(TRUE, 0, Yii::t('api_message', 'action_success'));
        }
    }

    /** 创建meta信息
     */
    public function handleFileMeta($filePath, $versionId, $userId, $userNick, $action, $deviceName, $fileSize) {
        //
        // 查询之前的版本
        //
        $handler = new FileMeta();
        $meta = $handler->getFileMeta($filePath, "version");
        if (!$meta) {
            $meta = new FileMeta();
            $meta["file_path"]  = $filePath;
            $meta["meta_key"]   = "version";
            $meta["meta_value"] = serialize(array());
        }
        $value = CUtils::getFileVersions($deviceName, $fileSize, $versionId, $action, $userId, $userNick, $meta["meta_value"]);
        $meta["meta_value"] = $value;
        if ($action == MConst::CREATE_FILE || $action == MConst::MODIFY_FILE || $action == CConst::WEB_RESTORE) {
            FileVersion::model()->updateRefCountByIds(array($versionId), TRUE);
        }
        return $meta->save();
    }
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException ($exception)
    {
        $this->result["msg"] = $exception->getMessage();
        echo CJSON::encode($this->result);
        Yii::app()->end();
    }
    
    /**
     * 获取回收站文件
     * @return boolean|string
     */
    private function getTrashList() {
        
        $condition = "user_id=:user_id and is_deleted = 1";
        //and parent_file_id not in (select id from ".Yii::app()->params['tablePrefix']."files where is_deleted = 1 and user_id=:user_id)";
        $params    = array(':user_id'=>$this->_userId);
        
        //
        // 修改查询条件
        //
        $value     = array('condition'=>$condition, 'params' => $params);
        $info = UserFile::model()->findAll(array(
                'condition' => $value['condition'],
                'params'    => $value['params'],
        )
        );
        $files = '';
        foreach ($info as $item) {
            $files .= ',' . $item['id'];
        }
        
        if (empty($files)) {
            return false;
        }
        return substr($files, 1); 
    }
}