<?php
/**
 * 设备检查
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class DeviceManager{
	/**
	 *
	 * 在用户验证失败后是否需要进行自身用户系统的验证
	 */
	public static function getDevice($userId, $deviceType, $deviceName, $deviceInfo){
		//生成设备的uuid
		if ($deviceType == MConst::DEVICE_WEB){
			$device_uuid = MiniUtil::getDeviceUUID("web", $deviceType, "web", $userId);
		} else {
			$device_uuid = MiniUtil::getDeviceUUID($deviceInfo, $deviceType, $deviceName, $userId);
		}

		//存在用户指定设备则通过
		$device     = NULL;
		if ($deviceType == MConst::DEVICE_WEB){
			$device = MiniUserDevice::getInstance()->getWebDevice($userId);
		} else {
			$device = MiniUserDevice::getInstance()->getByUuid($device_uuid);
		}
		if (isset($device)){
			return $device;
		}
		//生成设备
        $device = MiniUserDevice::getInstance()->create($userId,
		                                                      $device_uuid,
		                                                      $deviceType,
		                                                      $deviceInfo,
		                                                      $deviceName
		                                                      ); 
		return $device;
	}
}