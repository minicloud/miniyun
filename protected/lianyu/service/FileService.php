<?php
/**
 * 文件服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class FileService extends MiniService{
    /**
     * download file
     */
    public function download() {
        $path = MiniHttp::getParam("path","");
        $biz = new FileBiz();
        $biz->download($path);
    }
    /**
     * 目录打包下载
     */
    public function downloadToPackage(){
        $paths = MiniHttp::getParam('paths','');
        $package = new FileBiz();
        $package->downloadToPackage($paths);
    }

    /**
     * 根据signature下载文件
     */
    public function downloadBySignature(){
        $signature = MiniHttp::getParam("signature","");
        $filePath = MiniHttp::getParam("file_path","");
        $biz = new FileBiz();
        $biz->downloadBySignature($filePath,$signature);
    }
    /**
     * get content
     */
    public function content() {
        $path = MiniHttp::getParam("path","");
        $signature = MiniHttp::getParam("signature","");
        $biz = new FileBiz();
        $biz->content($path,$signature);
    }

    /**
     * 文本文件預覽
     * @return mixed
     */
    public function preViewTxt(){
        $path    = MiniHttp::getParam("path","");
        $signature = MiniHttp::getParam("signature","");
        $biz    = new FileBiz();
        $content = $biz->txtContent($path,$signature);
        return $content;

    }

    /**
     * office文件预览
     * @return string
     */
    public function preViewDoc(){
        $path = MiniHttp::getParam("path","");
        $signature = MiniHttp::getParam("signature","");
        $biz = new FileBiz();
        $url  = $biz->doc($path,$signature);
        return $url;
    }

    /**
     * 上传文件
     */
    public function upload(){
        $path = MiniHttp::getParam("path","");
        $biz = new FileBiz();
        $biz->upload($path);
    }

    /**
     * 判断文件是否被分享
     * @return bool
     */
    public function shared(){
        $path = MiniHttp::getParam("path","");
        $biz = new FileBiz();
        $isShared = $biz->shared($path);
        return $isShared;
    }
    /**
     * 清空回收站
     */
    public function cleanRecycle(){
        $biz = new FileBiz();
        $op  = $biz->cleanRecycle();
        return $op;
    }
    /**
     * 获取文件打开的方式
     */
    public function getExtendTactics(){
        $biz = new FileBiz();
        $data = $biz->getExtendTactics();
        return $data;
    }
    /**
     * 判断迷你文档插件是否打开
     */
    public function validMiniDocPlugin(){
        $biz = new FileBiz();
        $data = $biz->validMiniDocPlugin();
        return $data;
    }
    /**
     *获取文件信息
     */
    public function getFileInfo(){
        $filePath = MiniHttp::getParam("file_path","");
        $biz = new FileBiz();
        $data = $biz->getFileInfo($filePath);
        return $data;
    }
    /**
     * 获取文本文档文本大小
     */
    public function limitPolicy(){
        $biz = new FileBiz();
        $data = $biz->limitPolicy();
        return $data;
    }
}