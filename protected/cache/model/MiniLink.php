<?php
/**
 * 缓存miniyun_shareFiles表的记录，V1.2.0该类接管部分miniyun_shareFiles的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniLink extends MiniCache{

    public static $PREVIEW_LINK="preview";
    public static $DIRECT_LINK="direct";

    /**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.link";

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
	 
	private function db2Item($item){
		if(empty($item)) return NULL;
		$value                = array();
		$value["id"]          = $item["id"];
        $value["user_id"]     = $item["user_id"];
        $value["expiry"]      = $item["expiry"];
		$value["share_key"]   = $item["share_key"];
		$value["file_id"]     = $item["file_id"];
		$value["password"]    = $item["password"]; 
		$value["created_at"]  = $item["created_at"]; 
		$value["updated_at"]  = $item["updated_at"]; 
		$value["down_count"]  = $item["down_count"]; 
		return $value;
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
     * 创建外链
     */
    public function create($userId, $fileId){
        $mode = Link::model()->find("user_id=:user_id and file_id=:file_id",
            array("user_id"=>$userId,"file_id"=>$fileId));
        if(!isset($mode)){
            $mode  = new Link();
            //寻找唯一的Key，如果发现系统已经存在，则重新生成一个，直到寻找到
            $shareKey = MiniUtil::randomString(6);
            $link     = $this->getByKey($shareKey);
            while(!empty($link)){
                $shareKey = MiniUtil::randomString(8);
                $link     = $this->getByKey($shareKey);
            }
            $mode->expiry    = -1;
            $mode->password  = "-1";
            $mode->share_key = $shareKey;
        }
        $mode->file_id   = $fileId;
        $mode->user_id   = $userId;
        $mode->save();
        return $this->db2Item($mode);
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
     * 根据Key获得文件外链
     */
    public function getByKey($shareKey){
        $mode = Link::model()->find("share_key=:key",array("key"=>$shareKey));
        return $this->db2Item($mode);
    }
    /**
     * 创建对象
     * @param string $key
     * @param int $fileId
     * @param int $expirys
     * @param string $password
     * @return bool
     */
	public function createWithPassword($key, $fileId, $expirys,$password="-1"){
		$mode = Link::model()->find("share_key=:share_key",array("share_key"=>$key));
		if(!isset($mode)){
			$mode  = new Link();
		}
		$mode->share_key = $key;
		$mode->file_id   = $fileId;
		$mode->expiry    = $expirys;
		$mode->password  = $password;
		$mode->save();
		return true;
	}

    /**
     * 删除外链
     * @param $ids
     */
    public function unlink($ids){
        if(empty($ids)){
            return;
        }
        $files = MiniFile::getInstance()->getFilesByIds($ids);
        // 更新每个元素以及子元素
        foreach ($files as $file) {
            $link = Link::model()->find(array('condition'=>'file_id=:file_id','params'=>array(':file_id'=>$file["id"])));
            if (!empty($link)){
                $link->delete();
            }
        }
    }
    /**
     * 更新系统UserId，针对的是V1.5.0
     * 此前通过fileId关联外链表，在数据库进行更新的时候，把用户Id同步到本表中
     */
    public function updateUserId(){
        $links = Link::model()->findAll();
        foreach($links as $link){
            $fileId = $link["file_id"];
            $file = MiniFile::getInstance()->getById($fileId);
            if(!empty($file)){
                $link->user_id = $file["user_id"];
                $link->save();
            }
        }
    }

    /**
     * 根據id獲取記錄
     */
    public function getById($id){
        $criteria = new CDbCriteria();
        $criteria->condition = "id=".$id;
        $item = Link::model()->find($criteria);
        return $this->db2Item($item);
    }
    /**
     * 根据文件ID获得外链
     * @param int $file_id
     * @return array|null
     */
	public  function getByFileId($file_id){
		$item = Link::model()->find("file_id=:file_id",array("file_id"=>$file_id));
		return $this->db2Item($item);
	}

    /**
     * 更新外链的时间
     * @param int $id
     * @param int $expirys
     * @return bool
     */
	public  function update($id, $expirys){
		$item = Link::model()->findByPk($id);
		if(isset($item)){
			$item->expiry = $expirys;
			$item->save();
		}
		return true;
	}
    /**
     * 文件外链下载次数统计
     * @param $key
     */
    public function downloadCount($key){
    	$item = Link::model()->find("share_key=:share_key",array("share_key"=>$key));
    	if(isset($item)){
    		$count = $item->down_count;
    		if(!isset($ori)){
    			$count = 1;
    		}else{
    			$count = $count+1;
    		}
    		$item->down_count = $count;
    		$item->save();
    	}
    }

    /**获取分页的share数据
     * @param $pageSet
     * @param $pageSize
     * @param $userId
     * @return array
     */
    public function getPageSize($pageSet,$pageSize,$userId){
        $criteria=new CDbCriteria;
        $criteria->select   ='*';
        $criteria->limit    = $pageSize;
        $criteria->offset   = $pageSet;
        $criteria->condition     = "user_id=:userId";
        $criteria->params        = array(':userId'=>$userId);
        $criteria->order    ='updated_at desc';
        $items = Link::model()->findAll($criteria);
        return $this->db2list($items);
    }
    /**统计数据总计
     * @param $userId
     * @return mixed
     */
    public function getCount($userId){
        $criteria=new CDbCriteria;
        $criteria->select    ='*';
        $criteria->condition = "user_id=:userId";
        $criteria->params    = array(':userId'=>$userId);
        $items = Link::model()->count($criteria);
        return $items;
    }
    /**删除本用户的外链
     * @param $userId
     * @param $fileId
     * @return int
     */
    public function deleteById($userId,$fileId){
            $data= Link::model()->deleteAll("file_id=(".$fileId.") and user_id=".$userId);
            return $data;
    }

    /**
     * 设置访问外联
     * @param $key
     * @param $newKey
     * @param $password
     * @param $time
     * @return bool
     */
    public function setAccessPolicy($key,$newKey,$password,$time){
        $share = Link::model()->findAll("share_key=:share_key",array("share_key"=>$newKey));
        if(count($share)>=1){
            return false;
        }
        $link = Link::model()->find("share_key=:share_key",array("share_key"=>$key));
        if(isset($link)){
            $change = false;
            if(!empty($newKey)){
                $link->share_key = $newKey;
                $change = true;
            }
            if(!empty($password)){
                $link->password = md5($password);
                $change = true;
            }
            if($time>0){
                $link->expiry = intval($time);
                $change = true;
            }
            if(empty($newKey) && empty($password) && $time<=0){
                $change = true;
            }
            if($change===true){
                $link->save();
                return true;
            }

        }
        return false;
    }

    /**
     * 检测输入的密码是否跟系统设置一致
     * @param $key
     * @param $password
     * @return bool
     */
    public function checkPassword($key,$password){
        $link = Link::model()->find("share_key=:share_key",array("share_key"=>$key));
        if(isset($link)){
            if($link['password'] === md5($password)){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

}