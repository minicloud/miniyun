<?php
/**
 * 管理员接口入口.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ConsoleController extends AnonymousController{
    /**
     * 获得白名单
     */
    protected function getWhiteList(){
        $list = parent::getWhiteList();
        $newList = array(
            "appManage",
            "chooser",
            "department",
            "deviceManage",
            "domain",
            "editorManage",
            "fileManage",
            "homePage",
            "module",
            "onlineUser",
            "plugin",
            "systemManage",
            "userInfo",
        );
        return array_merge($list,$newList);
    }
    public function invoke(){
        $filter = new MUserFilter();
        $filter->oauth2Judge();
        //check user auth
        $user = MUserManager::getInstance()->getCurrentUser();

        $userId = $user["id"];
        $user = MiniUser::getInstance()->getUser($userId);

        if($user["is_admin"]!==true){
            throw new  MiniException(1200);
        }
        parent::invoke();
    }

}