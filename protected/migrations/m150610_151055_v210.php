<?php
/**
 * 默认公共目录权限仅开放下载
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 2.1
 */
?>
<?php
class m150610_151055_v210  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try { 
            $this->updateData();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    }
    private  function updateData(){
        $this->update(DB_PREFIX.'_group_privileges', array("permission"=>'000000001'),"group_id=-1");
    } 
}