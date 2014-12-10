<?php
/** 
 * 在线用户
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class OnlineListBiz extends MiniBiz{
    private $items = array();
    public function getOnlineUsers($refresh = false){
        $data = array();
        if (empty ( $this->items ) || $refresh) {
            $devices = MiniOnlineDevice::getInstance()->getOnlineDevices();
            foreach ($devices as $item ) {
                $appId                 = $item["application_id"];
                $device                = MiniUserDevice::getInstance()->getUserDevice($item["device_id"]);
                $user                  = MiniUser::getInstance()->getUser($device["user_id"]);
                $online                = array(
                    "name"=>$user ["user_name"],
                    "appname"=>$appId,
                    "deviceName"=>$device["user_device_name"],
                    "deviceType"=>$device["user_device_type"],
                    "lastLoginTime"=>$item["updated_at"],
                    "avatar"=>$user["avatar"]
                );
                array_push ( $this->items, $online );
            }
        }
        $data['list']  = $this->items;
        $data['total'] = MiniOnlineDevice::getInstance()->getOnlineCount();
        return $data;
    }
}