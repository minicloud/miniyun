<?php
/**
 * 用户模型: 对应用户相关信息属性
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MUser extends MModel
{
    /**
     *
     * 初始化用户数据, 以便之后存入缓存
     * @param array $user 用户对象
     * @return mixed $value 返回初始化对象的结果
     */
    public static function initUser($user) {
        if ($user === null) {
            return null;
        }
        $userObj = new MUser();
        $userObj->assembleUser($user);
        return $userObj;
    }

    /**
     *
     * 根据用户id查询用户信息
     * @param int $id 用户id
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function queryUsersByIdN($id) {
        $db = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_users where id=$id";
        $db_user = $db->selectDb($sql);

        if (empty($db_user)){
            return false;
        }
        return $db_user[0];
    }
    
    
    /**
     *
     * 根据用户id查询用户信息
     * @param int $id 用户id
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function queryUsersByID($id) {
        $db = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_users where id=$id";
        $db_user = $db->selectDb($sql);

        if (empty($db_user)){
            return false;
        }
        return self::assembleUser($db_user[0]);
    }


    /**
     * 处理验证用户是否存在
     * @param string $user_name 用户的名称
     */
    public function queryUserName($user_name) {
        $db = MDbManager::getInstance();
        // 用户名称登录
        $sql = "select * from ".DB_PREFIX."_users where user_name=\"{$user_name}\"";

        Yii::trace("queryUserName:".$sql);
        $db_user = $db->selectDb($sql);

        if (empty($db_user)){
            return false;
        }
        return $db_user[0];
    }

    /**
     * 处理验证用户是否存在
     * @param string $user_name 用户的名称
     * @param string $password 用户密码
     */
    public function queryUserByUserName($user_name, $password) {
        $db = MDbManager::getInstance();
        // 用户名称登录
        $sql = "select * from ".DB_PREFIX."_users where user_name=\"{$user_name}\" and user_pass=\"{$password}\"";

        Yii::trace("queryUserByUserName:".$sql);
        $db_user = $db->selectDb($sql);

        if (empty($db_user)){
            return false;
        }
        return self::assembleUser($db_user[0]);
    }
    /**
     *
     * 根据查询出的用户信息组装user对象
     * @param int $user_id 用户id
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function assembleUser($user_data){
        $this->id             = $user_data["id"];
        $this->user_id        = $user_data["id"];
        $this->user_uuid      = $user_data["user_uuid"];
        $this->user_name      = $user_data["user_name"];
        $this->user_pass      = $user_data["user_pass"];
        $this->user_status    = $user_data["user_status"];
        $this->created_at     = $user_data["created_at"];
        $this->updated_at     = $user_data["updated_at"];
        //填充用户当前空间，与当前使用空间
        $db = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_user_metas where user_id={$this->user_id} and meta_key in('space','phone','email','nick')";
        $items = $db->selectDb($sql);

        $this->nick  = $this->user_name;//用户昵称
        $this->phone = "";//用户电话
        $this->email = "";//用户邮件
        $this->space = FALSE;
        foreach($items as $index=>$item){
        	$value = $item["meta_value"];
            if($item["meta_key"]=="space"){
                $this->space=doubleval($value)*1024*1024;
            }
            if($item["meta_key"]=="nick"&&strlen(trim($value))>0){
                $this->nick=$value;
            }
            if($item["meta_key"]=="phone"){
                $this->phone=$value;
            }
            if($item["meta_key"]=="email"){
                $this->email=$value;
            }
        }

        //查询用户的默认空间
        if ($this->space === FALSE){
            $this->space = MUtils::defaultTotalSize();
        }
        
        $this->usedSpace = $this->getUsedSpaceById($this->user_id);
        return $this;
    }
    
    // 获取用户使用的空间大小
    public function getUsedSpaceById($id) {
        $usedSpace = 0;
        $used  =  UserFile::model()->getUsedSpace($id);
        if (count($used) > 0){
            $usedSpace = $used[0]["usedSpace"];
        }
        return $usedSpace;
    }
}
?>