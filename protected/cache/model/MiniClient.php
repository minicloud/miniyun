<?php
/**
 * 缓存整个Miniyun_Clients表的记录，V1.2.0该类接管所有Miniyun_Clients的操作
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniClient extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.MiniClient";

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
	 * Client按照key逐一放入内存
	*/
	private function getCacheKey($clientId){
		return MiniClient::$CACHE_KEY."_".$clientId;
	}
	/**
	 * 通过db获得记录
	 * @param string $key
	 */
	private function get4Db($clientId){
		$item =  OClients::model()->find("client_id=:client_id",array("client_id"=>$clientId));
		if(isset($item)){
			$value = array();
			$value["id"]             = $item->id;
			$value["user_id"]        = $item->user_id;
			$value["client_name"]    = $item->client_name;
			$value["client_id"]      = $item->client_id;
			$value["client_secret"]  = $item->client_secret;
			$value["redirect_uri"]   = $item->redirect_uri;
			$value["enabled"]        = $item->enabled;
			return  $value;
		}
		return false;
	}
	/**
	 * 根据ClientID获得对象
	 */
	public function getClient($clientId){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->get4Db($clientId);
		}
		$object = $this->get4Db($clientId);
		return $object;
	}
    /**
     * 获得所有app情况
     */
    public function getAll(){
        $disabled = $this->getDisabled('','');//todo
        $normal = $this->getNormal('','');//todo
        $all = $this->getAppList('','');
        $data = array();
        $data['disabled'] = $disabled;
        $data['normal'] = $normal;
        $data['all'] = $all;
        return $data;
    }
    /**
     * 根据id获得对象
     */
    public function getApp($id){
        $item         = OClients::model()->findByPk($id);
        return $this->db2Item($item);
    }
    /**
     * 根据name获得对象
     */
    public function getAppByName($name,$currentPage,$pageSize){
        $criteria                = new CDbCriteria();
        $criteria->condition="client_name like :clientName and id not in (1,3,5,7,8)";
        $criteria->params=array('clientName'=>"%" . $name . "%");
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="-id";
        $items              	=OClients::model()->findAll($criteria);
        $total              	=OClients::model()->count($criteria);
        $data = array();
        $data['list'] = $this->db2list($items);
        $data['total'] = $total;
        return $data;
    }
    /**
	 * 删除应用
	 * @param int $id
	 */
	public function deleteClient($id){
		//更新DB
		$item         = OClients::model()->findByPk($id);
		if(isset($item)){
			$clientId = $item->client_id;
			$item->delete();
			//更新一级缓存
			if($this->hasCache){
				$key  = $this->getCacheKey($clientId);
				$this->deleteCache($key);
			}
		}
	}
	/**
	 * 禁用应用
	 */
	public function diabledClient($id){
		//更新DB
		$item              = OClients::model()->findByPk($id);
		if(isset($item)){
			$clientId      = $item->client_id;
			$item->enabled = 0;
			$item->save();
			//更新一级缓存
			if($this->hasCache){
				$key       = $this->getCacheKey($clientId);
				$this->deleteCache($key);
			}
		}
	}
	/**
	 * 启用应用
	 */
	public function enabledClient($id){
		//更新DB
		$item          = OClients::model()->findByPk($id);
		if(isset($item)){
			$clientId      = $item->client_id;
			$item->enabled = 1;
			$item->save();
			//更新一级缓存
			if($this->hasCache){
				$key      = $this->getCacheKey($clientId);
				$this->deleteCache($key);
			}
		}
	}
    /**
     * 获得applist
     */
    public function getAppList($currentPage,$pageSize){
        $criteria                = new CDbCriteria();
        $criteria->condition="user_id != -1 or id in (2,4,6)";
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="-id";
        $items              	=MClients::model()->findAll($criteria);
        $total              	=MClients::model()->count($criteria);
        $data = array();
        if($total == 0){
            $data['total'] = $total;
            $data['list'] = array();
        }else{
            $data['total'] = $total;
            $data['list'] = $this->db2list($items);
        }
        return $data;
    }
    /**
     * 获得app disabled
     */
    public function getDisabled($currentPage,$pageSize){
        $criteria                = new CDbCriteria();
        $criteria->condition="enabled = 0";
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="-id";
        $items              	=MClients::model()->findAll($criteria);
        $total              	=MClients::model()->count($criteria);
        $data = array();
        if($total == 0){
            $data['total'] = $total;
            $data['list'] = array();
        }else{
            $data['total'] = $total;
            $data['list'] = $this->db2list($items);
        }
        return $data;
    }
    /**
     * 获得app normal
     */
    public function getNormal($currentPage,$pageSize){
        //获取默认的6个
        $criteria                = new CDbCriteria();
        $criteria->condition="enabled = 1 and id not in (1,3,5,7,8)";
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="-id";
        $items              	=MClients::model()->findAll($criteria);
        $total              	=MClients::model()->count($criteria);
        $data = array();
        if($total == 0){
            $data['total'] = $total;
            $data['list'] = array();
        }else{
            $data['total'] = $total;
            $data['list']  = $this->db2list($items);
        }
        return $data;
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
    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                     = array();
        $value["id"]               = $item->id;
        $value["user_id"]          = $item->user_id;
        $value["client_name"]      = $item->client_name;
        $value["client_id"]        = $item->client_id;
        $value["client_secret"]    = $item->client_secret;
        $value["redirect_uri"]     = $item->redirect_uri;
        $value["enabled"]          = $item->enabled;
        $value["description"]      = $item->description;
        $value["created_at"]       = $item->created_at;
        $value["updated_at"]       = $item->updated_at;
        return $value;
    }
    /**
     *
     * 检查app是否已经存在
     */
    private function _validateKeyAndSecret($client_id,$client_secret){
        $app = MClients::model()->find("client_id=? and client_secret=?",array($client_id,$client_secret));
        if(isset($app)){
//            echo ("已经存在相同的key或secret");
            return false;
        }
        return true;
    }
    /**
     * 获得数据库client对象
     */
    public function getModelByClientId($clientId){
        return MClients::model()->find("client_id=:client_id",array("client_id"=>$clientId));
    }
    /**
     * 保存数据
     */
    public function createClient($name,$description,$client_id,$client_secret){
            //基础验证
            if($this->_validateKeyAndSecret($client_id,$client_secret)){
                //存储App数据
                $app = new MClients();
            }
            else{
                $app = $this->getModelByClientId($client_id);
            }
            $user = Yii::app()->session["user"];
            $app["user_id"]               = $user['id'];
            $app["client_name"]           = $name;
            $app["client_id"]             = $client_id;
            $app["client_secret"]         = $client_secret;
            $app["description"]           = $description;
            $app["enabled"]               = 1;
            $app["updated_at"]            = date('Y-m-d H:i:s',time());
            $app["created_at"]            = date('Y-m-d H:i:s',time());
            $app->save();
    }
}
?>