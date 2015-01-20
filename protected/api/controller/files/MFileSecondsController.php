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
    protected $handler = NULL;
    protected $size = 0;
    protected $version_id = 0;
    private $isNew = false;//标示是否是迷你云新版本，新版本启用传统的参数传递模式
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
        $urlManager = new MUrlManager();
        $path = $urlManager->parsePathFromUrl($uri);
        $root = $urlManager->parseRootFromUrl($uri);
        if ($path == false || $root == false) {
            //支持参数模式传递上传路径
            $path = MiniHttp::getParam("path","");
            $root = "miniyun";
            $this->isNew = true;
            if(empty($path)){
                throw new MFilesException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_411);
            }
        }
        
        //$path        = "/" . $path;
        $pathInfo   = MUtils::pathinfo_utf($path);
        $fileName   = $pathInfo["basename"];
        $parentPath = $pathInfo["dirname"];

        // 检查是否在共享文件夹内， 如果在共享文件夹内，则进行权限检查
        $user     = MUserManager::getInstance ()->getCurrentUser ();
        $userId  = $user["user_id"];
        $shareFilter = MSharesFilter::init();
        if ($shareFilter->handlerCheck($userId, $path)){
            $userId   = $shareFilter->master;
            $path      = $shareFilter->_path;
            $filePath = "/".$userId.$path;
            $shareFilter->hasPermissionExecute($filePath, MPrivilege::FILE_CREATE);
        }

        // 检查版本是否存在
        if ($this->handleCheckFileVersion($hash) == FALSE)
        {
            return;
        }
        
        $this->handler = MFilesCommon::initMFilesCommon();
        $this->handler->parent_path    = MUtils::convertStandardPath($parentPath);;
        $this->handler->file_name      = $fileName;
        $this->handler->root           = $root;
        $this->handler->path           = MUtils::convertStandardPath($path);
        $this->handler->type           = CUtils::mime_content_type($fileName);
        $this->handler->size           = $this->size;
        $this->handler->file_hash      = $hash;
        $this->handler->version_id     = $this->version_id;
        
        // 保存文件meta
        $this->handler->saveFileMeta();
        if (MUserManager::getInstance()->isWeb() === true)
        {
            $this->handler->buildWebResponse();
            return ;
        }
        $this->handler->buildResult();
        
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
        $storePath = MiniUtil::getPathBySplitStr ($hash);
        $dataObj = Yii::app()->data;
        $version = MiniVersion::getInstance()->getBySignature($hash);
        if(!$this->isNew){
            //data源处理对象
            if ($version == null) {
                return $this->handleAssign();
            }
            // 检查文件是否存在
            if ($dataObj->exists($storePath) == false) {
                return $this->handleAssign();
            }
            $this->version_id = $version['id'];
            $this->size       = $version['file_size'];
            return true;
        }else{
            //按http://doc.mini-inc.cn/?p=175接口文档实现新版本的处理
            //返回断点文件信息
            if ($version == null) {
                header("HTTP/1.1 200 part upload");
                $data = array();
                $data['success'] = false;
                $data['url'] =  MiniHttp::getMiniHost()."/api/1/file/upload";
                $filePath = BASE."upload_block/cache/".$storePath;
                if (file_exists($filePath) == true) {
                    $data['offset'] = filesize($filePath);
                    if($data['offset'] == false){
                        $data['offset'] = 0;
                    }
                }
                echo json_encode($data);exit;
            }else{
                $this->version_id = $version['id'];
                $this->size       = $version['file_size'];
                return true;
            }
        }

    }
}