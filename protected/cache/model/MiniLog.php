<?php
/**
 * Created by JetBrains PhpStorm.
 * User: miniyun
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniLog extends MiniCache{
    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    static private $_instance = null;
    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        parent::MiniCache();
    }

    /**
     * 静态方法, 单例统一访问入口
     * @return object  返回对象的唯一实例
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 得到日志的数量
     */
    public function getCount($criteria) {
        $count = Logs::model()->count($criteria);
        return $count;
    }
    /**
     * 通过db获得日志记录
     */
    public function getAll($criteria) {
        $rows = Logs::model()->findAll($criteria);
        if(isset($rows)){
            $retVal          = array();
            foreach ($rows as $item){
                $retVal[]    = $this->db2Item($item);
            }
            return $retVal;
        }
        return NULL;
    }
    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                     = array();
        $value["id"]             = $item["id"];
        $value["type"]           = $item["type"];
        $value["user_id"]        = $item["user_id"];
        $value["message"]        = $item["message"];
        $value["context"]        = $item["context"];
        $value["created_at"]     = $item["created_at"];
        $value["updated_at"]     = $item["updated_at"];
        $value["is_deleted"]     = $item["is_deleted"];
        return $value;
    }
    /**
     * 把数据库值序列化
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }
    /**
     *根据type获取记录
     */
    public function getByType($userId,$type,$limit,$offset){
        $criteria = new CDbCriteria();
        $criteria->order = 'created_at  desc';
        $criteria->addCondition("type=:type");
        $criteria->params[':type']=$type;
        $criteria->addCondition("user_id=:user_id");
        $criteria->params[':user_id']=$userId;
        $criteria->addCondition("is_deleted=:is_deleted");
        $criteria->params[':is_deleted']=0;
        $criteria->limit     = $limit;
        $criteria->offset    = $offset;
        $logs = Logs::model()->findAll($criteria);
        return $this->db2list($logs);
    }

    /**
     * 根据type获取总记录数
     * @param $userId
     * @param $type
     * @return \CDbDataReader|mixed|string
     */
    public function getCountByType($userId,$type){
        $criteria = new CDbCriteria();
        $criteria->condition="type=:type and is_deleted=:is_deleted";
        $criteria->params[':type']=$type;
        $criteria->params[':is_deleted']=0;
        $criteria->addCondition("user_id=:user_id");
        $criteria->params[':user_id']=$userId;
        $count = logs::model()->count($criteria);
        return $count;
    }
    /**
     * 添加操作日志
     */
    public function createOperateLog($user_id,$newPath){
        $logs = new Logs();
        $logs['message']  = $this->getIP();
        $logs['type']     = 1;
        $logs['user_id'] = $user_id;
        $logs['context'] = $newPath;
        $logs->save();
        return $logs;
    }

    /**
     * 添加登陆日志
     * @param $deviceId
     * @return Logs
     */
    public function createLogin($deviceId){
        if (empty($deviceId)) {
            return;
        }
        $device = MiniUserDevice::getInstance()->getById($deviceId);
        $logs = new Logs();
        $logs['message'] = $this->getIP();//当前登陆用户的IP
        $logs['user_id'] = $device["user_id"];
        $logs['type'] = 0;

        $arr = array(
            "action"       => MConst::LOGIN,
            "device_id"    => $deviceId,
            "device_type"  => $device["user_device_type"]
        );
        $logs['context'] = serialize($arr);
        $logs->save();
        MiniUserDevice::getInstance()->updateLastModifyTime($deviceId);
        return $logs;
    }

    /**
     * 添加登出日志
     * @param $userId
     * @param $device_type
     * @return Logs
     */
    public function createLogout($userId, $device_type){
        $logs = new Logs();
        $logs['message']=$this->getIP();//当前登出用户的IP
        $logs['user_id'] = $userId;
        $logs['type'] = 0;
        $arr = array(
            "action"       => MConst::LOGOUT,
            "device_type"  => $device_type
        );
        $logs['context'] = serialize($arr);
        $logs->save();
        return $logs;
    }
    /**
     * 获取IP地址
     */
    private function getIP() {
        if (@$_SERVER["HTTP_X_FORWARDED_FOR"])
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        else if (@$_SERVER["HTTP_CLIENT_IP"])
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        else if (@$_SERVER["REMOTE_ADDR"])
            $ip = $_SERVER["REMOTE_ADDR"];
        else if (@getenv("HTTP_X_FORWARDED_FOR"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (@getenv("HTTP_CLIENT_IP"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (@getenv("REMOTE_ADDR"))
            $ip = getenv("REMOTE_ADDR");
        else
            $ip = "Unknown";
        return $ip;
    }

    /**
     * 假删除日志
     */
    public function feignDeleteLogs($userId,$type=''){
        $criteria            = new CDbCriteria();
        $criteria->condition = 'user_id=:user_id';
        $criteria->params    = array(
            ':user_id'=>$userId
        );
        $attributes          = array(
            'is_deleted'=>1
        );
        if($type=='0'){
            $criteria->addCondition("type =:type","and");
            $criteria->params[':type']=$type;
        }
        Logs::model()->updateAll($attributes,$criteria);
        if($this->hasCache===true){
            $this->cleanCache($userId);
        }

    }
}