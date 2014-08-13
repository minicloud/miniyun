<?php
/**
 *  miniyun_options的模型文件
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Option extends CMiniyunModel
{
    /**
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
        return Yii::app()->params['tablePrefix'].'options';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    { 
        return array(
        array('option_name', 'required'), 
        );
    }
 	/**
     *
     * 此后都是用MiniOption::getInstance()->setOptionValue替代
     */
    public function setOptionValue($key, $value){
         MiniOption::getInstance()->setOptionValue($key, $value);
    }

    /**
     *
     * 此后都是用MiniOption::getInstance()->getOptionValue替代
     * 现在之所以存在是因为很多Module采用这样的接口模式
     * @param string $key
     * @deprecated
     */
    public function getOptionValue($key){
        return MiniOption::getInstance()->getOptionValue($key);
    }
}