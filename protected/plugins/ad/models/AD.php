<?php
/**
 * 根据过滤条件+账号+密码向AD查询用户的相关信息
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 *
 */
class AD{
	private $host = "";//LDAP服务器地址，例如:corp.hengwei.com
	private $filter = "";//过滤条件，例如：OU=dev1,OU=dev,DC=corp,DC=hengwei,DC=com
	private $port = 389;//LDAP服务器端口，默认是389
	private $coding = "GB2312";
	private $syncDepartment = true;
	private $code;
	public function AD(){
	}
	public function getCode(){
		return $this->code;
	}
	public function setCoding($coding){
		$this->coding = $coding;
	}
	public function setFilter($filter){
		$this->filter = $filter;
	}
	public function setSyncDepartment($syncDepartment){
		$this->syncDepartment = $syncDepartment;
	}
	public function setHost($host){
		$host = strtolower($host);
		$host = str_replace("ldap://", "", $host);
		$this->host = $host;
	}
	public function setPort($port){
		$this->port = $port;
	}
	/**
	 * 通过分析filter的dc获得Host
	 */
	private function getHost(){
		$host = "";
		$value = str_replace(" ", "", strtolower($this->filter));
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
     * 根据用户名+密码查询账号是否在AD服务器中
     * @param string $userName
     * @param string $password
     * @return array|bool
     */
    function getMember($userName,$password) {
        $adWhiteListOpen     = MiniOption::getInstance()->getOptionValue('ad_white_list_open');
        if($adWhiteListOpen == 'true'){
            $user = MiniUser::getInstance()->getUserByName($userName);
            if(empty($user)){
                $this->code = -2;
                return false;
            }
        }
		$ldapUsrDom = "@".$this->getHost();
		$userName   = str_replace($ldapUsrDom, "", $userName);
        $ldapConn   = @ldap_connect($this->host,$this->port);
        if (!$ldapConn){
        	$this->code = -1;#服务器无法连接
        	return false;
        }
        @ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        $loginResult = @ldap_bind($ldapConn,iconv('utf-8', $this->coding,$userName.$ldapUsrDom),$password); //验证账号与密码
        if (!$loginResult){
        	$this->code = -2;#测试帐号与密码错误
        	return false;
        }
        $dn        = $this->filter;
        $attrItems = array( "ou", "sn","mail","telephonenumber","displayname","department");
        $query = "(&(userprincipalname=".iconv('utf-8', $this->coding,$userName.$ldapUsrDom)."))"; //验证账号是否在过滤条件中
        $results   = @ldap_search($ldapConn,$dn,$query,$attrItems);
        $output    = array();
        $extend             = array();
        $extend["nick"]     = $userName;
        $output["user_name"]= $userName;
        if($results!=false){
        	$entries = @ldap_get_entries($ldapConn, $results);
        	array_shift($entries);
        	if(count($entries)>0){//获得更加详细的信息
        		$entry = $entries[0];
        		$phoneInfo = $this->getValue("telephonenumber", $entry);//获得电话号码
        		if($phoneInfo!=null){
        			$extend["phone"] = $phoneInfo;
        		}
                $displayNameInfo = $this->getValue("displayname", $entry);//获得昵称与全名
        		if($displayNameInfo!=null){
        			$extend["nick"]     =  $displayNameInfo;
                }else{
        			$extend["nick"]     = $userName;
                }
        		$mailInfo = $this->getValue("mail", $entry);//获得电子邮件
        		if($mailInfo!=null){
        			$extend["email"] = $mailInfo;
        		}
                if($this->syncDepartment != 'false'){
                    $department = $this->getValue("dn", $entry);//获得昵称与全名
                    if($department!=null){
                        $departmentInfo = $this->getDevelopment($department);
                        if(!empty($departmentInfo)){
                            $output['departmentData'][0][] = $departmentInfo;
                            $output['departmentData'][0][] = $userName;
                        }
                    }
                }
        	}
        }
        $output["extend"] = $extend;
        ldap_close($ldapConn);
        return $output;
	}
	/**
	 *
	 * 获得AD查询出来后的值
	 * @param string $key
	 * @param array  $entry
     * @return array|null
	 */
	private function getValue($key,$entry){
		if(array_key_exists($key,$entry)){
			$item = $entry[$key];
			if(is_array($item) && count($item)>0){
				return $item[0];
			}else{
				return $item;
			}
		}
		return null;
	}
    /**
     * 根据dn解析出部门隶属关系
     * 形如：CN=test1,OU=dev1,OU=dev,DC=corp,DC=miniyun,DC=com
     * 解析成[dev][dev1]
     * @param string $dn
     * @return string
     */
    private function getDevelopment($dn){
        $itemList = explode(",",$dn);
        $retList = array();
        $departmentInfo = '';
        foreach($itemList as $item){
            if(strlen($item)>3 && substr($item,0,3)=="OU="){
                array_push($retList,str_replace("OU=","",$item));
            }
        }
        if(!empty($retList)){
            foreach($retList as $department){
                $departmentInfo = $department.'|'.$departmentInfo;
            }
        }
        return substr($departmentInfo, 0, -1);
    }
}