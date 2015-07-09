<?php
/**
 * LDAP活动目录接口
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 2.2
 */
class PluginLdapService extends MiniService{
    /**
     * 存储LDAP信息
     */
    public function setLdapInfo(){
        $ldapInfo = array();
        $ldapInfo['ldap_host']        = MiniHttp::getParam('ldap_host',"");
        $ldapInfo['ldap_port']        = MiniHttp::getParam('ldap_port',"");
        $ldapInfo['ldap_base_cn']     = MiniHttp::getParam('ldap_base_cn',"");
        $ldapInfo['ldap_primary_key']     = MiniHttp::getParam('ldap_primary_key',"");
        $ldapInfo['ldap_nick']     = MiniHttp::getParam('ldap_nick',"");
        $ldapInfo['ldap_coding']           = MiniHttp::getParam('ldap_coding',"");
        $ldapInfo['ldap_white_list_open']  = MiniHttp::getParam('ldap_white_list_open',"");
        $ldapInfo['ldap_sync_department']  = MiniHttp::getParam('ldap_sync_department',"");
        $ldapInfo['ldap_test_user_name']   = MiniHttp::getParam('user_name',"");
        $ldapInfo['ldap_test_password']    = MiniHttp::getParam('password',"");
        $model = new PluginLdapBiz();
        return $model->setLdapInfo($ldapInfo);
    }
    /**
     * 获取LDAP信息
     */
    public function getLdapInfo(){
        $model = new PluginLdapBiz();
        return $model->getLdapInfo();
    }
}