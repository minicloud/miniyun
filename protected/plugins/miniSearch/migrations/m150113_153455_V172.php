<?php
/**
 * 迷你搜索数据库
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
/**
 * 支持sphinx索引
 */
?>
<?php

class m150113_153455_V172  extends EDbMigration{

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
    public  function addTable(){
        if (!defined("DB_TYPE")){
            $dbType = "mysql";
        } else {
            $dbType = DB_TYPE;
        }
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        //创建miniyun_search_files表存储搜索文本内容
        $this->createTable(DB_PREFIX.'_search_files',
            array(
                'id'                   => 'pk',
                'file_signature'       => 'varchar(128) NOT NULL',
                'content'              => 'longblob NOT NULL',
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend);
//        $this->createIndex("group_user_id", DB_PREFIX.'_groups', "user_id");

        //创建增量索引记数表
        $this->createTable(DB_PREFIX.'_sphinx_count',
            array(
                'count_id'              => 'pk',
                'max_doc_id'            => 'int(11) NOT NULL',
            ),$extend);
    }
}