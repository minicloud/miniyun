<?php
/**
 * 个人信息服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ProfileService extends MiniService{
    /**
     * 个人空间用户信息
     * @return array
     */
    public function account(){
        $model = new ProfileBiz();
        $data  = $model->getProfile();
        return $data;
    }
    /**
     * 解锁，判断用户密码是否正确
     * @return array
     */
    public function unlock(){
        $model = new ProfileBiz();
        $password = MiniHttp::getParam("password","");
        $data  = $model->unlock($password);
        return $data;
    }
    /**
     * 修改昵称
     * @return bool
     */
    public function nick(){
        $nick = MiniHttp::getParam("nick","-1");
        $model = new ProfileBiz();
        $success = true;
        if($nick != "-1"){
            $model->editNick($nick);
        }
        return array('success'=>$success );
    }
    /**
     * 设备数据
     * @return array
     */
    public function devices(){
        $deviceInfo = MiniHttp::getParam('device_info','');
        $currentPage = MiniHttp::getParam("current_page","1");
        $pageSize = MiniHttp::getParam("page_size","15");
        $biz = new ProfileBiz();
        $data = $biz->getDevices($currentPage,$pageSize,$deviceInfo);
        return $data;
    }
    /**
     * 设备数据
     * @return array
     */
    public function log(){
        $currentPage = MiniHttp::getParam("current_page","1");
        $pageSize = MiniHttp::getParam("page_size","15");
        $model = new ProfileBiz();
        $data = $model->getLogs($currentPage,$pageSize);
        return $data;
    }
    /**
     * 删除设备
     */
    public function deleteDevice(){
        $uuid = MiniHttp::getParam("device_uuid","-1");
        $biz = new ProfileBiz();
        $biz->deleteDevice($uuid);
        return array('success'=>true);
    }
    /**
     * 保存头像
     * @return string
     */
    public function saveAvatar(){
        $url = rawurldecode(MiniHttp::getParam("thumbnail_link",""));
        $model = new ProfileBiz();
        $avatar = $model->saveAvatar($url);
        return array('url'=>$avatar);
    }
    /**
     * 保存微信头像
     * @return string
     */
    public function saveWxAvatar(){
        $avatar = MiniHttp::getParam("avatar","");
        $nick = MiniHttp::getParam("nick","");
        $model = new ProfileBiz();
        $avatar = $model->saveWxAvatar($avatar,$nick);
        return array('url'=>$avatar);
    }
    /**
     * delete头像
     * @return string
     */
    public function deleteAvatar(){
        $avatar = MiniHttp::getParam("avatar","");
        $model = new ProfileBiz();
        $result = $model->deleteAvatar($avatar);
        return $result;
    }
    /**
     * 修改邮箱
     */
    public function updateEmail(){
        $newEmail = MiniHttp::getParam('email','');
        $model = new ProfileBiz();
        $result = $model->updateEmail($newEmail);
        return array('success'=>$result);
    }/**
     * 修改密码
     */
    public function updatePassword(){
        $password = MiniHttp::getParam('password','');
        $newPassword = MiniHttp::getParam('new_password','');
        $model = new ProfileBiz();
        $result = $model->updatePassword($newPassword,$password);
        return array('success'=>$result);
    }
    /**
     * 清空所有记录
     */

    public function cleanLog(){
        $model = new ProfileBiz();
        $result = $model->cleanLog();
        return array('success'=>$result);
    }

    /**
     * 转换隐藏文件名单状态
     */
    public function  updateFileHideStatus(){
        $filePath = MiniHttp::getParam('path','');
        $isHide   = MiniHttp::getParam('is_hide',true);
        $model = new ProfileBiz();
        return $model->updateFileHideStatus($filePath,$isHide);
    }
}