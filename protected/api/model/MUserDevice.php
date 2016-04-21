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

class MUserDevice extends MModel
{

	/**
	 *
	 * 根据device_uuid查找用户对应设备
	 * @param string $device_uuid 用户唯一设备值
	 * @return mixed $value 返回最终需要执行完的结果
	 */
	public function queryUserDeviceByDeviceUUID($device_uuid) {
		$companyId = $_SESSION['company_id'];
		$db = MDbManager::getInstance();
		$sql = "select * from ".DB_PREFIX."_user_devices where user_device_uuid=\"{$device_uuid}\" and company_id={$companyId}";
		$db_data = $db->selectDb($sql);

		if (empty($db_data)){
			return false;
		}
		return self::assembleDevice($db_data[0]);
	}

	/**
	 *
	 * @param $user_id
	 * @param $device_uuid
	 */
	public static function queryUserDeviceByDeviceUUIDAndUserID($user_id, $device_uuid) {
		$companyId = $_SESSION['company_id'];
		$db = MDbManager::getInstance();
		$sql = "select * from " . DB_PREFIX . "_user_devices where user_id = {$user_id} " . "and user_device_uuid = \"{$device_uuid}\" and company_id={$companyId}";
		$db_data = $db->selectDb($sql);

		return $db_data;
	}

	/**
	 *
	 * 根据id查找用户对应设备
	 * @param string $id 用户设备的id号
	 * @return object $device 返回用户的设备信息
	 */
	public function queryUserDeviceById($id) {
		$companyId = $_SESSION['company_id'];
		$db = MDbManager::getInstance();
		$sql = "select * from ".DB_PREFIX."_user_devices where id=$id and company_id={$companyId}";
		$db_data = $db->selectDb($sql);

		if (empty($db_data)){
			return false;
		}
		return self::assembleDevice($db_data[0]);
	}

	/**
	 *
	 * Enter 添加用户设备
	 * @param int $user_id 用户id
	 * @param string $device_uuid 设备唯一值
	 * @param string $device_type 设备类型
	 * @param string $device_info 设备信息
	 * @param string $name 设备名称
	 * @return mixed $value 返回true/false
	 */
	public function addUserDevices($user_id, $device_uuid, $device_type, $device_info, $device_name) {
		$companyId = $_SESSION['company_id'];
		$key        = "user_device_delete_record";
		$db_manager = MDbManager::getInstance();
		$value      = MiniOption::getInstance()->getOptionValue($key);
		$id         = "";
		if (isset($value)){
			$ids   = explode(",",$value);
			if(count($ids)>0){
				$id       = $ids[0];
				unset($ids[0]);
				$newValue = implode(",", $ids);
				MiniOption::getInstance()->setOptionValue($key,$newValue);
			}
		}
		if($id==""){
			$created_at = $updated_at = date("Y-m-d H:i:s", time());
			$sql = "INSERT INTO ".DB_PREFIX."_user_devices(user_id, user_device_type, user_device_name, user_device_info, user_device_uuid, created_at, updated_at,company_id)".
               " VALUES ($user_id, $device_type, \"{$device_name}\", \"{$device_info}\", \"{$device_uuid}\", \"{$created_at}\", \"{$updated_at}\"),{$companyId}";
		}else{
			$created_at = $updated_at = date("Y-m-d H:i:s", time());
			$sql = "INSERT INTO ".DB_PREFIX."_user_devices(id,user_id, user_device_type, user_device_name, user_device_info, user_device_uuid, created_at, updated_at,company_id)".
               " VALUES ($id,$user_id, $device_type, \"{$device_name}\", \"{$device_info}\", \"{$device_uuid}\", \"{$created_at}\", \"{$updated_at}\",$companyId)";
		}
		try {
			$db_manager->insertDb($sql);
		}
		catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * 根据查询出的设备信息组装device对象
	 * @param int $db_data 查询出的device信息
	 * @return mixed $value 返回最终需要执行完的结果
	 */
	private function assembleDevice($db_data){
		$this->id                  = $db_data["id"];
		$this->device_id           = $db_data["id"];
		$this->user_device_uuid    = $db_data["user_device_uuid"];
		$this->user_id             = $db_data["user_id"];
		$this->user_device_type    = $db_data["user_device_type"];
		$this->user_device_name    = $db_data["user_device_name"];
		$this->user_device_info    = $db_data["user_device_info"];
		$this->created_at          = $db_data["created_at"];
		$this->updated_at          = $db_data["updated_at"];

		return $this;
	}

	/**
	 *
	 * 根据设备id 删除用户设备记录
	 * @param $device_id 设备id
	 */
	public static function deleteUserDeviceById($device_id) {
		$companyId = $_SESSION['company_id'];
		$db_manager = MDbManager::getInstance();
		$sql = "delete from " . DB_PREFIX . "_user_devices where id = {$device_id} and company_id={$companyId}";
		$db_manager->deleteDb($sql);
		$sql = "delete from " . DB_PREFIX . "_server_token where ost_usa_id_ref = {$device_id} and company_id={$companyId}";
		$db_manager->deleteDb($sql);
	}
}
?>