<?php
/**
 * 支持办公文档在线浏览
 */
?>
<?php

class m150111_101855_V172  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try { 
            $this->modifyTable();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    } 
    public  function modifyTable(){
        //doc_convert_status=-1表示文件转换失败
        //doc_convert_status=0表示默认状态
        //doc_convert_status=1表示已经发送给了迷你文档服务器
        //doc_convert_status=2表示迷你文档已经转换成功了pdf
        $this->addColumn(DB_PREFIX.'_file_versions', 'doc_convert_status', 'int(11) NOT NULL DEFAULT  0');
        $this->createIndex("miniyun_file_versions_doc_convert_status", DB_PREFIX.'_file_versions', "doc_convert_status");
  }
}