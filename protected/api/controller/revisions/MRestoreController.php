<?php
/**
 * Miniyun 处理版本恢复数据
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MRestoreController extends MApplicationComponent implements MIController{
	
    private $_root      = null;
    private $_user_id   = null;
    private $_user_device_name = null;
    /**
     * 控制器执行主逻辑函数
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null)
    {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $params = $_REQUEST;
        // 检查参数
        if (isset($params) === false || $params == null) {
            throw new Exception(Yii::t('api','Invalid parameters'));
        }
        
        // 文件大小格式化参数
        $locale = "bytes";
        if (isset($params["locale"])) {
            $locale = $params["locale"];
        }
        $url                = $uri;
        $url_manager = new MUrlManager();
        $this->_root        = $url_manager->parseRootFromUrl($uri);
        $path               = $url_manager->parsePathFromUrl($uri);
        $path               = MUtils::convertStandardPath($path);
        $originalPath       = $path;
        
        // 检查共享
        $share_filter = MSharesFilter::init();
//        $share_filter
        //
        // 获取用户数据，如user_id
        $user                      = MUserManager::getInstance()->getCurrentUser();
        $device                    = MUserManager::getInstance()->getCurrentDevice();
        $this->_user_id            = $user["user_id"];
        $user_nick                 = $user["user_name"];
        $user_device_id            = $device["device_id"];
        $this->_user_device_name   = $device["user_device_name"];
        
        $rev                       = $params["rev"];
        $rev                = intval($rev);
        $path               = "/{$this->_user_id}{$path}";
        
        //
        // 该文件是否具有此版本
        //
        $file_meta = MFileMetas::queryFileMeta($path, MConst::VERSION);
        if ($file_meta == false || empty($file_meta)) {
            throw new MFileopsException(
                                        Yii::t('api','    Unable to find the revision at that path'),
                                        MConst::HTTP_CODE_404);
        }
        
        if (MUtils::isExistReversion($rev, $file_meta[0]["meta_value"]) == false) {
            throw new MFileopsException(
                                        Yii::t('api','    Unable to find the revision at that path'),
                                        MConst::HTTP_CODE_404);
        }
        
        //
        // 查询版本信息
        //
        $version = MiniVersion::getInstance()->getVersion($rev);
        if ($version == null)
        {
            throw new MFileopsException(
                                        Yii::t('api','    Unable to find the revision at that path'),
                                        MConst::HTTP_CODE_404);
        }
        $size = $version["file_size"];
        $file_hash = $version["file_signature"];
        //
        // 查询文件信息
        //
        $query_db_file = MFiles::queryFilesByPath($path);
        if ($query_db_file === false || empty($query_db_file))
        {
            throw new MFileopsException(
                                        Yii::t('api','not existed'),
                                        MConst::HTTP_CODE_404);
        }
        if ($query_db_file[0]["file_type"] == MConst::OBJECT_TYPE_DIRECTORY)
        {
            // 文件夹不需要版本
            throw new MFileopsException(
                                        Yii::t('api','folder not existed version'),
                                        MConst::HTTP_CODE_403);
        }
        if ($rev !== $query_db_file[0]["version_id"])
        {
            //
            // 更新文件版本
            //
            $updates = array();
            $updates["version_id"]          = $rev;
            $updates["file_update_time"]    = time();
            $updates["file_size"]           = $size;
            $updates ["event_uuid"]         = MiniUtil::getEventRandomString ( MConst::LEN_EVENT_UUID );
            $ret = MFiles::updateFileDetailById($query_db_file[0]["id"], $updates);
            if ($ret === false)
            {
                throw new MFileopsException(
                                            Yii::t('api','Internal Server Error'),
                                            MConst::HTTP_CODE_500);
            }
            $file_detail = new MFiles();
            $file_detail->file_name         = $query_db_file[0]["file_name"];
            
            //
            // 保存事件
            //
            $context = array( 
                      "hash"  => $file_hash,
                      "rev"   => (int)$rev,
                      "bytes" => (int)$size);
            //
            // 增加修改事件
            //
            $ret = MiniEvent::getInstance()->createEvent( $this->_user_id,
                                          $user_device_id, 
                                          MConst::MODIFY_FILE,
                                          $path,
                                          serialize($context), 
                                          $updates ["event_uuid"]);
            if ($ret === false)
            {
                throw new MFileopsException(
                                            Yii::t('api','Internal Server Error'),
                                            MConst::HTTP_CODE_500);
            }
            $this->handleFileMeta($path, $rev, $user_nick, $this->_user_device_name, $query_db_file[0]["file_size"]);
        }
        // TODO
        $mime_type = $version["mime_type"];
        $response = array();
        $response["size"]           = MUtils::getSizeByLocale($locale, $size);
        $response["is_deleted"]     = true;
        $response["bytes"]          = intval($size);
        $response["thumb_exists"]   = MUtils::isExistThumbnail($mime_type, $size);
        $response["path"]           = $originalPath;
        $response["root"]           = $this->_root;
        $response["is_dir"]         = false;
        $response["mime_type"]      = $mime_type;
        $response["modified"]       = MUtils::formatIntTime(time());
        $response["rev"]            = strval($rev);
        $response["revision"]       = $rev;
        echo json_encode($response);
    }

    /**
     * 处理添加当前文件记录的版本
     * @param string $file_path
     * @param int $version_id
     * @param string $user_nick
     */
    private function handleFileMeta($file_path, $version_id, $user_nick, $deviceName, $fileSize)
    {
        //
        // 查询之前的版本
        //
        $file_meta = MFileMetas::queryFileMeta($file_path, MConst::VERSION);
        if ($file_meta)
        {
            $meta_value = MUtils::getFileVersions(
                                                    $deviceName, 
                                                    $fileSize, 
                                                    $version_id, 
                                                    MConst::RESTORE, 
                                                    $this->_user_id, 
                                                    $user_nick,
                                                    $file_meta[0]["meta_value"]);
            $ret = MFileMetas::updateFileMeta(
                                        $file_meta->file_path, 
                                        MConst::VERSION, 
                                        $meta_value);
        }
        else 
        {
            $meta_value = MUtils::getFileVersions(
                                                    $deviceName, 
                                                    $fileSize, 
                                                    $version_id, 
                                                    MConst::RESTORE, 
                                                    $this->_user_id, 
                                                    $user_nick);
            $ret = MFileMetas::createFileMeta(
                                        $file_path, 
                                        MConst::VERSION, 
                                        $meta_value);
        }
        return $ret;
    }
}
?>