<?php
/**
 * 文档转换服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class DocConvertService extends MiniService{
    /**
     * 根据文件hash值进行下载文件
     */
    public function  download(){
        //TODO 这里要进行IP的安全过滤，否则将会导致文件匿名下载并外泄
        $fileHash = MiniHttp::getParam('hash',"");
        $biz = new DocConvertBiz();
        $biz->download($fileHash);
    } 
    /**
     * 根据文件hash值报告文档转换情况
     */
    public function  report(){
        //TODO 这里要进行IP的安全过滤，否则将会导致文件匿名下载并外泄
        $fileHash = MiniHttp::getParam('hash',"");
        $status = MiniHttp::getParam('status',"");
        $biz = new DocConvertBiz();
        $result = $biz->report($fileHash,$status);
        if($status==="1"){
            //TODO 把转换的文本内容放入数据库中
        }
        return $result;
    } 
}