<?php
/**
 * 管理后台首页服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class HomePageService extends MiniService{
    /**
     * 获取剩余空间大小
     */
    public function getUsedSpace(){
        $model = new HomePageBiz();
        $data  = $model->getUsedSpace();
        return $data;
    }
}