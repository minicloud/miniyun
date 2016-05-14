<?php
/**
 * 视频接口
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2016 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 3.0
 */
class VideoService extends MiniService{
    protected function anonymousActionList(){
        return array( 
        );
    }
     /**
     * 查询文件转换状态
     */
    public function status(){
        $path = MiniHttp::getParam("path","");
        $path = rawurldecode($path);
        $biz = new PluginMiniDocBiz();
        return $biz->videoStatus($path);
    }
    /**
     * 视频在线浏览文件获得内容
     * path文件当前路径
     * type文件类型，可选择mp4/png
     */
    public function content(){
        $path = MiniHttp::getParam("path","");
        $path = rawurldecode($path);
        $type = MiniHttp::getParam("type","mp4");
        $biz = new PluginMiniDocBiz();
        return $biz->videoContent($path,$type);
    }
}