<?php
/**
 * AD活动目录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginAdBiz extends MiniBiz{
    private $code;
    /**
     * 通过分析dc获得Host
     */
    private function getHost($cbInfo){
        $host = "";
        $value = str_replace(" ", "", strtolower($cbInfo));
        $value = str_replace(",", "", $value);
        $items = explode("dc=",$value);
        $index = 0;
        foreach ($items as $item){
            if($index>0){
                if(strlen($host)>0){
                    $host = $host.".";
                }
                $host = $host.$item;
            }
            $index = $index+1;
        }
        return $host;
    }

    /**
     *存储AD信息
     */
    public function setAdInfo($adInfo){
        $checkResult = $this->checkAdInfo($adInfo);
        if($checkResult){
            unset($adInfo['user_name']);
            unset($adInfo['password']);
            foreach($adInfo as $key=>$val){
               MiniOption::getInstance()->setOptionValue($key,$val);
            }
           return array('success'=>true);
        }else{
           return array('success'=>false,'msg'=>$this->code);
        }
    }

    /**
     *获取AD信息
     */
    public function getAdInfo(){
        $adInfo  = array();
        $adInfo['ad_ldap_host']           = MiniOption::getInstance()->getOptionValue('ad_ldap_host');
        $adInfo['ad_ldap_port']           = MiniOption::getInstance()->getOptionValue('ad_ldap_port');
        $adInfo['ad_ldap_base_cn']        = MiniOption::getInstance()->getOptionValue('ad_ldap_base_cn');
        $adInfo['ad_white_list_open']     = MiniOption::getInstance()->getOptionValue('ad_white_list_open');
        $adInfo['ad_sync_department']     = MiniOption::getInstance()->getOptionValue('ad_sync_department');
        $adInfo['ad_coding']              = MiniOption::getInstance()->getOptionValue('ad_coding');
        return $adInfo;
    }

    /**
     * 根据用户名+密码查询账号是否在AD服务器中
     */
    function checkAdInfo($adInfo) {
        $ldap_usr_dom = "@".$this->getHost($adInfo['ad_ldap_base_cn']);
        $userName = str_replace($ldap_usr_dom, "", $adInfo['user_name']);
        $ldap_conn = @ldap_connect($adInfo['ad_ldap_host'],$adInfo['ad_ldap_port']);
        if (!$ldap_conn){
            $this->code = -1;
            return false;
        }
        $loginResult = @ldap_bind($ldap_conn,iconv('utf-8', $adInfo['ad_coding'],$userName.$ldap_usr_dom),$adInfo['password']); //验证账号与密码
        if (!$loginResult){
            $this->code = -2;
            return false;
        }
        return true;
    }
}