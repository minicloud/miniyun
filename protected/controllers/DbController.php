<?php
/**
 * 修复数据库
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class DbController extends CController {
    /**
     * 修复数据库
     */
    public function actionRepair() {
         foreach(Yii::app()->db->schema->getTableNames() as $name){
         	Yii::app()->db->createCommand("check  table ".$name)->execute();
         	Yii::app()->db->createCommand("repair table ".$name)->execute();
         }
         echo("success!<br>");
    }
    /**
     * 升级数据库
     */
    public function actionUpgrade() {
        $migration = new MiniMigration();
        $migration->up();
        echo("success!<br>");
    }
}