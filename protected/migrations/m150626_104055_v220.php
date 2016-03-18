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
            $transaction->commit();
        }
    }
    private function createOAuthTable(){
        $dbType = "mysql";
        $extend = "";
        if ($dbType == "mysql"){
            $extend = "ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
        }
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
        
    } 

}