<?php
/**
 * 修复数据库
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class RepairDbController extends CController {
	
    public function actionIndex() {
         foreach(Yii::app()->db->schema->getTableNames() as $name){
         	Yii::app()->db->createCommand("check  table ".$name)->execute();
         	Yii::app()->db->createCommand("repair table ".$name)->execute();
         }
         echo("success!<br>");
    }
 
}