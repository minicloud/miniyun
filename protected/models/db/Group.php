<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-9-10
 * Time: 下午3:59
 */
class Group extends CMiniyunModel{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'groups';
    }
}