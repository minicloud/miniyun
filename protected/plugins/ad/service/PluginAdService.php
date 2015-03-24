<?php
/**
 * AD活动目录接口
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class PluginAdService extends MiniService{
    /**
     * 存储AD信息
     */
    public function setAdInfo(){
        $adInfo = array();
        $adInfo['ad_ldap_host']        = MiniHttp::getParam('ad_ldap_host',"");
        $adInfo['ad_ldap_port']        = MiniHttp::getParam('ad_ldap_port',"");
        $adInfo['ad_ldap_base_cn']     = MiniHttp::getParam('ad_ldap_base_cn',"");
        $adInfo['ad_coding']           = MiniHttp::getParam('ad_coding',"");
        $adInfo['ad_white_list_open']  = MiniHttp::getParam('ad_white_list_open',"");
        $adInfo['ad_sync_department']  = MiniHttp::getParam('ad_sync_department',"");
        $adInfo['user_name']           = MiniHttp::getParam('user_name',"");
        $adInfo['password']            = MiniHttp::getParam('password',"");
        $model = new PluginAdBiz();
        return $model->setAdInfo($adInfo);
    }
    /**
     * 获取AD信息
     */
    public function getAdInfo(){
        $model = new PluginAdBiz();
        return $model->getAdInfo();
    }
}