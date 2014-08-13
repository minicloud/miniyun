<?php
/**
 * 文件标签关联
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class FileTag extends CMiniyunModel {
    /**
     * Returns the static model of the specified AR class.
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    /**
     * (non-PHPdoc)
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'file_tags';
    }
}