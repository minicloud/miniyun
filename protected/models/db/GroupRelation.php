<?php
class GroupRelation extends CMiniyunModel
{

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'group_relations';
    }

}