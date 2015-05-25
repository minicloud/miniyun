
<?php
/**
 * 修订APP相关信息
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 2.1
 */

class m150525_090955_v210 extends EDbMigration{
    /**
     * 更新系统
     * @return bool|void
     */
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

    /**
     * 修订APP相关信息 
     */
    private function updateData(){
        $this->update(DB_PREFIX.'_clients', array("client_name"=>'网页版',"description"=>'迷你云网页版'),"id=1");
        $this->update(DB_PREFIX.'_clients', array("client_name"=>'PC客户端',"description"=>'PC客户端'),"id=2");
        $this->update(DB_PREFIX.'_clients', array("client_name"=>'Mac PC客户端',"description"=>'Mac PC客户端'),"id=3");
        $this->update(DB_PREFIX.'_clients', array("client_name"=>'Android客户端',"description"=>'Android客户端'),"id=4");
        $this->update(DB_PREFIX.'_clients', array("client_name"=>'Linux PC客户端',"description"=>'Linux PC客户端'),"id=5");
        $this->update(DB_PREFIX.'_clients', array("client_name"=>'iPhone客户端',"description"=>'iPhone客户端'),"id=6");
        $this->update(DB_PREFIX.'_clients', array("client_name"=>'iPad客户端',"description"=>'iPad客户端'),"id=7");
    }
}