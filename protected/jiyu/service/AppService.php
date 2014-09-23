<?php
/**
 * 站点信息
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class AppService extends MiniService{
    /**
     * 站点信息
     * @return array
     */
    public function info() {
        $biz = new AppBiz();
        $data = $biz->getAppInfo();
        $data = apply_filters("api_info_add", $data);
        return $data;
    }
    /**
     * 创建外联（1.6将会去掉）
     * @return array
     */
    public function createLink() {
        $tid    = MiniHttp::getParam('tid',"");
        $status = MiniHttp::getParam('is_link',"");
        $biz    = new AppBiz();
        return $biz->createLink($tid,$status);
    }
    /**
     * 注册用户
     */
    public function createUser(){
        $name       = MiniHttp::getParam('name',"");
        $email      = MiniHttp::getParam('email',"");
        $password   = MiniHttp::getParam('password',"");
        $biz = new AppBiz();
        return  $biz->createUser($name,$password,$email);
    }
    /**
     * 根据OpenId获得accessToken
     */
    public function bind(){
        $appKey   = MiniHttp::getParam('app_key',"");
        $openId   = MiniHttp::getParam('open_id',"");
        $biz = new AppBiz();
        return  $biz->bindOpenId($appKey,$openId);
    }
    /**
     * 系统是否只有默认的账号
     */
    public function onlyDefaultAccount(){
        $biz = new AppBiz();
        return  $biz->onlyDefaultAccount();
    }
}