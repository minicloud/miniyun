<?php
/**
 * 基础版本数据库文件
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class m121214_134155_add_online_index extends EDbMigration
{
    /**
     * (non-PHPdoc)
     * @see CDbMigration::up()
     */
    public function up()
    {
        // ALTER TABLE  `miniyun_online_devices` ADD INDEX (  `user_id` ,  `device_id` ,  `application_id` ) ;
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->createIndex("user_device_application_id", DB_PREFIX.'_online_devices', "user_id, device_id, application_id");
            $transaction->commit();
        } catch (Exception $e) {
            echo "Exception: ".$e->getMessage()."\n";
            $transaction->rollback();
            return false;
        }
    }
}