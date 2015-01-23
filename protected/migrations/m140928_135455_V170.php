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

class m140928_135455_V170  extends EDbMigration{
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
        if (!defined("DB_TYPE")){
            $dbType = "mysql";
        } else {
            $dbType = DB_TYPE;
        }
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        $this->createTable(DB_PREFIX.'_message',
            array(
                'id'            => 'pk',
                'user_id'       => 'int(11) NOT NULL ',
                'uu_id'       => 'int(11) NOT NULL ',
                'content'       => 'varchar(255) NOT NULL',
                'status'        => 'varchar(255) NOT NULL DEFAULT -1',
                'created_at'    => 'datetime NOT NULL',
                'updated_at'    => 'datetime NOT NULL',
            ),$extend);
        //用户组
        $this->createTable(DB_PREFIX.'_groups',
            array(
                'id'            => 'pk',
                'user_id'       => 'int(11) NOT NULL DEFAULT -1',
                'name'          => 'varchar(255) NOT NULL',
                'description'   => 'varchar(255) NOT NULL',
                'created_at'    => 'datetime NOT NULL',
                'updated_at'    => 'datetime NOT NULL',
            ),$extend);
        $this->createIndex("group_user_id", DB_PREFIX.'_groups', "user_id");
        //用户组关系
        $this->createTable(DB_PREFIX.'_group_relations',
            array(
                'id'              => 'pk',
                'group_id'        => 'int(11) NOT NULL',
                'parent_group_id' => 'int(11) NOT NULL',
                'created_at'      => 'datetime NOT NULL',
                'updated_at'      => 'datetime NOT NULL',
            ),$extend);
        $this->createIndex("group_relation_group_id", DB_PREFIX.'_group_relations', "group_id");
        $this->createIndex("group_relation_par_group_id", DB_PREFIX.'_group_relations', "parent_group_id");
        //用户与用户组的关系
        $this->createTable(DB_PREFIX.'_user_group_relations',
            array(
                'id'             => 'pk',
                'user_id'        => 'int(11) NOT NULL',
                'group_id'       => 'int(11) NOT NULL',
                'created_at'     => 'datetime NOT NULL',
                'updated_at'     => 'datetime NOT NULL',
            ),$extend);
        $this->createIndex("user_group_relation_user_id", DB_PREFIX.'_user_group_relations', "user_id");
        $this->createIndex("user_group_relation_group_id", DB_PREFIX.'_user_group_relations', "group_id");
        //用户组权限
        $this->createTable(DB_PREFIX.'_group_privileges',
            array(
                'id'             => 'pk',
                'group_id'       => 'int(11) NOT NULL',
                'file_path'      => 'varchar(300) NOT NULL',
                'permission'     => 'varchar(255) NOT NULL',
                'created_at'     => 'datetime NOT NULL',
                'updated_at'     => 'datetime NOT NULL',
            ),$extend);
        $this->createIndex("group_privilege_group_id", DB_PREFIX.'_group_privileges', "group_id");
        $this->createIndex("group_privilege_file_path", DB_PREFIX.'_group_privileges', "file_path");
        $this->createIndex("group_privilege_group_id_file_path", DB_PREFIX.'_group_privileges', "group_id, file_path");
        //用户权限
        $this->createTable(DB_PREFIX.'_user_privileges',
            array(
                'id'            => 'pk',
                'user_id'       => 'int(11) NOT NULL',
                'file_path'     => 'varchar(300) NOT NULL',
                'permission'    => 'varchar(255) NOT NULL',
                'created_at'    => 'datetime NOT NULL',
                'updated_at'    => 'datetime NOT NULL',
            ),$extend);
        $this->createIndex("user_privilege", DB_PREFIX.'_user_privileges', "user_id");
        $this->createIndex("user_privilege_file_path", DB_PREFIX.'_user_privileges', "file_path");
        $this->createIndex("user_privilege_user_id_file_path", DB_PREFIX.'_user_privileges', "user_id, file_path");
        //对miniyun_clients插入一条记录是关于插件通信使用到的，当前使用到的是miniDoc/miniSearch,迷你存储 使用的是PHP签名好的，暂时没有使用
        $currentDate = date("Y-m-d H:i:s",time());
        $this->insert(DB_PREFIX.'_clients', array("id"=>100,"user_id"=>-1,"client_name"=>"MiniyunPlugin", "client_id"=>"JsQCsjF3yr7KACy1", "client_secret"=>"bqGeM4Yrjs3tncJ1","redirect_uri"=>"","enabled"=>1,"description"=>"迷你云插件使用到的应用","created_at"=>$currentDate,"updated_at"=>$currentDate));

    }
}