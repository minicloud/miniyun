<?php
/**
 * 记录用户所有的操作和登陆日志
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Logs extends CMiniyunModel
{
    public $maxUpdatedAt;
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    public function tableName(){
        return Yii::app()->params['tablePrefix'].'logs';
    }

}