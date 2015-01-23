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


class m141020_173955_V150  extends EDbMigration{
    
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
        MiniUser::getInstance()->updateAllUserNamePinyin();
    }
    public  function addTable(){
        $this->addColumn(DB_PREFIX.'_users', 'user_name_pinyin', 'varchar(255)');
        $this->createIndex("miniyun_usrs_user_name_pinyin", DB_PREFIX.'_users', "user_name_pinyin");
  }
}