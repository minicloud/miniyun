<?php
/**
 * Miniyun 获取文件以前版本的metadata
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MRevisionsController  extends MApplicationComponent implements MIController
{
    /**
     * 获取文件以前版本的mtadata
     * @param null $uri
     * @throws MException
     * @throws MFilesException
     */
    public function invoke($uri=null) {
        parent::init();
        
        // 解析url地址，获取root和path，path必须指向一个文件
        $urlManager  = new MUrlManager();
        $path         = $urlManager->parsePathFromUrl($uri);
        $root         = $urlManager->parseRootFromUrl($uri);
        if ($path == false) {
            throw new MException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_411);;
        }
        $path         = "/" . $path;
        // Default is 10. Max is 1,000.
        $revLimit     = 10;
        if (isset($_REQUEST["rev_limit"]) != false) {
            $revLimit = $_REQUEST["rev_limit"];
        }
        $revLimit     = $revLimit <= 1000 ? $revLimit : 1000;
        // 文件大小格式化参数
        $locale = "bytes";
        if (isset($_REQUEST["locale"])) {
            $locale    = $_REQUEST["locale"];
        }
        // callback - 
        // TODO 实现callback
        $callback         = NULL;
        
        // 获取用户数据，如user_id
        $user             = MUserManager::getInstance()->getCurrentUser();
        $device           = MUserManager::getInstance()->getCurrentDevice();
        
        $userId          = $user["user_id"];
        $userNick        = $user["user_name"];
        $userDeviceId   = $device["device_id"];
        //
        // 查询文件
        //
        $fileDetail = MFiles::queryAllFilesByPath("/". $userId. $path);
        if ($fileDetail === false || count($fileDetail) == 0) {
            throw new MException(Yii::t('api',MConst::NOT_FOUND), MConst::HTTP_CODE_404);
        }
        
        // 判断文件类型，如不是文件则返回错误
        if ($fileDetail[0]["file_type"] != 0) {
            throw new MException(Yii::t('api',"Not Acceptable"), MConst::HTTP_CODE_406);
        }
        
        $fileMeta = MFileMetas::queryFileMeta("/". $userId. $path, MConst::VERSION);
        if ($fileMeta == false || empty ( $fileMeta )) {
            throw new MFilesException (Yii::t ("api",MConst::INTERNAL_SERVER_ERROR ), MConst::HTTP_CODE_500 );
        }
        //
        // 文件版本历史
        //
        $versions = unserialize($fileMeta[0]["meta_value"]);
        $count    = 1; // 计数器
        // 
        // 轮询
        //
        $response = array();
        foreach ($versions as $k => $v){
            $var                 = array();
            $var["rev"]          = strval($v["version_id"]);
            $var["revision"]     = (int)$v["version_id"];
            $var["bytes"]        = 0;
            $var["size"]         = "0 bytes";
            $var["thumb_exists"] = false;
            $var["modified"]     = MUtils::formatIntTime(microtime(true) * 10000);
            $var["mime_type"]    = MConst::DEFAULT_FILE_MIME_TYPE;
            $var["path"]         = $path;
            $var["is_dir"]       = false;
            $var["root"]         = $root;
            if ($v["type"] == MConst::DELETE) {
                $var["is_deleted"] = true;
            }
            //
            // 文件版本信息
            //
            $fileVersion = MiniVersion::getInstance()->getVersion($v["version_id"]);
            if ($fileVersion == null) {
                $var["is_deleted"] = true;
            } else {
                $var['hash']         = $fileVersion["file_signature"];
                $var["bytes"]        = (int)$fileVersion["file_size"];
                $var["size"]         = MUtils::getSizeByLocale($locale, $fileVersion["file_size"]);
                $var["thumb_exists"] = $this->isExistThumbnail($fileVersion["file_size"], $fileVersion["mime_type"]);
                $var["modified"]     = MUtils::formatIntTime($fileVersion["updated_at"]);
                $var["mime_type"]    = is_null($fileVersion["mime_type"]) ? $var["mime_type"] : $fileVersion["mime_type"];
            }
            
            array_push($response, $var);
            if ($count >= $revLimit) {
                break;
            }
            $count += 1;
        }
        echo json_encode($response);
    }

    /**
     * 判断是否存在缩略图
     * @param int $size
     * @param string $mimeType
     * @return bool
     */
    public function isExistThumbnail($size, $mimeType) {
        if ($size > MConst::MAX_IMAGE_SIZE || $size <= 0) {
            return false;
        }
        
        foreach (MThumbnailBase::$supportTypes as $value) {
            if ($value == $mimeType) {
                return true;
            }
        }
        return false;
    }
}
?>