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
 
    private function db2Item($item){ 
        if(empty($item)) return NULL;
        $value                   = array();
        $value["id"]             = $item->id;
        $value["type"]           = "0";
        $value["user_id"]        = $item->user_id;
        $context                 = json_decode($item->context);
        $value["message"]        = $context->{"ip"};
        
        $newContext              = array();
        $newContext["action"]    = $context->{"action"};
        $device = MiniUserDevice::getInstance()->getById($item->user_device_id);
        $newContext["device_id"]   = $device["id"];
        $newContext["device_type"] = $device["user_device_type"]."";
        $value["context"]        = serialize($newContext);

        $value["created_at"]     = $item->created_at;
        $value["updated_at"]     = $item->updated_at;
        $value["is_deleted"]     = 0;
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
        $criteria->addCondition("type=1"); 
        $criteria->addCondition("user_id=:user_id");
        $criteria->params[':user_id']=$userId; 
        $criteria->limit     = $limit;
        $criteria->offset    = $offset;
        $events = Event::model()->findAll($criteria);
        return $this->db2list($events);
    }

    /**
     * 根据type获取总记录数
     * @param $userId
     * @param $type
     * @return \CDbDataReader|mixed|string
     */
    public function getCountByType($userId,$type){
        $criteria = new CDbCriteria();
        $criteria->condition="type=1"; 
        $criteria->addCondition("user_id=:user_id");
        $criteria->params[':user_id']=$userId;
        $count = Event::model()->count($criteria);
        return $count;
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
        $event = new Event();
        $event['user_device_id'] = $deviceId;
        $event['user_id'] = $device["user_id"];
        $event['type'] = 1;

        $arr = array(
            "action"   => 0,
            "ip"       => $this->getIP()
        );
        $event['context'] = json_encode($arr);
        $event->save();
        MiniUserDevice::getInstance()->updateLastModifyTime($deviceId);
        return $event;
    }

    /**
     * 添加登出日志
     * @param $userId
     * @param $device_type
     * @return Logs
     */
    public function createLogout($userId, $device_type){
        $event = new Logs();
        $event['user_device_id'] = $deviceId;
        $event['user_id'] = $device["user_id"];
        $logs['type'] = 1;
        $arr = array(
            "action"   => 1,
            "ip"       => $this->getIP()
        );
        $event['context'] = json_encode($arr);
        $event->save();
        return $event;
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
        Event::model()->deleteAll($criteria);       

    }
}