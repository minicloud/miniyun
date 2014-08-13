<?php
/**
 * 设备管理服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class DeviceManageService extends MiniService{
    /**
     * 获取在线用户列表
     */
    public function getList(){
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $model = new DeviceManageBiz();
        $data  = $model->getDevices($pageSize,$currentPage);
        return $data;
    }
    /**
     * 删除指定设备
     */
    public function deleteDevice(){
        $deviceId = MiniHttp::getParam('device_id','');
        $model    = new DeviceManageBiz();
        $data     = $model->deleteDeviceById($deviceId);
        return $data;
    }
    /**
     * 根据设备类型查找设备
     */
    public function searchByDeviceType(){
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $deviceType  = MiniHttp::getParam('device_type','');
        $model       = new DeviceManageBiz();
        $data        = $model->getDeviceByType($deviceType,$pageSize,$currentPage);
        return $data;
    }
    /**
     * 显示某个用户的设备信息
     */
    public function getUserDevices(){
        $userId    = MiniHttp::getParam('user_id','');
        $model     = new DeviceManageBiz();
        $data      = $model->getUserDevices($userId);
        return $data;
    }
    /**
     * 根据用户名或设备名查找设备信息
     */
    public function searchDevicesByName(){
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $key         = MiniHttp::getParam('key','');
        $model       = new DeviceManageBiz();
        $data        = $model->searchDevicesByName($key,$pageSize,$currentPage);
        return $data;
    }
    /**
     * 获取各类设备统计数
     */
    public function getDevicesCount(){
        $model       = new DeviceManageBiz();
        $data        = $model->getDevicesCount();
        return $data;
    }

}