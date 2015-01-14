<?php
/**
 * 缓存miniyun_file_versions表的记录，V1.2.0该类接管miniyun_file_versions的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniVersion extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.version";

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
	 */
	private function getCacheKey($id){
		return MiniVersion::$CACHE_KEY."_".$id;
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
		if(!isset($item)) return NULL;
		$value                   = array();
		$value["id"]             = $item->id;
		$value["file_signature"] = $item->file_signature;
		$value["file_size"]      = $item->file_size;
		$value["block_ids"]      = $item->block_ids;
		$value["ref_count"]      = $item->ref_count;
		$value["mime_type"]      = $item->mime_type;
		$value["updated_at"]      = $item->updated_at;
		$value["createTime"]      = strtotime($item->created_at);
		return  $value;
	}
	/**
	 * 通过id获得记录
	 * @param string $id
     * @return array
	 */
	private function get4DbById($id){
		$item =  FileVersion::model()->find("id=:id",array("id"=>$id));
		return $this->db2Item($item);
	}
	/**
	 * 通过signature获得记录
	 * @param string $signature
     * @return array
	 */
	private function get4DbBySignature($signature){
		$item =  FileVersion::model()->find("file_signature=:signature",array("signature"=>$signature));
		return $this->db2Item($item);
	}
	/**
	 * 根据id获得version完整信息
	 * @param $id
     * @return array
	 */
	public function getVersion($id){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->get4DbById($id);
		}
		$key = $this->getCacheKey($id);
		//先判断是否已经缓存，否则进行直接缓存
		$dataStr    = $this->get($key);
		if($dataStr===false){
			Yii::trace(MiniVersion::$CACHE_KEY." set cache versionId:".$id,"miniyun.cache1");
			$object = $this->get4DbById($id);
			$this->set($key,serialize($object));
		}else{
			Yii::trace(MiniVersion::$CACHE_KEY." get cache versionId:".$id,"miniyun.cache1");
			$object = unserialize($dataStr);
		}
		if($object===false) return NULL;
		return $object;
	}
	/**
	 * 根据signature获得Version完整信息
	 * @param $signature
     * @return array
	 */
	public function getBySignature($signature){
		if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
			return $this->get4DbBySignature($signature);
		}
		$key      = $this->getCacheKey($signature);
		//先判断是否已经缓存，否则进行直接缓存
		$dataString  = $this->get($key);
		if($dataString===false){
			Yii::trace(MiniVersion::$CACHE_KEY." set cache signature:".$signature,"miniyun.cache1");
			$object = $this->get4DbBySignature($signature);
			$this->set($key,serialize($object));
		}else{
			Yii::trace(MiniVersion::$CACHE_KEY." get cache signature:".$signature,"miniyun.cache1");
			$object = unserialize($dataString);
		}
		if($object===false) return NULL;
		return $object;
	}
	
	/**
	 * 创建文件版本
	 * @param string $signature
	 * @param int $size
     * @param string $mime_type
     * @return array
	 */
	public function create($signature, $size, $mime_type) {
	    $fileVersion                 = new FileVersion();
        $fileVersion->file_signature = $signature;
        $fileVersion->file_size      = $size;
        $fileVersion->block_ids      = 0;
        $fileVersion->ref_count      = 0;
        $fileVersion->mime_type      = $mime_type;
        $fileVersion->save();
        $item                        = $this->db2Item($fileVersion);
	    $key                         = $this->getCacheKey($signature);
	    $this->set($key,serialize($item));
	    return $item;
	}
	
	/**
	 *
	 * data server 增加需要的返回值
	 *
	 */
	public function updateVersionDataBySignature($signature, $data) {
	    
	    $object         =  FileVersion::model()->find("file_signature=:signature",array("signature"=>$signature));
	    if (empty($object)) {
	        return False;
	    }
	    $object['data'] = $data;
	    $object->save();
	    
	    $key            = $this->getCacheKey($signature);
	    $this->set($key,serialize($object));
	    return True;
	}
	/**
	 * 更新版本引用次数
	 */
 	public function updateRefCount($id, $add = true) {
    	$this->updateRefCountByIds(array($id),$add);
    	return true;
    }
 	/**
     * 更新文件引用次数
     */
	public function updateRefCountByIds($ids, $add = true) {
		$criteria            = new CDbCriteria();
		$criteria->addInCondition("id", $ids); 
		$attributes 		= array(
							'ref_count'=>new CDbExpression("ref_count - 1"),
							);
		if ($add) {
			$attributes 	= array(
							'ref_count'=>new CDbExpression("ref_count + 1"),
							);
		}
		FileVersion::model()->updateAll($attributes,$criteria);   
    }
    /**
     * 获得系统总的文件开销
     * 这里是硬盘实际开销
     */
    public function getTotalSize(){
        $criteria            = new CDbCriteria;
        $criteria->select    = 'sum(file_size) as usedSize';
        $row                 = FileVersion::model()->find($criteria);
        if(isset($row)){
            $usedSize        = $row['usedSize'];
            if(!isset($usedSize)){
                $usedSize    = 0;
            }
        }else{
            $usedSize        = 0;
        }
        return $usedSize;
    }

    /**
     * 获得迷你文档需要转换的文件列表，每次返回最多10条记录
     * doc_convert_status:-1
     * mime_type:
     * application/msword
     * application/mspowerpoint
     * application/msexcel
     * application/pdf
     */
    public function getReadyDocConvertList(){

        $mimeTypeList = array("application/mspowerpoint","application/msword","application/msexcel","application/pdf");
        foreach ($mimeTypeList as $mimeType){
            $criteria                = new CDbCriteria();
            $criteria->condition     = "doc_convert_status=0  and  mime_type=:mime_type";
            $criteria->limit         = 10;
            $criteria->offset        = 0;
            $criteria->params        = array(
                "mime_type"=>$mimeType
            );
            $list = FileVersion::model()->findAll($criteria);
            if(count($list)>0){
                return $this->db2list($list);
            }
        }
        return NULL;

    }
    public function getConvertListByType($type,$limit=10,$offset=0){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "doc_convert_status=0  and  mime_type=:mime_type";
        $criteria->limit         = $limit;
        $criteria->offset        = $offset;
        $criteria->params        = array(
            "mime_type"=>$type
        );
        $list = FileVersion::model()->findAll($criteria);
        return $this->db2list($list);
    }
    /**
     * 更改文档转换状态
     * doc_convert_status:-1 表示转换失败
     * doc_convert_status:0 表示尚未转换
     * doc_convert_status:1 表示正在转换
     * doc_convert_status:2 表示转换成功
     * @param $hash 文件内容hash值
     * @param $status 文件转换状态值
     * @return boolean
     */
    public function updateDocConvertStatus($hash,$status){
		$version         =  FileVersion::model()->find("file_signature=:signature",array("signature"=>$hash));
        if(isset($version)){
            $version["doc_convert_status"] = $status;
            $version->save();
            return true;
        }
        return false;

    }
    /**
     * 删除记录
     * @param $id
     */
    public function deleteById($id){
        $item  = FileVersion::model()->findByPk($id);
        if(isset($item)){
            MiniVersionMeta::getInstance()->deleteByVersionId($id);
            $item->delete();
        }
    }
    /**
     * 获得要删除的记录
     * @param $limit
     * @return array
     */
    public function getCleanFiles($limit=100){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "ref_count<=0";
        $criteria->limit         = $limit;
        $criteria->offset        = 0;
        $list = FileVersion::model()->findAll($criteria);
        if(count($list)>0){
            return $this->db2list($list);
        }
        return NULL;
    }
}