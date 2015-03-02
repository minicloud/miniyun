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
        //创建miniyun_search_nodes表存储迷你搜索节点信息
        $this->createTable(DB_PREFIX.'_search_nodes',
            array(
                'id'                   => 'pk',
                'name'                 => 'varchar(128) NOT NULL',//存储服务器名称，表唯一
                'host'                 => 'varchar(128) NOT NULL',//访问域名，这个域名不对用户最终设备开放
                'safe_code'            => 'varchar(128) NOT NULL',//访问安全码，用于安全校验
                'status'               => 'int NOT NULL',//-1表示服务器不可用，1表示服务器可用
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend);
        $this->createIndex(DB_PREFIX.'_search_nodes_name',DB_PREFIX.'_search_nodes', "name");

        //创建miniyun_search_files表存储搜索文本内容
        $this->createTable(DB_PREFIX.'_search_files',
            array(
                'id'                   => 'pk',
                'file_signature'       => 'varchar(128) NOT NULL',
                'content'              => 'longblob NOT NULL',
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend);
        //创建search
    }
}