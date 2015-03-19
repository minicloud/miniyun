<?php
/**
 *文件选择器依赖的数据库  
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class m140320_173955_V150  extends EDbMigration{
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
     * 添加表
     */
    public  function addTable(){
        $dbType = "mysql";
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        //文件选择器
        $this->createTable(DB_PREFIX.'_choosers',array(
            "id"             => "pk",
            "name"           => "varchar(50) NOT NULL",//名称
            "app_key"        => "varchar(80) NOT NULL",//唯一匹配key码
            "type"           => "int(2) NOT NULL",//1指的是web ,2 指的是android
            "created_at"     => "datetime NOT NULL",//创建时间
            "updated_at"     => "datetime NOT NULL",//修改时间
        ),$extend);
        //文件选择器域名表
        $this->createTable(DB_PREFIX.'_chooser_domains',array(
            'id'             => "pk",
            "chooser_id"     => "int(11) NOT NULL", //对应的chooser_nodes表的id
            "domain"         => "varchar(256) NOT NULL",//域名
            "created_at"     => "datetime NOT NULL",//创建时间
            "updated_at"     => "datetime NOT NULL",//修改时间
        ),$extend);
        $this->createIndex("id", DB_PREFIX.'_chooser_domains', "id");
        //外链与文件选择器之间的关系
        $this->createTable(DB_PREFIX.'_chooser_links',array(
            "id"             => "pk",
            "link_id"        => "varchar(50) NOT NULL",//外链ID
            "app_key"        => "varchar(32) NOT NULL",//chooser的app_key
            "session"        => "varchar(32) NULL",//会话ID，针对一次性选择多个，记录当前的会话
            "created_at"     => "datetime NOT NULL",//创建时间
            "updated_at"     => "datetime NOT NULL",//修改时间
        ),$extend);
        $this->createIndex("mini_chooser_links_app_key_link_id", DB_PREFIX.'_chooser_links', "app_key,link_id",true);
        $this->createIndex("mini_chooser_session", DB_PREFIX.'_chooser_links', "session");
        //为share_files添加user_id
        $this->addColumn(DB_PREFIX.'_share_files', 'user_id', 'int(11) NOT NULL DEFAULT  \'-1\'');
        //为share_files添加到期时间
        $this->addColumn(DB_PREFIX.'_share_files', 'expiry', 'bigint NOT NULL DEFAULT  \'-1\'');
        $this->createIndex("mini_share_file_user_id", DB_PREFIX.'_share_files', "user_id");
        $this->createIndex("mini_share_file_user_id_file_id", DB_PREFIX.'_share_files', "user_id,file_id");
  }
}