<?php
/**
 * Miniyun web缩略图生成，访问地址
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Thumbnail extends CApiComponent {
    const MAX_IMAGE_SIZE = 20971520; // 20 * 1024 * 1024，最大支持20M图片转换
     /**
     * 支持图片缩略图大小    小图    中图  大图
     * @var array
     */
    public static $thumbSize = array(
        "small" => array("width"=>48,"height"=>48,"default" => "/static/images/file.png"),
        "normal" => array("width"=>80,"height"=>80,"default" => "/static/images/main/thumb/image.gif"),
        "large"  => array("width"=>128,"height"=>128, "default" => "/static/images/main/thumb_large/image.gif"),
    );
    /**
     * 支持图片缩略图类型
     * @var array
     */
    public static $_support_types = array(
//        "bmp" => "image/bmp",
         "gif" => "image/gif",
//         "ief" => "image/ief",
         "jpeg" => "image/jpeg",
         "jpg" => "image/jpeg",
         "jpe" => "image/jpeg",
         "png" => "image/png",
         "tiff" => "image/tiff",
         "tif" => "image/tif",
//         "djvu" => "image/vnd.djvu",
//         "djv" => "image/vnd.djvu",
//         "wbmp" => "image/vnd.wap.wbmp",
//         "ras" => "image/x-cmu-raster",
//         "pnm" => "image/x-portable-anymap",
//         "pbm" => "image/x-portable-bitmap",
//         "pgm" => "image/x-portable-graymap",
//         "ppm" => "image/x-portable-pixmap",
//         "rgb" => "image/x-rgb",
//         "xbm" => "image/x-xbitmap",
//         "xpm" => "image/x-xpixmap",
//         "xwd" => "image/x-windowdump"
    );
    public $fileId;     // 图片文件对应id
    public $filePath;    // path
    public $width   = 200;      // 缩略图宽度
    public $height  = 200;      // 缩略图高度
    public $quality = 75;    // 缩略图质量
    public $rotate  = 0;     // 旋转
    public $sharpen = 20;    // 锐化
    public $default = "/static/images/main/thumb_large/image.gif";
    public $format  = 'PNG'; // 默认转换为jpeg格式
    public $image;      // 图片对象
    public $isDirect = TRUE;  // 表示直接输出
    /**
     * 
     * 外部调用入口
     */
    public function invoke($ext,$v_id,$byte,$size) {
     $px = self::$thumbSize[$size];
            if ($px) {
                $this->width   = $px["width"];
                $this->height  = $px["height"];
                $this->default = $px["default"];
            }
        set_error_handler(array ($this, 'handleError' ));
        
        // 检查是否支持缩略图
        $this->checkExistThumbnail($ext,$byte);
        $version = FileVersion::model()->findByPk($v_id);
        $signature = $version["file_signature"];
        
        
        //为第三方源下缩略图添加hook
        $thumData = array();
        $thumData["hash"]    = $signature;
        $thumData["width"]   = $this->width;
        $thumData["height"]  = $this->height;
        $thumData["rotate"]  = $this->rotate;
        $thumData["sharpen"] = $this->sharpen;
        $thumData["quality"] = $this->quality;
        $thumData["format"]  = $this->format;
        $url = apply_filters("image_thumbnails", $thumData);
        if ($url !== $thumData && !empty($url)){
             Yii::app()->request->redirect($url);
            return;
        }
        
        //
        // 缩略图存储位置
        //
        $thumbnail  = THUMBNAIL_TEMP . CUtils::getPathBySplitStr($signature);
        $thumbnail .= "_{$this->width}_{$this->height}.{$this->format}";
        // 如果图片已经存在，则直接输出，否则执行转换
        if (file_exists($thumbnail) == true) {
            $this->image  = Yii::app()->image->load($thumbnail);
            $this->handleEnd();
              Yii::app()->end();
        }
        
        //data源处理对象
        $dataObj = Yii::app()->data;

        $isTmp = false;
        $signaturePath = CUtils::getPathBySplitStr($signature);
        if ($dataObj->isExistLocal()){
            $storePath = $dataObj->documentStorePath($signaturePath) . $signaturePath;
        } else {
            $isTmp = true;
            $storePath = DOCUMENT_TEMP . $signature . ".tmp";
            //将远程文件下载到本地， 如果是本地备份，则直接从本地读取
            $dataObj->get($signaturePath, $storePath);
        }
        
        if (file_exists($storePath) == false) {
            throw new ApiException("File is NOT FOUND.");
        }
        $tmpPath = DOCUMENT_TEMP . $signature . ".{$ext}";
        $this->handleConvert($storePath,$tmpPath,$thumbnail);
        $this->handleEnd();
        // 删除临时文件
        unlink($tmpPath);
        if ($isTmp){
            unlink($storePath);
        }
        Yii::app()->end();
    }
    
    
    /**
     * 计算是否存在缩略图
     * @param string $file_name
     * @param int $size
     * @param string $mime_type
     */
    public function checkExistThumbnail($extension, $size) {
        if ($size == 0 || $size > self::MAX_IMAGE_SIZE) {
            throw new ApiException("File don't have thumbnail.");
        }
     
        //
        // 检查文件类型是否支持
        //
        if (empty(self::$_support_types[strtolower($extension)])) {
            throw new ApiException("File don't have thumbnail .");
        }
    }
    /**
     * 
     * 执行转换
     */
    public function handleConvert($fromPath,$tmpPath,$thumbnail) {
        // 临时文件父目录
        if (file_exists(dirname($tmpPath)) == false) {
            if (CUtils::MkDirs(dirname($tmpPath)) == false) {
                throw new ApiException("Make dirs failure.");
            }
        }
        // 拷贝文件到临时目录
        if (file_exists($tmpPath) == false) {
            if (copy($fromPath, $tmpPath) == false) {
                throw new ApiException("Copy file failure.");
            }
        }
        // 创建缩略图片父目录
        if (file_exists(dirname($thumbnail)) == false) {
            if (CUtils::MkDirs(dirname($thumbnail))== false) {
                throw new ApiException("Make dirs failure.");
            }
        }
        try {
            // 加载临时文件
            $this->image  = Yii::app()->image->load($tmpPath);
            $imageObject = $this->image->getImage();
            if ($imageObject["width"] > $this->width || $imageObject["height"] > $this->height) {
                $this->image->resize($this->width, $this->height)->rotate($this->rotate)->quality($this->quality)->sharpen($this->sharpen);
            }
            $start = microtime(true);
            $this->image->save($thumbnail,0644,true);
            $this->isDirect = FALSE;
            
        } catch (CException $e) {
            $this->image = NULL;
            throw new ApiException($e->getMessage());
        }
    }
    /**
     * 
     * 输出转换结果
     */
    public function handleRender() {
        if (!$this->image) {
            $errorPath = dirname(__FILE__) . "/../../.." . $this->default;
            $this->image = Yii::app()->image->load($errorPath);
        }
        $this->image->render_direct($this->isDirect);
    }
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleEnd()
     */
    public function handleEnd() {
        $this->handleRender();
    }
    public function handleError($code, $message, $file, $line){
        $this->image = NULL;
        $this->handleEnd();
        Yii::app()->end();
    }
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException($exception) {
        $this->handleEnd();
    }
    
    /**
     * 
     * 实现图片缓存，返回304
     */
    public function handleCache() {
        // 过期时间和标志任意一个不存在，则返回
        if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || !isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return ;
        }
        // 检查缓存是否过期
        $d = date_parse_from_format("D, d M Y H:i:s",$_SERVER['HTTP_IF_MODIFIED_SINCE']);
        $e = mktime($d['hour'],$d['minute'],$d['second'],$d['month'],$d['day'],$d['year']);
        if ($e - time() < 0) {
            return ;
        }
        
        // 返回304
        if ($_SERVER['HTTP_IF_NONE_MATCH'] == md5($_SERVER['REQUEST_URI'])) {
            header('HTTP/1.1 304 Not Modified');
            header("Etag: " . $_SERVER['HTTP_IF_NONE_MATCH']);
            $offset = 60*60*24*30; // cache 1 month
            $ExpStr = "Expires: ".gmdate("D, d M Y H:i:s", time() + $offset)." GMT";
            header($ExpStr);
            header('Cache-Control: public' );
            header('Pragma: cache');
            Yii::app()->end();
        }
    }
}