<?php
/**
 * 迷你文档数据库
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
?>
<?php

class m150111_101855_V172  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->modifyTable();
            $this->setDefault();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    }

    /**
     * 设置迷你云默认地址，迷你文档服务器下载文件内容需要获得这个地址
     */
    private function setDefault(){
        //判断是否在网页启动数据库生成，在command模式下无法获得$_SERVER的值
        if(array_key_exists("SERVER_PORT",$_SERVER)){
            $host = MiniHttp::getMiniHost();
            MiniOption::getInstance()->setOptionValue("miniyun_host",$host);
        }
    }
    private  function modifyTable(){
        if (!defined("DB_TYPE")){
            $dbType = "mysql";
        } else {
            $dbType = DB_TYPE;
        }
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        //创建miniyun_doc_nodes表存储迷你文档节点信息
        $this->createTable(DB_PREFIX.'_doc_nodes',
            array(
                'id'                   => 'pk',
                'name'                 => 'varchar(128) NOT NULL',//存储服务器名称，表唯一
                'host'                 => 'varchar(128) NOT NULL',//访问域名，这个域名不对用户最终设备开放
                'safe_code'            => 'varchar(128) NOT NULL',//访问安全码，用于安全校验
                'status'               => 'int NOT NULL',//-1表示服务器不可用，1表示服务器可用
                'convert_file_count'   => 'int NOT NULL',//已转换文件的总数
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend);
        $this->createIndex(DB_PREFIX.'_doc_nodes_name',DB_PREFIX.'_doc_nodes', "name");

        //doc_convert_status=-1表示文件转换失败
        //doc_convert_status=0表示默认状态
        //doc_convert_status=1表示已经发送给了迷你文档服务器
        //doc_convert_status=2表示迷你文档已经转换成功了pdf
        $this->addColumn(DB_PREFIX.'_file_versions', 'doc_convert_status', 'int(11) NOT NULL DEFAULT  0');
        $this->createIndex("miniyun_file_versions_doc_convert_status", DB_PREFIX.'_file_versions', "doc_convert_status");

  }
}