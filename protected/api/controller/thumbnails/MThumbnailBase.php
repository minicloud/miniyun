<?php 
/**
 * Miniyun 图片处理基类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MThumbnailBase extends MModel {
    /**
     * 输出图片对应的尺寸大小,单位:px (像素)
     * @var array
     */
    public static $sizes = array(
        "small"  => array("w"=>32, "h"=>32),
        "medium" => array("w"=>64, "h"=>64),
        "large"  => array("w"=>128, "h"=>128),
        "large@2x" => array("w"=>256, "h"=>256),
        "s"      => array("w"=>64, "h"=>64),
        "m"      => array("w"=>128, "h"=>128),
        "l"      => array("w"=>640, "h"=>480),
        "xl"     => array("w"=>1024, "h"=>768)
    );
    /**
     * 支持图片缩略图类型
     * @var array
     */
    public static $supportTypes = array(
         "gif" => "image/gif",
        "jpeg" => "image/jpeg",
         "jpg" => "image/jpeg",
         "jpe" => "image/jpeg",
         "png" => "image/png",
         "tiff" => "image/tiff",
         "tif" => "image/tif",
   );

    /**
     * 初始化参数对象
     * 解析外部参数
     * @param string $uri
     * @param array $config
     * @throws Exception
     * @throws MException
     * @return mix
     */
    public static function initMThumbnailBase($uri, $config = NULL) {
        $thumbnailBase   = new MThumbnailBase();
        $format          = "jpeg";
        $size            = "small";
        if (isset($_REQUEST["format"])) {
            $format = strtolower($_REQUEST["format"]);
        }
        // 默认值format
        if ($format != "jpeg" && $format != "png") {
            $format = "jpeg";
        }
        // 默认值size
        if (isset($_REQUEST["size"])) {
            $size = $_REQUEST["size"];
        }
        $signature  = $_REQUEST["signature"];
        // 解析文件路径，若返回false，则错误处理
        $urlManager = new MUrlManager();
        $path = $urlManager->parsePathFromUrl($uri);
        if ($path == false) {
            throw new MException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_404);
        }
        $parts       = array_slice(explode('/', $uri), 3);
        $root        = $parts[0];  // 检索的根路径
        // 解析路径
        $path                            = "/" . $path;

        $path                            = MUtils::convertStandardPath($path);
        // 用户信息
        $user                            = MUserManager::getInstance()->getCurrentUser();
        if (!empty($_REQUEST["userId"]) && $_REQUEST["userId"] != 'undefined' && $user['user_name'] == 'admin') {
            $userId = $_REQUEST["userId"];
            $user = MiniUser::getInstance()->getUser($userId);
        }
        if(dirname($path)=="/"){
            $path = "/".$user['id'].$path;
        }
        $device                          = MUserManager::getInstance()->getCurrentDevice();
        $thumbnailBase->user_id          = $user["user_id"];
        $thumbnailBase->user_nick        = $user["user_name"];
        $thumbnailBase->user_device_id   = $device["device_id"];
        $thumbnailBase->size             = $size;
        $thumbnailBase->format           = $format;
        $thumbnailBase->path             = MUtils::convertStandardPath($path);
        $thumbnailBase->root             = $root;
        $thumbnailBase->config           = $config;
        // 检查共享
        $share_filter                    = MSharesFilter::init();
        if ($share_filter->handlerCheck($thumbnailBase->user_id, $path, true)) {
            $thumbnailBase->user_id      = $share_filter->master;
            $thumbnailBase->path         = $share_filter->_path;
        }
        
        return $thumbnailBase;
    }

    /**
     * 计算是否存在缩略图
     * @param string $fileName
     * @param int $size
     * @throws MException
     */
    public function checkExistThumbnail($fileName,$size) {
        if ($size == 0 || $size > MConst::MAX_IMAGE_SIZE) {
            throw new MException(Yii::t('api',"The image is invalid and cannot be thumbnailed."), MConst::HTTP_CODE_415);
        }
        $pathInfo  = MUtils::pathinfo_utf($fileName);
        $extension = strtolower($pathInfo["extension"]);
        // 检查文件类型是否支持
        if (empty(self::$supportTypes[$extension])) {
            throw new MException(Yii::t('api',"The file extension doesn't allow thumbnailing."), MConst::HTTP_CODE_404);;
        }
    }
    
    /**
     * 创建对象
     */
    public function create() {

        // 查询文件信息
        $path = MiniUtil::getAbsolutePath($this->user_id,$this->path);
        $file = MiniFile::getInstance()->getByPath($path);
        if (empty($file)) {
            throw new MException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_404);
        }
        $fileName  = $file["file_name"];
        $fileSize  = $file["file_size"];
        $versionId = $file["version_id"];
        // 检查是否支持缩略图
        $this->checkExistThumbnail($fileName, $fileSize);

        
        // 获取文件版本
        $version = MFileVersions::queryFileVersionByID($versionId);
        if (count($version) == 0) {
            throw new MException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_404);
        }
        // 获取文件存储路径
        $isTmp = false;
        $signature  = $_REQUEST["signature"];        
        if(empty($signature)||$signature==="undefined"){
            $signature = $version[0]["file_signature"];
        }
        // 缩略图大小
        $sizeInfo = self::$sizes[$this->size];
        if($sizeInfo===NULL){
          $sizeStr = strtolower($this->size);
          $sizeList = explode("x",$sizeStr);
          $sizeInfo = array(
              "w"=>$sizeList[0],
              "h"=>$sizeList[1],
          );
        }
        $this->width   = $sizeInfo["w"];
        $this->height  = $sizeInfo["h"];
        // 检查缩略图是否存在
        $thumbnail  = THUMBNAIL_TEMP . MiniUtil::getPathBySplitStr($signature);
        $thumbnail .= "_{$this->width}_{$this->height}.{$this->format}";

        if (file_exists($thumbnail) == true) {
            //直接跳转，避免重复生成缩略图
            $url = MiniHttp::getMiniHost()."assets/thumbnails/".MiniUtil::getPathBySplitStr($signature);
            $url .= "_{$this->width}_{$this->height}.{$this->format}";
            header('Location: '.$url);
            exit;
        }
        //判断文件是否在迷你存储中，兼容非迷你存储的文件
        $version = MiniVersion::getInstance()->getBySignature($signature);
        $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],"store_id");
        $thumbnailData = array();
        if(!empty($meta)){
            //为迷你存储缩略图添加hook            
            $thumbnailData["signature"] = $signature;
            $storePath = apply_filters("image_path", $thumbnailData); 
        }
        if (empty($storePath)||$storePath === $thumbnailData){
            //data源处理对象
            $dataObj = Yii::app()->data;
            $signaturePath = MiniUtil::getPathBySplitStr($signature);
            if ($dataObj->isExistLocal()){
                $storePath = $dataObj->documentStorePath($signaturePath) . $signaturePath;
            }
        }
        if (file_exists($storePath) == false) {
            throw new MException(Yii::t('api',"The file path was not found."), MConst::HTTP_CODE_404);
        }
        $pathInfo  = MUtils::pathinfo_utf($fileName);
        $extension = $pathInfo["extension"];
        $tmpPath  = DOCUMENT_TEMP . $signature . ".$extension";
        // 缩略图对象
        $this->handler = NULL;
        $this->image   = $tmpPath;
        $this->resize  = true;
        // 创建缩略图片父目录
        if (file_exists(dirname($thumbnail)) == false) {
            if (MUtils::MkDirsLocal(dirname($thumbnail)) == false) {
                throw new MException(Yii::t('api',"The file path was not found."), MConst::HTTP_CODE_404);
            }
        }
        // 临时文件父目录
        if (file_exists(dirname($tmpPath)) == false) {
            if (MUtils::MkDirsLocal(dirname($tmpPath)) == false) {
                throw new MException(Yii::t('api',"The file path was not found."), MConst::HTTP_CODE_404);
            }
        }
        // 拷贝文件到临时目录
        if (file_exists($tmpPath) == false) {
            if (copy($storePath, $tmpPath) == false) {
                throw new MException(Yii::t('api',"The file path was not found."), MConst::HTTP_CODE_404);
            }
        }
        // 如果图片格式与后缀不一致，转换为一致的
        if ($this->format != strtolower($extension)) {
            $fm = new Image($tmpPath);
            $format_path = DOCUMENT_TEMP . $signature . ".{$this->format}";
            $fm->save($format_path);
            // 转换成功删除临时文件
            unlink($tmpPath);
            $this->image = $format_path;
        }
        if ($isTmp){
            unlink($storePath);
        }
        // 初始化图像对象
         try {
            $this->handler = new Image($this->image, isset($this->config)?$this->config:NULL);
         } catch (MException $e) {
             Yii::log("Exception : {$e->getTraceAsString()}");
             throw new MException(Yii::t('api',"The image is invalid and cannot be thumbnailed."), MConst::HTTP_CODE_415);
         }
        // 生成缩略图
        if ($this->resize == true) {
            $this->handler->resize($this->width, $this->height)->rotate(0)->quality(75)->sharpen(20);
            $chmod        = 0644;
            $keep_actions = true;
            try {
                $this->handler->save($thumbnail, $chmod, $keep_actions);
                $this->handler->setImageFile($thumbnail);
                $this->image = $thumbnail;
                @unlink($format_path);
            } catch (MException $e) {
                Yii::trace("Exception : $e","miniyun.api");
                throw new MException(Yii::t('api',"The image is invalid and cannot be thumbnailed."), MConst::HTTP_CODE_415);
            }
        }
    }
    /**
     * 将对象输出返回
     */
    public function render() {
        $this->handler->render_direct(true);
    }
}
