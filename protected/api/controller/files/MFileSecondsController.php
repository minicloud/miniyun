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
    private $isNewVersion = false;//标示是否是迷你云新版本，新版本启用传统的参数传递模式
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
        if (empty($hash)) {
            $hash = MiniHttp::getParam("signature","");
        }
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
            $this->isNewVersion = true;
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
        if ($this->handleCheckFileVersion($hash,$fileName) == FALSE)
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
            return;
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
     * @param string $hash      文件sha1
     * @param string $fileName  文件名
     * @return bool|void
     */
    protected function handleCheckFileVersion ($hash,$fileName)
    {
        $version = MiniVersion::getInstance()->getBySignature($hash);
        if(!$this->isNewVersion){
            //data源处理对象
            if ($version == null) {
                return $this->handleAssign();
            } 
            // 检查文件是否存在
            $dataObj = Yii::app()->data;
            $storePath = MiniUtil::getPathBySplitStr ($hash);
            if ($dataObj->exists($storePath) == false) {
                return $this->handleAssign();
            }
            $this->version_id = $version['id'];
            $this->size       = $version['file_size'];
            return true;
        }else{
            //返回断点文件信息 
            $data = array(); 
            if (empty($version)) {  
                $miniStoreInfo = MiniUtil::getPluginMiniStoreData(); 
                if(empty($miniStoreInfo)){
                    //普通文件上传
                    $data['success'] = false;
                    $data['url'] =  MiniHttp::getMiniHost()."api.php";
                    $storePath = MiniUtil::getPathBySplitStr($hash);
                    $filePath = BASE."upload_block/cache/".$storePath;
                    if (file_exists($filePath)) {
                        $data['offset'] = filesize($filePath);
                        //如文件大小相同而且Hash值相同，说明流数据文件已经存在，直接生成元数据即可
                        $size = MiniHttp::getParam("size",""); 
                        if($data['offset']==$size){
                            //生成version记录，为使用老逻辑代码，这里处理得很羞涩
                            //理想的逻辑是在这里直接返回相关结果 
                            $mimeType = CUtils::mime_content_type($fileName);
                            $version = MiniVersion::getInstance()->create($hash,$size,$mimeType);
                            $this->version_id = $version['id'];
                            $this->size       = $version['file_size'];
                            return true;
                        }
                    }else{
                        $data['offset'] = 0;
                    } 
                    echo json_encode($data);exit;
                }else{
                    //迷你存储与第3方存储秒传接口
                    apply_filters("file_sec", array(
                            "route"=>"module/miniStore/report",
                            "sign"=>MiniHttp::getParam("sign",""),
                            "access_token"=>MiniHttp::getParam("access_token",""),
                            "signature"=>$hash,
                            "size"=>MiniHttp::getParam("size",""),
                            "path"=>MiniHttp::getParam("path",""), 
                    )); 
                }
                
            }else{
                //上传文件到其它目录下，支持秒传
                $this->version_id = $version['id'];
                $this->size       = $version['file_size'];
                return true;
            }
        }

    }
}