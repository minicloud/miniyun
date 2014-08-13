<?php
/**
 * 用戶設備的Model
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserDevice extends CMiniyunModel
{
	public $maxId;
	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
                'id'               => 'ID',
                'user_id'          => '用户ID',
                'user_device_uuid' => '设备编号',
                'user_device_type' => '设备类型',
                'user_device_name' => '设备名称',
                'user_device_info' => '设备详情',
                'created_at'       => '创建时间',
                'updated_at'       => '修改时间',
		);
	}
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	public function tableName()
	{
		return Yii::app()->params['tablePrefix'].'user_devices';
	}
	/**
	 * 存储前设置ID，因为这里要重用被删除的ID
	 * @see CActiveRecord::beforeSave()
	 */
	public function beforeSave()
	{
		if(parent::beforeSave()){ 
			if($this->isNewRecord){//如果是添加记录，而且Options表存在上次被删除的用户ID，则重复使用
				$currentId    = MiniUserDevice::getInstance()->getTemporaryId();
				if(!empty($currentId)){
					$this->id = $currentId;
				}
			}
			return true;
		}
		return false;
	}
	/**
	 *
	 * 删除用户设备信息
	 */
	public function  deleteUserDevice($userIds){
		if($userIds!='' && strlen($userIds)>0){
			//删除设备token信息
			$data      = $this->findAll("user_id in(".$userIds.")");
			$deviceIds = $this->getIds($data);
			//把设备ID转移到Options表中
			if(!empty($deviceIds)){
				$this->delete2Option(explode(",", $deviceIds));
			}
			//删除设备出现的token信息
			$serverToken = new MTokens();
			$serverToken->deleteServerToken($deviceIds);
			//删除设备元数据
			$userDeviceMeta = new UserDeviceMeta();
			$userDeviceMeta->deleteUserDeviceMeta($userIds);
			//删除设备自身
			$this->deleteAll("user_id in(".$userIds.")");
		}
	}
	
	/**
	 *
	 * 获取用户的设备总数
	 *
	 * @since 1.0.7
	 */
	public function countUserDevice($user_id){
		return $this->count('user_id=:user_id', array(':user_id'=>$user_id));
	}

	/**
	 *
	 * 获取系统设备总数
	 *
	 * @since 1.0.7
	 */
	public function countTotalDevice(){
		return $this->count();
	}
	/**
	 * 根据用户id和设备类型获取设备
	 *
	 * @param int $userId
	 * @param int $type
	 */
	public function findByUserIdAndType($userId,$type) {
		return $this->find("user_id={$userId} and user_device_type=$type");
	}
}