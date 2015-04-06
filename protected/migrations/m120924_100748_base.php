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
class m120924_100748_base extends EDbMigration
{
    /**
     * 执行数据迁移
     * @see CDbMigration::up()
     */
    public function up(){
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
     * @since 1.0.4
     */
    public function executeMigrate(){
        $data = date("Y-m-d H:i:s",time());

        $dbType = "mysql";

        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }

        // 新数据库版本的代码，可以是创建新表、修改表结构、增删数据 等等
        $this->createTable(DB_PREFIX.'_access_logs',
        array(
             'id'            => 'pk',
             'namespace'     => 'varchar(45) NOT NULL',
             'start_time'    => 'bigint(14) NOT NULL',
             'end_time'      => 'bigint(20) NOT NULL', 
             'appliction_id' => 'int(11) NOT NULL',
             'use_time'      => 'bigint(20) NOT NULL',
             'created_at'    => 'datetime NOT NULL',
             'updated_at'    => 'datetime NOT NULL',
        ),$extend);


        $this->createTable(DB_PREFIX.'_online_devices',
        array(
             'id'            => 'pk',
             'user_id'       => 'int(11) NOT NULL',//'用户ID',
             'device_id'     => 'int(11) NOT NULL',//'设备ID',
             'application_id'=> 'int(11) NOT NULL',//'应用ID',
             'created_at'    => 'datetime NOT NULL',
             'updated_at'    => 'datetime NOT NULL',
        ),$extend);


        $this->createTable(DB_PREFIX.'_pv',
        array(
             'id'            => 'pk',
             'current_date'  => 'varchar(32) NOT NULL',
             'application_id' => 'int(11) NOT NULL',
             'hour_period'   => 'int(11) NOT NULL',
             'namespace'     => 'varchar(255) NOT NULL',
             'use_count'     => 'int(11) NOT NULL',
             'use_time'      => 'int(11) NOT NULL',
             'created_at'    => 'datetime NOT NULL',
             'updated_at'    => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("current_date", DB_PREFIX.'_pv', "current_date,application_id,hour_period,namespace");


        $this->createTable(DB_PREFIX.'_error_logs',
        array(
             'id'            => 'pk',
             'namespace'     => 'varchar(45) NOT NULL',
             'memo'          => 'text NOT NULL',
             'appliction_id' => 'int(11) NOT NULL',
             'created_at'    => 'datetime NOT NULL',
             'updated_at'    => 'datetime NOT NULL',
        ),$extend);


        $this->createTable(DB_PREFIX.'_events',
        array(
             'id'            => 'pk',
             'user_id'       => 'int(11) NOT NULL',
             'user_device_id'=> 'int(11) NOT NULL',
             'action'        => 'int(11) NOT NULL',
             'file_path'     => 'varchar(300) NOT NULL',
             'context'       => 'text NOT NULL',
             'event_uuid'    => 'char(64) NOT NULL',
             'created_at'    => 'datetime NOT NULL',
             'updated_at'    => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("user_id", DB_PREFIX.'_events', "user_id, user_device_id, created_at");
        $this->createIndex("id", DB_PREFIX.'_events', "id, user_id, user_device_id, created_at");
        $this->createIndex("event_uuid", DB_PREFIX.'_events', "event_uuid");


        $this->createTable(DB_PREFIX.'_files',
        array(
             'id'              => 'pk',
             'user_id'         => 'int(11) NOT NULL',
             'file_type'       => 'int(11) NOT NULL',
             'parent_file_id'  => 'int(11) NOT NULL',
             'file_create_time'=> 'bigint(20) NOT NULL',
             'file_update_time'=> 'bigint(20) NOT NULL',
             'file_name'       => 'varchar(255) NOT NULL',
             'version_id'      => 'int(11) NOT NULL',
             'file_size'       => 'bigint(64) NOT NULL',
             'file_path'       => 'varchar(300) NOT NULL',
             'event_uuid'      => 'char(64) NOT NULL',
             'is_deleted'      => 'tinyint(4) NOT NULL DEFAULT \'0\'',
             'mime_type'       => 'varchar(64)',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("file_user_id", DB_PREFIX.'_files', "user_id");
        $this->createIndex("file_file_type", DB_PREFIX.'_files', "file_type");
        $this->createIndex("file_parent_file_id", DB_PREFIX.'_files', "parent_file_id");
        $this->createIndex("file_user_id_p", DB_PREFIX.'_files', "user_id, parent_file_id");
        $this->createIndex("file_file_path", DB_PREFIX.'_files', "file_path");
        $this->createIndex("file_user_id_file_type", DB_PREFIX.'_files', "user_id, file_type");
        $this->createIndex("file_file_path_is_deleted", DB_PREFIX.'_files', "file_path, is_deleted");
        $this->createIndex("file_user_id_parent_is_deleted", DB_PREFIX.'_files', "user_id, parent_file_id, is_deleted");


        $this->createTable(DB_PREFIX.'_file_metas',
        array(
             'id'              => 'pk',
             'file_path'       => 'varchar(255) NOT NULL',
             'meta_key'        => 'varchar(255) NOT NULL',
             'meta_value'      => 'text NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("file_path", DB_PREFIX.'_file_metas', "file_path");


        $this->createTable(DB_PREFIX.'_file_versions',
        array(
             'id'              => 'pk',
             'file_signature'  => 'varchar(64) NOT NULL',
             'file_size'       => 'bigint(64) NOT NULL',
             'ref_count'       => 'int(11) NOT NULL',
             'mime_type'       => 'varchar(255)',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("file_signature", DB_PREFIX.'_file_versions', "file_signature");


        $this->createTable(DB_PREFIX.'_file_version_metas',
        array(
             'id'              => 'pk',
             'version_id'      => 'int(11) NOT NULL',
             'meta_key'        => 'varchar(255) NOT NULL',
             'meta_value'      => 'text NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);


        $this->createTable(DB_PREFIX.'_options',
        array(
             'option_id'       => 'pk',
             'option_name'     => 'varchar(64) NOT NULL DEFAULT \'\'',
             'option_value'    => 'longtext NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("option_name", DB_PREFIX.'_options', "option_name", true);


        $this->createTable(DB_PREFIX.'_share_files',
        array(
             'id'              => 'pk',
             'share_key'       => 'varchar(45) NOT NULL',
             'file_id'         => 'int(11) NOT NULL',
             'password'        => 'varchar(45) NOT NULL DEFAULT \'-1\'',
             'down_count'      => 'int(11) NOT NULL DEFAULT \'0\'',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("share_file_share_key", DB_PREFIX.'_share_files', "share_key");
        $this->createIndex("share_file_file_id", DB_PREFIX.'_share_files', "file_id");


        $this->createTable(DB_PREFIX.'_users',
        array(
             'id'              => 'pk',
             'user_uuid'       => 'varchar(32) NOT NULL',
             'user_name'       => 'varchar(255) NOT NULL',
             'user_pass'       => 'varchar(255) NOT NULL',
             'user_status'     => 'tinyint(1) NOT NULL DEFAULT \'1\'',
             'salt'            => 'char(6) NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("user_name", DB_PREFIX.'_users', "user_name", true);
        $this->createIndex("user_uuid", DB_PREFIX.'_users', "user_uuid");
        $salt      = MiniUtil::genRandomString(6);
        $user_pass = MiniUtil::signPassword("admin", $salt);
        $this->insert(DB_PREFIX.'_users', array("id"=>1,"user_uuid"=>uniqid(),"user_name"=>"admin","user_pass"=>$user_pass,"user_status"=>1,"salt"=>$salt,"created_at"=>$data,"updated_at"=>$data));


        $this->createTable(DB_PREFIX.'_user_metas',
        array(
             'id'              => 'pk',
             'user_id'         => 'int(11) NOT NULL',
             'meta_key'        => 'varchar(255) NOT NULL',
             'meta_value'      => 'text NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->insert(DB_PREFIX.'_user_metas', array("id"=>1,"user_id"=>1,"meta_key"=>"is_admin","meta_value"=>"1","created_at"=>$data,"updated_at"=>$data));


        $this->createTable(DB_PREFIX.'_user_devices',
        array(
             'id'              => 'pk',
             'user_device_uuid'=> 'varchar(32) NOT NULL',
             'user_id'         => 'int(11) NOT NULL',
             'user_device_type'=> 'int(11) NOT NULL',
             'user_device_name'=> 'varchar(255) NOT NULL',
             'user_device_info'=> 'varchar(255) NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("user_device_device_uuid", DB_PREFIX.'_user_devices', "user_device_uuid");
        $this->createIndex("user_device_user_id", DB_PREFIX.'_user_devices', "user_id");
        $this->createIndex("user_device_user_id_and_type", DB_PREFIX.'_user_devices', "user_id, user_device_type");
        $this->createIndex("user_device_user_id_and_type_uuid", DB_PREFIX.'_user_devices', "user_id, user_device_type, user_device_uuid");
        $this->execute("INSERT INTO `".DB_PREFIX."_user_devices` (`id`, `user_device_uuid`, `user_id`, `user_device_type`, `user_device_name`, `user_device_info`, `created_at`, `updated_at`) VALUES (1, '4e1e434de8808a289f064da60cf9a48d', 1, 1, 'web', 'admin_Mozilla/5.0', '{$data}', '{$data}');;");


        $this->createTable(DB_PREFIX.'_user_devices_metas',
        array(
             'id'              => 'pk',
             'user_id'         => 'int(11) NOT NULL',
             'device_id'       => 'int(11) NOT NULL',
             'meta_name'       => 'varchar(255) NOT NULL',
             'meta_value'      => 'text NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);


        $this->createTable(DB_PREFIX.'_file_star',
        array(
             'id'              => 'pk',
             'user_id'         => 'int(11) NOT NULL',
             'file_id'         => 'int(11) NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("file_star_user_id", DB_PREFIX.'_file_star', "user_id, file_id");

        $this->createTable(DB_PREFIX.'_file_exifs',
        array(
             'id'              => 'pk',
             'version_id'      => 'int(11) NOT NULL',
             'latitude'        => 'varchar(11)',
             'longtitude'      => 'varchar(11)',
        	 'exif'            => 'text',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);


        $this->createTable(DB_PREFIX.'_auth_codes',
        array(
             'code'            => 'varchar(40) NOT NULL',
             'client_id'       => 'varchar(32) NOT NULL',
             'redirect_uri'    => 'varchar(200) NOT NULL',
             'expires'         => 'int(11) NOT NULL',
             'scope'           => 'varchar(250)',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
             'PRIMARY KEY (`code`)',
        ),$extend);


        $this->createTable(DB_PREFIX.'_clients',
        array(
             'id'              => 'pk',
             'user_id'         => 'int(11)',
             'client_name'     => 'varchar(255) NOT NULL',
             'client_id'       => 'varchar(32) NOT NULL',
             'client_secret'   => 'varchar(32) NOT NULL',
             'redirect_uri'    => 'varchar(200)',
             'enabled'         => 'tinyint(1) NOT NULL DEFAULT \'1\'',
             'description'     => 'text',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
        ),$extend);
        $this->createIndex("client_id", DB_PREFIX.'_clients', "client_id");
        $this->insert(DB_PREFIX.'_clients', array("id"=>1,"user_id"=>-1,"client_name"=>"MiniyunWeb",           "client_id"=>"JsQCsjF3yr7KACyT",    "client_secret"=>"bqGeM4Yrjs3tncJZ","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyunWeb","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_clients', array("id"=>2,"user_id"=>-1,"client_name"=>"MiniyunDesktopWindows","client_id"=>"d6n6Hy8CtSFEVqNh",    "client_secret"=>"e6yvZuKEBZQe9TdA","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyunDesktopWindows","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_clients', array("id"=>3,"user_id"=>-1,"client_name"=>"MiniyunDesktopMac",    "client_id"=>"c9Sxzc47pnmavzfy",    "client_secret"=>"9ZQ4bsxEjBntFyXN","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyunDesktopMac","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_clients', array("id"=>4,"user_id"=>-1,"client_name"=>"MiniyunAndroidPhone",  "client_id"=>"MsUEu69sHtcDDeCp",    "client_secret"=>"5ABU5XPzsR6tTxeK","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyunAndroidPhone","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_clients', array("id"=>5,"user_id"=>-1,"client_name"=>"MiniyunDesktopLinux",    "client_id"=>"V8G9svK8VDzezLum",    "client_secret"=>"waACXBybj9QXhE3a","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyunDesktopLinux","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_clients', array("id"=>6,"user_id"=>-1,"client_name"=>"MiniyuniPhone",        "client_id"=>"UmxT6CuwQYrtJGFp",    "client_secret"=>"GxsxayamApUSwTq9","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyuniPhone","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_clients', array("id"=>7,"user_id"=>-1,"client_name"=>"MiniyuniPad",          "client_id"=>"Lt7hPcA6nuX38FY4",    "client_secret"=>"vV2RpBsZBE4pNGG2","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyuniPad","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_clients', array("id"=>8,"user_id"=>-1,"client_name"=>"MiniyunTest",          "client_id"=>"c1d8132456e5d2eef452","client_secret"=>"c988b0cc440c5dcde2d39e4d47d0baf4","redirect_uri"=>"","enabled"=>1,"description"=>"MiniyunTest","created_at"=>$data,"updated_at"=>$data));


        $this->createTable(DB_PREFIX.'_refresh_token',
        array(
             'client_id'       => 'varchar(32) NOT NULL',
             'oauth_token'     => 'varchar(32) NOT NULL',
             'refresh_token'   => 'varchar(32) NOT NULL',
             'expires'         => 'int(11) NOT NULL',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
             'PRIMARY KEY (`oauth_token`)',
        ),$extend);


        $this->createTable(DB_PREFIX.'_tokens',
        array(
             'oauth_token'     => 'varchar(40) NOT NULL',
             'client_id'       => 'varchar(32) NOT NULL',
             'device_id'       => 'int(11) NOT NULL',
             'expires'         => 'int(11) NOT NULL',
             'scope'           => 'varchar(200)',
             'created_at'      => 'datetime NOT NULL',
             'updated_at'      => 'datetime NOT NULL',
             'PRIMARY KEY (`oauth_token`)',
        ),$extend);
        $this->createIndex("tokens_client_id", DB_PREFIX.'_tokens', "client_id");
        $this->createIndex("tokens_oauth_token", DB_PREFIX.'_tokens', "oauth_token");
        $this->createIndex("tokens_oauth_token_client_id", DB_PREFIX.'_tokens', "oauth_token, client_id");

		//模版文件中 初始化option数据
		$this->insert(DB_PREFIX.'_options', array("option_id"=>1,"option_name"=>"site_url","option_value"=>"NETLOC","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>2,"option_name"=>"site_title","option_value"=>"新型文件管理平台","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>3,"option_name"=>"site_name","option_value"=>"迷你云","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>4,"option_name"=>"site_logo_small_url","option_value"=>"/static/images/logo.png","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>5,"option_name"=>"site_company","option_value"=>"成都迷你云科技有限公司","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>6,"option_name"=>"user_register_enabled","option_value"=>"1","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>7,"option_name"=>"mid","option_value"=>"ORIGINAL_MID","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>8,"option_name"=>"site_logo_url","option_value"=>"/static/images/logo.png","created_at"=>$data,"updated_at"=>$data));
        $this->insert(DB_PREFIX.'_options', array("option_id"=>9,"option_name"=>"site_default_space","option_value"=>"1048576","created_at"=>$data,"updated_at"=>$data));
    }

    public function down(){
        return false;
    }

}