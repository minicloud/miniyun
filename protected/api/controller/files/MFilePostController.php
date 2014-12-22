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
     * 控制器执行主逻辑函数
     */
    public function invoke($uri=null)
    {
        $this->setAction(MConst::CREATE_FILE);
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $url_manager = new MUrlManager();
        $root = $url_manager->parseRootFromUrl($uri);
        if ($root == false) {
            throw new MFilesException(Yii::t('api',MConst::PATH_ERROR), MConst::HTTP_CODE_411);;
        }
        // 初始化创建文件公共类句柄
        $createFileHandler = MFilesCommon::initMFilesCommon();
        if (count($_FILES) == 0) {
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }

        $keys = array_keys($_FILES);
        if (count($keys) != 1) {
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }
        $key = $keys[0];
        // 检查请求参数$_FILES
        if (isset($_FILES[$key]) === false)
        {
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }
        // 检查文件上传过程是否有错
        if ($_FILES[$key]["error"] != 0) {
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }

        $file_name = $_FILES[$key]["name"];
        $type      = CUtils::mime_content_type($file_name);
        $size      = $_FILES[$key]["size"];
        $tmp_name  = $_FILES[$key]["tmp_name"];
        // 验证文件是否已经上传成功
        if (file_exists($tmp_name) === false) {
            throw new MFilesException(Yii::t('api',MConst::INTERNAL_SERVER_ERROR), MConst::HTTP_CODE_500);
        }
        
        // 检查文件上传错误
        if (filesize($tmp_name) != $size) {
            throw new MFilesException(Yii::t('api',"The file upload error!"), MConst::HTTP_CODE_400);
        }
        $signature = MiniUtil::getFileHash($tmp_name);
        // 解析路径
        $parent_path = "/" . $url_manager->parsePathFromUrl($uri);
        $user = MUserManager::getInstance()->getCurrentUser();
        $folderPath = MiniFile::getInstance()->getByPath($parent_path);
        //如果目录不存在，则创建
        if(!empty($folderPath)){
            $values = array();
            $values['is_deleted'] = false;
            MiniFile::getInstance()->updateByPath($parent_path,$values);
        }else{
            MiniFile::getInstance()->createFolder($parent_path,$user['id']);
        }
        $path        = $parent_path . "/" . $file_name;
        $createFileHandler->size           = $size;
        $createFileHandler->parent_path    = MUtils::convertStandardPath($parent_path);
        $createFileHandler->file_name      = $file_name;
        $createFileHandler->root           = $root;
        $createFileHandler->path           = MUtils::convertStandardPath($path);;
        $createFileHandler->type           = $type;
        // 文件不存在,保存文件
        $createFileHandler->saveFile($tmp_name, $signature, $size);
        // 保存文件meta
        $createFileHandler->saveFileMeta();
        // 处理不同端，不同返回值
        if (MUserManager::getInstance()->isWeb() === true)
        {
            $createFileHandler->buildWebResponse();
            return ;
        }
        $createFileHandler->buildResult();
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
