<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-9-12
 * Time: 上午10:11
 */
class UserGroupRelation extends CMiniyunModel{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'user_group_relation';
    }
}