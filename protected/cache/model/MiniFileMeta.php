<?php
/**
 * 缓存miniyun_file_metas表的记录，V1.2.0该类接管部分miniyun_file_metas的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniFileMeta extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.filemeta";

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
	 * 把数据库值序列化
	 * @param object $item
     * @return Array
	 */
    private function db2Item($item){
        if(empty($item)) return NULL;
		$value                 = array();
		$value["id"]           = $item->id;
		$value["file_path"]    = $item->file_path;
		$value["meta_key"]     = $item->meta_key;
		$value["meta_value"]   = $item->meta_value;
		$value["created_at"]   = $item->created_at;
		$value["updated_at"]   = $item->updated_at; 
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
	 * 根据文件path与Key获得文件的属性
	 * @param string $path
	 * @param string $key
     * @return Array|NULL
	 */
	public function getFileMeta($path,$key){
        if($key==="version"){
            $key="versions";
        }
		$model = $this->getModelByPath($path,$key);
		if($model){
			return  $this->db2Item($model);
		}
		return NULL;
	}
	/**
	 * 根据路径获得权限
	 * @param string $path
	 */
	public function getPermission($path){
		return $this->getMeta($path, "permission");
	}
	/**
	 * 新建Meta
	 */
	public function createFileMeta($file_path,$meta_key,$meta_value){
        if($meta_key==="version"){
            $meta_key="versions";
        }
		$fileMeta              = $this->getModelByPath($file_path, $meta_key);
        if (empty($fileMeta)){
            $fileMeta = new FileMeta();
        }
        $fileMeta["file_path"]  = $file_path;
        $fileMeta["meta_key"]   = $meta_key;
        $fileMeta["meta_value"] = $meta_value;
        return $fileMeta->save();
	}
	/**
	 * 根据路径+Key获得Model对象
	 * @param string $filePath
	 * @param string $key
     * @return ActiveModel
	 */
	private function getModelByPath($filePath,$key){
        if($key==="version"){
            $key="versions";
        }
		return FileMeta::model()->find("file_path=:file_path AND meta_key=:meta_key",array("file_path"=>$filePath,"meta_key"=>$key));
	}
    /**
     * 根据文件路径模糊查找文件
     */
    public function getChildrenFileMetaByPath($filePath,$key){
        if($key==="version"){
            $key="versions";
        }
        $criteria                = new CDbCriteria();
        $criteria->condition     = "meta_key=:meta_key and file_path like :file_path";
        $criteria->params        = array(
            "meta_key"=>$key,
            "file_path"=>$filePath."%"
        );
        $items              	 =FileMeta::model()->findAll($criteria);
        $total              	 =FileMeta::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }
    }
    /**
	 * 更新Meta
	 * @param string $filePath
	 * @param string $key
	 * @param string $value
     * @return bool
	 */
	public function updateFileMeta($filePath,$key, $value){
        if($key==="version"){
            $key="versions";
        }
		$model                 = $this->getModelByPath($filePath, $key);
		if(isset($model)){
			$model->meta_value = $value;
			$model->save();
		}
		return true;
	}
	/**
	 * 批量查询文件的Meta
	 * @param int $key
	 * @param array $files
     * @return array
	 */
	public function getFileMetas($key, $files){
        if($key==="version"){
            $key="versions";
        }
		$value = array();
		foreach ($files as $file){
			$item = $this->getFileMeta($file["file_path"], $key);
			if($item!==NULL){
                $value[] = $item;
			}
		}
		return $value;
	}
	/**
	 * 批量创建Metas
	 * @param array $createArray
	 * @param string $metaKey
     * @return bool
	 */
	public function createFileMetas($createArray, $metaKey){
        if($metaKey==="version"){
            $metaKey="versions";
        }
		 foreach ($createArray as $file_meta) {
		 	 if ($file_meta["is_add"] === false){
		 	 	continue;
		 	 }
		 	 $this->createFileMeta($file_meta["file_path"], $metaKey, $file_meta["meta_value"]);
		 }
		 return true;
	}
    /**
     * 删除meta信息
     * @param $filePath 文件路径
     * @param $key meta_key
     * @return bool
     */
    public function deleteFileMetaByPath($filePath,$key){
        if($key==="version"){
            $key="versions";
        }
        $modal = FileMeta::model()->findAll("file_path=:file_path and meta_key=:meta_key", array(":file_path" => $filePath,":meta_key" => $key));
        if(!empty($modal)){
            foreach($modal as $item){
                $item->delete();
            }
        }
        return true;
    }
    /**
     *清理meta的记录
     * @param $filePath 文件路径
     * @return boolean
     */
    public function cleanFileMetaByPath($filePath){
        $modal = FileMeta::model()->findAll("file_path=:file_path", array(":file_path" => $filePath));
        if(!empty($modal)){
            foreach($modal as $item){
                $item->delete();
            }
        }
        return true;
    }
    /**
     * 设置公共目录权限
     * @param $filePath 文件路径
     * @param $privilege 权限
     * @return array
     */
    public function setPublicPrivilege($filePath,$privilege){
        $meta_key=MConst::PUBLIC_FOLDER;
        $fileMeta              = $this->getModelByPath($filePath, $meta_key);
        if(empty($fileMeta)){
            $fileMeta = new FileMeta();
        }
        $fileMeta["file_path"]  = $filePath;
        $fileMeta["meta_key"]   = $meta_key;
        $fileMeta["meta_value"] = $privilege;
        $fileMeta->save();
        return array('success'=>true);
    }
    /**
     * 根据from_path和meta_key=‘create_id’改file_path
     * @param $fromPath
     * @param $key
     * @param $toPath
     * @param $fileType
     * @return bool
     */
    public function modifyFilePath($fromPath,$key,$toPath,$fileType){
        if($key==="version"){
            $key="versions";
        }
        if($fileType == 0){//文件时候meta信息如下处理
            $criteria                = new CDbCriteria();
            $criteria->condition     = "meta_key=:meta_key and file_path=:file_path";
            $criteria->params        = array(
                "meta_key"=>$key,
                "file_path"=>$fromPath
            ); 
            $item              	 =FileMeta::model()->find($criteria);
            if(!empty($item)){
                $item->file_path = $toPath;
                $item->save();
            } 
            return true;
        }else{//目录时meta信息如下处理
            $fromPathArr = explode('/',$fromPath);
            $toPathArr = explode('/',$toPath);
            $fromPathArrCount = count($fromPathArr);
            $toPathArrCount = count($toPathArr);
            $toFolderName = $toPathArr[$toPathArrCount-1];
            $criteria                = new CDbCriteria();
            $criteria->condition     = "meta_key=:meta_key and file_path like :file_path";
            $criteria->params        = array(
                "meta_key"=>$key,
                "file_path"=>$fromPath.'%'
            );
            $items              	 =FileMeta::model()->findAll($criteria);
            foreach($items as $item){
                $itemPath = $item->file_path;
                $itemPathArr = explode('/',$itemPath);
                $itemPathArr[$fromPathArrCount-1] = $toFolderName;
                $itemPathArrCount = count($itemPathArr);
                $newPath = "";
                for($i=1;$i<$itemPathArrCount;$i++){
                    $newPath .= '/'.$itemPathArr[$i];
                }
                $item->file_path = $newPath;
                $item->save();
            }
            return true;
        }

    }
}