<?php
/**
 * 在线用户列表服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class OnlineUserService extends MiniService{
    /**
     * 获取在线用户列表
     */
    public function getList()
    {
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $model       = new OnlineListBiz();
        $data        = $model->getOnlineUsers($pageSize,$currentPage);
        return $data;
    }

}