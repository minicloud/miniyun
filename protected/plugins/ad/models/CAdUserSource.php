<?php
/**
 * HTTP请求方式获取用户信息
 * ldap用户源模块
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 *  注意： 如果使用ldap服务，需要配置php.ini, extension=php_ldap.dll extension=php_gettext.dll需要打开
 */
class CAdUserSource extends CUserSource implements IUserSourceInterface
{
    //ldap用户源初始化错误
    const LDAP_INIT_ERROR   = 5;
    /**
     * (non-PHPdoc)
     * @see IUserSourceInterface::getUser()
     * 获取用户信息 
     * @since 1.0.0
     */
    public function getUser($userInfo){
        $userName = $userInfo['userName'];
        $password = $userInfo['password'];
        //扩展信息
        $host_str   =  'ad_ldap_host';
        $port_str   =  'ad_ldap_port';
        $baseCn_str =  'ad_ldap_base_cn';
        $coding_str =  'ad_coding';
        $department_str  =  'ad_sync_department';
        $syncDepartment   = MiniOption::getInstance()->getOptionValue($department_str);
        $ldapHost   = MiniOption::getInstance()->getOptionValue($host_str);
        if ($ldapHost===NULL){
            $this->errorCode = self::LDAP_INIT_ERROR;
            return false;
        }
        $ldapPort   = MiniOption::getInstance()->getOptionValue($port_str);
        if ($ldapPort===NULL){
            $this->errorCode = self::LDAP_INIT_ERROR;
            return false;
        }
        $ldapBaseCn   = MiniOption::getInstance()->getOptionValue($baseCn_str);
        if ($ldapBaseCn===NULL){
            $this->errorCode = self::LDAP_INIT_ERROR;
            return false;
        }
        
        $ldapCoding   = MiniOption::getInstance()->getOptionValue($coding_str);
        if ($ldapCoding===NULL){
            $ldapCoding = "GB2312";
        }
        
        if (empty($ldapHost) || empty($ldapPort) || empty($ldapBaseCn)){
           $this->errorCode = self::LDAP_INIT_ERROR; 
           return false;
        }
        
        //链接AD服务器
        $ad = new AD();
        $ad->setHost($ldapHost);
		$ad->setFilter($ldapBaseCn);
		$ad->setCoding($ldapCoding);
		$ad->setPort($ldapPort);
		$ad->setSyncDepartment($syncDepartment);
		$result = $ad->getMember($userName, $password);
        if(!$result){
        	$this->errorCode = MConst::ERROR_PASSWORD_INVALID; // 用户名不存在
            return false;
        }
        return $result;
    }

    /**
     *
     * 在用户验证失败后是否需要进行自身用户系统的验证
     * @since 1.0.0
     */
    public function judgeSelf(){
        return false;
    }

}