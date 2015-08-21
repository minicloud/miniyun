<?php
/**
 * 更新miniyun_events
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 2.2
 */
?>
<?php
class m150821_143055_v220  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try { 
            $criteria            = new CDbCriteria;  
            $items               = Event::model()->findAll($criteria);
            foreach ($items as $key => $event) {
                $oriObject = @unserialize($event->context);
                if($oriObject){
                    $event->context = json_encode($oriObject);
                    $event->save();
                }             
            }
            $transaction->commit();
        } catch (Exception $e) {
            var_dump($e);exit;
            $transaction->commit();
        }
    }
}