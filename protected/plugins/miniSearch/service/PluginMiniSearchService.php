<?php
/**
 * 迷你搜索接口
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginMiniSearchService extends MiniService{
    /**
     * 根据关键词搜索全文内容
     */
    public function  search(){
        $key = MiniHttp::getParam("key","");
        $biz = new PluginMiniSearchBiz();
        return $biz->search($key);
    }
}