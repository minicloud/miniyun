<?php
/**
 * Miniyun 上传到s3等第三方数据源时获取上传参数
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MParamsController extends MFileSecondsController{

    /**
     * (non-PHPdoc)
     * @see MFilesecController::invoke()
     */
    public function invoke ($uri = NULL){
        $size       = isset($_REQUEST['size']) ? $_REQUEST['size'] : NULL;
        if ($size === NULL || $size < 0){
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR . 'Missing parameter'), MConst::HTTP_CODE_400);
        }

        $hash        = $_REQUEST["hash"];
        $this->size  = $size;
        $url_manager = new MUrlManager();
        $path        = $url_manager->parsePathFromUrl($uri);
        $path_info   = MUtils::pathinfo_utf($path);
        $file_name   = $path_info["basename"];
        $this->type  = MiniUtil::getMimeType($file_name);

        //如果文件的block存在则直接创建meta，表示创建成功否则返回上传文件的参数
        if ($this->handleCheckFileVersionSearch($hash)) {
            parent::invoke($uri);
        } else {
            //空间检查
            $this->spaceFilter($size);
            $params = apply_filters("upload_params", array("hash"=>$hash, "filename"=>$file_name));
            echo json_encode($params);
        }
    }

    /**
     *
     * 空间检查
     */
    private function spaceFilter($size) {
        $user       = MUserManager::getInstance ()->getCurrentUser ();
		$space      = $user["space"];
        $used_space = $user["usedSpace"]; 
        //
        // 空间检查
        //
        $used_space += $size;
        if ($used_space > $space) {
            throw new MFilesException(Yii::t('api',"User is over storage quota."), MConst::HTTP_CODE_507);
        }
    }

    /**
     *
     * 检查文件data和 meta是否存在
     */
    protected function handleCheckFileVersionSearch ($hash)
    {
        $version = MiniVersion::getInstance()->getBySignature($hash);
        if ($version === NULL) {
            return false;
        }
        return true;
    }
    
    /**
     *
     * 检查文件data和 meta是否存在
     */
    protected function handleCheckFileVersion ($hash)
    {
        $version = MiniVersion::getInstance()->getBySignature($hash);
        if ($version === NULL) {
            $version = MiniVersion::getInstance()->create($hash, $this->size, $this->type);
        }
        $this->version_id = $version["id"];
        $this->file_hash  = $version["file_signature"];
        return true;
    }
}