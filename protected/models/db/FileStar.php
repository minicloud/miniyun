<?php
/**
 * 数据模型 --  文件加星表模型
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class FileStar extends CMiniyunModel
{
    /**
     * Returns the static model of the specified AR class.
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
        return Yii::app()->params['tablePrefix'].'file_star';
    }

    public function findByUseridAndFileid($user_id, $file_id){
        $data =  UserFile::model()->findAllBySql("select * from ".Yii::app()->params['tablePrefix']."file_star where user_id=".$user_id." and file_id=".$file_id);
        return $data;
    }

    /**
     *
     * 根据file_id删除
     */
    public function deleteAllByFileIds($userId, $fileIds) {
        return $this->deleteAll(array('condition' => "user_id=:user_id and file_id in ({$fileIds})",
                               'params'    => array(':user_id'=>$userId)));
    }

    /**
     * 根据user_id删除
     *
     * @param $user_id 用户编号
     *
     * @since 1.0.7
     */
    public function deleteUserAllStar($user_id)
    {
        $this->deleteAll('user_id=:user_id', array(':user_id'=>$user_id));
    }
}