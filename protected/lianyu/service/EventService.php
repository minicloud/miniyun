<?php
/**
 * 事件列表服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class EventService extends MiniService {
    public function getList(){
        $path = MiniHttp::getParam('path','');
        $time = MiniHttp::getParam("time","-1");
        $deviceUuid = MiniHttp::getParam('device_uuid','-1');
        $currentPage = MiniHttp::getParam('current_page','1');
        $pageSize = MiniHttp::getParam('page_size','20');
        $model = new EventBiz();
        $data  = $model->getList($path,$time,$deviceUuid,$pageSize,$currentPage);
        return $data;
    }
}