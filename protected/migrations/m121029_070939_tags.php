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
class m121029_070939_tags extends EDbMigration
{
    // Use safeUp/safeDown to do migration with transaction
    /**
    * (non-PHPdoc)
    * @see CDbMigration::safeUp()
    */
    public function safeUp()
    {
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
        $this->createTable(DB_PREFIX.'_tags',
        array(
             'id'      => 'pk',
             'user_id' => 'int(11) NOT NULL',
             'name'    => 'varchar(255) NOT NULL', 
        ),$extend);
        $this->createIndex("tags_user_id", DB_PREFIX.'_tags', "user_id");

        // 新数据库版本的代码，可以是创建新表、修改表结构、增删数据 等等
        $this->createTable(DB_PREFIX.'_file_tags',
        array(
             'id'    => 'pk',
             'file_id'  => 'int(11) NOT NULL', 
             'tag_id'  => 'int(11) NOT NULL', 
        ),$extend);
        $this->createIndex("file_tags_tag_id", DB_PREFIX.'_file_tags', "tag_id");
        $this->createIndex("file_tags_file_id", DB_PREFIX.'_file_tags', "file_id");
        $this->createIndex("file_tags_file_id_tag_id", DB_PREFIX.'_file_tags', "file_id, tag_id");

        return true;
    }
     
    /**
     * (non-PHPdoc)
     * @see CDbMigration::safeDown()
     */
    public function down()
    {
        echo "m121029_070939_tags does not support migration down.\n";
        return false;
    }
}