<?php
/**
 * 新版本oauth
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 2.2
 */
?>
<?php
class m150626_104055_v220  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try { 
            $this->createOAuthTable();
            $transaction->commit();
        } catch (Exception $e) {
            var_dump($e);exit;
            $transaction->commit();
        }
    }
    private function createOAuthTable(){
        $dbType = "mysql";
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
        //新加oauth_access_tokens/oauth_refresh_tokens
        $this->createTable('oauth_access_tokens',array(
                'id'                   => 'pk',
                'access_token'         => 'varchar(64) NOT NULL',
                'client_id'            => 'varchar(64) NOT NULL',
                'user_id'              => 'int NOT NULL',
                'expires'              => 'bigint(20) NOT NULL',
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend); 
        $this->createTable('oauth_refresh_tokens',array(
                'id'                   => 'pk',
                'refresh_token'        => 'varchar(64) NOT NULL',
                'client_id'            => 'varchar(64) NOT NULL',
                'user_id'              => 'int NOT NULL',
                'expires'              => 'bigint(20) NOT NULL',
                'created_at'           => 'datetime NOT NULL',
                'updated_at'           => 'datetime NOT NULL',
            ),$extend);
        //把miniyun_user_devices添加client_id
        $this->addColumn(DB_PREFIX.'_user_devices', 'client_id', 'varchar(64)'); 
        $criteria = new CDbCriteria(); 
        $items = UserDevice::model()->findAll($criteria);
        foreach ($items as $key => $item) {
            $userDeviceType = $item->user_device_type;
            if($userDeviceType==1){
                $item->client_id='JsQCsjF3yr7KACyT';
            }
            if($userDeviceType==2){
                $item->client_id='d6n6Hy8CtSFEVqNh';
            }
            if($userDeviceType==3){
                $item->client_id='c9Sxzc47pnmavzfy';
            }
            if($userDeviceType==4){
                $item->client_id='MsUEu69sHtcDDeCp';
            }
            if($userDeviceType==5){
                $item->client_id='V8G9svK8VDzezLum';
            }
            if($userDeviceType==6){
                $item->client_id='Lt7hPcA6nuX38FY4';
            }
            $item->save();
        }
        //把miniyun_online_devices添加client_id
        $this->addColumn(DB_PREFIX.'_online_devices', 'client_id', 'varchar(64)'); 
        $criteria = new CDbCriteria(); 
        $items = OnlineDevice::model()->findAll($criteria);
        foreach ($items as $key => $item) {
            $userDeviceType = $item->application_id;
            if($userDeviceType==1){
                $item->client_id='JsQCsjF3yr7KACyT';
            }
            if($userDeviceType==2){
                $item->client_id='d6n6Hy8CtSFEVqNh';
            }
            if($userDeviceType==3){
                $item->client_id='c9Sxzc47pnmavzfy';
            }
            if($userDeviceType==4){
                $item->client_id='MsUEu69sHtcDDeCp';
            }
            if($userDeviceType==5){
                $item->client_id='V8G9svK8VDzezLum';
            }
            if($userDeviceType==6){
                $item->client_id='Lt7hPcA6nuX38FY4';
            }
            $item->save();
        }
        //把miniyun_logs记录转移到miniyun_events中
        $this->alterColumn(DB_PREFIX.'_events', 'action', 'int(11) NULL'); 
        $this->alterColumn(DB_PREFIX.'_events', 'file_path', 'varchar(300) NULL'); 
        $this->alterColumn(DB_PREFIX.'_events', 'context', 'text NULL'); 
        $this->alterColumn(DB_PREFIX.'_events', 'event_uuid', 'varchar(64) NULL');
        $criteria = new CDbCriteria();  
        $criteria->condition = "type='0'";
        $items = Logs::model()->findAll($criteria);
        foreach ($items as $key => $item) {
            $ip = $item->message;
            $context = unserialize($item->context);
            $newContent = array();
            $newContent["ip"] = $ip; 
            $event = new Event();
            $event->user_id = $item->user_id;
            $event->user_device_id = $context["device_id"]; 
            $event->type = 1;
            $event->context = serialize($newContent);
            $event->save(); 
        }
    } 

}