<?php
/**
 * Miniyun upload服务主要入口地址，实现文件上传
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFilePostController extends MApplicationComponent  implements MIController {
    /**
     * 获得上传文件临时路径
     */
    private function getUploadFileTmpPath(){
        $keys = array_keys($_FILES);
        $key = $keys[0];
        return $_FILES[$key]["tmp_name"];
    }
    /**
     * 判断是否是断点上传文件
     * 如果是网页上传，是没有文件的size与文件的hash值的
     * 如果size/hash值不为空，则是客户端上传逻辑，包括移动客户端、PC客户端
     * 如果size与临时文件的size一致，则认为是完整文件上传，而不是断点文件上传
     */
    private function isBreakpointUpload(){
        $tmpPath = $this->getUploadFileTmpPath();
        $clientFileSize = MiniHttp::getParam("size","");
        $clientFileSignature = MiniHttp::getParam("hash","");
        if(empty($clientFileSize)){
            return false;
        }
        if(empty($clientFileSignature)){
            return false;
        }
        $tempFileSize = filesize($tmpPath);
        if(intval($clientFileSize)==$tempFileSize){
            return false;
        }
        return true;
    }
    /**
     * 控制器执行主逻辑函数
     */
    public function invoke($uri=null)
    {
        $this->setAction(MConst::CREATE_FILE);
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $urlManager = new MUrlManager();
        $root = $urlManager->parseRootFromUrl($uri);
        if ($root == false) {
            //支持参数模式传递上传路径
            $path = MiniHttp::getParam("path", "");
            if (empty($path)) {
                throw new MFilesException(Yii::t('api', MConst::PATH_ERROR), MConst::HTTP_CODE_411);;
            }
        }
        // 初始化创建文件公共类句柄
        $createFileHandler = MFilesCommon::initMFilesCommon();
        if (count($_FILES) == 0) {
            throw new MFilesException(Yii::t('api', MConst::PARAMS_ERROR . "5"), MConst::HTTP_CODE_400);
        }
        $keys = array_keys($_FILES);
        if (count($keys) != 1) {
            throw new MFilesException(Yii::t('api', MConst::PARAMS_ERROR . "6"), MConst::HTTP_CODE_400);
        }
        $key = $keys[0];
        // 检查请求参数$_FILES
        if (isset($_FILES[$key]) === false) {
            throw new MFilesException(Yii::t('api', MConst::PARAMS_ERROR . "7"), MConst::HTTP_CODE_400);
        }
        // 检查文件上传过程是否有错
        if ($_FILES[$key]["error"] != 0) {
            throw new MFilesException(Yii::t('api', MConst::PARAMS_ERROR . "8"), MConst::HTTP_CODE_400);
        }

        $fileName = $_FILES[$key]["name"];
        $type     = CUtils::mime_content_type($fileName);
        $size     = $_FILES[$key]["size"];
        $tmpName  = $_FILES[$key]["tmp_name"];
        // 验证文件是否已经上传成功
        if (file_exists($tmpName) === false) {
            throw new MFilesException(Yii::t('api', MConst::INTERNAL_SERVER_ERROR), MConst::HTTP_CODE_500);
        }
        // 检查文件上传错误
        if (filesize($tmpName) != $size) {
            throw new MFilesException(Yii::t('api', "The file upload error!"), MConst::HTTP_CODE_400);
        }
        //断点文件上传
        if ($this->isBreakpointUpload()) {
            $filesController = new MFilePutController();
            $filesController->invoke($uri);
        } else {
            //完整文件上传
            $signature = MiniUtil::getFileHash($tmpName);
            // 解析路径
            $path = "/" . $urlManager->parsePathFromUrl($uri);
            $parentPath = dirname($path);
            $user = MUserManager::getInstance()->getCurrentUser();
            $parentFile = MiniFile::getInstance()->getByPath($parentPath);

            //如果目录存在，且该目录is_delete=1，则把目录状态删除状态修改为0
            if (!empty($parentFile) && $parentFile['is_deleted'] == 1) {
                $values = array();
                $values['is_deleted'] = false;
                MiniFile::getInstance()->updateByPath($parentPath, $values);
            } else {
                //如果是根目录，则不用新建目录
                //否则会创建文件名名称的文件夹出来，而且目标文件位于该文件夹的下面
                if (!MiniUtil::isRootPath($parentPath, $user["id"])) {
                    MiniFile::getInstance()->createFolder($parentPath, $user['id']);
                }
            }
            $createFileHandler->size = $size;
            $createFileHandler->parent_path = MUtils::convertStandardPath($parentPath);
            $createFileHandler->file_name = $fileName;
            $createFileHandler->root = $root;
            $createFileHandler->path = MUtils::convertStandardPath($path);;
            $createFileHandler->type = $type;
            // 文件不存在,保存文件
            $createFileHandler->saveFile($tmpName, $signature, $size);
            // 保存文件meta
            $createFileHandler->saveFileMeta();
            // 处理不同端，不同返回值
            if (MUserManager::getInstance()->isWeb() === true) {
                $createFileHandler->buildWebResponse();
                return;
            }
            $createFileHandler->buildResult();
        }
    }
    /**
     * post处理异常入口地址
     *
     */
    public function handleException($exception)
    {
        parent::displayException($exception);
    }
}
