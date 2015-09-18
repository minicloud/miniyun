<?php
/**
 * LDAP业务层
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 2.2
 */
class PluginLdapCommand extends CConsoleCommand{
    private  $userId = -1;//当为-1时，表示部门
    /**
     * 通过分析dc获得Host
     */
    private function getLdapHost($cbInfo){
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
     * 根据dn解析出部门隶属关系
     * 形如：CN=test1,OU=dev1,OU=dev,DC=corp,DC=miniyun,DC=com
     * 解析成[dev][dev1]
     * @param string $dn
     * @return string
     */
    private function getDepartment($dn){
        $itemList = explode(",",$dn);
        $retList = array();
        $departmentInfo = '';
        foreach($itemList as $item){
            if(strlen($item)>3 && substr($item,0,3)=="ou="){
                array_push($retList,str_replace("ou=","",$item));
            }
        }
        if(!empty($retList)){
            foreach($retList as $department){
                $departmentInfo = $department.'|'.$departmentInfo;
            }
        }
        return substr($departmentInfo, 0, -1);
    }
    /**
     * 添加部门
     */
    private function create($departmentName,$parentDepartmentId,$departmentOu){
        $result = MiniGroup::getInstance()->create4Ldap($departmentName,$this->userId,$parentDepartmentId,$departmentOu);
        return $result;
    }

    /**
     * 获取部门ID
     */
    private function getId($data,$departmentOu,$parentId=-1,$index=0,$createCount=0){
        for($i=$index;$i<count($data);$i++){
            $groupRelations = MiniGroupRelation::getInstance()->getByParentId($parentId);
            $isExist = false;
            if(!empty($groupRelations)){
                foreach($groupRelations as $groupRelation){
                    $group = MiniGroup::getInstance()->findById($groupRelation['group_id']);
                    if($group['description']==$departmentOu){//以group的description为主键，当ou=description时则成立
                        $isExist = true;
                        $groupId = $groupRelation['group_id'];
                        $this->create($data[$i],$parentId,$departmentOu);
                        break;
                    }
                }
            }
            if(!$isExist){
                $id = $this->create($data[$i],$parentId,$departmentOu);
                $groupRelation = MiniGroupRelation::getInstance()->getById($id);
                $groupId = $groupRelation['group_id'];
                $createCount++;
            }
            if($i==count($data)-1){
                return $groupId;
            }else{
                return $this->getId($data,$groupId,$index+1,$createCount);
            }
        }
    }

    /**
     * 存储部门信息，以及用户与部门的关系
     */
    private function saveData($userName,$departmentId){
        $user = MiniUser::getInstance()->getUserByName($userName);
        $userGroupRelations = MiniUserGroupRelation::getInstance()->getByUserId($user['id']);
        $isExist = false;//判断用户是否已经被导入，存在则修改
        if(!empty($userGroupRelations)){
            foreach($userGroupRelations as $userGroupRelation){
                $group = MiniGroup::getInstance()->getById($userGroupRelation['group_id']);
                if(!empty($group)){
                    if($group['user_id']>0){
                        continue;
                    }else{
                        $isExist = true;
                        break;
                    }
                }
            }

        }
        $group = MiniGroup::getInstance()->findById($departmentId);
        if($isExist){
            MiniUserGroupRelation::getInstance()->update($user['id'],$group['id']);
        }else{
            MiniUserGroupRelation::getInstance()->create($user['id'],$group['id']);
        }
    }
    /**
     * 导入部门
     */
    private function importDepartment($userName,$department,$departmentEntries,$departmentAlias){
        $arr = explode('|',$department);
        $num = count($arr);
        $department = $arr[$num-1];
        $departmentArray = array_splice($departmentEntries,1);
        for($i=0;$i<count($departmentArray);$i++){
            if($departmentArray[$i]['ou'][0] == $department){
                $departmentName = $departmentArray[$i][$departmentAlias][0];
            }
            if(count($departmentArray[$i]['ou'])>2 && $departmentArray[$i]['ou'][1] == $department){
                $departmentName = $departmentArray[$i][$departmentAlias][0];
            }
        }
        $departmentNames = array();
        if(strpos($departmentName,'|')){
            $departmentNames = explode('|',trim($departmentName));
        }else{
            $departmentNames[] = $departmentName;
        }
        $departmentOu = $department;
        $departmentId = $this->getId($departmentNames,$departmentOu);
        $this->saveData($userName,$departmentId);
    }
    /**
     * 同步所有域帐号
     */
    public function actionSyncUsers()
    {
        $ldapInfo = array();
        $userSource = apply_filters('third_user_source', false);
        if($userSource == false){
            echo 'LDAP插件未启用';exit;
        }
        $ldapInfo['ldap_host']              = MiniOption::getInstance()->getOptionValue('ldap_host');
        $ldapInfo['ldap_port']              = MiniOption::getInstance()->getOptionValue('ldap_port');
        $ldapInfo['ldap_base_cn']           = MiniOption::getInstance()->getOptionValue('ldap_base_cn');
        $ldapInfo['ldap_primary_key']       = MiniOption::getInstance()->getOptionValue('ldap_primary_key');
        $ldapInfo['ldap_nick']              = MiniOption::getInstance()->getOptionValue('ldap_nick');
        $ldapInfo['department_alias']       = MiniOption::getInstance()->getOptionValue('ldap_department_name');
        $ldapInfo['ldap_test_user_name']    = MiniOption::getInstance()->getOptionValue('ldap_test_user_name');
        $ldapInfo['ldap_test_password']     = MiniOption::getInstance()->getOptionValue('ldap_test_password');
        $ldapInfo['ldap_sync_department']   = MiniOption::getInstance()->getOptionValue('ldap_sync_department');
        foreach($ldapInfo as $info){
            if(empty($info)){
                echo 'LDAP插件未设置！';exit;
            }
        }
        $ldapUsrDom = "@".$this->getLdapHost($ldapInfo['ldap_base_cn']);
        $ldapConn = @ldap_connect($ldapInfo['ldap_host'],$ldapInfo['ldap_port']);
        @ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        // @ldap_bind($ldapConn,iconv('utf-8', $ldapInfo['ldap_coding'],$ldapInfo['ldap_test_user_name'].$ldapUsrDom),$ldapInfo['ldap_test_password']); //验证账号与密码
        @ldap_bind($ldapConn);
        $attrItems = array( "ou","dn","mail","telephoneNumber",$ldapInfo['ldap_nick'],"useraccountcontrol",$ldapInfo['department_alias']);
        $results   = @ldap_search($ldapConn,$ldapInfo['ldap_base_cn'],"(|(sn=*)(givenname=*))",$attrItems);
        $entries   = @ldap_get_entries($ldapConn, $results);

        $results2   = @ldap_search($ldapConn,$ldapInfo['ldap_base_cn'],"(ou=*)",$attrItems);
        $entries2   = @ldap_get_entries($ldapConn, $results2);
        foreach($entries as $key => $entry){
            $userData       = array();
            $extend         = array();
            if(!empty($entry['dn'])){
                $userData['user_status'] = 1;
                $dn = $entry['dn'];
                $cn = explode(',',$dn)[0];
                $department = $this->getDepartment($dn);
                $userName = explode('=',$cn)[1];
                $userData['nick'] = $userName;
                $userData['name'] = $userName;
                if(!empty($entry['telephonenumber'])){
                    $extend['phone'] = $entry['telephonenumber'][0];
                }
                if(!empty($entry[$ldapInfo['ldap_nick']])){
                    $extend['nick'] = $entry[$ldapInfo['ldap_nick']][0];
                }
                if(!empty($entry['mail'])){
                    $extend['email'] = $entry['mail'][0];
                }
                if(!empty($entry['dn'])){
                    $extend['dn'] = $entry['dn'];   
                }
                if(!empty($extend)){
                    $userData['extend'] = $extend;
                }
            }
            if(!empty($userData)){
                MiniUser::getInstance()->create($userData);
                echo('已导入：'.$userData['name']."\n");
                if($key+1 == $entries['count']){
                    echo('共导入'.$entries['count']."位用户\n");
                }
                if($ldapInfo['ldap_sync_department'] != 'false' && !empty($department)){
                    $this->importDepartment($userName,$department,$entries2,$ldapInfo['department_alias']);
                }
            }
        }
    }
}