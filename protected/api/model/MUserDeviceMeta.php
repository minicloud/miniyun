<?php
/**
 * 用户设备模型: 用户设备信息属性
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MUserDeviceMeta extends MModel
{
    
    /**
     * 为设备创建meta信息
     * @param int $user_id
     * @param int $device_id
     * @param string $meta_key
     * @param string $meta_value
     */
    public static function createUserDeviceMeta($user_id, $device_id, $meta_name, $meta_value) {
        $sql = "INSERT INTO " . DB_PREFIX . "_user_devices_metas";
        $sql .= " (user_id,device_id,meta_name,meta_value,created_at,updated_at)";
        $sql .= " VALUES(";
        $sql .= "'{$user_id}','{$device_id}','{$meta_name}','{$meta_value}',now(),now()";
        $sql .= ")";
        Yii::trace("function: '{createFileMeta}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        if ($db_manager->insertDb($sql) === false){
            return false;
        }
        return true;
    }

    /**
     *
     * 根据$user_id,$meta_name查找对应的用户设备
     * @param  int $user_id 用户唯一设备值
     * @param  string $meta_name 返回最终需要执行完的结果
     * @return $db_data 数据库查询结果 只有一条
     */
    public static function queryUserDeviceMetaByUserIDAndKey($device_id, $meta_name) {
        $db = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_user_devices_metas where device_id={$device_id} and meta_name='{$meta_name}'";
        $db_data = $db->selectDb($sql);
        return $db_data;
    }
    
    /**
     * 更新device_neta数据库表中 用户的pc端请求event最小值
     * @param int $user_id
     * @param string $neta_name
     * @param string $meta_value
     */
    public static function updateUserDeviceMetaByKey($device_id,$neta_name,$meta_value) {
        $sql  = "UPDATE " . DB_PREFIX . "_user_devices_metas SET ";
        $sql .= "meta_value='{$meta_value}',updated_at=now() ";
        $sql .= "WHERE device_id={$device_id} AND meta_name='{$neta_name}'";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        if ($db_manager->updateDb($sql) === false){
            return false;
        }
        return true;
    }
    
    /**
     * 如果出现多条数据 都删除
     * @param int $id
     */
    public static function deleteByID($id) {
        $sql  = "DELETE from " . DB_PREFIX . "_user_devices_metas WHERE id={$id}";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        if ($db_manager->deleteDb($sql) === false){
            return false;
        }
        return true;
    }
}
?>