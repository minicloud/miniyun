<?php
/**
 * 
 * 
 *
 */
class m140201_141051_store extends EDbMigration
{
    /**
     * (non-PHPdoc)
     * @see CDbMigration::up()
     */
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        
        try
        {
            $this->executeMigrate();
            $transaction->commit();
        }
        catch(Exception $e)
        {
            echo "Exception: ".$e->getMessage()."\n";
            $transaction->rollback();
            return false;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see CDbMigration::down()
     */
    public function down()
    {
        echo "m130108_033851_dataserver_base does not support migration down.\n";
        return false;
    }
    
    /**
     *
     * 数据库升级
     *
     */
    private function executeMigrate(){
        
        if(!defined("DB_TYPE")) {
            $dbType = "mysql";
        } else {
            $dbType = DB_TYPE;
        }
        $extend     = "";
        if($dbType == "mysql") {
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        
        // 新数据库版本的代码，可以是创建新表、修改表结构、增删数据 等等
        $this->createTable(DB_PREFIX.'_store_nodes',
                array(
                        'id'            => 'pk',
                		'name'          => 'varchar(255)',
                		'run_status'    => 'int(11)', // -1 离线 1在线 2只读
                        'ip'            => 'varchar(255)',
                		'port'          => 'int(11)',
                        'path'          => 'text',
                        'safe_code'     => 'varchar(32)',
                        'created_at'    => 'datetime NOT NULL',
                        'updated_at'    => 'datetime NOT NULL',
                ), $extend);
    }
}