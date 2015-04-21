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
        $data = array();
        $data['id'] = $this->user['id'];
        $data['display_name'] = $this->user['nick'];
        $data['user_name']    = $this->user['user_name'];
        $data['avatar']       = $this->user['avatar'];
        $data['nick']         = $this->user['nick'];
        $data['email']        = $this->user['email'];
        $data['space']        = $this->user['space'];
        $data['usedSpace']    = $this->user['usedSpace'];
        $data['used_space']   = $this->user['usedSpace'];
        $data['email']        = $this->user['email'];
        $data['phone']        = $this->user['phone'];
        //这里的信息比较冗余，把站点的逻辑融入到用户信息获得的地方，是为了不让PC客户端发送多次请求
        //打印用户是否是管理员
        $info = array("success"=>$this->user["is_admin"]);
        $data['is_admin'] = $info;
        //输出服务器时间
        $info = array("time"=>time());
        $data['time'] = $info;
        //获得站点信息
        $app = new SiteService();
        $data['app_info'] = $app->info();
        //是否系统只有默认账号
        $data['only_default_account'] = $app->onlyDefaultAccount();
        //获得授权码信息
        $code = MiniOption::getInstance()->getOptionValue("code");
        if(empty($code)){
            $code = "";
        }
        $data['code']          = $code;
        return $data;
    }
    /**
     * 解锁，判断当前用户的密码是否正确
     * @password 用户密码
     */
    public function unlock($password){
        $user         = $this->user;
        $uerObject    = new CUserValid();
        $userObj      = $uerObject->validUser($user["user_name"], $password);
        if(!isset($userObj) || !$userObj){
            return array("success"=>false);
        }
        return array("success"=>true);

    }
    /**
     * 修改昵称
     */
    public function editNick($nick){
        $user = $this->user;
        $userId = $user['user_id'];
        MiniUserMeta::getInstance()->updateMeta($userId,"nick",$nick);
        //更新用户的拼音信息
        MiniUser::getInstance()->updateUserNamePinYin($userId);
    }
    /**
     * 设备相关数据
     *
     */
    public function getDevices($currentPage,$pageSize,$deviceInfo){
        $userId = $this->user['user_id'];
        $devices = MiniUserDevice::getInstance()->getDevices($userId,($currentPage-1)*$pageSize,$pageSize,$deviceInfo);
        $deviceList = array();
        foreach($devices as $device){
            $item = array();
            $item["user_device_uuid"] = $device["user_device_uuid"];
            $item["user_device_type"] = $device["user_device_type"];
            $item["user_device_name"] = $device["user_device_name"];
            $item['updated_at'] = MiniUtil::formatTime(strtotime($device['updated_at']));
            $item['update_time'] = strtotime($device['updated_at']);
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
     * 保存微信头像与昵称
     */
    public function saveWxAvatar($avatar,$nick){
        //如果用户已设置了头像，则不替换为微信的头像
        $needReplace = true;
        $meta = MiniUserMeta::getInstance()->getUserMetas($this->user["id"]);
        if(array_key_exists("avatar",$meta)){
            $value = $meta["avatar"];
            if(!(strpos($value,"http")==0)){
                $needReplace = false;
            }
        }
        if($needReplace){
            MiniUserMeta::getInstance()->updateMeta($this->user["id"],"avatar",$avatar);
        }
        MiniUserMeta::getInstance()->updateMeta($this->user["id"],"nick",$nick);
        return $avatar;
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
        $savePath = THUMBNAIL_TEMP . "avatar";
        $path = $savePath.'/'.$avatarName;
        if(!file_exists($savePath)){
            mkdir($savePath);
        }
        file_put_contents($path, file_get_contents($url));
        //save to db
        MiniUserMeta::getInstance()->updateMeta($user["id"],"avatar",$avatarName);
        return MiniHttp::getMiniHost()."assets/thumbnails/avatar/".$avatarName;
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
    /**
     * 转换隐藏文件名单状态
     */
    public function updateFileHideStatus($filePath,$isHide){
        $data    = array();
        $meta    = array();
        $extends = array();
        $userId = $this->user['id'];
        $userMetaData = MiniUserMeta::getInstance()->getUserMetas($userId);
        if($isHide == "true"){
            if(empty($userMetaData['user_hide_path'])){
                array_push($data,$filePath);
            }else{
                $data = unserialize($userMetaData['user_hide_path']);
                array_push($data,$filePath);
            }
        }else{
            $data = unserialize($userMetaData['user_hide_path']);
            $key = array_search($filePath, $data);
            unset($data[$key]);
        }
        $extends['user_hide_path'] = serialize($data);
        $meta['extend'] = $extends;
        MiniUserMeta::getInstance()->create($this->user,$meta);
        return true;
    }
}