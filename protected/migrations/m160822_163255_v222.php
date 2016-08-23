<?php
/**
 * 存储节点分区
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
?>
<?php


class m160822_163255_v222  extends EDbMigration{
    
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
    private  function addTable(){
        $this->addColumn(DB_PREFIX.'_store_nodes', 'region', 'int(2)');
        $this->update(DB_PREFIX.'_store_nodes', array("region"=>0));
  }
}