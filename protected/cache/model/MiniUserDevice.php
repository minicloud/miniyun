<?php
/**
 * 缓存miniyun_userDevices表的记录，V1.2.0该类接管所有miniyun_userDevices的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniUserDevice extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY  = "cache.model.UserDevice";
	/**
	 *
	 * Options表deviceId缓存的Key
	 * @var string
	 */
	public static $OPTION_KEY = "user_device_delete_record";

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
     * @param string $id
     * @return string
     */
	private function getCacheKey($id){
		return MiniUserDevice::$CACHE_KEY."_".$id;
	}
	/**
	 * 通过db获得记录
	 * @param $item
     * @return array
	 */
	private function db2Item($item){
		if(!$item) return NULL;
		$value = array();
		$value["id"]                    = $item->id;
		$value["device_id"]             = $item->id;
		$value["user_device_uuid"]      = $item->user_device_uuid;
		$value["user_id"]               = $item->user_id;
		$value["user_device_type"]      = $item->user_device_type;
		$value["user_device_name"]      = $item->user_device_name;
		$value["user_device_info"]      = $item->user_device_info;
        $value["created_at"]            = $item->created_at;
		$value["updated_at"]            = $item->updated_at;
        if(array_key_exists("device_status",$item->metaData->columns)){
            $value["device_status"] = $item["device_status"];
        }
		return  $value;
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
	private function get4Db($id){
		$item = UserDevice::model()->findByPk($id);
		return $this->db2Item($item);
	}

    /**
     * 分页获取所有设备对象
     */
    public function getAllDevices($pageSize,$currentPage){
        $criteria = new CDbCriteria();
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="id desc";
        $items = UserDevice::model()->findAll($criteria);
        return $this->db2List($items);
    }

    /**
     * @param $userId
     * 获取用户所有的设备信息
     */
    public function getUserDevices($userId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id =:user_id";
        $criteria->params    = array('user_id' => $userId);
        $items               = UserDevice::model()->findAll($criteria);
        $total               = UserDevice::model()->count($criteria);
        $data                = array();
        if($total == 0){
            return null;
        }else{
            $data['list']  = $this->db2list($items);
            $data['total'] = $total;
            return $data;
        }
    }
	/**
	 * 根据id获得UserDevice对象
	 * @param $id
     * @return fix
	 */
	public function getUserDevice($id){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->get4Db($id);
		}
		//先判断是否已经缓存，否则进行直接缓存
		$datastr    = $this->get($this->getCacheKey($id));
		if($datastr===false){
			Yii::trace(MiniUserDevice::$CACHE_KEY." set cache userId:".$id,"miniyun.cache1");
			$object = $this->get4Db($id);
			$this->set($this->getCacheKey($id),serialize($object));
		}else{
			Yii::trace(MiniUserDevice::$CACHE_KEY." get cache userId:".$id,"miniyun.cache1");
			$object = unserialize($datastr);
			//补偿，如果返回值为NULL，则重新向DB请求
			if($object===NULL){
				Yii::trace(MiniUserDevice::$CACHE_KEY." set cache userId:".$id,"miniyun.cache1");
				$object = $this->get4Db($id);
				$this->set($this->getCacheKey($id),serialize($object));
			}
		}
		if($object===false) return NULL;
		return $object;
	}
    /**
     * 根据id获得device对象
     * @param string $id
     * @return array
     */
    public function getById($id){
        $item = UserDevice::model()->find('id=:id', array(':id'=>$id));
        return $this->db2Item($item);
    }
	/**
	 * 根据UUID获得device对象
	 * @param string $deviceUuid
     * @return array
	 */
	public function getByUuid($deviceUuid){
		$item = UserDevice::model()->find('user_device_uuid=:user_device_uuid', array(':user_device_uuid'=>$deviceUuid));
		return $this->db2Item($item);
	}
	private function getModel4WebDevice($userId){
		return UserDevice::model()->find('user_id=:user_id and user_device_type=:user_device_type', array(':user_id'=>$userId,':user_device_type'=>MConst::DEVICE_WEB));
	}
	/**
	 * 获得用户的 Web设备
	 * @param int $userId
     * @return array
	 */
	public function getWebDevice($userId){
		$item = $this->getModel4WebDevice($userId);
		return $this->db2Item($item);
	}
    /**
     *获取数据的分页(排除自己的设备)
     */
    public function getDevices($userId,$offset,$limit){
        $criteria            = new CDbCriteria;
        $criteria->condition = "user_id =:user_id and user_device_type!=1";
        $criteria->params    = array('user_id' => $userId);
        $criteria->offset = $offset;
        $criteria->limit = $limit;
        $devices = UserDevice::model()->findAll($criteria);
        return $this->db2list($devices);
    }
    /**
	 * 删除用户的设备
	 * @param int $userId
     *
	 */
	public function deleteDeviceByUser($userId){
		$devices = UserDevice::model()->findAll("user_id=:userId",array("userId"=>$userId));
		$ids     = array();
		foreach ($devices as $device){
			$ids[] = $device->id;
		}
		foreach ($ids as $id) {
			$this->deleteDevice($id);
		}
	}
    public function deleteDeviceByUuid($uuid){
        $device = $this->getByDeviceUuid($uuid);
        if(isset($device)){
            $this->deleteDevice($device["id"]);
        }
    }
	/**
	 *根据ID删除设备
	 * @param $deviceId
     * @return boolean
	 */
	public function deleteDevice($deviceId){
		//删除设备的Meta
		MiniUserDeviceMeta::getInstance()->deleteMeta($deviceId);
		//删除设备的Token
		MiniToken::getInstance()->deleteToken($deviceId);
		if($this->hasCache===true){
			//删除设备的cache
			$this->deleteCache($this->getCacheKey($deviceId));
		}
		//把在线设备清单进行删除
		MiniOnlineDevice::getInstance()->deleteOnlineDevice($deviceId);
		//把设备ID资源暂存到Options表中
		$this->temporary2Option($deviceId);
		//删除设备自己，这里不能修改为sql模式，因为设备ID在删除的时候，自动将ID记录到了Options表中
		$device = UserDevice::model()->findByPk($deviceId);
		if(isset($device)){
			$device->delete();
		}
		return true;
	}
	/**
	 * 创建设备对象 
	 */
	public function create($user_id,$device_uuid,$device_type,$device_info,$device_name){
		$device                   = new UserDevice();
		$device->user_id          = $user_id;
		$device->user_device_uuid = $device_uuid;
		$device->user_device_type = $device_type;
		$device->user_device_info = $device_info;
		$device->user_device_name = $device_name; 
		$device->save();
		return $this->db2Item($device);
	}
	/**
	 * 把设备ID转移到Options表中，所有执行Detlete操作的动作都需调用该接口
	 */
	public function temporary2Option($id){
		$ids   = array();
		$ids[] = $id;
		//把ID记录到Options表中，以便授权控制。如果是删除记录，先从Options表补充ID到UserDevices
		$value = MiniOption::getInstance()->getOptionValue(MiniUserDevice::$OPTION_KEY);
		if (isset($value)){
			if(!(empty($value) || strlen(trim($value))==0)){
				$oriIds = explode(",", $value);
				$ids    = $this->mergeIds(array_merge($oriIds,$ids));
			}
		}
		MiniOption::getInstance()->setOptionValue(MiniUserDevice::$OPTION_KEY,implode(",",$ids));
	}
	/**
	 * 获得被删除的ID，如果没有删除的记录，则返回空值
	 */
	public function getTemporaryId(){
		$key        = MiniUserDevice::$OPTION_KEY;
		$value      = MiniOption::getInstance()->getOptionValue($key);
		$id         = "";
		if (isset($value)){
			$ids    = explode(",",$value);
			if(count($ids)>0){
				$id       = $ids[0];
				unset($ids[0]);
				$newValue = implode(",", $ids);
				MiniOption::getInstance()->setOptionValue($key, $newValue);
			}
		}
		return $id;
	}
    /**
     * 获得当前用户总设备数
     */
    public function count($userId){
       $criteria            = new CDbCriteria;
       $criteria->condition = "user_id =:user_id and user_device_type!=1";
       $criteria->params    = array('user_id' => $userId);
       return UserDevice::model()->count($criteria);
    }
    /**
	 * 合并主键，剔除重复的,避免因为主键而导致的系统紊乱
	 * @param array $oirIds
	 * @return array
	 */
	private function mergeIds($oirIds){
		$newIds = array();
		foreach ($oirIds as $key=>$id){
			$exist = false;
			foreach($newIds as $key1=>$id1){
				if($id==$id1){
					$exist = true;
					break;
				}
			}
			if($exist==false){
				array_push($newIds, $id);
			}
		}
		return $newIds;
	}
    /**
     * 根据device_type获得用户的第一个设备数据
     * @param $userId 用户ID
     * @param $deviceType 设备类型
     * @return array
     */
    public function getFirstByDeviceTypeAndDeviceName($userId,$deviceType){
        $criteria            = new CDbCriteria;
        $criteria->condition = 'user_id = :user_id and user_device_type=:user_device_type';
        $criteria->params    = array(':user_id'=>$userId,":user_device_type"=>$deviceType);
        $item = UserDevice::model()->find($criteria);
        return $this->db2Item($item);
    }
    /**
     * 根据device_type获得数据
     */
    public function getByDeviceUuid($deviceUuid){
        $criteria            = new CDbCriteria;
        $criteria->condition = 'user_device_uuid = :user_device_uuid';
        $criteria->params    = array('user_device_uuid'=>$deviceUuid);
        $item = UserDevice::model()->find($criteria);
        return $this->db2Item($item);
    }

    /**
     * 更新device的最后修改时间
     * @param $deviceId
     */
    public function  updateLastModifyTime($deviceId){
        $device = UserDevice::model()->findByPk($deviceId);
        $device->updated_at = date("Y-m-d H:i:s",time());
        $device->save();
    }
    /**
     * 根据设备类型查找设备
     * 分页获取数据
     */
    public function getDeviceByType($deviceType,$pageSize,$currentPage){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "user_device_type=:user_device_type";
        $criteria->params        = array(
            "user_device_type"=>$deviceType
        );
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="id desc";
        $items              	 = UserDevice::model()->findAll($criteria);
        $total              	 = UserDevice::model()->count($criteria);
        $data                    = array();
        if($total == 0){
            return null;
        }else{
            $data['list']  = $this->db2list($items);
            $data['total'] = $total;
            return $data;
        }
    }
}