
<?php
/**
 * 1.7.1版本在线编辑文件后会出现/1/这样的目录信息
 * 这里修复数据库
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class m150116_101455_v172 extends EDbMigration{
    /**
     * 更新系统
     * @return bool|void
     */
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $this->modifyData();
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->commit();
        }
    }
    /**
     * 删除多余数据
     */
    private function modifyData(){
        $users = MiniUser::getInstance()->getAll();
        foreach($users as $user){
            $path = "/".$user["id"];
            $file = MiniFile::getInstance()->getByPath($path);
            if(!empty($file)){
                if($user["id"]===$file["user_id"]){
                    try {
                        MiniFile::getInstance()->deleteFile($file["id"]);
                    } catch (Exception $e) {
                    }
                }
            }
        }
    }
}