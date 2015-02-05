<?php
/**
 * 用户信息
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserInfoService extends MiniService{
    /**
     * get current user info
     */
    public function  getUser(){
        $id = MiniHttp::getParam('id',"");
        if($id === ""){
            $model = new UserInfoBiz();
            $data  = $model->currentUserInfo();
        }else{
            $model = new UserInfoBiz();
            $data  = $model->userInfo($id);
        }
        return $data;
    }
    /**
     * 获取所有用户信息
     */
    public function getList(){
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new UserInfoBiz();
        $data  = $model->getUserList($currentPage,$pageSize);
        return $data;
    }
    /**
     * 获取管理员列表
     */
    public function getAdminList(){
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new UserInfoBiz();
        $data  = $model->getAdminList($currentPage,$pageSize);
        return $data;
    }
    /**
     * 获取冻结用户列表
     */
    public function getDisabledList(){
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new UserInfoBiz();
        $data  = $model->getDisabledList($currentPage,$pageSize);
        return $data;
    }
    /**
     * 搜索用户
     */
    public function searchUsers(){
        $name = MiniHttp::getParam('name','');
        $currentPage = MiniHttp::getParam('current_page',1);
        $pageSize    = MiniHttp::getParam('page_size',10);
        $model = new UserInfoBiz();
        $data  = $model->searchUsers($name,$currentPage,$pageSize);
        return $data;
    }
    /**
     * 创建用户
     */
    public function createUser(){
        $isExtend = apply_filters("license_user_extend");
        if($isExtend===1){
            throw new MiniException(1003);
        }
        $userName = MiniHttp::getParam('user_name',"");
        $password    = MiniHttp::getParam('password',"");
        $nick    = MiniHttp::getParam('nick',"");
        $email    = MiniHttp::getParam('email',"");
        $space    = MiniHttp::getParam('space',0);
        $isAdmin    = MiniHttp::getParam('is_admin',0);
        $userData = array();
        $userData['user_name'] = $userName;
        $userData['password'] = $password;
        $userData['nick'] = $nick;
        $userData['email'] = $email;
        $userData['space'] = $space;
        $userData['is_admin'] = $isAdmin;
        $model = new UserInfoBiz();
        $data  = $model->create($userData);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 冻结用户
     */
    public function disableUser(){
        $id = MiniHttp::getParam('id',"");
        $isAdmin    = MiniHttp::getParam('is_admin',"");
        if($isAdmin == 'false'){
            $isAdmin = false;
        }else{
            $isAdmin = true;
        }
        $model = new UserInfoBiz();
        $data  = $model->disable($id,$isAdmin);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 解冻用户
     */
    public function enableUser(){
        $id = MiniHttp::getParam('id',"");
        $model = new UserInfoBiz();
        $data  = $model->enable($id);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 设置普通用户
     */
    public function normalizeUser(){
        $id = MiniHttp::getParam('id',"");
        $model = new UserInfoBiz();
        $data  = $model->normalizeUser($id);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 设置管理员
     */
    public function setAdmin(){
        $id = MiniHttp::getParam('id',"");
        $model = new UserInfoBiz();
        $data  = $model->setAdmin($id);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 删除用户
     */
    public function deleteUser(){
        $id = MiniHttp::getParam('id',"");
        $isAdmin    = MiniHttp::getParam('is_admin',"");
        $model = new UserInfoBiz();
        $data  = $model->delete($id,$isAdmin);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 更新用户基本信息
     */
    public function updateBaseInfo(){
        $id = MiniHttp::getParam('id',"");
        $email    = MiniHttp::getParam('email',"");
        $nick    = MiniHttp::getParam('nick',"");
        $status    = MiniHttp::getParam('status',"");
        $isAdmin    = MiniHttp::getParam('is_admin',"");
        $model = new UserInfoBiz();
        $data  = $model->updateBaseInfo($id,$email,$nick,$status,$isAdmin);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 更新用户可用空间
     */
    public function updateUserSpace(){
        $id = MiniHttp::getParam('id',"");
        $space = MiniHttp::getParam('space',"");
        $model = new UserInfoBiz();
        $data  = $model->updateUserSpace($id,$space);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 更新密码
     */
    public function updatePassword(){
        $id = MiniHttp::getParam('id',"");
        $password = MiniHttp::getParam('password',"");
        $model = new UserInfoBiz();
        $data  = $model->updatePassword($id,$password);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 更新更多信息
     */
    public function updateMoreInfo(){
        $id = MiniHttp::getParam('id',"");
        $phone = MiniHttp::getParam('phone',"");
        $real_name = MiniHttp::getParam('real_name',"");
        $qq = MiniHttp::getParam('qq',"");
        $website = MiniHttp::getParam('website',"");
        $memo = MiniHttp::getParam('memo',"");
        $model = new UserInfoBiz();
        $data  = $model->updateMoreInformation($id,$phone,$real_name,$qq,$website,$memo);
        $status = array();
        $status['success']=$data;
        return $status;
    }
    /**
     * 获得统计图表所需用户数
     */
    public function getGraphUsers(){
        $model = new UserInfoBiz();
        $data  = $model->getGraphUsers();
        return $data;
    }
    /**
     * 获得传入时间之前创建的用户数
     */
    public function getGraphBeforeDateUsers(){
        $wholeDate = MiniHttp::getParam('wholeDate',"");
        $model = new UserInfoBiz();
        $count  = $model->getGraphBeforeDateUsers($wholeDate);
        $data = array();
        $data['count'] = $count;
        return $data;
    }
    /**
     * 获取用户统计数据
     */
    public function getUserCount(){
        $model = new UserInfoBiz();
        $data  = $model->getUserCount();
        return $data;
    }
    /**
     * 批量导入用户
     */
    public function importExcel(){
        $userData = MiniHttp::getParam('userData',"");
        $errorList = array();
        $successList = array();
        foreach($userData as $user){//简单验证数据是否符合标准
            if(count($user)===4){
                //自动把电子邮件给补充上去
                $user[]="";
            }
            if(count($user)<5){
                $user[5]="为空的数据请以“”填充";
                $errorList[] = $user;
            }else{
                if($user[0]===""||$user[1]===""){//帐号和密码不得为空
                    $user[5]="帐号和密码不得为空";
                    $errorList[] = $user;
                }else{
                    $successList[] = $user;
                }
            }
        }
        $userList = array();
        $userList['error'] = $errorList;
        $userList['success'] = $successList;
        $userList['total'] = count($userData);
        $count = 0;
        foreach($successList as $item){
            $user['name']=trim($item[0]);
            $user['password']=trim($item[1]);
            $user['extend']['nick']= trim($item[2]);
            $user['extend']['space']=(trim($item[3])*1024=="")?10240:trim($item[3])*1024;//默认10G
            $user['extend']['email']=trim($item[4]);
            if(MiniUser::getInstance()->getUserByName($user['name'])){
                $count++;
            }
            $isExtend = apply_filters("license_user_extend");
            if($isExtend===1){
                throw new MiniException(1003);
            }
            MiniUser::getInstance()->create($user);
        }
        $tempUrl ="upload/temp/error.csv";
        $fp = fopen($tempUrl, 'w+');
        if($fp){
            foreach($errorList as $item){
                fputcsv($fp,$item);
            }
        }
        fclose($fp);
        $userList['duplicateCount']=$count;
        return $userList;
    }
}