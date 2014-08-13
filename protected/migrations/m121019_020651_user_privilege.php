<?php
/**
 * 添加用户的权限以及企业组织结构
 *
 * @author 南京恒为网络科技
 * @copyright 版权所有 2011南京恒为网络科技有限公司
 * @license http://www.5yun.com/license/
 * @create_time 2012-10-19
 * @version 1.0.7
 */
class m121019_020651_user_privilege extends EDbMigration
{
    /**
     * 执行数据迁移
     * @see CDbMigration::up()
     */
    public function up()
    {
        //进行事务操作
        $transaction=$this->getDbConnection()->beginTransaction();
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
     *
     * 执行插入数据等操作
     *
     * @since 1.0.7
     */
    public function executeMigrate(){
        $data = date("Y-m-d H:i:s",time());

        if (!defined("DB_TYPE")){
            $dbType = "mysql";
        } else {
            $dbType = DB_TYPE;
        }

        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }

        // 新数据库版本的代码，可以是创建新表、修改表结构、增删数据 等等
        $this->createTable(DB_PREFIX.'_user_privilege',
        array(
             'id'            => 'pk',
             'user_id'       => 'int(11) NOT NULL',
             'file_path'     => 'varchar(300) NOT NULL',
             'permission'    => 'varchar(255) NOT NULL', 
             'created_at'    => 'datetime NOT NULL',
             'updated_at'    => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("user_privilege", DB_PREFIX.'_user_privilege', "user_id");
        $this->createIndex("user_privilege_file_path", DB_PREFIX.'_user_privilege', "file_path");
        $this->createIndex("user_privilege_user_id_file_path", DB_PREFIX.'_user_privilege', "user_id, file_path");
        //添加排序sort字段
        $this->addColumn(DB_PREFIX.'_files', 'sort', 'int(11) NOT NULL DEFAULT  \'0\'');
    }

    public function down()
    {
        echo "m121019_020651_group_privilege does not support migration down.\n";
        return false;
    }
}