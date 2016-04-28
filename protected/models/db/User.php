<?php
/**
 * 用戶的Model
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class User extends CMiniyunModel
{
	public $maxId;
    public $minCreatedAt;
    public $maxUpdatedAt;
	/**
	 * Returns the static model of the specified AR class.
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return Yii::app()->params['tablePrefix'].'users';
	}

	public function relations()
	{
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
            'user_name' => '用户名',
            'user_pass' => '用户密码',
            'password_confirm' => '确认密码',
            'user_status' => '用户状态',
            'created_at' => '创建时间',
		);
	}
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{

		$criteria=new CDbCriteria;

		$criteria->compare('user_uuid',$this->user_uuid,true);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('user_pass',$this->user_pass,true);
		$criteria->compare('user_status',$this->user_status);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('updated_at',$this->updated_at,true);

		return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
		));
	}
	/**
	 * 验证账号与密码.
	 */
	public function getByUserAndPasswd($username,$passwd){
		$user = $this->find("user_name=? and user_pass=? and user_status=1",array($username,md5($passwd)));
		return $user;
	} 
	/**
	 *存储前设置ID，因为这里要重用被删除的ID
	 * @see CActiveRecord::beforeSave()
	 */
	public function beforeSave()
	{
		if(parent::beforeSave()){
			if(!isset($this->user_status))
			{
				$this->user_status = 1;
			}
			if($this->isNewRecord){//如果是添加记录，而且Options表存在上次被删除的用户ID，则重复使用
				$currentId         = MiniUser::getInstance()->getTemporaryId();
				if(!empty($currentId)){
					$this->id      = $currentId;
				}
			}
			return true;
		}
		return false;
	}
	
	/**
	 *
	 * 获得属于管理员的总数
	 * @since 1.0.3
	 */
	public function adminCount(){
		$count = User::model()->count("role = 9");
		return $count;
	}

	/**
	 *
	 * 获得冻结用户的总数
	 */
	public function disabledCount(){
		return $this->count("user_status=0");
	}
	/**
	 *
	 * 冻结用户
	 */
	public function disabledUsers($userIds){
		$ids = explode(",", $userIds);
		foreach ($ids as $id){
			MiniUser::getInstance()->disableUser($id);
		}
	}
	/**
	 *
	 * 解冻用户
	 */
	public function enabledUsers($userIds){
		$ids = explode(",", $userIds);
		foreach ($ids as $id){
			MiniUser::getInstance()->enableUser($id);
		}
	}
	/**
	 *
	 * 赋予用户角色
	 */
	public function changeRole($userIds,$isAdmin){
		if($userIds!='' && strlen($userIds)>0){
			$idsArray = explode(",",$userIds);
			//查出admin 用户信息
			$userInfo = $this->find('user_name=:user_name',array(':user_name'=>'admin'));
			foreach($idsArray as $index=>$userId){
				//如果是amdin账号id则不修改角色信息
				if ($userInfo['id'] == $userId){
					continue;
				}
				$userMeta = new UserMeta();
				$item = $userMeta->find("meta_key='is_admin' and user_id=".$userId);
				if(isset($item)){
					$userMeta = $item;
				}else{
					$userMeta["meta_key"]="is_admin";
					$userMeta["user_id"]=$userId;
					$userMeta["created_at"]=date("Y-m-d H:i:s");
					$userMeta["updated_at"]=date("Y-m-d H:i:s");
				}
				$userMeta["meta_value"]=$isAdmin?"1":"0";
				$userMeta->save();
			}
		}
	}
	/**
	 *
	 * 删除用户相关信息
	 * @userIds 用户列表{1,2,3,4,5}这样的格式
	 */
	public function deleteUsers($userIds){
		if($userIds !='' && strlen($userIds) > 0) {
			$ids = explode(',', $userIds);
			$userFile = new UserFile();
			foreach($ids as $id) {
				// 删除用户共享文件
				$userFile->deleteSharedFolders($id);
				//删除所有标签信息
				Tag::model()->deleteUserAllTag($id);
				//删除我的最爱文件
				FileStar::model()->deleteUserAllStar($id);
			} 
			//删除用户的文件信息
			$userFile->deleteUserFile($userIds);
            //删除用户的群组部门关系
            MiniUserGroupRelation::getInstance()->deleteUserRelation($userIds);
			//删除用户的事件信息
            MiniEvent::getInstance()->deleteByIds($userIds);
			//删除用户Meta以及用户自己
			foreach($ids as $id) {
				//删除用户自身
				MiniUser::getInstance()->deleteUser($id);
			}
		}
	}
	
	 
}