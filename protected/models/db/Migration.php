<?php
/**
 *
 * 升级管理模块
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Migration extends CMiniyunModel
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
     *
     * @since 1.0.6
     */
    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'migration';
    }

    public function relations()
    {
        return array(
        );
    }

    public function attributeLabels()
    {
        return array(
        );
    }

    /**
     *
     * 在数据迁移表中寻找指定版本的数据
     */
    public function findByVersion($version)
    {
        return Migration::model()->find('version=:version', array(':version'=>$version));
    }

    /**
     *
     * 在数据迁移表中寻找指定版本的数据
     */
    public function findByVersionAndModule($version, $module)
    {
        return Migration::model()->find('version=:version and module=:module', array(':version'=>$version, ':module'=>$module));
    }

    /**
     *
     * 在数据迁移表中创建指定版本的数据
     */
    public function checkMigration($version, $module, $apply_time)
    {
        //插入需要的数据
        $migration_data = $this->findByVersionAndModule($version, $module);
        if (empty($migration_data)){
            $migration = new Migration();
            $migration->version    = $version;
            $migration->apply_time = $apply_time;
            $migration->module     = $module;
            $migration->save();
        }
    }
     
}
