<?php
/**
 *文件信息外域传输 
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class m140319_173955_V140  extends EDbMigration{
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
     * 添加Miniins_ajax表
     */
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
        $this->createTable(DB_PREFIX.'_chooser_select_files',array(
            'id'             => "pk",
            'user_id'        => "int(11) NOT NULL",
            'file_key'       => "varchar(256) NOT NULL", //文件信息传输标记
            "file_ids"       => "varchar(256) NOT NULL", //文件信息
            "created_at"     => "datetime NOT NULL",//创建时间
            "updated_at"     => "datetime NOT NULL",//修改时间
        ),$extend);
        $this->createIndex("id", DB_PREFIX.'_chooser_select_files', "id");
    }
}