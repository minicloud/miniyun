<?php
/**
 * @author 南京恒为网络科技
 * @copyright 版权所有 2011南京恒为网络科技有限公司
 * @license http://www.miniyun.cn/license/
 * @create_time 2012-12-14
 * @version 1.0.0
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