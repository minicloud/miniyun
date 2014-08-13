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
     * URL Structure: http://www.miniyun.cn/api/files/<root>/<path>  这里的path不能指向一个文件
     * @method GET
     * @version 0
     * @param
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null) {
        parent::init();
        
        // 解析url地址，获取root和path，path必须指向一个文件
        $url_manager  = new MUrlManager();
        $path         = $url_manager->parsePathFromUrl($uri);
        $root         = $url_manager->parseRootFromUrl($uri);
        if ($path == false) {
            throw new MException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_411);;
        }
        $path         = "/" . $path;
        // Default is 10. Max is 1,000.
        $rev_limit     = 10;
        if (isset($_REQUEST["rev_limit"]) != false) {
            $rev_limit = $_REQUEST["rev_limit"];
        }
        $rev_limit     = $rev_limit <= 1000 ? $rev_limit : 1000;
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
        
        $user_id          = $user["user_id"];
        $user_nick        = $user["user_name"];
        $user_device_id   = $device["device_id"];
        //
        // 查询文件
        //
        $file_detail = MFiles::queryAllFilesByPath("/". $user_id. $path);
        if ($file_detail === false || count($file_detail) == 0) {
            throw new MException(Yii::t('api',MConst::NOT_FOUND), MConst::HTTP_CODE_404);
        }
        
        // 判断文件类型，如不是文件则返回错误
        if ($file_detail[0]["file_type"] != 0) {
            throw new MException(Yii::t('api',"Not Acceptable"), MConst::HTTP_CODE_406);
        }
        
        $file_meta = MFileMetas::queryFileMeta("/". $user_id. $path, MConst::VERSION);
        if ($file_meta == false || empty ( $file_meta )) {
            throw new MFilesException (Yii::t ("api",MConst::INTERNAL_SERVER_ERROR ), MConst::HTTP_CODE_500 );
        }
        //
        // 文件版本历史
        //
        $versions = unserialize($file_meta[0]["meta_value"]);
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
            $file_version = MiniVersion::getInstance()->getVersion($v["version_id"]);
            if ($file_version == null) {
                $var["is_deleted"] = true;
            } else {
                $var['hash']         = $file_version["file_signature"];
                $var["bytes"]        = (int)$file_version["file_size"];
                $var["size"]         = MUtils::getSizeByLocale($locale, $file_version["file_size"]);
                $var["thumb_exists"] = $this->isExistThumbnail($file_version["file_size"], $file_version["mime_type"]);
                $var["modified"]     = MUtils::formatIntTime($file_version["updated_at"]);
                $var["mime_type"]    = is_null($file_version["mime_type"]) ? $var["mime_type"] : $file_version["mime_type"];
            }
            
            array_push($response, $var);
            if ($count >= $rev_limit) {
                break;
            }
            $count += 1;
        }
        echo json_encode($response);
    }
    /**
     * 判断是否存在缩略图
     * @param int    $size
     * @param string $mime_type
     */
    public function isExistThumbnail($size, $mime_type) {
        if ($size > MConst::MAX_IMAGE_SIZE || $size <= 0) {
            return false;
        }
        
        foreach (MThumbnailBase::$_support_types as $value) {
            if ($value == $mime_type) {
                return true;
            }
        }
        return false;
    }
}
?>