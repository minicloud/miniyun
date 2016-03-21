<?php
/**
 * 用户信息业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserBiz  extends MiniBiz{
    /**
     * @param $v1
     * @param $v2
     * @return array
     */
    public function findSame($v1,$v2){
        if ($v1===$v2)
        {
            return 0;
        }
        if ($v1 > $v2) return 1;
        {
            return -1;
        }
        return 1;
    }
    public function getFriends($pageSize,$page){
        $userId = $this->user["id"];
        $userCount = MiniUser::getInstance()->getEnableCount()-1;
        $items = MiniUser::getInstance()->getPageList($userId,"-id",$pageSize,($page-1)*$pageSize);
        $users = array();
        foreach($items as $item){
            $friend = array();
            $friend["id"]   = $item["id"];
            $friend["nick"] = $item["nick"];
            $friend["name"] = $item["user_name"];
            $friend["avatar"] = $item['avatar'];
            $arr = MiniUserGroupRelation::getInstance()->findUserGroup($userId,$item["id"]);//找到好友对应的群组(这里会关联到非当前用户新建的用户组，下面解决此问题)
            $ownerGroup = MiniGroup::getInstance()->getList($userId);
            $ownerGroupList = $ownerGroup['list'];//找到当前用户拥有的群组
            $result = array();
            foreach($arr as $brr){//遍历好友的群组，
                if(in_array($brr,$ownerGroupList)){//找出其群组与当前用户相同的则放入该用户的user_group,就是此处的$result
                    array_push($result,$brr);
                }
            }
            $friend["user_group"]= $result;
            array_push($users,$friend);
        }
        $data = array();
        $data["count"] = $userCount;
        $data["users"] = $users;
        return $data;
    }

    /**
     * 搜索用户
     */
    public function searchFriends($key){
        $userId = $this->user["id"];
        $items = MiniUser::getInstance()->searchByName($userId,$key);
        $users = array();
        foreach($items as $item){
            $friend = array();
            $friend["id"]   = $item["id"];
            $friend["nick"] = $item["nick"];
            $friend["name"] = $item["user_name"];
            $friend["avatar"] = $item['avatar'];
            $arr = MiniUserGroupRelation::getInstance()->findUserGroup($userId,$item["id"]);
            $friend["user_group"]= $arr;
            array_push($users,$friend);
        }
        return $users;
    }
    /**
     * 获取用户组列表
     */
    public function getGroupList(){

    }

    public function getCodeByUserId(){
        $data= MiniOption::getInstance()->getOptionValue("code");
        return $data;
    }

    /**
     * 用户登录
     * @return array|bool
     * @throws
     */
    public function oauth2(){
        $isExtend = apply_filters("license_expired");
        if($isExtend===1){
            $userName = MiniHttp::getParam("username","");
            if($userName!=="admin"){
                throw new MiniException(440);
            }
        }
        $oauth = new PDOOAuth2();
        $token = $oauth->grantAccessToken();
        #添加登陆日志
        $deviceId = $oauth->getVariable("device_id");
        MiniLog::getInstance()->createLogin($deviceId);
        #返回site_id，便于与cloud.miniyun.cn通信
        $token["site_id"] = MiniSiteUtils::getSiteID();
        return $token;
    }

    public function saveSortType($type,$sortOrder){
        $user = $this->user;
        $metas['extend'] = ['file_sort_type'=>$type,'file_sort_order'=>$sortOrder];
        return MiniUserMeta::getInstance()->create($user,$metas);
    }
    /**
     * 存储隐藏空间密码  
     */
    public function newHideSpacePassword($passwd){
         $user = MUserManager::getInstance()->getCurrentUser();
         $userMeta = MiniUserMeta::getInstance()->getUserMetas($user['id']);
         if(!array_key_exists('hide_space_passwd', $userMeta)){
            $salt = $user['salt'];
            $meta = array('hide_space_passwd'=>strtolower(md5($passwd.$salt)));
            $metas = array('extend'=>$meta);
            MiniUserMeta::getInstance()->create($user,$metas);
            //创建空目录
            
            return array('status'=>'ok');
         }
         return array('status'=>'error','msg'=>'has existed');
    }
    /**
     * 验证隐藏空间密码  
     */
    public function validHideSpacePassword($passwd){
        $user = MUserManager::getInstance()->getCurrentUser();
        $userMeta = MiniUserMeta::getInstance()->getUserMetas($user['id']);
        if(array_key_exists('hide_space_passwd', $userMeta)){
           $salt = $user['salt'];
           $currentPasswd = strtolower(md5($passwd.$salt));
           $rightPasswd = $userMeta['hide_space_passwd'];
           if($currentPasswd===$rightPasswd){
                return array('status'=>'ok');
           }    
        }
        return array('status'=>'error','msg'=>'password invalid');
    }
    /**
     * 重置隐藏空间密码  
     */
    public function resetHideSpacePassword($oldPasswd,$newPasswd){
        $user = MUserManager::getInstance()->getCurrentUser();
        $userMeta = MiniUserMeta::getInstance()->getUserMetas($user['id']);
        if(array_key_exists('hide_space_passwd', $userMeta)){
           $salt = $user['salt'];
           $currentPasswd = strtolower(md5($oldPasswd.$salt));
           $rightPasswd = $userMeta['hide_space_passwd'];
           if($currentPasswd===$rightPasswd){
                $meta = array('hide_space_passwd'=>strtolower(md5($newPasswd.$salt)));
                $metas = array('extend'=>$meta);
                MiniUserMeta::getInstance()->create($user,$metas);
                return array('status'=>'ok');
           }else{
            return array('status'=>'error','msg'=>'old password invalid');
           }   
        }
        return array('status'=>'error');
    }
    /**
     * 管理员重置隐藏空间密码  
     */
    public function adminResetHideSpacePassword($userId,$newPasswd){
        $currentUser = MUserManager::getInstance()->getCurrentUser();
        $userMeta = MiniUserMeta::getInstance()->getUserMetas($currentUser['id']);
        if(array_key_exists('is_admin', $userMeta)){
            $value = $userMeta['is_admin'];
            if($value==='1'){
                $user = MiniUser::getInstance()->getById($userId);
                if($user){
                    $salt = $user['salt']; 
                    $meta = array('hide_space_passwd'=>strtolower(md5($newPasswd.$salt)));
                    $metas = array('extend'=>$meta);
                    MiniUserMeta::getInstance()->create($user,$metas);
                    return array('status'=>'ok');   
                }else{
                    return array('status'=>'error','msg'=>'user not existed');
                }                 
            }          
        }
        return array('status'=>'error','msg'=>'no permission');
    }
}