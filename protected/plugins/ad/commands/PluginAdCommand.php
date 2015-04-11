<?php
/**
 * AD业务层
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginAdCommand extends CConsoleCommand{
    private  $userId = -1;//当为-1时，表示部门
    /**
     * 通过分析dc获得Host
     */
    private function getAdHost($cbInfo){
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
    /**
     * 添加部门
     */
    private function create($departmentName,$parentDepartmentId){
        $result = MiniGroup::getInstance()->create($departmentName,$this->userId,$parentDepartmentId);
        return $result;
    }

    /**
     * 获取部门ID
     */
    private function getId($data,$parentId=-1,$index=0,$createCount=0){
        for($i=$index;$i<count($data);$i++){
            $groupRelations = MiniGroupRelation::getInstance()->getByParentId($parentId);
            $isExist = false;
            if(!empty($groupRelations)){
                foreach($groupRelations as $groupRelation){
                    $group = MiniGroup::getInstance()->findById($groupRelation['group_id']);
                    if($group['group_name']==$data[$i]){
                        $isExist = true;
                        $groupId = $groupRelation['group_id'];
                        break;
                    }
                }
            }
            if(!$isExist){
                $this->create($data[$i],$parentId);
                $id = Yii::app()->db->getLastInsertID();
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
    private function importDepartment($userName,$department){
        $departmentNames = array();
        if(strpos($department,'|')){
            $departmentNames = explode('|',trim($department));
        }else{
            $departmentNames[] = $department;
        }
        $departmentId = $this->getId($departmentNames);
        $this->saveData($userName,$departmentId);
    }
    /**
     * 同步所有域帐号
     */
    public function actionSyncUsers()
    {
        $adInfo = array();
        $userSource = apply_filters('third_user_source', false);
        if($userSource == false){
            echo 'AD插件未启用';exit;
        }
        $adInfo['ad_ldap_host']           = MiniOption::getInstance()->getOptionValue('ad_ldap_host');
        $adInfo['ad_ldap_port']           = MiniOption::getInstance()->getOptionValue('ad_ldap_port');
        $adInfo['ad_ldap_base_cn']        = MiniOption::getInstance()->getOptionValue('ad_ldap_base_cn');
        $adInfo['ad_test_user_name']      = MiniOption::getInstance()->getOptionValue('ad_test_user_name');
        $adInfo['ad_test_password']       = MiniOption::getInstance()->getOptionValue('ad_test_password');
        $adInfo['ad_sync_department']     = MiniOption::getInstance()->getOptionValue('ad_sync_department');
        foreach($adInfo as $info){
            if(empty($info)){
                echo 'AD插件未设置！';exit;
            }
        }
        $ldapUsrDom = "@".$this->getAdHost($adInfo['ad_ldap_base_cn']);
        $ldapConn = @ldap_connect($adInfo['ad_ldap_host'],$adInfo['ad_ldap_port']);
        @ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        @ldap_bind($ldapConn,iconv('utf-8', $adInfo['ad_coding'],$adInfo['ad_test_user_name'].$ldapUsrDom),$adInfo['ad_test_password']); //验证账号与密码
        $attrItems = array( "ou","dn","mail","telephonenumber","displayname");
        $results   = @ldap_search($ldapConn,$adInfo['ad_ldap_base_cn'],"(|(sn=*)(givenname=*))",$attrItems);
        $entries   = @ldap_get_entries($ldapConn, $results);
        foreach($entries as $entry){
            $userData       = array();
            $extend         = array();
            if(!empty($entry['dn'])){
                $dn = $entry['dn'];
                $cn = explode(',',$dn)[0];
                $department = $this->getDepartment($dn);
                $userName = explode('=',$cn)[1];
                $userData['nick'] = $userName;
                $userData['name'] = $userName;
                if(!empty($entry['telephonenumber'])){
                    $extend['phone'] = $entry['telephonenumber'][0];
                }
                if(!empty($entry['displayname'])){
                    $extend['nick'] = $entry['displayname'][0];
                }
                if(!empty($entry['mail'])){
                    $extend['email'] = $entry['mail'][0];
                }
                if(!empty($extend)){
                    $userData['extend'] = $extend;
                }
            }
            if(!empty($userData)){
                MiniUser::getInstance()->create($userData);
                if($adInfo['ad_sync_department'] != 'false' && !empty($department)){
                    $this->importDepartment($userName,$department);
                }
            }
        }
    }
}