<?php
/**
 * Miniyun 模型
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MEvents extends MModel {
    /**
     * 生成事件列表
     * @param array $event_list 需要上行创建的事件列表
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function CreateEvents($user_id, $user_device_id, $action, $path, $context, $event_uuid, $extends = NULL) {
        $context = str_replace("'", "\\'", $context);
        $sql = "insert into " . DB_PREFIX . "_events(user_id,action,user_device_id,";
        $sql .= "file_path,context,event_uuid,created_at,updated_at";
        //
        // 添加参数hook
        //
        $sql = apply_filters('create_events_key', $sql);
        $sql .= " )values (";
        $sql .= "{$user_id},";
        $sql .= "{$action},";
        $sql .= "{$user_device_id},";
        $sql .= "\"{$path}\",";
        $sql .= "'{$context}',";
        $sql .= "'{$event_uuid}',";
        $sql .= "now(),now()";
        //
        // 添加参数hook
        //
        $var = apply_filters('create_events_value', array('object'=>$sql, 'extends'=>$extends));
        $sql = $var['object'];
        $sql .= ")";
        Yii::trace ( "function: '{CreateEvents}',sql:'{$sql}'" );
        $db_manager = MDbManager::getInstance ();
        $result = $db_manager->insertDb ( $sql );
        return $result;
    }
    
    /**
     * 批量处理生成事件
     * @param int $user_id
     * @param int $user_device_id
     * @param array $file_details
     * @return mixed 创建成功返回true，否则返回false
     * @since 1.0.7
     */
    public static function batchCreateEvents($user_id, $user_device_id, $file_details, $extends= NULL) {
        $driver = Yii::app()->db->driverName;
        $sql = "insert into " . DB_PREFIX . "_events(user_id,action,user_device_id,";
        $sql .= "file_path,context,event_uuid,created_at,updated_at ";
        //
        // 添加参数hook
        //
        $sql = apply_filters('create_events_key', $sql);
        $sql .= ") values ";
        if ($driver == 'sqlite') {
            $sql = "insert into  " . DB_PREFIX . "_events(user_id,action,user_device_id,";
            $sql = apply_filters('create_events_key', $sql);
            $sql .= "file_path,context,event_uuid,created_at,updated_at ";
            $sql .= " ) select ";
        }
        $sql_value  = '';
        $db_manager = MDbManager::getInstance ();
        $len        = strlen($sql);
        $result     = true;
        $index = 0;
        $count = count($file_details);
        foreach ( $file_details as $file_detail ) {
            $context = str_replace("'", "\\'", $file_detail->context);
            $sql_value .= $driver == 'sqlite' ? "" : "(";
            $sql_value .= "{$user_id},";
            $sql_value .= "{$file_detail->event_action},";
            $sql_value .= "{$user_device_id},";
            $sql_value .= "\"{$file_detail->from_path}\",";
            $sql_value .= "'{$context}',";
            $sql_value .= "\"{$file_detail->event_uuid}\",";
            $sql_value .= "now(),now()";
            //
            // 添加参数hook
            //
            $var = apply_filters('create_events_value', array('object'=>$sql_value, 'extends'=>$extends));
            $sql_value = $var['object'];
            $sql_value .= $driver == 'sqlite' ? "" : ")";
            //添加 union all select 或者 ','
            $index++;
            if ($index < $count){
                $sql_value .= $driver == 'sqlite' ? " union all select " : ",";
            }

            if (strlen($sql_value) + $len >= MConst::MAX_SQL_STRING_LENGTH 
            || strlen($sql_value) + $len >= MConst::MAX_SQL_STRING_LENGTH - 1000) {
                $sql_exe = $sql . $sql_value;
                Yii::trace ( "function: '{batchCreateEvents}',sql:'{$sql_exe}'" );
                $result = $db_manager->insertDb ( $sql_exe );
                if ($result == false) {
                    return $result;
                }
                $sql_value = '';
            }
        }
        
        if (!empty($sql_value)) {
            $sql_exe = $sql . $sql_value;
            $result = $db_manager->insertDb ( $sql_exe );
        }
        return $result;
    }
    
    /**
     * 
     * 通过event_uuid查找对应数据
     * @param string $event_uuid
     * @return mixed
     */
    public static function queryEventByEventUUID($event_uuid) {
        $db_manager = MDbManager::getInstance ();
        $sql = "select * from " . DB_PREFIX . "_events where event_uuid='{$event_uuid}'";
        Yii::trace ( "function: '{__FUNCTION__}',sql:'{$sql}'" );
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 
     * 根据条件计数event
     * @param string $condition
     */
    public static function count($condition) {
        $db_manager = MDbManager::getInstance ();
        $sql = "select count(*) as count from " . DB_PREFIX . "_events ";
        $sql .= "WHERE ";
        $sql .= $condition;
        Yii::trace ( "function: '{count}',sql:'{$sql}'" );
        $retval = $db_manager->selectDb ( $sql );
        return $retval [0] ["count"];
    }
    /**
     * 
     * 返回所有符合条件的记录
     * @param string $condition
     */
    public static function findAll($condition) {
        $db_manager = MDbManager::getInstance ();
        $sql = "select * from " . DB_PREFIX . "_events ";
        $sql .= "WHERE ";
        $sql .= $condition;
        Yii::trace ( "function: '{count}',sql:'{$sql}'" );
        return $db_manager->selectDb ( $sql );
    }
}
?>