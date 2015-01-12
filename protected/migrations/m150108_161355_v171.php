
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

class m150108_161355_v170 extends EDbMigration{
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
        $this->addColumn(DB_PREFIX.'_groups', 'parent_group_id', 'int(11)');
    }
}