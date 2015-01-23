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

class m131016_104855_V121  extends EDbMigration{
    /**
     * 更新系统
     * @return bool|void
     */
    public function up()
    {
        //
        //添加 miniyun_logs 表
        //
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->addTable();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    }

    /**
     * 添加数据库表miniyun_logs
     */
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
        $this->createTable(DB_PREFIX.'_logs',array(
            "id"             => "pk",
            "type"           => "enum('0', '1')  NOT NULL",//日志类型 '0'表示登陆日志 '1'表示操作日志
            "user_id"        => "int(11) NOT NULL",//用户ID
            "message"        => "varchar(256) NOT NULL",//消息
            "context"        => "text NOT NULL",//内容
            "created_at"     => "datetime NOT NULL",//消息创建时间
            "updated_at"     => "datetime NOT NULL",//修改时间
            "is_deleted"      => "tinyint(4) default 0"//是否删除
        ),$extend);
        $this->createIndex("user_id", DB_PREFIX.'_logs', "user_id");
    }
}