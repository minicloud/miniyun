<?php
/**
 * 缓存miniyun_event表的记录，V1.2.0该类接管部分miniyun_event的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniEvent extends MiniCache{

    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY          = "cache.model.MiniEvent";
    public static $OBJECT_TYPE_PUBLIC  = 16;//公共目录
    public static $CREATE_FILE         = 3;//event.action(create file)
    public static $CREATE_FOLDER       = 0;//event.action(create folder)
    public static $EVENT_COMMON_TYPE   = 0;//event.type(common type)
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
     * 按照id逐一放入内存
     */
    private function getCacheKey($id){
        return MiniEvent::$CACHE_KEY."_".$id;
    }
    /**
     * 获得当前用户最大的事件ID
     */
    public function getMaxIdByUser($userId){
        Yii::trace(MiniEvent::$CACHE_KEY." get db EventMaxIdByUser userId:".$userId," miniyun.cache1");
        $criteria         = new CDbCriteria;
        $criteria->select = 'max(id) AS maxId';
        $criteria->addCondition('user_id = :user_id');
        $criteria->params = array(':user_id' =>$userId);
        $row              = Event::model()->find($criteria);
        if(isset($row)){
            $userMaxId          = $row['maxId'];
            if(!isset($userMaxId)){
                $userMaxId      = -1;
            }
        }else{
            $userMaxId          = -1;
        }
        $criteria         = new CDbCriteria;
        $criteria->select = 'max(id) AS maxId';
        $criteria->addCondition('type=16');
        $row              = Event::model()->find($criteria);
        if(isset($row)){
            $publicMaxId          = $row['maxId'];
            if(!isset($publicMaxId)){
                $publicMaxId      = -1;
            }
        }else{
            $publicMaxId          = -1;
        }
        if($publicMaxId>$userMaxId){
            return $publicMaxId;
        }
        return $userMaxId;
    }
    /**
     * 根据条件获得最大当前用户最大的事件ID
     */
    public function getMaxIdByCondition($conditon){
        Yii::trace(MiniEvent::$CACHE_KEY." get db getMaxIdByCondition:".$conditon,"miniyun.cache1");
        $criteria            = new CDbCriteria;
        $criteria->select    = 'max(id) AS maxId';
        $criteria->condition = $conditon;
        $row                 = Event::model()->find($criteria);
        if(isset($row)){
            $id              = $row['maxId'];
            if(!isset($id)){
                $id          = -1;
            }
        }else{
            $id              = -1;
        }
        return $id;
    }
    /**
     * 生成事件
     * @param $user_id
     * @param $user_device_id
     * @param $action
     * @param $path
     * @param $context
     * @param $event_uuid
     * @param $extends
     */
    public  function createEvent($user_id, $user_device_id, $action, $path, $context, $event_uuid, $extends = NULL) {
        $event                 = new Event();
        $event->user_id        = $user_id;
        $event->user_device_id = $user_device_id;
        $event->action         = $action;
        $event->file_path      = $path;
        $event->context        = $context;
        $event->event_uuid     = $event_uuid;
        $event->type           = MiniEvent::$OBJECT_TYPE_PUBLIC;
        if($extends!==MiniEvent::$OBJECT_TYPE_PUBLIC){
            $event->type       = 0;
        }
        try {
            $event->save();
        }
        catch(Exception $e) {
            return false;
        }
        //
        // 为创建事件记录日志信息
        //
        $this->createLogs($user_id, $action, $path, $context);
        return true;
    }

    /**
     * 为创建事件记录日志信息
     * @param $user_id
     * @param $action
     * @param $path
     * @param $context
     */
    private function createLogs($user_id, $action, $path, &$context) {
        if ($action == MConst::CREATE_FILE) {
            //
            // 如果是创建文件，记录path
            //
            $new = $path;
        } else {
            $new = $context;
        }
        $context = array($path,$new,$action);
        MiniLog::getInstance()->createOperateLog($user_id, serialize($context));
        return $new;
    }


    /**
     * 批量创建事件
     */
    public  function createEvents($userId, $deviceId, $fileDetails, $extends= NULL) {
        foreach ( $fileDetails as $item ) {
            //TODO: $file_detail MFile对象，后期统一处理$file_detail["context"] =>$file_detail->context
            $context       = str_replace("'", "\\'", $item->context);
            $action        = $item->event_action;
            $path          = $item->from_path;
            $event_uuid    = $item->event_uuid;
            $this->createEvent($userId, $deviceId, $action, $path, $context, $event_uuid,$extends);
        }
        return true;
    }
    /**
     *
     * 获得指定条件下的事件列表
     */
    public  function getAll($userId,$eventId,$limit) {
        $var                 = array('condition'=>"user_id = {$userId} and id > $eventId", 'params'=>array('id'=>$eventId, 'user_id'=>$userId));
        $condition           = $var['condition'];
        $criteria            = new CDbCriteria;
        $criteria->order     = 'id asc';
        $criteria->limit     = $limit;
        $criteria->condition = $condition;
        $rows                = Event::model()->findAll($criteria);
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
        $value["id"]               = $item->id;
        $value["user_id"]          = $item->user_id;
        $value["user_device_id"]   = $item->user_device_id;
        $value["action"]           = $item->action;
        $value["file_path"]        = $item->file_path;
        $value["context"]          = $item->context;
        $value['created_at']       = $item->created_at;
        $value["event_uuid"]       = $item->event_uuid;
        $value["type"]             = $item->type;
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
     * 根据用户ID删除事件
     * 请注意不用使用设备ID进行删除，比如把Web设备的事件删除后，其它设备就无法消费新产生的事件
     * 因此只有当用户删除的时候，才进行事件表的清理
     */
    public function deleteByUserId($userId){
        $criteria            = new CDbCriteria;
        $criteria->condition = 'user_id = :userId';
        $criteria->params    = array('userId' => $userId);
        Event::model()->deleteAll($criteria);
    }

    /**
     * 批量删除用户事件
     * @param $userIds
     */
    public function deleteByIds($userIds) {
        if($userIds!='' && strlen($userIds)>0){
            Event::model()->deleteAll("user_id in (".$userIds.")");
        }
    }

    /**
     * 根据条件返回所有信息
     */
    public function queryAllbyCondition($e_user_id,$event_id,$limit) {
        return Event::model()->findAll(' user_id = ? AND id < ? limit ? ',array($e_user_id,$event_id,$limit) );
    }

    /**
     * create file
     * @param $userId
     * @param $file
     * @param $deviceId
     * @return array
     */
    public  function createFile($userId,$file,$deviceId) {
        $event                 = new Event();
        $event->user_id        = $userId;
        $event->user_device_id = $deviceId;
        if((int)$file["file_type"]===MiniFile::$TYPE_FILE){
            $event->action     = MiniEvent::$CREATE_FILE;
        }else{
            //TODO include public folder/share folder/
            $event->action     = MiniEvent::$CREATE_FOLDER;
        }
        $event->file_path      = $file["file_path"];
        $context               = $file["file_path"];
        if((int)$file["file_type"]===MiniFile::$TYPE_FILE){
            //get version
            $version = MiniVersion::getInstance()->getVersion($file["version_id"]);
            $versionInfo = array(
                "hash"  => $version["file_signature"],
                "rev"   => (int)$version["id"],
                "bytes" => (int)$file["file_size"],
                "update_time" => (int)$file["file_update_time"],
                "create_time" => (int)$file["file_create_time"]
            );
            $context = serialize($versionInfo);
        }
        $event->context        = $context;
        $event->event_uuid     = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);;
        $event->type           = MiniEvent::$EVENT_COMMON_TYPE;
        $event->save();
        //update file event_uuid
        MiniFile::getInstance()->update(
            $file["id"],
            array(
                "event_uuid"=>$event->event_uuid
            )
        );
    }
    /**
     *   获取当前总的记录数
     */
    public function getTotal($filePath,$time,$userId,$deviceUuid){
        $criteria            = new CDbCriteria;
        $criteria->condition = 'user_id = :userId';
        $criteria->params    = array('userId' => $userId);
        if($time!=="-1"){
            $criteria->addCondition("created_at <=:created_at","and");
            $criteria->params[':created_at']=$time;
        }
        if($deviceUuid!=="-1"){
            $device = MiniUserDevice::getInstance()->getByDeviceUuid($deviceUuid);
            $criteria->addCondition("user_device_id =:user_device_id","and");
            $criteria->params[':user_device_id']=$device["id"];
        }
        if($filePath!==""){
            $criteria->addCondition("file_path like :file_path","and");
            $criteria->params[':file_path']=$filePath;
        }
        return Event::model()->count($criteria);
    }
    /**
     * 根据created_at来获取事件
     */
    public function getByCondition($filePath,$userId,$time,$deviceUuid,$limit,$offset){
        $criteria            = new CDbCriteria;
        $criteria->condition = 'user_id = :userId';
        $criteria->params    = array('userId' => $userId);
        if($time!=="-1"){
            $criteria->addCondition("created_at <=:created_at","and");
            $criteria->params[':created_at'] = $time;
        }
        if($deviceUuid!=="-1"){
            $device = MiniUserDevice::getInstance()->getByDeviceUuid($deviceUuid);
            $criteria->addCondition("user_device_id =:user_device_id","and");
            $criteria->params[':user_device_id']=$device["id"];
        }
        if($filePath!==""){
            $criteria->addCondition("file_path like :file_path","and");
            $criteria->params[':file_path']=$filePath;
        }
        $criteria->order     = '-id';
        $criteria->limit     = $limit;
        $criteria->offset    = $offset;
        $items = Event::model()->findAll($criteria);
        return $this->db2list($items);
    }
}