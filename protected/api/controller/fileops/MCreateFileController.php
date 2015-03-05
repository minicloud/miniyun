<?php
/**
 * Miniyun S3上传方式，上传元数据
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MCreateFileController extends MFileSecondsController
{

    /**
     * (non-PHPdoc)
     * @see MFilesecController::invoke()
     */
    public function invoke ($uri = NULL){
    	
        $this->setAction(MConst::COPY);
        $size   = isset($_REQUEST['size']) ? $_REQUEST['size'] : NULL;
        if ($size === NULL || $size < 0){
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR . 'Missing parameter'), MConst::HTTP_CODE_400);
        }

        //在web端创建文件元数据之前将s3对象进行移动
        if (MUserManager::getInstance()->isWeb() === true)
        {
            $filename = $_REQUEST["filename"];
            $hash     = $_REQUEST["hash"];
            $data     = array("filename" => $filename, "hash" => $hash);
            $success  = apply_filters("before_create_file", $data);
            if ($success !== $data && $success !== true){
                throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR . 'Missing parameter'), MConst::HTTP_CODE_400);
            }
        }else{ //如果是api进行上传需要检查远程是否存在
            $hash     = $_REQUEST["hash"];
            $data     = array("hash" => $hash);
            $exist    = apply_filters("third_check_file_exist", $data);
            if ($exist !==$data && $exist !== true){
                throw new MFilesException(Yii::t('api',MConst::FILE_NOT_EXIST), MConst::HTTP_CODE_400);
            }
        }

        $this->size  = $size;
        $urlManager = new MUrlManager();
        $path        = $urlManager->parsePathFromUrl($uri);
        $pathInfo   = MUtils::pathinfo_utf($path);
        $fileName   = $pathInfo["basename"];
        $this->type  = MiniUtil::getMimeType($fileName);

        parent::invoke($uri);
    }

    /**
     *
     * 检查文件data和 meta是否存在
     */
    protected function handleCheckFileVersion ($hash)
    {
        // 获取文件版本
        $version         = MiniVersion::getInstance()->getBySignature( $hash );
        if ($version === NULL) {
        	$version = MiniVersion::getInstance()->create($hash, $this->size, $this->type); 
        }
        // 文件版本id
        $this->version_id = $version["id"];
        $this->file_hash  = $version["file_signature"];
        return true;
    }
}