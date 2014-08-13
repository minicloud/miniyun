<?php
/**
 * 用戶設備元數據Meta
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserDeviceMeta extends CMiniyunModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'user_devices_metas';
    }

    /**
     *
     * 删除用户设备元数据
     */
    public function  deleteUserDeviceMeta($userIds){
        if($userIds!='' && strlen($userIds)>0){
            $this->deleteAll("user_id in(".$userIds.")");
        }
    }

}