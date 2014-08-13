<?php
/**
 * 数据库管理
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MDbManager
{
    private $con;
    
    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    static private $_instance = null;

    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct() {
        $this->con = Yii::app()->db;
    }

    /**
     *  静态方法, 单例统一访问入口
     *  @return  object  返回对象的唯一实例
     */
    static public function getInstance() {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /* Transactions functions */
    function begin(){
         return true;
    }
    
    function commit(){
      return true;
    }
    
    function rollback(){
      return true;
    } 
    
    /**
     * 获取数据库连接
     */
    public function getCon() {
        return $this->con;
    }

    /**
     * 
     * 关闭数据库
     */
    public function closeDb() {
        return true;
    }

    /**
     * 
     * 插入数据
     * @param $sql
     */
    public function insertDb($sql) 
    {
        try {
            $sql = $this->replaceNow($sql);
            $command = $this->con->createCommand($sql);
            $command->execute();
        }
        catch(Exception $e) { 
            return false;
        }
        return true;
    }

    /**
     * 
     * 查询数据
     * @param $sql
     */
    public function selectDb($sql) 
    {
        $ret_array = false;
        try {
            $sql = $this->replaceNow($sql);
            $ret_array = $this->con->createCommand($sql)->queryAll();
        }
        catch(Exception $e) { 
            return false;
        }
        return $ret_array;
    }

    /**
     * 更新数据库
     * @param $sql
     */
    public function updateDb($sql) {
        try {
            $sql = $this->replaceNow($sql);
            $command = $this->con->createCommand($sql);
            $command->execute();
        }
        catch(Exception $e) { 
            return false;
        }
        return true;
    }

    /**
     * 删除数据库
     * @param $sql
     */
    public function deleteDb($sql) {
        try {
            $command = $this->con->createCommand($sql);
            $command->execute();
        }
        catch(Exception $e) { 
            return false;
        }
        return true;
    }
    /**
     *
     * @since 1.0.7
     */
    private function replaceNow($sql) {
        $data = date("Y-m-d H:i:s",time());
        $sql = str_replace('now()', "'{$data}'", $sql);
        return $sql;
    }
}