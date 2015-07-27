<?php
/**
 * LDAP活动目录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 2.2
 */
class PluginLdapBiz extends MiniBiz{
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
     *存储LDAP信息
     */
    public function setLdapInfo($ldapInfo){
        $checkResult = $this->checkLdapInfo($ldapInfo);
        if($checkResult){
            foreach($ldapInfo as $key=>$val){
               MiniOption::getInstance()->setOptionValue($key,$val);
            }
           return array('success'=>true);
        }else{
           return array('success'=>false,'msg'=>$this->code);
        }
    }

    /**
     *获取LDAP信息
     */
    public function getLdapInfo(){
        $ldapInfo  = array();
        $ldapInfo['ldap_host']           = MiniOption::getInstance()->getOptionValue('ldap_host');
        $ldapInfo['ldap_port']           = MiniOption::getInstance()->getOptionValue('ldap_port');
        $ldapInfo['ldap_base_cn']        = MiniOption::getInstance()->getOptionValue('ldap_base_cn');
        $ldapInfo['ldap_primary_key']        = MiniOption::getInstance()->getOptionValue('ldap_primary_key');
        $ldapInfo['ldap_nick']        = MiniOption::getInstance()->getOptionValue('ldap_nick');
        $ldapInfo['ldap_department_name']        = MiniOption::getInstance()->getOptionValue('ldap_department_name');
        $ldapInfo['ldap_white_list_open']     = MiniOption::getInstance()->getOptionValue('ldap_white_list_open');
        $ldapInfo['ldap_sync_department']     = MiniOption::getInstance()->getOptionValue('ldap_sync_department');
        $ldapInfo['ldap_coding']              = MiniOption::getInstance()->getOptionValue('ldap_coding');
        return $ldapInfo;

    }
    /**
     * 根据用户名+密码查询账号是否在LDAP服务器中
     */
    function checkLdapInfo($ldapInfo) {
        $ldapUsrDom = "@".$this->getHost($ldapInfo['ldap_base_cn']);
        $userName = str_replace($ldapUsrDom, "", $ldapInfo['ldap_test_user_name']);
        $ldapConn = @ldap_connect($ldapInfo['ldap_host'],$ldapInfo['ldap_port']);
        if (!$ldapConn){
            $this->code = -1;
            return false;
        }
        @ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        $userPath = "uid=".$userName.",".$ldapInfo['ldap_base_cn'];
        $loginResult = @ldap_bind($ldapConn); //验证账号与密码
        if (!$loginResult){
            $this->code = -2;
            return false;
        }
        $filter="cn=*";
        $results   = @ldap_search($ldapConn,$ldapInfo['ldap_base_cn'],$filter);
        $entries   = @ldap_get_entries($ldapConn, $results);
        if ($entries['count'] == 0){
            $this->code = -2;
            return false;
        }
        return true;
    }
}