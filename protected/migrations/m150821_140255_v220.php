<?php
/**
 * 更新active_plugins
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 2.2
 */
?>
<?php
class m150821_140255_v220  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try { 
            $value = MiniOption::getInstance()->getOptionValue("active_plugins");
            $plugins = unserialize($value);
            MiniOption::getInstance()->setOptionValue("active_plugins",json_encode($plugins));
            $transaction->commit();
        } catch (Exception $e) {
            var_dump($e);exit;
            $transaction->commit();
        }
    }
}