<?php
/**
 * 个人信息业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ProfileBiz  extends MiniBiz{
    Const TYPE = "0";
    /**
     * 获取用户及空间信息
     * @return array
     */
    public function getProfile(){
       $user['user_name'] = $this->user['user_name'];
       $user['avatar']    = $this->user['avatar'];
       $user['avatar']    = $this->user['avatar'];
       $user['nick']      = $this->user['nick'];
       $user['email']     = $this->user['email'];
       $user['space']     = $this->user['space'];
       $user['usedSpace'] = $this->user['usedSpace'];
       return $user;
    }
    /**
     * 修改昵称
     */
    public function editNick($nick){
        $user = $this->user;
        $userId = $user['user_id'];
        MiniUserMeta::getInstance()->updateMeta($userId,"nick",$nick);
    }
    /**
     * 设备相关数据
     *
     */
    public function getDevices($currentPage,$pageSize){
        $userId = $this->user['user_id'];
        $devices = MiniUserDevice::getInstance()->getDevices($userId,($currentPage-1)*$pageSize,$pageSize);
        $deviceList = array();
        foreach($devices as $device){
            $item = array();
            $item["user_device_uuid"] = $device["user_device_uuid"];
            $item["user_device_type"] = $device["user_device_type"];
            $item["user_device_name"] = $device["user_device_name"];
            $item['updated_at'] = MiniUtil::formatTime(strtotime($device['updated_at']));
            $deviceList[] = $item;
        }
        $data['devices'] = $deviceList;
        $data['deviceCount'] = ceil((MiniUserDevice::getInstance()->count($userId))/$pageSize);
        return $data;
    }
    /**
     * 登陆信息
     *
     */

    public function getLogs($currentPage,$pageSize){
        $data = array();
        $logs = array();
        $item = array();
        $userId = $this->user['user_id'];
        $list = MiniLog::getInstance()->getByType($userId, ProfileBiz::TYPE,$pageSize,($currentPage-1)*$pageSize);
        $count = MiniLog::getInstance()->getCountByType($userId,ProfileBiz::TYPE);
        foreach($list as $log){
            $context = unserialize($log['context']);
            $item['action'] = $context['action'];
            $device = MiniUserDevice::getInstance()->getById($context['device_id']);
            $item['device_name'] = $device['user_device_name'];
            $item['device_type'] = $context['device_type'];
            $item['created_at'] = MiniUtil::formatTime(strtotime($log['created_at']));
            $item['message'] = $log['message'];
            $logs[] = $item;
        }
        $data['logs'] = $logs;
        $data['logCount'] = ceil($count/$pageSize);
        return $data;
    }
    /**
     * 保存头像
     * @param $url
     * @return string
     */
    public function saveAvatar($url){
        //a.php/1/linkAccess/thumbnail?key=38ezrz&size=256x256&path=/align-right.png
        //save image to avatar folder,file name is user_uuid.png
        $user = MiniUser::getInstance()->getUser($this->user["id"]);
        $avatarName = MiniUtil::getRandomName(8).".png";
        $savePath = THUMBNAIL_TEMP . "/avatar/";
        $path = $savePath.'/'.$avatarName;
        if(!file_exists($savePath)){
            mkdir($savePath);
        }
        file_put_contents($path, file_get_contents($url));
        //save to db
        MiniUserMeta::getInstance()->updateMeta($user["id"],"avatar",$avatarName);
        return MiniHttp::getMiniHost()."static/thumbnails/avatar/".$avatarName;
    }
    /**
     * delete头像
     * @param
     * @return string
     */
    public  function deleteAvatar($avatar){
        $userId = $this->user['id'];
        $file = MiniHttp::getMiniHost()."static/thumbnails/avatar/".$avatar;
        MUtils::RemoveFile($file);
        $result = MiniUserMeta::getInstance()->deleteAvatar($userId,$avatar);
        return array(success => $result);
    }
    /**
     *  修改邮箱
     */
    public function updateEmail($email){
        if($email != ""){
            $userId = $this->user['id'];
            MiniUserMeta::getInstance()->updateMeta($userId,"email",$email);
            return true;
        }
        return false;
    }
    /**
     *  修改密码
     */
    public function updatePassword($newPassword,$password){
        $userId = $this->user['id'];
        $userName = $this->user['user_name'];
        $model = new CUserValid();
        $success = $model->validUser($userName,$password);
        if($success != false){
            MiniUser::getInstance()->updatePassword($userId,$newPassword);
            $success = true;
        }
        return $success;
    }
    /**
     * 清空所有登陆记录
     */
    public function cleanLog(){
        $userId = $this->user['id'];
        MiniLog::getInstance()->feignDeleteLogs($userId,ProfileBiz::TYPE);
        return true;
    }

    /**
     * 根据UUID删除设备
     * @param $deviceUUid
     * @return bool
     */
    public function deleteDevice($deviceUUid){
        $device = $this->device;
        if($device["user_device_uuid"]==$deviceUUid){
            return false;
        }
        MiniUserDevice::getInstance()->deleteDeviceByUuid($deviceUUid);
        return true;
    }
}