<?php
/**
 * 把用户名或昵称由汉字转化为拼音,便于用户检索 
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
?>
<?php


class m150528_113755_v210  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->addTable();
            $this->updateData();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    }
    public  function updateData(){
        MiniFile::getInstance()->updateAllFileNamePinyin();
    }
    public  function addTable(){
        $this->addColumn(DB_PREFIX.'_files', 'file_name_pinyin', 'varchar(255)');
  }
}