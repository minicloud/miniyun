<?php
/**
 * Miniyun 解除设备绑定的入口地址
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MUnmountController
extends MApplicationComponent
implements MIController
{
    /**
     * 控制器执行主逻辑函数
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null)
    {
        $user        = MUserManager::getInstance()->getCurrentUser();
        $device      = MUserManager::getInstance()->getCurrentDevice();
        $user_id     = $user["user_id"];
        $user_name   = $user["user_name"];
        $device_uuid = $device["user_device_uuid"];
        $device_name = $device["user_device_name"];
        
        $db_user_device = MiniUserDevice::getInstance()->getByUuid($device_uuid);
        if ($db_user_device === NULL) {
            throw new MFileopsException(
                                        Yii::t('api','the device does not match'),
                                        MConst::HTTP_CODE_403);
        }
        $id  = $db_user_device["id"];
        $ret = MiniUserDevice::getInstance()->deleteDevice($db_user_device["id"]);
        if ($ret === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','remove the device failure'),
                                        MConst::HTTP_CODE_403);
        }
        $response                   = array();
        $response["stataus"]        = "ok";
        $response["did"]            = $device_uuid;
        $response["display_name"]   = $device_name;
        $response["uid"]            = $user_id;
        $response["user_name"]      = $user_name;
        echo json_encode($response);
    }
}