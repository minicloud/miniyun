<?php
/**
 * @author 南京恒为网络科技
 * @copyright 版权所有 2011南京恒为网络科技有限公司
 * @license http://www.miniyun.cn/license/
 * User: hcp
 * Date: 13-10-16
 * Time: 上午10:47
 * To change this template use File | Settings | File Templates.
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
        $this->createIndex("sort", DB_PREFIX.'_files', "sort");
        $this->createIndex("file_type", DB_PREFIX.'_files', "file_type");
    }
}