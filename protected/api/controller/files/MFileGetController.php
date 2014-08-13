<?php
/**
 * Miniyun file get服务主要入口地址,实现文件下载
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFileGetController extends MApplicationComponent implements MIController {
    /**
     * 控制器执行主逻辑函数
     * URL Structure http://www.miniyun.cn/api/files/<root>/<path> 
     * @version 0
     * @method GET
     * 
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri = null) {
        //data源处理对象
        $dataObj = Yii::app()->data;
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init ();
        // 解析文件路径，若返回false，则错误处理
        $url_manager = new MUrlManager ();
        $path = $url_manager->parsePathFromUrl ( $uri );
        $root = $url_manager->parseRootFromUrl ( $uri );
        if ($path == false || $root == false) {
            throw new MFilesException ( Yii::t('api',MConst::PATH_ERROR ), MConst::HTTP_CODE_411 );
        }
        // 获取用户数据，如user_id
        $user           = MUserManager::getInstance ()->getCurrentUser ();
        $device         = MUserManager::getInstance ()->getCurrentDevice ();
        $user_id        = $user["user_id"];
        $user_nick      = $user["user_name"];
        $user_device_id = $device["device_id"];
        // 接收参数,文件对应版本信息
        $rev     = 0;
        if (isset ( $_REQUEST ["rev"] )) {
            $rev = ( int ) $_REQUEST ["rev"];
            if ($rev == 0) { // 传入参数不能为0
                throw new MFilesException ( Yii::t('api',"The revision doesn't exist." ), MConst::HTTP_CODE_404 );
            }
        }
        // 解析路径
        $path         = MUtils::convertStandardPath ( "/" . $path );
        $share_filter = MSharesFilter::init();
        if ($share_filter->handlerCheck($user_id, $path, true)) {
            $user_id = $share_filter->master;
            $path    = $share_filter->_path;
        }
        
        $path = "/" . $user_id . $path;
        $file_detail = MFiles::queryFilesByPath ( $path );
        if ($file_detail === false || count ( $file_detail ) == 0) {
            throw new MFilesException ( Yii::t('api',MConst::NOT_FOUND ), MConst::HTTP_CODE_404 );
        }

        //判断用户是否有下载此文件的权限
        if ($share_filter->is_shared){
            $share_filter->hasPermissionExecute($path, MPrivilege::RESOURCE_READ);
        }

        $file_detail = MFiles::exchange2Object ( $file_detail );
        
        $rev = isset($_REQUEST ["rev"]) ? $rev : $file_detail->version_id;
        // 有服务未能正确记录
//        $file_meta = MFileMetas::queryFileMeta ( $file_detail->file_path, MConst::VERSION );
//        if ($file_meta == false || empty ( $file_meta )) {
//            throw new MFilesException ( Yii::t('api',MConst::INTERNAL_SERVER_ERROR ), MConst::HTTP_CODE_500 );
//        }
//        //
//        // 判断文件指定版本是否存在
//        //
//        if (MUtils::isExistReversion ( $rev, $file_meta [0] ["meta_value"] ) == false) {
//            throw new MFilesException ( Yii::t('api',"The revision dosen't exist." ), MConst::HTTP_CODE_404 );
//        }
        $file_name = $file_detail->file_name;
        $file_id = $file_detail->id;
        //
        // 获取文件版本
        //
        $version_id = $file_detail->version_id;
        if ($rev != 0) {
            $version_id = $rev;
        }
        $file_version = MiniVersion::getInstance()->getVersion($version_id);
        if ($file_version === null) {
            throw new MFilesException ( Yii::t('api',MConst::NOT_FOUND ), MConst::HTTP_CODE_404 );
        }
        
        //
        // 获取文件绝对路径
        //
        $signature = $file_version["file_signature"];
        $content_type = $file_version["mime_type"];
        $content_type = empty ( $content_type ) ? MConst::DEFAULT_DOWNLOAD_MIME_TYPE : $content_type;

        //下载文件的hook
        $data = array();
        $data["hash"]         = $signature;
        $data["filename"]     = $file_name;
        $data["content_type"] = $content_type;
        $retData = apply_filters("location_down_load", $data);
        if ($retData !== $data && !empty($retData)){
            header( "HTTP/1.1 ".MConst::HTTP_CODE_301." Moved Permanently" );
            header( "Location: ". $retData );
            return;
        }

        $file_path = MUtils::getPathBySplitStr ( $signature );
        if ($dataObj->exists( $file_path ) === false) {
            throw new MFilesException ( Yii::t('api',MConst::NOT_FOUND ), MConst::HTTP_CODE_404 );
        }
        // 检查是否输出
        if (headers_sent ()) {
            exit ();
        }
        CUtils::outContent($file_path, $content_type, $file_name);
    }
    /**
     * get处理异常入口地址
     *
     */
    public function handleException($exception) {
        header ( "HTTP/1.1 " . $exception->getCode () . " " . $exception->getMessage () );
        echo $exception->getCode () . " " . $exception->getMessage ();
        return;
    }
}
?>