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
     * 站点信息
     * @return array
     */
    public function info() {
        $biz = new SiteBiz();
        $data = $biz->getSiteInfo();
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
     * 系统是否只有默认的账号
     */
    public function onlyDefaultAccount(){
        $biz = new SiteBiz();
        return  $biz->onlyDefaultAccount();
    }
}