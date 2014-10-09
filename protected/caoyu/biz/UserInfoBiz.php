<?php
/**
 * Created by PhpStorm.
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserInfoBiz extends MiniBiz{
    /**
     * 当前用户信息
     */
    public function currentUserInfo(){
        $item=$this->user;
        $userMeta=MiniUserMeta::getInstance()->getUserMetas($item['id']);
        $data = array();
        $data['id']=$item['id'];
        $data['user_name']=$item['user_name'];
        $data['nick']=$item['nick'];
        $data['phone']=$item['phone'];
        $data['email']=$item['email'];
        $data['is_admin']=$item['is_admin'];
        $data['usedSpace']=$item['usedSpace'];
        $data['user_status']=$item['user_status'];
        $data['avatar']=$item['avatar'];
        $data['space']=$userMeta['space']/1024/1024;
        $data['memo']=$userMeta['memo'];
        $data['website']=$userMeta['website'];
        $data['qq']=$userMeta['qq'];
        $data['realname']=$userMeta['realname'];
        return $data;
    }
    /**
     * 用户信息
     */
    public function userInfo($id){
        $user = MiniUser::getInstance()->getUser($id);
        $userMeta=MiniUserMeta::getInstance()->getUserMetas($id);
        $data = array();
        $data['id']=$user['id'];
        $data['user_name']=$user['user_name'];
        $data['nick']=$user['nick'];
        $data['phone']=$user['phone'];
        $data['email']=$user['email'];
        $data['is_admin']=$user['is_admin'];
        $data['usedSpace']=$user['usedSpace'];
        $data['user_status']=$user['user_status'];
        $data['avatar']=$user['avatar'];
        $data['space']=$userMeta['space']/1024/1024;
        $data['memo']=($userMeta['memo']===null)?'':$userMeta['memo'];
        $data['website']=($userMeta['website']===null)?'':$userMeta['website'];
        $data['qq']=($userMeta['qq']===null)?'':$userMeta['qq'];
        $data['realname']=($userMeta['realname']===null)?'':$userMeta['realname'];
        return $data;
    }
    /**
     * 分页获取用户信息
     */
    public function getUserList($currentPage,$pageSize){
        $list = MiniUser::getInstance()->ajaxGetUsers($currentPage,$pageSize);
        $data = array();
        $data['all']=array();
        $data['total'] = $list['total'];
        foreach($list['list'] as $item){
            $arr = array();
            $arr['user_id']=$item['id'];
            $arr['user_name']=$item['user_name'];
            $arr['nick']=$item['nick'];
            $arr['phone']=$item['phone'];
            $arr['email']=$item['email'];
            $arr['user_status']=$item['user_status'];
            $arr['is_admin']=$item['is_admin'];
            $arr['usedSpace']=MiniFile::getInstance()->getUsedSize($item['id']);
            $arr['space']=$item['space']/1024/1024;
            $arr['avatar']=$item['avatar'];
            array_push($data['all'],$arr);
        }
        return $data;
    }
    /**
     * 分页获取admin用户信息
     */
    public function getAdminList($currentPage,$pageSize){
        $list = MiniUser::getInstance()->ajaxGetAdmins($currentPage,$pageSize);
        $data = array();
        $data['admin']=array();
        $data['total'] = $list['total'];
        foreach($list['list'] as $item){
            $arr = array();
            $arr['user_id']=$item['id'];
            $arr['user_name']=$item['user_name'];
            $arr['nick']=$item['nick'];
            $arr['phone']=$item['phone'];
            $arr['email']=$item['email'];
            $arr['user_status']=$item['user_status'];
            $arr['is_admin']=$item['is_admin'];
            $arr['usedSpace']=MiniFile::getInstance()->getUsedSize($item['id']);
            $arr['space']=$item['space']/1024/1024;
            $arr['avatar']=$item['avatar'];
            array_push($data['admin'],$arr);
        }
        return $data;
    }
    /**
     * 分页获取冻结用户信息
     */
    public function getDisabledList($currentPage,$pageSize){
        $list = MiniUser::getInstance()->ajaxGetDisabled($currentPage,$pageSize);
        $data = array();
        $data['disabled']=array();
        $data['total'] = $list['total'];
        foreach($list['list'] as $item){
            $arr = array();
            $arr['user_id']=$item['id'];
            $arr['user_name']=$item['user_name'];
            $arr['nick']=$item['nick'];
            $arr['phone']=$item['phone'];
            $arr['email']=$item['email'];
            $arr['user_status']=$item['user_status'];
            $arr['is_admin']=$item['is_admin'];
            $arr['usedSpace']=MiniFile::getInstance()->getUsedSize($item['id']);
            $arr['space']=$item['space']/1024/1024;
            $arr['avatar']=$item['avatar'];
            array_push($data['disabled'],$arr);
        }
        return $data;
    }
    public function searchUsers($name,$currentPage,$pageSize){
        $list = MiniUser::getInstance()->searchUsers($name,$currentPage,$pageSize);
        $data = array();
        $data['list']=array();
        $data['total'] = $list['total'];
        foreach($list['list'] as $item){
            $arr = array();
            $arr['user_id']=$item['id'];
            $arr['user_name']=$item['user_name'];
            $arr['nick']=$item['nick'];
            $arr['phone']=$item['phone'];
            $arr['email']=$item['email'];
            $arr['user_status']=$item['user_status'];
            $arr['is_admin']=$item['is_admin'];
            $arr['usedSpace']=MiniFile::getInstance()->getUsedSize($item['id']);
            $arr['space']=$item['space']/1024/1024;
            $arr['avatar']=$item['avatar'];
            array_push($data['list'],$arr);
        }
        return $data;
    }

    /**
     * @param $userData
     * @return mixed
     * 创建用户
     */
    public function create($userData){
        $data = MiniUser::getInstance()->adminCreateUser($userData);
        return $data;
    }
    /**
     * 冻结用户
     */
    public function disable($id,$isAdmin){
        if($isAdmin==="0"||$isAdmin===false){//"0"为了适配“信息修改页面”“false”为了适配all-user。
            MiniUser::getInstance()->disableUser($id);
            return true;
        }
        else{
            return false;
        }
    }
    /**
     * 解冻用户
     */
    public function enable($id){
            MiniUser::getInstance()->enableUser($id);
            return true;
    }
    /**
     * 设置普通用户
     */
    public function normalizeUser($id){
        MiniUser::getInstance()->normalizeUser($id);
        return true;
    }
    /**
     * 设置管理员
     */
    public function setAdmin($id){
        MiniUser::getInstance()->setAdministrator($id);
        return true;
    }
    /**
     * 删除用户
     */
    public function delete($id,$isAdmin){
        if(!$isAdmin){
            $user = new User();
            $user->deleteUsers($id);
            MiniUserPrivilege::getInstance()->deletePrivilegeWhenKillUser($id);
            return true;
        }
        else{
            return false;
        }
    }
    /**
     * 更新基本信息
     */
    public function updateBaseInfo($id,$email,$nick,$status,$isAdmin){
        $metas   = MiniUserMeta::getInstance()->getUserMetas($id);
        $user = MiniUser::getInstance()->getUser($id);
        if($status ==="0"){
            $this->disable($id,$isAdmin);
        }
        if($status ==="1"){
            $this->enable($id);
        }
        foreach ($metas as $key=>$value){
            if($key==="space"){
                $metas["space"] = $metas["space"]/1024/1024;
            }
        }
        $metas['email']=$email;
        $metas['nick']=$nick;
        $metas['email']=$email;
        $metas['is_admin']=$isAdmin;
        $userMetas=array();
        $userMetas['extend']=$metas;
        MiniUserMeta::getInstance()->create($user,$userMetas);
        return true;
    }
    /**
     *
     */
    public function updateUserSpace($id,$space){
        $metas   = MiniUserMeta::getInstance()->getUserMetas($id);
        $user = MiniUser::getInstance()->getUser($id);
        foreach ($metas as $key=>$value){
            if($key==="space"){
                $metas["space"] = $metas["space"]/1024/1024;
            }
        }
        $metas['space']=$space;
        $userMetas=array();
        $userMetas['extend']=$metas;
        MiniUserMeta::getInstance()->create($user,$userMetas);
        return true;
    }
    /**
     * 更新用户密码
     */
    public function updatePassword($id,$oldPassword,$password){
        $data =MiniUser::getInstance()->updatePassword2($id,$oldPassword,$password);
        return $data;
    }

    /**
     * @param $id
     * @param $phone
     * @param $real_name
     * @param $qq
     * @param $website
     * @param $space
     * @param $memo
     */
    public function updateMoreInformation($id,$phone,$real_name,$qq,$website,$memo){
        $metas   = MiniUserMeta::getInstance()->getUserMetas($id);
        $user = MiniUser::getInstance()->getUser($id);
        foreach ($metas as $key=>$value){
            if($key==="space"){
                $metas["space"] = $metas["space"]/1024/1024;
            }
        }
        $metas['phone']=$phone;
        $metas['realname']=$real_name;
        $metas['qq']=$qq;
        $metas['website']=$website;
        $metas['memo']=$memo;
        $userMetas=array();
        $userMetas['extend']=$metas;
        MiniUserMeta::getInstance()->create($user,$userMetas);
        return true;
    }
    /**
     * 统计图标
     */
    public function getGraphUsers(){
        $list = MiniUser::getInstance()->getAllUsers();
        return $list;
    }
    /**
     * 统计图标
     */
    public function getGraphBeforeDateUsers($wholeDate){
        $count = MiniUser::getInstance()->getBeforeDateUsers($wholeDate);
        return $count;
    }
    /**
     * 获取用户数统计
     */
    public function getUserCount(){
        $data = array();
        $userModel = new User();
        $data['online_user_count'] = MiniOnlineDevice::getInstance()->getOnlineCount();
        $data['user_count'] = $userModel->count();
        $data['disabled_count'] = $userModel->disabledCount();
        $data['admin_count'] = $userModel->adminCount();
        return $data;
    }
}