<?php
/**
 * 约定filie表的filePath唯一
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 2.21
 */
?>
<?php
class m160401_103955_v221  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try { 
            $this->constraintFileTable();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    }
    private function constraintFileTable(){
        $this->createIndex("miniyun_files_file_path", DB_PREFIX.'_files', "file_path",true); 
    } 
}