<?php
/**
 * 文件的Model
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserFile extends CMiniyunModel
{
	/**
	 * 已经使用的空间，单位字节
	 * @var int
	 */
    public $usedSize;
    public $maxUpdatedAt;
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'files';
    }
    /**
     * 获得用户文件总数，用户列表场景
     */
    public function getUserFileCount($ids){
        if ($ids=="") return array();
        $dbCommand = Yii::app()->db->createCommand("SELECT user_id,COUNT(*) as count FROM `".Yii::app()->params['tablePrefix']."files` where user_id in(".$ids.") GROUP BY user_id");
        return $dbCommand->queryAll();
    }
    /**
     *
     * 用户使用的空间数
     * 仅仅计算用户自己的空间消耗，该函数仅用于管理后台，同时该函数被弃用
     * @deprecated
     */
    public function getUsedSpace($ids){

        if ($ids=="") return array();
        $dbCommand = Yii::app()->db->createCommand("SELECT user_id,sum(file_size) as usedSpace FROM `".Yii::app()->params['tablePrefix']."files` where user_id in(".$ids.") GROUP BY user_id");
        $spaceData = $dbCommand->queryAll();
        return $spaceData;
    }
    /**
     * 清理用户文件
     */
    public function deleteUserFile($userIds){
        if ($userIds!='' && strlen($userIds)>0){
            $data = $this->findAll("user_id in(".$userIds.")");//可根据version_id>0把目录过滤除去，不过考虑到后面要取全部的file.id，没有加上这层过滤
            $versionIds = $this->getIds($data,"version_id");
            $fileIds = $this->getIds($data);
            $fileVersion = new FileVersion();
            $fileVersion->deleteFileVersion($versionIds);//将对应用户的文件版本减1
            $fileMeta = new FileMeta();
            $fileMeta->deleteFileMeta($userIds);//删除FileMeta信息
            MiniLink::getInstance()->unlink($fileIds);//删除共享文件信息
            $this->deleteAll("user_id in(".$userIds.")");//删除文件与文件夹自身
        }
    }

    /**
     * 还原回收站文件
     */
    public function revertFile($fileIds){

        if ($fileIds==='' && strlen($fileIds)<=0)
        {
            return 0;
        }
        $models = $this->findAll("id in(".$fileIds.")");
        $device_id = Yii::app()->session["deviceId"];
        foreach ($models as $model) {
            $share_filter = MSharesFilter::init();
            $user_id    = $model['user_id'];
            $path       = $model['file_path'];
            $context    = $path;
            
            $share_filter->handlerCheckByFile($user_id, $model);
            
            // 确保父目录被还原
            $createFolder = new CreateFolder();
            $createFolder->_deviceId = $device_id;
            $createFolder->_userId   = $user_id;
            $createFolder->share_filter = $share_filter;
            $pathInfo = CUtils::pathinfo_utf($path);

            try {
                $parentId  = $createFolder->handleCreateByPath($pathInfo["dirname"]);
            } catch (ApiException $e) {
                Yii::log($e->getMessage());
                continue;
            }

            $event_uuid = MiniUtil::getEventRandomString(46);
            $action     = 0;  // 创建文件夹
            if ($model['file_type'] == 0) {
                //
                // 如果是文件,需要创建版本
                //
                $user = Yii::app()->session['user'];
                $device = UserDevice::model()->findByUserIdAndType($user['id'], CConst::DEVICE_WEB);
                $deviceName = $device["user_device_name"];
                $this->_saveFileMeta($path, $model['version_id'], $user['id'], $user['name'], CConst::WEB_RESTORE, $deviceName, $model['file_size']);
                $action = CConst::CREATE_FILE;
                $version = FileVersion::model()->findByPk((int)$model['version_id']);
                $context = array( "hash"  => $version['file_signature'],
                          "rev"   => (int)$model["version_id"],
                          "bytes" => (int)$model["file_size"]);
                $context = serialize($context);
            }
            MiniEvent::getInstance()->createEvent($user_id, $device_id, $action, $path, $context, $event_uuid,$share_filter->type);
            $model['event_uuid'] = $event_uuid;
            $model['file_update_time'] = time();
            $model["is_deleted"] = 0;
            $model->save();
            
            $share_filter->handlerAction($action, $device_id, $path,unserialize($context));
        }

        return TRUE;

    }
    /**
     * 回收站文件
     */
    public function removedFile($fileIds){

        if ($fileIds==='' && strlen($fileIds)<=0)
        {
            return 0;
        }
        $ids='';
        $arr = array();
        $data = $this->findAll("id in(".$fileIds.")");
        foreach ($data as $key=>$value)
        {
            //将需要删除的自身加入到待删除列表
            if(strlen($ids)>0){
                $ids = $ids.",";
            }
            $ids = $ids.$value["id"];
            
            //如果是文件没有递归操作
            if ($value['file_type'] == 0){
                continue;
            }
            //
            // 递归删除所有子文件
            //
            $moreData = $this->findAll("user_id=:type1 and file_path like :type2",
            array('type1'=>$value['user_id'], 'type2'=>$value['file_path']."/%"));
            $aaa = $value['user_id'];
            $bbb = $value['file_path'];
            foreach ($moreData as $k=>$v)
            {
                $id = $v['id'];
                if (in_array($id, $arr))
                {
                    // 排除重复
                    continue;
                }
                $ids = $ids.",";
                
                array_push($arr,$id);
                $ids = $ids.$id;
            }
        }

        $models = $this->findAll("id in(".$ids.")");
        $device_id = Yii::app()->session["deviceId"];
        foreach ($models as $model) {

            $user_id    = $model['user_id'];
            $path       = $model['file_path'];
            $context = $path;
            $event_uuid = MiniUtil::getEventRandomString(46);
            $action     = 1;  // 修改文件
            $share_filter = MSharesFilter::init();
            $share_filter->handlerCheckByFile($user_id, $model);
            if ($model['file_type'] == 0) {
                //
                // 如果是文件,需要创建版本
                //
                $user = Yii::app()->session['user'];
                $device = UserDevice::model()->findByUserIdAndType($user['id'], CConst::DEVICE_WEB);
                $deviceName = $device["user_device_name"];
                $this->_saveFileMeta($path, $model['version_id'], $user['id'], $user['name'], CConst::DELETE, $deviceName, $model['file_size']);
            } else {
                $handler = new ShareManager();
                $handler->_userId = $user_id;
                $handler->_id     = $model["id"];
                try {
                    $handler->invoke(ShareManager::CANCEL_SHARED);
                } catch (Exception $e) {
                }
            }
            $model['event_uuid'] = $event_uuid;
            $model["is_deleted"] = 1;
            if ($model['file_type'] != 3){
                if ($model['file_type']==2){
                    $model["file_type"]  = 1;
                }
                $model->save();
            }
            MiniEvent::getInstance()->createEvent($user_id, $device_id, $action, $path, $context, $event_uuid, $share_filter->type);
            $share_filter->handlerAction($action, $device_id, $path,$context);
        }

        return true;

    }
    /**
     * 彻底删除文件
     * 根据{id}递归删除自目录
     * 根据{id}找出文件的{version_id}，把文件引用数减1
     * 根據{id}删除fileMeta
     * 根据{id}删除file
     * @param $fileIds
     * @param $force
     * @return boolean
     */
    public function deletedFile($fileIds,$force=false){

        if ($fileIds==='' && strlen($fileIds)<=0)
        {
            return 0;
        }
        $ids  = '';
        $arr  = array();
        $data = $this->findAll("id in(".$fileIds.")");
        $list = array();
        $device_id = Yii::app()->session["deviceId"];
        foreach ($data as $key=>$value)
        {
            //
            // 递归删除所有子文件
            //
            $moreData = $this->findAll("user_id=:type1 and file_path like :type2",
                                       array('type1'=>$value['user_id'], 'type2'=>$value['file_path']."%"));
            
            
            
            foreach ($moreData as $k=>$v)
            {
                if ($v['is_deleted'] == 0||$v['is_deleted'] == -1) {
                    $user_id    = $v['user_id'];
                    $path       = $v['file_path'];
                    $context = $path;
                    $event_uuid = MiniUtil::getEventRandomString(46);
                    $action     = 1;  // 修改文件

                    $share_filter = MSharesFilter::init();
                    $share_filter->handlerCheckByFile($user_id, $v);
                    if ($v['file_type'] == 0) {
                        //
                        // 如果是文件,需要创建版本
                        //
                        $user = Yii::app()->session['user'];
                        $device = UserDevice::model()->findByUserIdAndType($user['id'], CConst::DEVICE_WEB);
                        $deviceName = $device["user_device_name"];
                        $this->_saveFileMeta($path, $v['version_id'], $user['id'], $user['name'], CConst::DELETE, $deviceName, $v['file_size']);
                    }
                    MiniEvent::getInstance()->createEvent($user_id, $device_id, $action, $path, $context, $event_uuid, $share_filter->type);
                    $share_filter->handlerAction($action, $device_id, $path, $context);
                    //
                    // 删除共享目录
                    //
                    if ($v['file_type'] == 2 || $v['file_type'] == 3) {
                        $handler = new ShareManager();
                        $handler->_userId = $share_filter->operator;
                        $handler->_id     = $v['id'];
                        try {
                            $handler->invoke(ShareManager::CANCEL_SHARED);
                        } catch (Exception $e) {
                            
                        }
                    } 
                }
                    
                $id = $v['id'];
                if (in_array($id, $arr))
                {
                    // 排除重复
                    continue;
                }

                if ($v['file_type'] == 0) {
                    array_push($list, $v['version_id']);
                }
                
                if(strlen($ids)>0){
                    $ids = $ids.",";
                }
                array_push($arr,$id);
                $ids = $ids.$id;
            }
        }
        
        //
        // 更新文件版本引用次数
        //
        $version = new FileVersion();
        $version->updateRefCountByIds($list);
        //删除共享文件信息
        MiniLink::getInstance()->unlink($ids);
        FileStar::model()->deleteAll('id in (:ids)', array(':ids' => $ids));
        //后台管理员台实现逻辑
        //回收站插件
        $needDelete = false;
        if($force){
            $needDelete = true;
        }
        $superDelete = apply_filters("trash_add");
        if (!($superDelete===true)) {
            $needDelete = true;
        }
        if ($needDelete) {
            $value = $this->deleteAll("id in(".$ids.")");
        } else {
            $value = $this->updateAll(array("is_deleted" => -1), "id in ($ids)");
        }
        return $value;

    }
    /**
     * 更名
     */
    public function updateFileName($id, $name, &$message)
    {
        if (CUtils::checkNameInvalid($name) || strstr($name, '&') || strstr($name, ';')) {
            $message = Yii::t("front_common", "name_error");
            return false;
        }
        $model = $this->findByAttributes(array('id'=>$id));
        if (empty($model))
        {
            $message = Yii::t("admin_common", "rename_file_not_found");
            return false;
        }
        $oldPath                   = $model['file_path'];
        $model['file_name']        = $name;
        $model['file_path']        = dirname($model['file_path'])."/".$name;
        //
        // 判断同名否
        //
        $same = $this->findByAttributes(array('file_path'=>$model['file_path']));
        if ($same)
        {
            $message = Yii::t("admin_common", "rename_file_already_exists");
            return false;
        }
        $time                      = (int)time();
        $model['file_update_time'] = $time;
        $path       = $model['file_path'];
        $context = $path;
        $device_id = Yii::app()->session["deviceId"];
        $share_filter = MSharesFilter::init();
        $share_filter->handlerCheckByFile($model['user_id'], $model);
        
        if ($model['file_type'] == 0)
        {
            $model['mime_type'] = CUtils::mime_content_type($name);
            //
            // 如果是文件,需要创建版本
            //
            $user = Yii::app()->session['user'];
            $device = UserDevice::model()->findByUserIdAndType($user['id'], CConst::DEVICE_WEB);
            $deviceName = $device["user_device_name"];
            $this->_saveFileMeta($path, $model['version_id'], $user['id'], $user['name'], CConst::CREATE_FILE, $deviceName, $model['file_size']);
        } else
        {
            //
            // 文件夹需要更名所有子目录
            //
            $moreFile = UserFile::model()->findAll("file_path like :type", array('type'=>$oldPath.'%'));
            
            foreach ($moreFile as $key=>$value)
            {
                $data = date("Y-m-d H:i:s",time());

                //
                // 查找匹配路径 index, 替换路径
                //
                $index = strlen($oldPath);
                $value['file_path'] = substr_replace($value['file_path'], $model['file_path'], 0, $index);
                $s = UserFile::model()->updateAll(array(
                        'file_path'        => $value['file_path'],
                        'file_update_time' => $time,
                        'updated_at'       => $data,
                ), "id=".$value['id']);
                // 文件创建meta

                if ($value['file_type'] == 0) {
                    //
                    // 如果是文件,需要创建版本
                    //
                    $user = Yii::app()->session['user'];
                    $device = UserDevice::model()->findByUserIdAndType($user['id'], CConst::DEVICE_WEB);
                    $deviceName = $device["user_device_name"];
                    $this->_saveFileMeta($value['file_path'], $value['version_id'], $user['id'], $user['name'], CConst::CREATE_FILE, $deviceName, $value['file_size']);
                }
            }
        }

        $user_id    = $model['user_id'];

        $event_uuid = MiniUtil::getEventRandomString(46);
        $action     = MConst::MOVE;  // 重命名
        MiniEvent::getInstance()->createEvent($user_id, $device_id, $action, $oldPath, $context, $event_uuid, $share_filter->type);
        //
        // 重命名共享目录
        //
        if ($model['file_type'] >= 2) {
            $share_filter->handlerRenameShared($oldPath, $context);
        } else {
            $share_filter->handlerAction($action, $device_id, $oldPath, $context);
        }
        
        $model['event_uuid'] = $event_uuid;
        $message = Yii::t("admin_common", "rename_file_success");
        return $model->save(false);
    }
    /**
     * 为更名文件创建meta版本
     */
    private function _saveFileMeta($filePath, $versionId, $userId, $userNick, $action, $deviceName, $fileSize)
    {
        //
        // 获取文件旧版本
        //
        $meta = FileMeta::model()->findByAttributes(array('file_path'=>$filePath,'meta_key'=>'version'));
        if (!$meta) {
            $meta = new FileMeta();
            $value = CUtils::getFileVersions($deviceName, $fileSize, $versionId, $action, $userId, $userNick);
            $meta["file_path"]  = $filePath;
            $meta["meta_key"]   = "version";
            $meta["meta_value"] = serialize(array());
        }
        $value = CUtils::getFileVersions($deviceName, $fileSize, $versionId, $action, $userId, $userNick, $meta["meta_value"]);
        $meta["meta_value"] = $value;
        $meta->save();
        
        if ($action == MConst::CREATE_FILE || $action == MConst::MODIFY_FILE || $action == CConst::WEB_RESTORE) {
            FileVersion::model()->updateRefCountByIds(array($versionId), TRUE);
        }
    }

    /**
     * 恢复文件版本
     */
    public function revertVersion($id, $version_id)
    {
        if (empty($version_id)) {
            return false;
        }
        $version = FileVersion::model()->findByPk($version_id);
        if (empty($version)) {
            return false;
        }
        $model = $this->findByPk($id);
        if (empty($model)) {
            return false;
        }
        $time                = (int)time();
        $model['version_id'] = $version_id;
        $model['file_update_time'] = $time;
        $model['file_size'] = $version['file_size'];
        
        // 事件
        $user_id    = $model['user_id'];
        $path       = $model['file_path'];
        $event_uuid = MiniUtil::getEventRandomString(46);
        $action     = MConst::MODIFY_FILE;  // 修改文件
        $context = array( "hash"  => $version['file_signature'],
                          "rev"   => (int)$model["version_id"],
                          "bytes" => (int)$model["file_size"]);
        // 共享判断
        $shareFilter = MSharesFilter::init();
        $shareFilter->handlerCheckByFile($user_id, $model);
        
        $user = Yii::app()->session['user'];
        $device = UserDevice::model()->findByUserIdAndType($user['id'], CConst::DEVICE_WEB);
        $deviceName = $device["user_device_name"];
        $this->_saveFileMeta($path, $version_id, $user['id'], $user['name'], CConst::WEB_RESTORE, $deviceName, $version['file_size']);
        $model['event_uuid'] = $event_uuid;
        $model->save();
        MiniEvent::getInstance()->createEvent($user_id, $device['id'], $action, $path, serialize($context), $event_uuid,$shareFilter->type);
        if ($shareFilter->is_shared) {
            $shareFilter->handlerAction($action, $device['id'], $path, $context);
        }
        return true;
    }


    /**
     * 全部还原
     */
    public function revertFileAll()
    {
        $models = $this->findAllByAttributes(array('is_deleted'=> 1));
        $device_id = Yii::app()->session["deviceId"];
        foreach ($models as $model) {
            $shareFilter = MSharesFilter::init();
            $shareFilter->handlerCheckByFile($model['user_id'], $model);
            $user_id    = $model['user_id'];
            $path       = $model['file_path'];
            $context    = $path;
            $event_uuid = MiniUtil::getEventRandomString(46);
            $action     = 0;  // 修改文件
            if ($model['file_type'] == 0) {
                //
                // 如果是文件,需要创建版本
                //
                $user = Yii::app()->session['user'];
                $device = UserDevice::model()->findByUserIdAndType($user['id'], CConst::DEVICE_WEB);
                $deviceName = $device["user_device_name"];
                $this->_saveFileMeta($path, $model['version_id'], $user['id'], $user['name'], CConst::WEB_RESTORE, $deviceName, $model['file_size']);
                $action = CConst::CREATE_FILE;
                $version = FileVersion::model()->findByPk($model["version_id"]);
                $context = array( "hash"  => $version['file_signature'],
                          "rev"   => (int)$model["version_id"],
                          "bytes" => (int)$model["file_size"]);
                $context = serialize($context);
            }

            $model['event_uuid'] = $event_uuid;
            $model["is_deleted"] = 0;
            $model->save();
            MiniEvent::getInstance()->createEvent($user_id, $device_id, $action, $path, $context, $event_uuid, $shareFilter->type);
        }
        return true;
    }

    /**
     * 返回回收站文件数量
     * 
     * @since 1.1.2
     */
    public function deletedCount($sql = "")
    {
        if (empty($sql)){
            return $this->count('is_deleted=1');
        }
        return $this->count("is_deleted=1 and {$sql}");
    }

    /**
     * 返回彻底删除文件数量
     */
    public function superDeletedCount()
    {
        return $this->count('is_deleted=-1');
    }
    
    /**
     * 文档数量
     * 
     * @since 1.1.2
     */
    public function officeCount($sql = "")
    {
        $officeConditons = "";
        $officeTypes = Yii::app()->params['officeType'];
        $officeConditons = "'".implode("','",array_values($officeTypes))."'";
        if (empty($sql)){
            return $this->count("mime_type in($officeConditons) and is_deleted=0");
        }
        return $this->count("mime_type in($officeConditons) and is_deleted=0 and {$sql}");
    }
   
    /**
     * 图片数量
     */
    public function imageCount($sql = "")
    {
        if (empty($sql)){
            return $this->count("mime_type like 'image%' and is_deleted=0");
        }
        return $this->count("mime_type like 'image%' and is_deleted=0 and {$sql}");
    }
    /**
     * 获得用户所有的文件数目，未删除的
     */
    public function getCount($userId){
        return $this->count("user_id=? and is_deleted=0",array($userId));
    }
    
    /**
     * 获得用户所有的文件夹数目，未删除的
     * 
     * @since 1.1.2
     */
    public function foldersCount($sql = ""){
        if (empty($sql)){
            return $this->count("is_deleted=0 and file_type > 0");
        }
        return $this->count("is_deleted=0 and file_type > 0 and {$sql}");
    }
   

    /**
     * 音乐数量
     * @since 1.1.2
     */
    public function audioCount($sql='')
    {
        if (empty($sql)){
            return $this->count("mime_type like 'audio%' and is_deleted=0");
        }
        return $this->count("mime_type like 'audio%' and is_deleted=0 and {$sql}");
    }

    /**
     * 视频数量
     * @since 1.1.2
     */
    public function vedioCount($sql='')
    {
        if (empty($sql)){
            return $this->count("mime_type like 'audio%' and is_deleted=0");
        }
        return $this->count("mime_type like 'audio%' and is_deleted=0 and {$sql}");
    }
  
    /**
     * 获得用户的所有目录信息 并转换为json对象
     */
    public function getAllFolderJson($userId){
        $share_filter = MSharesFilter::init();
        $ids = $share_filter->handlerFilterDocuments($userId, 1);
        $condition = "user_id=?";
        if (!empty($ids)) {
            $condition = "(id in (" . $ids . ") or user_id=?)";
        }
        $items =  $this->findAll($condition . " and file_type != 0 and is_deleted=0",array($userId));
        $data = array();
        $first = Array();
        $first['name'] = Yii::app()->getLanguage() == 'en' ? Yii::t('common', 'my_cloud') : Yii::t('front_common', 'netdisk_index_file', array("{name}"=>Yii::app()->params['app']['name']));
        $first['aid'] = 1;
        $first['cid'] = "1_0";
        $first['pid'] = 0;
        $first['path'] = "/";
        $first['s'] = 0;
        $data['1_0'] = $first;

        foreach ($items as $index=>$item){
            $folder = array();
            $folder["name"]=$item["file_name"];
            $folder["aid"]="1";
            $folder["cid"]=$item["id"];
            $folder['path'] = $item["file_path"];
            if ($item["parent_file_id"] == 0){
                $folder["pid"]="1_0";
            }else{
                $folder["pid"]=$item["parent_file_id"];
            }

            $folder["is_share"]="0";
            if ($item['file_type'] > 1) {
                $folder["is_share"]="1";
            }
            
            if ($item['file_type'] > 2) {
                //判断是否有权限进行显示
                try {
                    $share_filter->hasPermissionExecute($item['file_path'], MPrivilege::RESOURCE_READ);
                } catch (Exception $e) {
                    continue;
                }
                $id = $share_filter->handlerFindSlave($item['user_id'], $item['file_path']);
                if ($id != false) {
                    $folder['cid'] = $id;
                    $item['id'] = $id;
                }
            }

            $folder["s"]=$index;
            $folder["category_file_count"]="45";
            $folder["pick_code"]="";
            $folder["category_cover"]="";
            $data[$item["id"]]=$folder;
        }
        
        $retVal = array(
            "state"=>true,
            "data" =>$data,
            "msg"=>"",
            "msg_code"=>""
            );
        return json_encode($retVal);
    }


    /**
     * 获得用户的所有目录信息
     */
    public function getAllFolder($userId,$data = array()){
        $items =  $this->findAll("user_id=? and file_type=1 and is_deleted=0",array($userId));
        foreach ($items as $index=>$item){
            $folder = array();
            $folder["name"]=$item["file_name"];
            $folder["aid"]="1";
            $folder["cid"]=$item["id"];
            if ($item["parent_file_id"] == 0){
                $folder["pid"]="1_0";
            }else{
                $folder["pid"]=$item["parent_file_id"];
            }
            $folder["is_share"]="0";//TODO 显示共享目录
            $folder["s"]=$index;
            $folder["category_file_count"]="45";
            $folder["pick_code"]="";
            $folder["category_cover"]="";
            $data[$item["id"]]=$folder;
        }
        return $data;
    }


    /**
     * 获取指定目录下文件的数量
     */
    public function getFileCount($fileId)
    {
        //
        // 回收站插件: -1保留值 0正常 1删除
        // 这里由is_deleted==1 特别修改为 is_deleted!=0
        // By Kindac 2012/11/5
        //
        $condition = "is_deleted=0 and parent_file_id=:parent_file_id and file_type = 0";
        $params    = array(':parent_file_id'=>$fileId);
        return $this->count($condition, $params);
    }

    /**
     * 获得垃圾箱信息
     */
    public function getTrashCount($userId){
        return $this->count("user_id=? and is_deleted=1",array($userId));
    }


    /**
     * 根据用户名称查询用户文件信息
     */
    public function getByFileName($userId, $fileName){
        return $this->find("user_id=? and file_name=?",array($userId,$fileName));
    }


    /**
     * 根据用户名称查询用户文件信息
     */
    public function getByParentID($userId, $parent_file_id){
        return $this->findAll("user_id=? and parent_file_id=? and is_deleted=0",array($userId,$parent_file_id));
    }
    
    /**
     *
     * 根据ids查询用户的文件（夹）
     * @param int $userId
     * @param string $ids   - 以,分割的字符串
     */
    public function getUserFilesByIds($userId, $ids, $is_deleted = 0) {
        return $this->findAll("user_id={$userId} and id in ($ids) and is_deleted = $is_deleted");
    }

    /**
     *
     * 根据ids查询用户的文件（夹）
     * @param int $userId
     * @param string $ids   - 以,分割的字符串
     */
    public function getFilesByIds($ids, $is_deleted = 0) {
        return $this->findAll("id in ($ids) and is_deleted = $is_deleted");
    }

    /**
     *
     * 根据path查询用户的文件（夹）
     * @param string $path
     */
    public function getFilesByPath($path, $is_deleted = 0) {
        return $this->findAll(array('condition' => 'file_path like :file_path and is_deleted =:is_deleted order by id DESC',
                                    'params'    => array(':file_path'=>"$path/%", ':is_deleted' => $is_deleted)));
    }


    /**
     *
     * 根据path查询用户的文件（夹）
     * @param string $path
     * @param int $type
     */
    public function getChildrenFileByPath($path, $type = 0) {
        return $this->findAll(array('condition' => 'file_path like :file_path and file_type = :file_type order by id DESC',
                                    'params'    => array(':file_path'=>"$path/%", ":file_type" => $type)));
    }

    /**
     * 根据path更新
     * @param string $path
     */
    public function updateAllByParentPath($fromPath, $toPath, $userId, $toUserId) {
        $fromPath .= "/";
        $toPath   .= "/";
        $sql = "Update `".Yii::app()->params['tablePrefix']."files` SET file_path = ";
        $sql .= "CONCAT('$toPath', SUBSTRING(file_path,CHAR_LENGTH(:length) + 1))";
        $sql .= ", user_id=:to_user_id";
        $sql .= " WHERE file_path like '$fromPath%' and user_id = :user_id";

        $dbCommand = Yii::app()->db->createCommand($sql);
        $dbCommand->bindParam(":user_id", $userId);
        $dbCommand->bindParam(":length", $fromPath);
        $dbCommand->bindParam(":to_user_id", $toUserId);
        return $dbCommand->execute();
    }

   /**
     * 查询某一用户目录下所有的图片  相册照片浏览
     */
    public function getUserFolderImg($user_id, $parent_file_id, $start, $limit, $is_root, $orderStr){
        $sql_str ="SELECT * FROM " . Yii::app()->params['tablePrefix'] . "files where is_deleted = 0 and mime_type like 'image%' AND parent_file_id=$parent_file_id ";
        if ($is_root == true){
            $sql_str .= "AND user_id=$user_id ";
        }
        $sql_str .= "ORDER BY " .$orderStr. " limit " . $start . ", " . $limit;
        $data = $this->findAllBySql($sql_str);
        return $data;
    }

    /**
     * 
     * 查询某一用户特定地点的 地理照片信息
     * @param unknown_type $user_id
     * @param unknown_type $limit
     * @param unknown_type $start
     * @param unknown_type $ids 是指file的version_id
     * @param unknown_type $share_file_id
     * 
     */
    public function getUserLocationImg($user_id, $limit = 45, $start = 0, $ids, $share_file_id){
        $sql_str ="SELECT * FROM " . Yii::app()->params['tablePrefix'] . "files WHERE is_deleted = 0 AND mime_type like 'image%' AND version_id in (" . $ids . ") ";
        $msf = MSharesFilter::init();
        $share_file_id  =  $msf->handlerFilterDocuments($user_id, 0);
        if ($share_file_id){
            $sql_str .= "AND (user_id=$user_id or id in ($share_file_id))";
        } else {
            $sql_str .= "AND user_id=$user_id";
        }
        $sql_str .= " ORDER BY created_at desc limit " . $start . ", " . $limit;
        $data = $this->findAllBySql($sql_str);
        return $data;
    }
    /**
     * 
     *获取我的最爱图片
     * @param unknown_type $user_id
     * @param unknown_type $limit
     * @param unknown_type $start
     * @param unknown_type $ids  //file_id
     * @param unknown_type $share_file_id
     */
    public function getFavouriteImg($user_id, $limit = 45, $start = 0, $ids, $share_file_id, $orderStr) {
        $sql_str ="SELECT * FROM " . Yii::app()->params['tablePrefix'] . "files WHERE is_deleted = 0 AND mime_type like 'image%' AND id in (" . $ids . ") ";
        $msf = MSharesFilter::init();
        $share_file_id  =  $msf->handlerFilterDocuments($user_id, 0);
        if ($share_file_id){
            $sql_str .= "AND (user_id=$user_id or id in ($share_file_id))";
        } else {
            $sql_str .= "AND user_id=$user_id";
        }
        $sql_str .= " ORDER BY " . $orderStr . " limit " . $start . ", " . $limit;
        
        $data = $this->findAllBySql($sql_str);
        return $data;
    }

    /**
     * 查询所有的包含图片的文件夹
     */
    public function getImageFolder($user_id, $start, $limit) {
        
        $condition = "user_id=:user_id and file_type>2";
        //
        // 修改查询条件
        //
        $value     = array('condition'=>$condition);
        $value = apply_filters('list_public_filter', $value);
        
        $files = $this->findAll(array('condition'=>$value['condition'], 'params'=>array(':user_id'=>$user_id)));
        if (empty($files)){  //没有共享的情况
            //
            // 回收站插件: -1保留值 0正常 1删除
            // 这里由is_deleted==1 特别修改为 is_deleted!=0
            // By Kindac 2012/11/5
            //
            $pdi = UserFile::model()->findAllBySql("SELECT DISTINCT (parent_file_id) AS parent_file_id FROM  " . Yii::app()->params['tablePrefix'] . "files WHERE (user_id =$user_id and is_deleted=0 AND mime_type like 'image%') ORDER BY created_at desc limit " . $start . ", " . $limit);
        } else {   //存在共享的情况
            $likeSql = "(user_id=".$user_id;
            foreach ($files as $file) {
                $version  = FileMeta::model()->getFileMeta($file["file_path"], "shared_folders");
                $var = array('version'=>$version, 'file'=>$file);
                $var = apply_filters('list_public_image', $var);
                $version = $var['version'];
                if (empty($version) || empty($version["meta_value"])) {
                    continue;
                }
                $versionData = unserialize($version["meta_value"]);
                $masterPath = $versionData["path"];
                $masterId   = $versionData["master"];
                $likeSql   .= " or (user_id={$masterId} and file_path like '{$masterPath}/%') ";
            }
            $likeSql .= ")";
            //
            // 回收站插件: -1保留值 0正常 1删除
            // 这里由is_deleted==1 特别修改为 is_deleted!=0
            // By Kindac 2012/11/5
            //
            $pdi = UserFile::model()->findAllBySql("SELECT DISTINCT (parent_file_id) AS parent_file_id FROM `".Yii::app()->params['tablePrefix']."files` where is_deleted=0 and mime_type like 'image%' and ".$likeSql." ORDER BY created_at desc limit " . $start . ", " . $limit);
        }
        return $pdi;
    }
    
    
    
    /**
     * 查询所有的包含图片的文件夹
     */
    public function getAllImageFolder($user_id){
        $files = $this->findAll(array('condition'=>'user_id=:user_id and file_type>2', 'params'=>array(':user_id'=>$user_id)));
        if (empty($files)){  //没有共享的情况
            //
            // 回收站插件: -1保留值 0正常 1删除
            // 这里由is_deleted==1 特别修改为 is_deleted!=0
            // By Kindac 2012/11/5
            //
            $pdi = UserFile::model()->findAllBySql("SELECT DISTINCT (parent_file_id) AS parent_file_id FROM  " . Yii::app()->params['tablePrefix'] . "files WHERE (user_id =$user_id and is_deleted=0 AND mime_type like 'image%') ORDER BY created_at desc");
        } else {   //存在共享的情况
            $likeSql = "(user_id=".$user_id;
            foreach ($files as $file){
                $version  = FileMeta::model()->getFileMeta($file["file_path"], "shared_folders");
                if (empty($version) || empty($version["meta_value"])) {
                    continue;
                }
                $versionData = unserialize($version["meta_value"]);
                $masterPath = $versionData["path"];
                $masterId   = $versionData["master"];
                $likeSql = $likeSql." or (user_id={$masterId} and file_path like '{$masterPath}/%') ";
            }
            $likeSql = $likeSql.")";
            //
            // 回收站插件: -1保留值 0正常 1删除
            // 这里由is_deleted==1 特别修改为 is_deleted!=0
            // By Kindac 2012/11/5
            //
            $pdi = UserFile::model()->findAllBySql("SELECT DISTINCT (parent_file_id) AS parent_file_id FROM `".Yii::app()->params['tablePrefix']."files` where is_deleted=0 and mime_type like 'image%' and ".$likeSql." ORDER BY created_at desc");
        }
        return $pdi;
    }
    
    
    public function creatUserFile($fileDetail)
    {
        $file = new UserFile();
        $file["user_id"]           = $fileDetail->user_id;
        $file["file_type"]         = $fileDetail->file_type;
        $file["parent_file_id"]    = $fileDetail->parent_file_id;
        $file["file_create_time"]  = $fileDetail->file_create_time;
        $file["file_update_time"]  = $fileDetail->file_update_time;
        $file["file_name"]         = $fileDetail->file_name;
        $file["version_id"]        = $fileDetail->version_id;
        $file["file_size"]         = $fileDetail->file_size;
        $file["file_path"]         = $fileDetail->file_path;
        $file["event_uuid"]        = $fileDetail->event_uuid;
        $file->save();
    }
    /**
     * 
     * 查询多个文件
     * @param unknown_type $ids
     */
    public function getFileByIds($ids){
        $fileIds = explode(",", $ids);
        $files = array();
        foreach ($fileIds as $id){
            $file = $this->findByPk($id);
            $files[] = $file;
        }
        
        return $files;
    }
    
    
    /**
     * 删除用户共享数据
     * @since 1.0.6
     * @param integer $userId
     */
    public function deleteSharedFolders($userId) {
        //
        // 删除共享文件
        //
        $shares = $this->findAll('user_id=:user_id and file_type > 1', array(':user_id'=>$userId));
        foreach ($shares as $file) {
            $handler          = new ShareManager();
            $handler->_userId = $file['user_id'];
            $handler->_id     = $file['id'];
            try {
                $handler->invoke(ShareManager::CANCEL_SHARED);
            } catch (Exception $e) {
                continue;
            }
        }
    }
}