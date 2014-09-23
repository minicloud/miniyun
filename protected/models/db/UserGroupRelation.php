<?php
class UserGroupRelation extends CMiniyunModel
{

	/**
	 * Returns the static model of the specified AR class.
	 * return User the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return Yii::app()->params['tablePrefix'].'user_group_relations';
	}
	 
}