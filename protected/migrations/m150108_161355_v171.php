
<?php
/**
 * 部门导入添加冗余字段
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class m150108_161355_v171 extends EDbMigration{
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