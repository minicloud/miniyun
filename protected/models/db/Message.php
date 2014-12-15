<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Mac
 * Date: 14-9-22
 * Time: 下午6:01
 * To change this template use File | Settings | File Templates.
 */
class Message extends CMiniyunModel{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'message';
    }
}