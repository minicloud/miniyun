<?php
/**
 * 对应数据库$prefix$_user_metas
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MUserMetas extends MModel
{
    /**
     *
     * 初始化用户数据, 以便之后存入缓存
     * @param array $user 用户对象
     * @return mixed $value 返回初始化对象的结果
     */
    public static function initUserMeta($userMeta) {
        if ($userMeta === null) {
            return null;
        }
        $user_meta_info = new MUserMetas();
        $user_meta_info->id             = $userMeta["id"];
        $user_meta_info->user_id        = $userMeta["user_id"];
        $user_meta_info->meta_key       = $userMeta["meta_key"];
        $user_meta_info->meta_value     = $userMeta["meta_value"];
        $user_meta_info->created_at     = $userMeta["created_at"];
        $user_meta_info->updated_at     = $userMeta["updated_at"];
        return $user_info;
    }
    
    /**
     * 更新用户当前使用空间大小
     * @param int $user_id
     * @param int $size
     */
    public static function updateMetaByKey($user_id,$key,$value) {
        $sql  = "UPDATE " . DB_PREFIX . "_user_metas SET ";
        $sql .= "meta_value=\"$value\" ";
        $sql .= "WHERE user_id=$user_id AND meta_key=\"$key\"";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        return $db_manager->updateDb($sql);
    }
    
    /**
     * 
     * 根据meta_id查询用户meta信息
     * @param int $id 用户meta_id
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function queryUserMetaByID($id) {
        $db = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_user_metas where id=$id";
        return $db->selectDb($sql);
    }
    /**
     * 根据meta_key查询user_meta信息
     * @param int $user_id
     * @param string $key
     */
    public static function queryMetaByKey($user_id, $key) {
        $sql  = "SELECT * FROM " . DB_PREFIX . "_user_metas WHERE ";
        $sql .= "user_id=$user_id AND meta_key=\"$key\"";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        return $db_manager->selectDb($sql);
    }
    
    
    public function queryUserByPhone($phone) {
        $db = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_user_metas where meta_key=\"phone\" and meta_value=\"{$phone}\"";

        Yii::trace("queryUserByPhone:".$sql);
        $db_data =  $db->selectDb($sql);
        
        if (empty($db_data)){
            return false;
        }
        return self::assembleUserMeta($db_data[0]);
    }
    
    /**
     * 根据email查询
     * @param string $user_name 用户手机号或者邮箱
     * @param string $password 用户密码
     */
    public function queryUserByEmail($email) {
        $db = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_user_metas where meta_key=\"email\" and meta_value=\"{$email}\"";

        Yii::trace("queryUserByEmail:".$sql);
        $db_data =  $db->selectDb($sql);
        
        if (empty($db_data)){
            return false;
        }
        return self::assembleUserMeta($db_data[0]);
    }
    
    /**
     * 根据meta的key与value查询用户meta信息
     * @param string $key   meta的键
     * @param string $value meta的值
     */
    public function queryMeta($key, $value) {
        $db = MDbManager::getInstance();
        // 用户名称登录
        $sql = "select * from ".DB_PREFIX."_user_metas where meta_key=\"{$key}\" and meta_value=\"{$value}\"";
        
        Yii::trace("queryMeta:".$sql);
        $db_data =  $db->selectDb($sql);
        
        if (empty($db_data)){
            return false;
        }
        return self::assembleUserMeta($db_data[0]);
    }
    
    
    /**
     * 根据用户的id与meta的key查询用户meta信息
     * @param string $user_id  用户的唯一标示
     * @param string $key      meta的键
     */
    public function queryMetaByIDAndKey($user_id, $key) {
        $db = MDbManager::getInstance();
        // 用户名称登录
        $sql = "select * from ".DB_PREFIX."_user_metas where user_id=$user_id and meta_key=\"{$key}\"";
        
        Yii::trace("queryMetaByIDAndKey:".$sql);
        $db_data =  $db->selectDb($sql);
        
        if (empty($db_data)){
            return false;
        }
        return self::assembleUserMeta($db_data[0]);
    }
    
    
    /**
     * 
     * 添加oauth 对应的token, secret到数据库
     * @param int $user_id 用户id
     * @param int $device_uuid 用户设备
     * @param string $token
     * @param string $secret
     * @return mixed $value 返回最终true/false
     * 注意： 由于需要每台设备对应不同的token,secret，所以privilege_uuid需要加上device_uuid
     */
    public function addUserMetas($user_id, $device_uuid, $token, $secret) {
        
    }

    
    /**
     *
     * 根据查询出的用户信息组装user对象
     * @param int $user_id 用户id
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function assembleUserMeta($db_data){
        $this->id             = $db_data["id"];
        $this->user_id        = $db_data["user_id"];
        $this->meta_key       = $db_data["meta_key"];
        $this->meta_value     = $db_data["meta_value"];
        $this->created_at     = $db_data["created_at"];
        $this->updated_at     = $db_data["updated_at"];
        return $this;
    }
}
?>