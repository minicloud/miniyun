<?php
/**
 * 用户接口入口.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
class APIController extends AnonymousController{
    /**
     * 获得白名单
     */
    protected function getWhiteList(){
        $list = parent::getWhiteList();
        $newList = array(
            "module",
        );
        return array_merge($list,$newList);
    }
    public function invoke(){
        parent::invoke();
    }

}