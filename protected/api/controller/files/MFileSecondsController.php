<?php
/**
 * Miniyun 文件上传服务,实现秒传
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFileSecondsController extends MApplicationComponent implements MIController
{
    protected $create_handler = NULL;
    protected $size = 0;
    protected $version_id = 0;
    /**
     * (non-PHPdoc)
     * @see MIController::invoke()
     */
    public function invoke ($uri = NULL)
    {
        $this->setAction(MConst::CREATE_FILE);
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $hash = @$_REQUEST['hash'];
        
        // 接收参数
        if (empty($hash)) {
            throw new MFilesException(
            Yii::t('api',MConst::PARAMS_ERROR . "Missing parameter 'hash'."), 
            MConst::HTTP_CODE_400);
        }
        
        // 解析文件路径，若返回false，则错误处理
        $url_manager = new MUrlManager();
        $path = $url_manager->parsePathFromUrl($uri);
        $root = $url_manager->parseRootFromUrl($uri);
        if ($path == false || $root == false) {
            throw new MFilesException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_411);;
        }
        
        $path        = "/" . $path;
        $path_info   = MUtils::pathinfo_utf($path);
        $file_name   = $path_info["basename"];
        $parent_path = $path_info["dirname"];

        // 检查是否在共享文件夹内， 如果在共享文件夹内，则进行权限检查
        $user     = MUserManager::getInstance ()->getCurrentUser ();
        $user_id  = $user["user_id"];
        $share_filter = MSharesFilter::init();
        if ($share_filter->handlerCheck($user_id, $path)){
            $user_id   = $share_filter->master;
            $path      = $share_filter->_path;
            $file_path = "/".$user_id.$path;
            $share_filter->hasPermissionExecute($file_path, MPrivilege::FILE_CREATE);
        }

        // 检查版本是否存在
        if ($this->handleCheckFileVersion($hash) == FALSE)
        {
            return;
        }
        
        $this->create_handler = MFilesCommon::initMFilesCommon();
        $this->create_handler->parent_path    = MUtils::convertStandardPath($parent_path);;
        $this->create_handler->file_name      = $file_name;
        $this->create_handler->root           = $root;
        $this->create_handler->path           = MUtils::convertStandardPath($path);
        $this->create_handler->type           = CUtils::mime_content_type($file_name);
        $this->create_handler->size           = $this->size;
        $this->create_handler->file_hash      = $hash;
        $this->create_handler->version_id     = $this->version_id;
        
        // 保存文件meta
        $this->create_handler->saveFileMeta();
        if (MUserManager::getInstance()->isWeb() === true)
        {
            $this->create_handler->buildWebResponse();
            return ;
        }
        $this->create_handler->buildResult();
        
    }
    
    /**
     * 
     * 表示要求客户端上传文件
     */
    protected function handleAssign() {
        header("HTTP/1.1 303 See Other");
        echo 'Upload File.';
        exit(0);
    }

    /**
     *
     * 检查文件data和 meta是否存在
     * @param string $hash
     * @return bool|void
     */
    protected function handleCheckFileVersion ($hash)
    {
        //data源处理对象
        $dataObj = Yii::app()->data;
        $file_version = MiniVersion::getInstance()->getBySignature($hash);
        if ($file_version == null) {
             return $this->handleAssign();
        }
        
        // 检查文件是否存在
        $store_path = MiniUtil::getPathBySplitStr ( $hash );
        if ($dataObj->exists ( $store_path ) == false) {
            return $this->handleAssign();
        }
        
        $this->version_id = $file_version['id'];
        $this->size       = $file_version['file_size'];
        return true;
    }
}