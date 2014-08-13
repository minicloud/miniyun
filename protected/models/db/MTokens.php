<?php
/**
 * Token的Model
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MTokens extends CMiniyunModel
{

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'tokens';
    }
    /**
     * 刪除用户的文件token信息
     * @param $deviceIds 设备id列表
     */
    public function deleteServerToken($deviceIds){
        if($deviceIds!='' && strlen($deviceIds)>0){
            $this->deleteAll("device_id in (".$deviceIds.")");
        }
    }

}