<?php
/** 
 * 设备管理
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class DeviceManageBiz extends MiniBiz{
    private $list = array();
    /**
     * 获取设备列表
     */
    public function getDevices($pageSize,$currentPage){
        $devices      = MiniUserDevice::getInstance()->getAllDevices($pageSize,$currentPage);
        $data                        = array();
        foreach($devices as $device){
            $dev                         = array();
            $user                        = MiniUser::getInstance()->getUser($device['user_id']);
            $dev['is_admin']             = $user['is_admin'];
            $dev['device_id']            = $device['id'];
            $dev['user_id']              = $device['user_id'];
            $dev['userName']             = $user['nick'];
            $dev['avatar']               = $user['avatar'];
            $dev['userDeviceType']       = $device['user_device_type'];
            $dev['userDeviceName']       = $device['user_device_name'];
            $dev['lastLoginTime']        = $device['updated_at'];
            array_push ( $data, $dev);
        }
        $this->list['list']  = $data;
        $this->list['total'] = UserDevice::model()->count();
        return $this->list;
    }
    /**
     * 删除指定设备
     */
    public function deleteDeviceById($id){
        $operate = MiniUserDevice::getInstance()->deleteDevice($id);
        if($operate){
            return array('success' => true);
        }
        return array('success' => false);
    }
    /**
     *根据设备类型查找设备
     */
    public function getDeviceByType($deviceType,$pageSize,$currentPage){
        $devices = MiniUserDevice::getInstance()->getDeviceByType($deviceType,$pageSize,$currentPage);
        $data                        = array();
        $list                        = array();
        foreach($devices['list'] as $device){
            $dev                         = array();
            $user                        = MiniUser::getInstance()->getUser($device['user_id']);
            $dev['is_admin']             = $user['is_admin'];
            $dev['device_id']            = $device['id'];
            $dev['user_id']              = $device['user_id'];
            $dev['userName']             = $user['nick'];
            $dev['avatar']               = $user['avatar'];
            $dev['userDeviceType']       = $device['user_device_type'];
            $dev['userDeviceName']       = $device['user_device_name'];
            $dev['lastLoginTime']        = $device['updated_at'];
            array_push ( $data, $dev);
        }
        $list['list']  = $data;
        $list['total'] = $devices['total'];
        return $list;
    }
    /**
     * 获取指定用户的设备信息
     */
    public function getUserDevices($userId){
        $devices = MiniUserDevice::getInstance()->getUserDevices($userId);
        $data                        = array();
        $list                        = array();
        foreach($devices['list'] as $device){
            $dev                         = array();
            $user                        = MiniUser::getInstance()->getUser($device['user_id']);
            $dev['is_admin']             = $user['is_admin'];
            $dev['device_id']            = $device['id'];
            $dev['user_id']              = $device['user_id'];
            $dev['userName']             = $user['nick'];
            $dev['avatar']               = $user['avatar'];
            $dev['userDeviceType']       = $device['user_device_type'];
            $dev['userDeviceName']       = $device['user_device_name'];
            $dev['lastLoginTime']        = $device['updated_at'];
            array_push ( $data, $dev);
        }
        $list['list'] = $data;
        $list['total'] = $devices['total'];
        return $list;
    }
    /**
     *根据用户名或设备名查找设备信息
     */
    public function searchDevicesByName($key,$pageSize,$currentPage){
            $companyId = $_SESSION['company_id'];
            $ids = '';
            $sql_str = "select mud.id from ".DB_PREFIX. "_user_devices mud";
            $sql_str .= " where mud.user_device_name like \"%$key%\" and mud.company_id={$companyId}";
            $sql = Yii::app()->db->createCommand( $sql_str );
            $byDeviceName = $sql->queryAll ();

            $sql_str = " select mud.id from ".DB_PREFIX. "_user_devices mud,".DB_PREFIX. "_users mu";
            $sql_str .= " where mud.user_id=mu.id and mu.user_name like \"%$key%\" and mud.company_id={$companyId}";
            $sql = Yii::app()->db->createCommand( $sql_str );
            $byUserName = $sql->queryAll();

            $sql_str = " select mud.id from ".DB_PREFIX. "_user_devices mud,".DB_PREFIX. "_user_metas mum";
            $sql_str .= " where mud.user_id=mum.user_id and mum.meta_key='nick' and mum.meta_value like \"%$key%\" and mud.company_id={$companyId}";
            $sql = Yii::app()->db->createCommand( $sql_str );
            $byNick = $sql->queryAll();

            $devices = array_merge($byDeviceName, $byUserName, $byNick);
            foreach ($devices as $device) {
                if(strpos($ids,$device['id']) === false){
                    $ids .= $device['id'];
                    $ids .= ',';
                }
            }
            $len = strlen($ids);
            if ($len <= 0) {
                return "0";
            }
            $ids    = substr($ids, 0, $len -1);
            $idsArr = explode(',',$ids);
            //分页获取数据
            if($pageSize < count($idsArr)){
                $idsArr = array_slice($idsArr,($currentPage-1)*$pageSize,$pageSize);
            }
            $data   = array();
            $list   = array();
            foreach($idsArr as $deviceId){
                $device = MiniUserDevice::getInstance()->getById($deviceId);
                $dev                         = array();
                $user                        = MiniUser::getInstance()->getUser($device['user_id']);
                $dev['is_admin']             = $user['is_admin'];
                $dev['device_id']            = $device['id'];
                $dev['user_id']              = $device['user_id'];
                $dev['userName']             = $user['nick'];
                $dev['avatar']               = $user['avatar'];
                $dev['userDeviceType']       = $device['user_device_type'];
                $dev['userDeviceName']       = $device['user_device_name'];
                $dev['lastLoginTime']        = $device['updated_at'];
                array_push ( $data, $dev);
            }
          $list['list']  = $data;
          $list['total'] = count($idsArr);
          return $list;
    }

    /**
     * 获取各类设备统计数
     */
    public function getDevicesCount(){
        $device = new UserDevice();
        $data['allCount']     = $device->count();//设备总数
        $data['webCount']     = $device->count("user_device_type=".MConst::DEVICE_WEB);//web端设备总数
        $data['pcCount']      = $device->count("user_device_type in (".MConst::DEVICE_WINDOWS.",".MConst::DEVICE_MAC.")");//pc客户端设备总数
        $data['androidCount'] = $device->count("user_device_type=".MConst::DEVICE_ANDROID);//android端设备总数
        $data['iphoneCount']  = $device->count("user_device_type=".MConst::DEVICE_IPHONE);//iPhone端设备总数
        return $data;
    }
}