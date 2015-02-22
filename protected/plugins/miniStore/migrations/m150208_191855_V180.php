<?php
/**
 * 迷你存储数据库
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
?>
<?php
class m150208_191855_V180  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->newTable();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    } 
    private  function newTable(){
         if (!defined("DB_TYPE")){
            $dbType = "mysql";
        } else {
            $dbType = DB_TYPE;
        }
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        //创建miniyun_store_nodes表存储迷你存储节点信息
        $this->createTable(DB_PREFIX.'_store_nodes',
            array(
                'id'                   => 'pk',
                'name'                 => 'varchar(128) NOT NULL',//存储服务器名称，表唯一
                'host'                 => 'varchar(128) NOT NULL',//访问域名，这个域名将会对用户最终设备开放
                'access_token'         => 'varchar(128) NOT NULL',//访问token，用于安全校验
                'status'               => 'int NOT NULL',//-1表示服务器不可用，1表示服务器可用
                'saved_file_count'     => 'int NOT NULL',//已存储文件的总数
                'downloaded_file_count'  => 'int NOT NULL',//已下载文件的总数
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend); 
        $this->createIndex(DB_PREFIX.'_store_nodes_name',DB_PREFIX.'_store_nodes', "name");
        //创建miniyun_break_files表存储断点文件
        //每次上传，客户端都会到迷你云取得断点文件位置，如果没有记录需要重新上传
        //客户端上传完毕一个文件块后，会更改break_files.uploaded_size
        //如果文件上传成功，break_files相关记录会被删除，另外生成相关用户的元数据
        //为用户生成的数据，包括有file_version_metas生成相关记录
        $this->createTable(DB_PREFIX.'_break_files',
            array(
                'id'                   => 'pk',
                'file_signature'       => 'varchar(128) NOT NULL',//文件signature值
                'size'                 => 'long NOT NULL',//文件总大小
                'store_node_id'        => 'varchar(128) NOT NULL',//存储到目标服务器标记
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend); 
        $this->createIndex(DB_PREFIX.'_break_files_signature',DB_PREFIX.'_break_files', "file_signature");
        //创建miniyun_replicate_tasks表存储冗余备份任务表
        //用户把文件上传成功后，系统自动为其它2个节点生成冗余备份请求
        //后台定时任务，会把请求推送到目标服务器
        //目标服务器冗余备份后，replicate_tasks相关记录将会被删除
        //文件冗余成功后，会更改file_version_metas的相关记录
        $this->createTable(DB_PREFIX.'_replicate_tasks',
            array(
                'id'                   => 'pk',
                'file_signature'       => 'varchar(128) NOT NULL', //文件signature值
                'store_node_id'        => 'varchar(128) NOT NULL',//存储到目标服务器标记
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend); 
        $this->createIndex(DB_PREFIX.'_replicate_tasks_signature',DB_PREFIX.'_replicate_tasks', "file_signature");
        //replicate_status=0表示默认状态
        //replicate_status=1表示正在冗余备份
        //replicate_status=2表示冗余备份成功
        $this->addColumn(DB_PREFIX.'_file_versions', 'replicate_status', 'int(11) NOT NULL DEFAULT  0');
        $this->createIndex("miniyun_file_versions_replicate_status", DB_PREFIX.'_file_versions', "replicate_status");
        //后台定时任务，将检查各个存储服务器的状态
        //后台定时任务，将把冗余备份请求发送到存储服务器上
        //迷你云将具备下面能力
        //接口1：客户端秒传接口，如是新建，为其随机分配迷你存储节点并将信息记录在在break_files，如还是上传该文件，且尚未上传成功，直接返回该迷你存储节点 
        //接口2：接口来自迷你存储服务器冗余备份成功的报俊信息
        //任务1：定时检查存储服务器的状态
        //任务2：定时把冗余备份请求发送到存储服务器上
        //业务1：根据store_nodes.saved_file_count的值大小轮训分配服务器给文件上传接口，结果将输出到plugin_info中与秒传接口中（11）
        //业务2：根据store_nodes.download_file_count的值大小轮训分配服务器给下载上传接口(1)
        //业务3：图片请求缩略图，如迷你云本机服务器缓存没有，则像迷你存储请求，并计算该缩略图
        //业务4：文件上传成功，如果是图片，可下载文件到本地并提取图片的元数据，比较地理信息等（TODO暂时不做）
        
        //迷你存储将具备下面能力
        //接口1：接受来自客户端秒传接口，判断文件是否成功，如果本地有该文件内容，则直接上传成功。否则返回文件的断点信息即可
        //接口2：接受来自客户端上传的断点文件，并将其写入本地磁盘。不用写缓存文件，直接写到硬盘中。
        //      写文件成功后，向迷你云报俊断点文件信息
        //接口3：接受来自客户端的下载请求(这里包括有业务1，使用Node计算，Nignx的sendfile模式文件下载)
        //接口4：接口来自迷你云的冗余备份请求，冗余备份成功后，向迷你云报俊冗余备份成功
        //业务1：本地磁盘将有多个目录，这里有一个找文件存储目录的过程
        //业务2：秒传生成空文件，并为该文件生成块信息记录表，以后每上传一块，就为其标注该快上传成功。此后断点文件上传直接返回快信息列表。
  }
}