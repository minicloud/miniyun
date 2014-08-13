<?php
/**
 * 文件历史版本服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class HistoryService extends MiniService{
    /**
     * 历史版本列表
     * @return mixed
     */
    public function history(){
        $path = MiniHttp::getParam("file_path","");
        $history = new HistoryBiz();
        $histories =  $history->getList($path);
        return $histories;
    }
    /**
     * 恢复版本
     */
    public function recover(){
        $signature = MiniHttp::getParam("signature","-1");
        $path = MiniHttp::getParam("file_path","");
        $history = new HistoryBiz();
        $result = $history->recover($signature,$path);
        return array('success'=>$result);
    }
}