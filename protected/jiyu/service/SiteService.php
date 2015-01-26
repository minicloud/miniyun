<?php
/**
 * 站点信息
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class SiteService extends MiniService{
    /**
     * 用户是否登录信息
     */
    public function userInfo(){
        $data = array();
        $data['user_name'] = 'admin';
        $data['used_space'] = '1024';
        $data['total_space'] = '1024';
        $data['is_login'] = false;
        return $data;
    }
    /**
     * 站点信息
     * @return array
     */
    public function info() {
        $biz = new SiteBiz();
        $info = $biz->getSiteInfo();
        $pluginInfo = array();
        $pluginInfo = apply_filters("plugin_info", $pluginInfo);
        $info["plugins"] = $pluginInfo;
        return $info;
    }
    /**
     * 创建外联（1.6将会去掉）
     * @return array
     */
    public function createLink() {
        $tid    = MiniHttp::getParam('tid',"");
        $status = MiniHttp::getParam('is_link',"");
        $biz    = new SiteBiz();
        return $biz->createLink($tid,$status);
    }
    /**
     * 注册用户
     */
    public function createUser(){
        $name       = MiniHttp::getParam('name',"");
        $email      = MiniHttp::getParam('email',"");
        $password   = MiniHttp::getParam('password',"");
        $biz = new SiteBiz();
        return  $biz->createUser($name,$password,$email);
    }
    /**
     * 根据OpenId获得accessToken
     */
    public function bind(){
        $appKey   = MiniHttp::getParam('app_key',"");
        $openId   = MiniHttp::getParam('open_id',"");
        $biz = new SiteBiz();
        return  $biz->bindOpenId($appKey,$openId);
    }
    /**
     * 系统是否只有默认的账号
     */
    public function onlyDefaultAccount(){
        $biz = new SiteBiz();
        return  $biz->onlyDefaultAccount();
    }
}