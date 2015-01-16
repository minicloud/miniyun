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
class m130621_092055_V120 extends EDbMigration
{
    /**
     * (non-PHPdoc)
     * @see CDbMigration::up()
     */
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            //为event添加type类型，针对启用了公共目录的迷你云会存在升级的问题，这里在异常中执行
            $this->addColumn(DB_PREFIX.'_events', 'type', 'int(4) NOT NULL DEFAULT  \'0\'');
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
        	$this->createIndex("event_user_id", DB_PREFIX.'_events', "user_id");
            $this->createIndex("event_type", DB_PREFIX.'_events', "type");
            $this->createIndex("version_id_meta_key", DB_PREFIX.'_file_version_metas', "version_id, meta_key");
            $this->createIndex("device_id_meta_name", DB_PREFIX.'_user_devices_metas', "device_id, meta_name");
            $transaction->commit();
        } catch (Exception $e) {
        	$transaction->commit();
        }
    }
}