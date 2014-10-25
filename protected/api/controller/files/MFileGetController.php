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
     *
     */
    public function invoke($uri = null) {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init ();
        // 解析文件路径，若返回false，则错误处理
        $urlManager = new MUrlManager ();
        $path = $urlManager->parsePathFromUrl ( $uri );
        $root = $urlManager->parseRootFromUrl ( $uri );
        if ($path == false || $root == false) {
            throw new MFilesException ( Yii::t('api',MConst::PATH_ERROR ), MConst::HTTP_CODE_411 );
        }
        // 解析路径
        $path = MUtils::convertStandardPath ($path);
        MiniFile::getInstance()->download($path);
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