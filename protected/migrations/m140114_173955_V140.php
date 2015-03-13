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

class m140114_173955_V140  extends EDbMigration{
    /**
     * 更新系统
     * @return bool|void
     */
    public function up()
    {

        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->addTable();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    }

    /**
     * 为files表添加索引sort
     */
    private function addTable(){
        $this->createIndex("file_type", DB_PREFIX.'_files', "file_type");
    }
}