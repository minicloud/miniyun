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
     * 判断是否是App应用发送的请求
     * 这类请求不用进行用户过滤
     * @return bool
     */
    private function isPluginSendRequest(){
        $uri = $_SERVER['REQUEST_URI'];
        $key = "/plugin/";
        $pos = strpos($uri,$key);
        if($pos){
            $key = "/app";
            $pos1 = strpos($uri,$key,$pos);
            if($pos1){
                return true;
            }
        }
        return false;
    }
    public function invoke(){
    	//IP安全检查
    	do_action('ip_check',false);
        //如属于app向迷你云发送的请求，则不进行用户认证
        if($this->isPluginSendRequest()===false){
            $filter = new MUserFilter();
            $filter->oauth2Judge();
            //check user auth
            $user = MUserManager::getInstance()->getCurrentUser();

            $userId = $user["id"];
            $user = MiniUser::getInstance()->getUser($userId);

            if($user["is_admin"]!==true){
                throw new  MiniException(1200);
            }
        }
        parent::invoke();
    }

}