<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class m131026_143551_mini_doc extends EDbMigration
{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->addTable();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
            return false;
        }
    }
    
    
    public function down()
    {
        echo "m130108_033751_container_base does not support migration down.\n";
        return false;
    }
    
    private function addTable(){
        if (!defined("DB_TYPE")){
            $dbType = "mysql";
        } else {
            $dbType = DB_TYPE;
        }
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        $this->createTable(DB_PREFIX.'_doc_nodes',array(
                "id"              => "pk",
                "run_status"      => "tinyint(4) NOT NULL",                "ip"              => "varchar(256) NOT NULL",                "port"            => "bigint(30) NOT NULL",                "created_at"      => "datetime NOT NULL",                "updated_at"      => "datetime NOT NULL"            ),$extend);
        $this->createIndex( "status_index", DB_PREFIX.'_services', "status,run_status" );
        $this->createIndex( "ip_index", DB_PREFIX.'_services', "ip,port,file_size_limit" );
    }
}