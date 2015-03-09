<?php
/**
 * 缓存miniyun_files表的记录，V1.2.0该类接管部分miniyun_files的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class MiniFile extends MiniCache{

    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.file";
    /**
     * root folder id
     * @var int
     */
    public static $ROOT_FOLDER_ID = 0;
    /**
     * file type
     * @var int
     */
    public static $TYPE_FILE = 0;
    /**
     * common folder
     * @var int
     */
    public static $TYPE_COMMON_FOLDER = 1;
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
     * 把数据库值序列化
     * @param array $items
     * @return array
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
        $value["file_type"]        = $item->file_type;
        $value["parent_file_id"]   = $item->parent_file_id;
        $value["file_create_time"] = $item->file_create_time;
        $value["file_update_time"] = $item->file_update_time;
        $value["file_name"]        = $item->file_name;
        $value["version_id"]       = $item->version_id;
        $value["file_size"]        = $item->file_size;
        $value["file_path"]        = $item->file_path;
        $value["event_uuid"]       = $item->event_uuid;
        $value["is_deleted"]       = $item->is_deleted;
        $value["mime_type"]        = $item->mime_type;
        $value["created_at"]       = $item->created_at;
        $value["updated_at"]       = $item->updated_at;
        $value["sort"]             = $item->is_deleted;
        return $value;
    }

    /**
     * * 获得用户消耗的空间大小，我们计算的用户空间开销只是用户自身的文件大小，2/3/4不计算在内
     * 用户空间消耗包括4块内容
     * 1：用户自己的文件
     * 2：用户被共享目录
     * 3：系统公共目录
     * 4：文件衍生出的文件，包括：图片缩略图、文档html文件、视频二次产生物
     * @param $userId
     * @return int
     */
    public function getUsedSize($userId){
        $criteria            = new CDbCriteria;
        $criteria->select    = 'sum(file_size) as usedSize';
        $criteria->condition = 'user_id = :user_id and file_type=0';
        $criteria->params    = array(':user_id' => $userId);
        $row                 = UserFile::model()->find($criteria);
        if(isset($row)){
            $usedSize          = $row['usedSize'];
            if(!isset($usedSize)){
                $usedSize      = 0;
            }
        }else{
            $usedSize          = 0;
        }
        return $usedSize;
    }

    /**
     * 获得用户的共享列表
     * @param int $userId
     * @return array
     */
    public function getShares($userId){
        $items = UserFile::model()->findAll('user_id=:user_id and file_type in(2,3,16) ', array('user_id' => $userId));
        return  $this->db2list($items);
    }
    /**
     * 获得系统公共目录
     */
    public function getPublics(){
        $items = UserFile::model()->findAll('file_type=:file_type ', array('file_type' => 4));
        return  $this->db2list($items);
    }
    public function getByFilePath($filePath){
        $criteria            = new CDbCriteria();
        $criteria->condition = "file_path = :file_path";
        $criteria->params    = array("file_path"=>$filePath);
        $criteria->order     = "id DESC";
        $item               = UserFile::model()->find($criteria);
        return  $this->db2Item($item);
    }

    /**
     * 根据条件获得子文件记录
     * @param int $parentFileId
     * @param bool $includeDeleted
     * @param array $user
     * @param int  $userId
     * @param string $filePaths
     * @return array
     */
    public function getChildrenByFileID($parentFileId, $includeDeleted = false, $user=null,$userId=null,$filePaths=null) {
        $criteria                 = new CDbCriteria();
        $params                   = array();
        $sql                      = "parent_file_id = :parent_file_id";
        $params["parent_file_id"] = $parentFileId;
        //处理是否包含已删除的, 请求过来的参数，是字符串
        if ($includeDeleted == false) {
            $sql                 .= " AND is_deleted=:is_deleted";
            $params["is_deleted"] = intval(false);
        }
        if (isset($userId)){
            $sql                 .= ' AND user_id=:user_id';
            $params["user_id"]    = $userId;
        }
        if(isset($filePaths)){
            $files = array();
            foreach($filePaths as $filePath){
                $value =    $this->getByFilePath($filePath);
                $value['file_type'] = 3;
                array_push($files,$value);
            }
        }
        $order                    = 'file_type desc,id DESC ';
        $criteria->condition      = $sql;
        $criteria->params         = $params;
        $criteria->order          = $order;
        $items                    = UserFile::model()->findAll($criteria);

        $items                    = $this->db2list($items);
        array_splice($items,0,0,$files);
        if(!empty($user)){
            $data = array();
            foreach($items as $file){
                $item = $this->getFolderExtendProperty($file,$user);
                array_push($data,$item);
            }
            return $data;
        }
        return $items;
    }

    /**
     * 获取share_key和privilege
     * @param array $file
     * @param array $user
     * @return mixed
     */
    public function getFolderExtendProperty($file,$user){
        $fileType = intval($file["file_type"]);
        //文件外链Key
        $file["share_key"]        = "";
        //文件/目录/共享目录才能发起
        if($fileType===0||$fileType===1||$fileType===2){
            $link = MiniLink::getInstance()->getByFileId($file["id"]);
            if(!empty($link)){
                $file["share_key"] = $link["share_key"];
            }
        }
        //共享目录/被共享目录才有可能有权限信息
        //TODO
//        $privilege = MiniUserPrivilege::getInstance()->getFolderPrivilege($user["id"],$file);
//        $file["privilege"]=$privilege;
        return $file;
    }

    /**
     * 根据目录下所有可显示的子文件
     * @param string $parentPath
     * @return array
     */
    public  function getShowChildrenByPath($parentPath) {
        $criteria            = new CDbCriteria();
        $criteria->condition = "file_path like :file_path and is_deleted=0";
        $criteria->params    = array("file_path"=>$parentPath."%");
        $criteria->order     = "parent_file_id desc";
        $items               = UserFile::model()->findAll($criteria);
        return  $this->db2list($items);
    }

    /**
     * 根据Parent_file_id获得该目录下的子文件
     * @param int $userId
     * @param int $parentFileId
     * @param bool $isDeleted
     * @return array
     */
    public  function getChildrenByParentId($userId,$parentFileId,$isDeleted = false) {
        $criteria            = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and parent_file_id=:parent_file_id and is_deleted=:is_deleted";
        $criteria->params    = array(
            "user_id"        => $userId,
            "parent_file_id" => $parentFileId,
            "is_deleted"     => $isDeleted,
        );
        $criteria->order    = "file_type desc, id desc";
        $items              = UserFile::model()->findAll($criteria);
        return  $this->db2list($items);
    }

    /**
     * 根据Parent_file_id获得该目录下的普通和公共目录
     * @param int  $userId
     * @param int $parentFileId
     * @param boolean $isDeleted
     * @return array
     */
    public  function getChildrenFolderByParentId($userId,$parentFileId,$isDeleted) {
        $criteria            = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and parent_file_id=:parent_file_id and is_deleted=:is_deleted and file_type = 1 or file_type = 4";
        $criteria->params    = array(
            "user_id"        => $userId,
            "parent_file_id" => $parentFileId,
            "is_deleted"     => $isDeleted,
        );
        $criteria->order    = "file_type desc, id desc";
        $items              = UserFile::model()->findAll($criteria);
        return  $this->db2list($items);
    }

    /**
     * 根据Parent_file_id获得该目录下的所有子文件
     * @param int $userId
     * @param int $parentFileId
     * @param int $pageSize
     * @param int $currentPage
     * @return array|null
     */
    public  function getAllChildrenByParentId($userId,$parentFileId,$pageSize,$currentPage) {
        $criteria            = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and parent_file_id=:parent_file_id";
        $criteria->limit         = $pageSize;
        $criteria->offset        = ($currentPage-1)*$pageSize;
        $criteria->params    = array(
            "user_id"        => $userId,
            "parent_file_id" => $parentFileId,
        );
        $criteria->order    = "id desc";
        $items              = UserFile::model()->findAll($criteria);
        $total              = UserFile::model()->count($criteria);
        $data               = array();
        if($total == 0){
            return null;
        }else{
            $data['list']  = $this->db2list($items);
            $data['total'] = $total;
            return $data;
        }
    }

    /**
     * 创建File对象
     * 这里使用了引用传值，确保event_uuid可传递到外面
     * @param array $file
     * @param int $userId
     * @return mixed
     */
    public function create(&$file,$userId){
        if (!isset($file["version_id"])){
            $file["version_id"] = 0;
        }
        $file["event_uuid"]      = MiniUtil::getEventRandomString( MConst::LEN_EVENT_UUID );
        $item                    = new UserFile();
        $item->user_id           = $userId;
        $item->file_type         = $file["file_type"];
        $item->parent_file_id    = $file["parent_file_id"];
        $item->file_create_time  = $file["file_create_time"];
        $item->file_update_time  = $file["file_update_time"];
        $item->file_name         = $file["file_name"];
        $item->version_id        = $file["version_id"];
        $item->file_size         = $file["file_size"];
        $item->file_path         = $file["file_path"];
        $item->event_uuid        = $file["event_uuid"];
        $item->mime_type         = $file["mime_type"];//有存在NULL的情况
        $item->save();
        $file["id"] = $item->id;
        return $file;

    }

    /**
     * 批量创建文件
     * 这里使用了引用传值，确保event_uuid可传递到外面
     * @param $userId
     * @param $files
     * @return bool
     */
    public function createFiles($userId,&$files){
        foreach ($files as $file) {
            $this->create($file, $userId);
        }
        return true;
    }

    /**
     * 根据路径获得文件File对象
     * @param string $path
     * @return array
     */
    public function getByPath($path){
        $model = $this->getModelByPath($path);
        return $this->db2Item($model);

    }

    /**
     * 根据路径获得文件的Model对象
     * @param string $path
     * @return mixed
     */
    private function getModelByPath($path){
        $criteria            = new CDbCriteria();
        $criteria->condition = "file_path =:file_path";
        $criteria->params    = array(
            "file_path"=>$path
        );
        $item                = UserFile::model()->find($criteria);
        return $item;
    }

    /**
     * 根据主键获得文件的Model对象
     * @param int $id
     * @return mixed
     */
    private function getModelById($id){
        $item  = UserFile::model()->findByPk($id);
        return $item;
    }

    /**
     * 根据Id获得文件File对象
     * @param int $id
     * @return array
     */
    public function getById($id){
        $model = $this->getModelById($id);
        return $this->db2Item($model);
    }

    /**
     * 根据路径，假删除文件
     * @param string $path
     * @return bool
     */
    public function supposeDelete($path) {
        $criteria            = new CDbCriteria();
        $criteria->condition = 'file_path=:path';
        $criteria->params    = array("path"=>$path);
        $attributes          = array(
            'is_deleted'=>1
        );
        UserFile::model()->updateAll($attributes,$criteria);
        return true;
    }

    /**
     * 根据路径，假删除文件夹
     * @param string $path
     * @return bool
     */
    public function supposeDeleteFolder($path) {
        $criteria            = new CDbCriteria();
        $criteria->condition = 'file_path like :path';
        $criteria->params    = array(
            "path"=>$path."%"
        );
        $attributes          = array(
            'is_deleted'=>1
        );
        UserFile::model()->updateAll($attributes,$criteria);
        return true;
    }

    /**
     * 移动目录
     * @param int $userId
     * @param string $fromPath
     * @param string $toPath
     * @return bool
     */
    public function moveFolder($userId, $fromPath,$toPath){
        $fromPath          .= "/";
        $toPath            .= "/";
        $criteria            = new CDbCriteria();
        $criteria->condition = 'file_path like :file_path and user_id=:user_id';
        $criteria->params    = array(
            "file_path" => $fromPath."%",
            "user_id"   => $userId,
        );
        if (Yii::app()->db->driverName == 'sqlite') {
            $sql    = "(\"$toPath\" || substr(file_path, LENGTH(\"$fromPath\") + 1))";
        } else {
            $sql    = "CONCAT(\"$toPath\", SUBSTRING(file_path,CHAR_LENGTH(\"$fromPath\") + 1))";
        }
        $attributes = array(
            'file_path'=>new CDbExpression($sql)
        );
        UserFile::model()->updateAll($attributes,$criteria);
        return true;
    }

    /**
     * 针对Id对应的文件更新其属性
     * @param int $id
     * @param string $values
     * @return bool
     */
    public function update($id,$values){
        $model               = $this->getModelById($id);
        if(isset($model)){
            foreach ($values as $key=>$value){
                $model->$key = $value;
            }
            $model->save();
            return true;
        }
        return false;
    }

    /**
     * 针对path对于的文件更新其属性
     * @param string $path
     * @param string $values
     * @return bool
     */
    public function updateByPath($path,$values){
        $model               = $this->getModelByPath($path);
        if(isset($model)){
            foreach ($values as $key=>$value){
//                return $key;
                if($key==="share_key"||$key==="privilege"){
                    continue;
                }
                $model->$key = $value;
            }
            $model->save();
            return true;
        }
        return false;
    }
    public function togetherShareFile($path,$fileType){
        $model               = $this->getModelByPath($path);
        if(isset($model)){
            $model->file_type = $fileType;
            $model->save();
            return true;
        }
        return false;
    }

    /**
     * 更新ParentID
     * @param int $fromId
     * @param int $toId
     * @return bool
     */
    public  function updateParentId($fromId,$toId){
        $criteria            = new CDbCriteria();
        $criteria->condition = 'parent_file_id=:parent_file_id"';
        $criteria->params    = array(
            "parent_file_id"=>$fromId
        );
        $attributes          = array(
            'parent_file_id'=>$toId
        );
        UserFile::model()->updateAll($attributes,$criteria);
        return true;
    }

    /**
     * 删除文件
     * @param int $id
     * @return bool
     */
    public  function deleteFile($id) {
        $model = $this->getModelById($id);
        if(isset($model)){
            $file = $this->db2Item($model);
            //删除FileMeta
            MiniFileMeta::getInstance()->cleanFileMetaByPath($file["file_path"]);
            //删除tag数据
            MiniTag::getInstance()->deleteByFileId($id);
            //Version数据减一
            $versionId = $file["version_id"];
            MiniVersion::getInstance()->updateRefCountByIds(array($versionId),false);
            //删除share_files
            MiniLink::getInstance()->unlink($id);
            //删除user_privilege
            MiniUserPrivilege::getInstance()->deleteByFilePath($file["file_path"]);
            //删除自己
            $model->delete();
        }
        return true;
    }

    /**
     * 清空回收站文件
     * @param int $userId
     * @return array
     */
    public function getUserRecycleFile($userId){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=1 and user_id=:user_id";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        $items                   = UserFile::model()->findAll($criteria);
        return $this->db2list($items);
    }

    /**
     * 根据外部指定条件进行搜索
     * 不建议这样使用，这个方法标记为废弃
     * @param string $condition
     * @return array
     */
    public  function search($condition) {
        $criteria            = new CDbCriteria();
        $criteria->condition = $condition;
        $items               = UserFile::model()->findAll($criteria);
        return $this->db2list($items);
    }

    /**
     * 搜索文件
     * @param string $path
     * @param string $key
     * @param int $userId
     * @param boolean $includeDeleted
     * @return array
     */
    public  function searchFilesByPath($path, $key, $userId, $includeDeleted) {
        $params                 = array();
        $sql                    = "file_name like :key ";
        $params["key"]          = "%".$key."%";
        $sql                   .= "and user_id = :user_id";
        $params["user_id"]      = $userId;
        if ($path !== "/{$userId}/")
        {
            $sql                .= "and file_path like :path";
            $params["user_id"]   = $path."%";
        }
        if ($includeDeleted == false) {
            $sql                 .= " AND is_deleted=:is_deleted";
            $params["is_deleted"] = intval($includeDeleted);
        }
        $criteria                = new CDbCriteria();
        $criteria->condition     = $sql;
        $criteria->params        = $params;
        $items                   = UserFile::model()->findAll($criteria);
        return $this->db2list($items);
    }

    /**
     * 在指定文件类型下搜索文件
     * @param string $type
     * @param string $fileName
     * @param int $pageSize
     * @param int $currentPage
     * @return array|null
     */
    public function searchFilesByName($type,$fileName,$pageSize,$currentPage){
        $criteria                = new CDbCriteria();
        $criteria->select        = '*';
        $criteria->limit         = $pageSize;
        $criteria->offset        = ($currentPage-1)*$pageSize;
        if($type == 'office'){
            $criteria->condition = "is_deleted=0 and file_name like '".$fileName."%'";
            $criteria->addInCondition("mime_type", Yii::app()->params['officeType']);
        }elseif($type == 'recycle'){
            $criteria->condition = "is_deleted=1 and file_name like '".$fileName."%' and mime_type like '".$type."%'";
        }elseif($type == 'image'){
            $criteria->condition = "is_deleted=0 and file_name like '".$fileName."%' and mime_type like '".$type."%'";
        }else{
            $criteria->condition = "is_deleted=0 and file_name like '".$fileName."%'";
        }
        $criteria->order         = 'file_create_time desc';
        $items              	 = UserFile::model()->findAll($criteria);
        $total              	 = UserFile::model()->count($criteria);
        $data                    = array();
        if($total == 0){
            return null;
        }else{
            $data['list']  = $this->db2list($items);
            $data['total'] = $total;
            return $data;
        }
    }

    /**
     * 获得用户未删除文件总数
     * @param int $userId
     * @return mixed
     */
    public function getFilesCount($userId){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and file_type=0 and user_id=:user_id";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        return              	 UserFile::model()->count($criteria);
    }

    /**
     * 获得用户未删除的文件夹总数
     * @param int $userId
     * @return mixed
     */
    public function getFoldersCount($userId){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and file_type>0 and user_id=:user_id";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        return              	 UserFile::model()->count($criteria);
    }

    /**
     * 用户文档数量
     * @param int $userId
     * @return mixed
     */
    public function getOfficeCount($userId)
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and user_id=".$userId;
        $criteria->addInCondition("mime_type", Yii::app()->params['officeType']);
        return              	 UserFile::model()->count($criteria);

    }

    /**
     * 用户图片数量
     * @param int $userId
     * @return mixed
     */
    public function getImageCount($userId)
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and mime_type like 'image%'";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        return              	 UserFile::model()->count($criteria);
    }

    /**
     * 用户音乐数量
     * @param int $userId
     * @return mixed
     */
    public function getMusicCount($userId)
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and mime_type like 'audio%'";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        return              	 UserFile::model()->count($criteria);
    }

    /**
     * 用户视频数量
     * @param int $userId
     * @param string $sql
     * @return mixed
     */
    public function getVedioCount($userId, $sql='')
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and mime_type like 'video%'";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        return              	 UserFile::model()->count($criteria);
    }

    /**
     * 用户各类文件数据
     * @param int $userId
     * @param string $type
     * @return array|null
     */
    public function getFileByUserType($userId,$type)
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and mime_type like '".$type."%'";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        $items              	 =UserFile::model()->findAll($criteria);
        $total              	 =UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }

    }

    /**
     * 根据文件名模糊查找文件
     * @param int $userId
     * @param string $fileName
     * @return array|null
     */
    public function getFileByName($userId,$fileName)
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and file_name like '%".$fileName."%'";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        $items              	 =UserFile::model()->findAll($criteria);
        $total              	 =UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }

    }

    /**
     * 根据文件Id查找文件
     * @param string $ids
     * @return array|null
     */
    public function getFilesByIds($ids)
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "id in ( ".$ids." )";
        $items              	 =UserFile::model()->findAll($criteria);
        $total              	 =UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }

    }

    /**
     * 回收站文件的个数(文件夹内的文件不计)
     * @param int $userId
     * @return mixed
     */
    public function trashCount($userId){
        $condition               = "user_id=:user_id and is_deleted = 1 and parent_file_id not in (select id from ".Yii::app()->params['tablePrefix']."files where is_deleted = 1 and user_id=:user_id)";
        $params                  = array('user_id'=>$userId);
        $criteria                = new CDbCriteria();
        $criteria->params        = $params;
        $criteria->condition     = $condition;
        return UserFile::model()->count($criteria);
    }

    /**
     * 获取指定目录下子文件的数量
     * @param int $fileId
     * @return mixed
     */
    public function getFileCount($fileId)
    {
        $criteria                = new CDbCriteria();
        $criteria->params        = array(':parent_file_id'=>$fileId);
        $criteria->condition     = "is_deleted=0 and parent_file_id=:parent_file_id and file_type = 0";
        return UserFile::model()->count($criteria);
    }

    /**
     * 网页版显示文件列表，对其进行分页
     * @param int $userId
     * @param int $parentFileId
     * @param string $order
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getPageList($userId, $parentFileId,$order,$limit = 45, $start = 0){
        $condition = "user_id=:user_id and is_deleted=0 and parent_file_id=:parent_file_id";
        $params    = array('user_id'=>$userId, 'parent_file_id'=>$parentFileId);
        // 修改查询条件
        $filter    = array('condition'=>$condition, 'params' => $params);
        $filter    = apply_filters('file_list_filter', $filter);
        $items     = UserFile::model()->findAll(
            array(
                'condition' => $filter["condition"],
                'params'    => $filter["params"],
                'order'     => $order,
                'limit'     => $limit,
                'offset'    => $start,
            )
        );
        return $this->db2list($items);
    }
    /**
     * 获得系统总的文件开销
     * 这里是硬盘虚拟开销
     */
    public function getTotalSize(){
        $criteria            = new CDbCriteria;
        $criteria->select    = 'sum(file_size) as usedSize';
        $row                 = UserFile::model()->find($criteria);
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
     * 根据versionId获得相关的文件列表
     * @param int $versionId
     * @return array
     */
    public function getAllByVersionId($versionId){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "version_id=:version_id and file_type = 0 and is_deleted=0";
        $criteria->params        = array(':version_id'=>$versionId);
        $items                   = UserFile::model()->findAll($criteria);
        return $this->db2list($items);

    }

    /**
     *  根据用户ID获得用户文件信息
     * @param int $userId
     * @param int $parentFileId
     * @return array
     */
    public function getFiles($userId,$parentFileId = 0){
        $criteria                = new CDbCriteria();
        $criteria->params        = array(':parent_file_id'=>$parentFileId,':user_id' => $userId);
        $criteria->condition     = "is_deleted=0 and parent_file_id=:parent_file_id and user_id=:user_id";
        $items                   = UserFile::model()->findAll($criteria);
        return $this->db2list($items);
    }
    /**
     * copy file to folder of new user
     * @param string $fileId
     * @param int $userId
     * @param int $deviceId
     * @param int $aimFolderId
     */
    public function copy($fileId,$userId,$deviceId,$aimFolderId){
        $file = $this->getById($fileId);
        $aimChildren = $this->getChildrenByFileID($aimFolderId,true,null,$userId);
        //get new file name
        $aimName = $this->getFileName($file["file_name"],$aimChildren);
        $file["file_name"] = $aimName;
        //modify file path
        if($aimFolderId===MiniFile::$ROOT_FOLDER_ID){
            $file["file_path"] = MiniUtil::joinPath($userId,$aimName);
        }else{
            $aimFolder = $this->getById($aimFolderId);
            $file["file_path"] = MiniUtil::joinPath($aimFolder["file_path"],$aimName);
        }
        $file["parent_file_id"]=$aimFolderId;
        if($file['file_type']!=0){
            $file['file_type']=1;
        }
        //set file.id = null
        unset($file["id"]);
        $newFile = $this->create($file,$userId);
        //create event
        MiniEvent::getInstance()->createFile($userId,$file,$deviceId);
        $metaData = array();
        $metaValue = array();
        $device    = MUserManager::getInstance()->getCurrentDevice();
        $metaData['type'] = $file['file_type'];
        $metaData['version_id'] = $file['version_id'];
        $metaData['user_id'] = $file['user_id'];
        $metaData['user_nick'] = "admin";
        $metaData['device_name'] = $device['user_device_name'];
        $metaData['file_size'] = $file['file_size'];
        $metaData['datetime'] = $file['updated_at'];
        array_push($metaValue,$metaData);
        $metaValue = serialize($metaValue);
        MiniFileMeta::getInstance()->createFileMeta($file["file_path"],"version",$metaValue);
        //copy folder
        if($file["file_type"]!==MiniFile::$TYPE_FILE){//不等于0的则为文件夹
            $children = $this->getChildrenByFileID($fileId);
            foreach($children as $item){
                $this->copy($item["id"],$userId,$deviceId,$newFile["id"]);
            }
        }


    }

    /**
     * @param string $filePath
     * @return array
     * 兼容 中文名 pathInfo 的方法
     */
    function cnPathInfo($filePath)
    {
        $path_parts = array();
        $path_parts ['dirname'] = rtrim(substr($filePath, 0, strrpos($filePath, '/')),"/")."/";
        $path_parts ['filename'] = ltrim(substr($filePath, strrpos($filePath, '/')),"/");
        $path_parts ['extension'] = substr(strrchr($filePath, '.'), 1);
        $path_parts ['basename'] = ltrim(substr($path_parts ['filename'], 0, strrpos($path_parts ['filename'], '.')),"/");
        return $path_parts;
    }
    /**
     * get file name in folder
     * @param string $fileName
     * @param string $children
     * @param int $index
     * @return bool|string
     */
    private function getFileName($fileName,$children,$index=0){
        $pathParts=$this->cnPathInfo($fileName);
        $name = $fileName;
        if($index>0){
            $newFileName = $pathParts['filename'];
            $extension = $pathParts['extension'];
            $basename  = $pathParts['basename'];
            $pathParts2=$this->cnPathInfo($basename);
            $extension2 = $pathParts2['extension'];
            $basename2  = $pathParts2['basename'];
            if($extension2=='tar'){
                $basename = $basename2;
                $extension = $extension2.'.'.$extension;
            }
            $name = $basename."(".$index.").".$extension;
            if($extension==""){
                $name = $newFileName."(".$index.")";
            }
        }
        foreach($children as $item){
            if($item["file_name"]==$name){
                $result = $this->getFileName($fileName,$children,$index+1);
                if($result!==true){
                    return $result;
                }
                return true;
            }
        }
        return $name;
    }

    /**
     * 下载文件
     * @param string $path
     * @throws MFilesException
     */
    public function download($path){
        $this->getContent($path,null,null,true);
    }

    /**
     * 获得文件内容同时支持迷你存储
     * @param string $signature
     * @param string $fileName
     * @param string $contentType
     * @param bool $forceDownload
     * @throws MFilesException
     */
    public function getContentBySignature($signature,$fileName,$contentType,$forceDownload=true){
        //下载文件的hook
        $data = array();
        $data["signature"]  = $signature;
        $data["file_name"]  = $fileName;
        $data["mime_type"]  = $contentType;
        //对网页的处理分为2种逻辑，1种是直接显示内容，1种是文件直接下载
        $data["force_download"] = $forceDownload;
        $retData = apply_filters("file_download_url", $data);
        if ($retData !== $data && !empty($retData)){
            header( "HTTP/1.1 ".MConst::HTTP_CODE_301." Moved Permanently" );
            header( "Location: ". $retData );
            exit;
            return;
        }
        $filePath = MiniUtil::getPathBySplitStr ($signature);
        //data源处理对象
        $dataObj = Yii::app()->data;
        if ($dataObj->exists( $filePath ) === false) {
            throw new MFilesException ( Yii::t('api',MConst::NOT_FOUND ), MConst::HTTP_CODE_404 );
        }
        // 检查是否输出
        if (headers_sent ()) {
            exit ();
        }
        MiniUtil::outContent($filePath, $contentType, $fileName,$forceDownload);
        exit;
    }
    /**
     * 通过signature获得文件内容到内存
     * @param $signature
     * @throws
     * @return mix
     */
    public function getFileContentBySignature($signature){
        //下载文件的hook
        $data = array();
        $data["signature"]  = $signature;
        $data["file_name"]  = "text.txt";
        $data["mime_type"]  = "text/html";
        //对网页的处理分为2种逻辑，-1种是直接显示内容，1种是文件直接下载
        $data["force_download"] = -1;
        $retData = apply_filters("file_download_url", $data);
        if ($retData !== $data && !empty($retData)){
            //通过迷你存储存储的文件，通过代理方式直接请求文件内容
            $content = apply_filters("file_content", $signature);
        }else{
            $filePath = MiniUtil::getPathBySplitStr ( $signature );
            //data源处理对象
            $dataObj = Yii::app()->data;
            if ($dataObj->exists( $filePath ) === false) {
                throw new MFilesException ( Yii::t('api',MConst::NOT_FOUND ), MConst::HTTP_CODE_404 );
            }
            $content = $dataObj->get_contents($filePath);
        }
        return $content;
    }
    /**
     * 获得文本文件内容
     * @param string $signature
     * @return mixed
     * @throws MFilesException
     */
    private function getText($signature){
        $content = $this->getFileContentBySignature($signature);
        //如果是UTF-8，不用转换。否则尝试将其转换为utf-8编码的文件，这里还是会存在转换失败的可能性，也就是说用户只是看到部分文本
        $encode = mb_detect_encoding($content);
        if($encode!=='UTF-8'){
            $resultContent = iconv($encode,'UTF-8', $content);
            $content = !$resultContent?$content:$resultContent;
        }
        //针对Windows系统下的记事本编辑的文件，默认是utf-8。但还需要进行二次转换
        $encodeResult = json_encode($content);
        if($encodeResult==="null"){
            $content = iconv("GB2312",'UTF-8', $content);
        }
        return $content;

    }

    /**
     * 检测是否是历史版本预览
     * @param string $path
     * @param string $signature
     * @param string $contentType
     * @param bool $forceDownload
     * @return array
     */
    private function hasContentPrivilege($path,$signature="",$contentType="",$forceDownload=false){
        $file = $this->getByPath($path);
        $version = MiniVersion::getInstance()->getVersion($file["version_id"]);
        $hasPrivilege = false;
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($path,'version');
        $versions = unserialize($fileMeta['meta_value']);
        if(isset($fileMeta)){
            foreach($versions as $item){
                if(intval($item['version_id']) === intval($version['id'])){
                    $hasPrivilege = true;
                    break;
                }
            }
        }
        if($hasPrivilege){
            if(empty($contentType)){
                $contentType = $version["mime_type"];
                $contentType = empty ( $contentType ) ? MConst::DEFAULT_DOWNLOAD_MIME_TYPE : $contentType;
            }
            //检测是否是历史版本浏览
            if(empty($signature)){
                $signature = $version["file_signature"];
            }
        }
        return array("hasPrivilege"=>$hasPrivilege,"signature"=>$signature,"fileName"=>$file["file_name"]);
    }
    /**
     * 由外部控制文件输出类型
     * @param string $path
     * @param string $signature
     * @param string $contentType
     * @param bool $forceDownload
     * @throws MFilesException
     */
    public function getContent($path,$signature="",$contentType="",$forceDownload=false){
        $privilege = $this->hasContentPrivilege($path,$signature,$contentType,$forceDownload);
        $version = MiniVersion::getInstance()->getBySignature($privilege['signature']);
        $this->getContentBySignature($privilege['signature'],$privilege['fileName'],$version["mime_type"],$forceDownload);
    }
    /**
     * 由外部控制文件输出类型
     * @param string $path
     * @param string $signature
     * @throws MFilesException
     * @return string
     */
    public function getTxtContent($path,$signature){
        $file = $this->getByPath($path);
        $version = MiniVersion::getInstance()->getVersion($file["version_id"]);
        $hasPrivilege = false;
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($path,'version');
        $versions = unserialize($fileMeta['meta_value']);
        if(isset($fileMeta)){
            foreach($versions as $item){
                if(intval($item['version_id']) === intval($version['id'])){
                    $hasPrivilege = true;
                    break;
                }
            }
        }
        if($hasPrivilege){
            //检测是否是历史版本浏览
            if(empty($signature)){
                $signature = $version["file_signature"];
            }
            return $this->getText($signature);
        }
        return null;
    }

    /**
     * 根据文件类型和用户ID获得对应文件分页信息
     * @param int $pageSet
     * @param int $pageSize
     * @param int $userId
     * @param $type
     * @return array|null
     */
    public function getFileListPage($pageSet,$pageSize,$userId,$type){
        $criteria                = new CDbCriteria();
        $criteria->select   ='*';
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and (mime_type='image/jpeg' or mime_type='image/png' or mime_type='image/gif')";
        $criteria->limit    = $pageSize;
        $criteria->offset   = $pageSet;
        $criteria->order    ='file_create_time desc';
        $criteria->params        = array(
            "user_id"=>$userId
        );
        $items              	 =UserFile::model()->findAll($criteria);
        $total              	 =UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }
    }

    /** 根据文件类型和用户ID获得对应文件信息
     * @param int $userId
     * @param string $type
     * @return array|null
     */
    public function getFileListByType($userId,$type){
        $criteria                = new CDbCriteria();
        $criteria->select   ='*';
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and (mime_type='image/jpeg' or mime_type='image/png' or mime_type='image/gif')";
        $criteria->order    ='file_create_time desc';
        $criteria->params        = array(
            "user_id"=>$userId
        );
        $items              	 =UserFile::model()->findAll($criteria);
        $total              	 =UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }
    }
    public function getByMimeType($userId,$mimeType,$offset=null,$pageSize=null){
        $criteria                = new CDbCriteria();
        $criteria->select   ='*';
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and (mime_type=:mime_type)";
        $criteria->order    =     'file_create_time desc';
        $criteria->params        = array(
            "user_id"=>$userId,'mime_type'=>$mimeType
        );
        if(isset($offset)&&isset($pageSize)){
            $criteria->limit = $pageSize;
            $criteria->offset = $offset;
        }
        $items              	 =UserFile::model()->findAll($criteria);
        return $this->db2list($items);
    }
    public function getTotalByMimeType($userId,$mimeType){
        $criteria                = new CDbCriteria();
        $criteria->select   ='*';
        $criteria->condition     = "is_deleted=0 and user_id=:user_id and (mime_type=:mime_type)";
        $criteria->params        = array(
            "user_id"=>$userId,'mime_type'=>$mimeType
        );
        $total              	 =UserFile::model()->count($criteria);
        return $total;
    }
    public function getSharedDocByPathType($path,$mimeType){
        $criteria                = new CDbCriteria();
        $criteria->select   ='*';
        $criteria->condition     = "is_deleted=0 and mime_type =:mime_type and file_path like '%".$path."%'";;
        $criteria->params        = array('mime_type'=>$mimeType);
        $criteria->order    =     'file_create_time desc';
        $items                   =UserFile::model()->findAll($criteria);
        return $this->db2list($items);
    }

    /**
     * 根据filePath和mine_type获取图片
     * @param string $filePath
     * @return array|null
     */
    public function searchFileByPathType($filePath){
        $criteria                = new CDbCriteria();
        $criteria->select   ='*';
        $criteria->condition     = "is_deleted=0 and  mime_type like 'image%' and file_path like '%".$filePath."%'";
        $criteria->order    ='file_create_time desc';
        $items              	 =UserFile::model()->findAll($criteria);
        $total              	 =UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }
    }

    /**
     * 历史版本恢复
     * @param int $deviceId
     * @param string $filePath
     * @param string $signature
     * @return bool
     */
    public function recover($deviceId,$filePath, $signature){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        $file =  $this->getModelByPath($filePath);
        if ($version["id"] == $file['version_id']){
            return true;
        }
        $device = MiniUserDevice::getInstance()->getById($deviceId);
        $userId = $device["user_id"];
        $user = MiniUser::getInstance()->getUser($userId);
        $userNick = $user["nick"];

        // events表 相关操作
        $userDeviceName = $device["user_device_name"];
        $userDeviceId = $device["id"];
        $signature = $version['file_signature'];
        $action = CConst::MODIFY_FILE;
        $file->file_update_time = time();
        $context = array('hash'=>$signature,
            'rev'=>(int)$version["id"],
            'bytes'=>(int)$version['file_size'],
            'update_time' => (int)$file->file_update_time,
            'create_time' => (int)$file['file_create_time']);


        $filePath             = $file['file_path'];
        $eventUuid            = MiniUtil::getEventRandomString(CConst::LEN_EVENT_UUID);
        MiniEvent::getInstance()->createEvent($file['user_id'], $userDeviceId, $action, $filePath, serialize($context), $eventUuid);//create event

        // files表相关操作
        $file->version_id       = $version["id"];
        $file->event_uuid       = $eventUuid;
        $file->file_size        = $version['file_size'];
        $file->save();

        // meta表相关操作
        $fileMeta = FileMeta::model()->find('file_path = ?', array($filePath));
        $versions = CUtils::getFileVersions($userDeviceName,
            $version['file_size'],
            $version["id"],
            CConst::WEB_RESTORE,
            $userId,
            $userNick,
            $fileMeta['meta_value']
        );
        $fileMeta->meta_value = $versions;
        $fileMeta->save();
        //更新版本引用数
        MiniVersion::getInstance()->updateRefCountByIds(array($version["id"]), TRUE);
        return true;
    }

    /**
     * 获得为删除文件列表
     * @param int $id
     * @return array
     */
    public function getUnDeleteFile($id){
        $criteria  =new CDbCriteria();
        $criteria->select     = '*';
        $criteria ->condition = "is_deleted=0 and id=:id";
        $criteria->params=array(
            "id"=>$id
        );
        $item=UserFile::model()->find($criteria);
        return $this->db2Item($item);
    }

    /**
     * 获得加删除文件分页列表
     * @param int $userId
     * @param int $pageSize
     * @param int $pageSet
     * @return array
     */
    public function getDeleteFile($userId,$pageSize=null,$pageSet=null,$parentFileId=null,$fileType=0){
        $criteria  =new CDbCriteria();
        $criteria->select     = '*';
        $criteria ->condition = "is_deleted=1 and user_id=:user_id and file_type <= :file_type";
        if($pageSize!=null&&$pageSet=null){
            $criteria->limit      = $pageSize;
            $criteria->offset     = $pageSet;
        }
        $criteria->params=array(
            "user_id"=>$userId,
            "file_type"=>$fileType
        );
        if($parentFileId!=null){
            $criteria->addCondition("parent_file_id =:parent_file_id","and");
            $criteria->params[':parent_file_id']=$parentFileId;
        }
        $criteria->order="file_create_time desc";
        $items=UserFile::model()->findAll($criteria);
        return $this->db2list($items);
    }

    /**
     * 获得加删除文件总数
     * @param int $userId
     * @return array
     */
    public function getDeleteFileCount($userId){
        $criteria  =new CDbCriteria();
        $criteria->select     = '*';
        $criteria ->condition = "is_deleted=1 and user_id=:user_id and file_type=0";
        $criteria->params=array(
            "user_id"=>$userId
        );
        $items=UserFile::model()->count($criteria);
        return $items;
    }

    /**
     * 根据路径，回复假删除文件
     * @param string $path
     * @param int $userId
     * @param string $device
     * @return bool
     */
    public function recoverDelete($path,$userId,$device) {
        if(strlen($path)!=0){
            $path = "/" . $userId.$path;
            $pathArr = explode('/',$path);
            $jointPath = '/'.$userId;
            for($i=2;$i<count($pathArr);$i++){
                $jointPath .= '/'.$pathArr[$i];
                $criteria            = new CDbCriteria();
                $criteria->condition = "file_path = :path";
                $criteria->params    = array(":path"=>$jointPath);
                $item = UserFile::model()->find($criteria);
                $item->is_deleted=0;
                $item->save();
            }
        }else{
            $items = $this->getDeleteFile($userId,null,null,null,1);
            foreach($items as $item){
                $pathArr = explode('/',$item['file_path']);
                $jointPath = '/'.$userId;
                for($i=2;$i<count($pathArr);$i++){
                    $jointPath .= '/'.$pathArr[$i];
                    $criteria            = new CDbCriteria();
                    $criteria->condition = "file_path = :path";
                    $criteria->params    = array(":path"=>$jointPath);
                    $item = UserFile::model()->find($criteria);
                    $item->is_deleted=0;
                    $item->save();
                }
            }
        }


        /**
         * 为创建事件做准备
         */
        $file    = MiniFile::getInstance()->getByPath($path);
        $version = FileVersion::model()->findByPk($file["version_id"]);
        $context = array(
            "hash"        => $version["file_signature"],
            "rev"         => (int)$file['version_id'],
            "bytes"       => (int)$file['file_size'],
            "update_time" => (int)$file['file_update_time'],
            "create_time" => (int)$file['file_create_time']
        );
        $action = 3;
        $context = serialize($context);
        if($file['file_type'] == 1){
            $context = $path;
            $action  = 0;
        }
        MiniEvent::getInstance()->createEvent(
            $userId,
            $device['device_id'],
            $action,
            $path,
            $context,
            MiniUtil::getEventRandomString( MConst::LEN_EVENT_UUID ),
            MSharesFilter::init()
        );
        return true;
    }

    /**
     * 根据文件名模糊查找假删除文件
     * @param int $userId
     * @param string $fileName
     * @return array|null
     */

    public function getFileByNameRecycle($userId,$fileName)
    {
        $criteria                = new CDbCriteria();
        $criteria->condition     = "is_deleted=1 and user_id=:user_id and file_name like '%".$fileName."%'";
        $criteria->params        = array(
            "user_id"=>$userId
        );
        $items              	 =UserFile::model()->findAll($criteria);
        $total              	 =UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }
    }

    /**
     * 显示所有文件，对其进行分页
     * @param int $pageSize
     * @param int $currentPage
     * @return array|null
     */
    public function getAllFilesList($pageSize,$currentPage){
        $criteria                = new CDbCriteria();
        $criteria->limit         = $pageSize;
        $criteria->offset        = ($currentPage-1)*$pageSize;
        $criteria->order         = "id desc";
        $items              	 = UserFile::model()->findAll($criteria);
        $total              	 = UserFile::model()->count($criteria);
        if($total == 0){
            return null;
        }else{
            return $this->db2list($items);
        }
    }

    /** 根据文件类型和用户ID获得对应文件信息
     * @param string $type
     * @param int $pageSize
     * @param int $currentPage
     * @return array|null
     */
    public function getAllFileListByType($type,$pageSize,$currentPage){
        $criteria                = new CDbCriteria();
        $criteria->select        = '*';
        if($type == 'office'){
            $criteria->addInCondition("mime_type", Yii::app()->params['officeType']);
        }elseif($type == 'recycle'){
            $criteria->condition = "is_deleted=1";
        }else{
            $criteria->condition = "is_deleted=0 and (mime_type='image/jpeg' or mime_type='image/png' or mime_type='image/gif')";
        }
        $criteria->limit         = $pageSize;
        $criteria->offset        = ($currentPage-1)*$pageSize;
        $criteria->order         = 'file_create_time desc';
        $items              	 = UserFile::model()->findAll($criteria);
        $total              	 = UserFile::model()->count($criteria);
        $data                    = array();
        if($total == 0){
            return null;
        }else{
            $data['list']  = $this->db2list($items);
            $data['total'] = $total;
            return $data;
        }
    }

    /**
     * 根据文件路径查询文件所属子文件
     * @param string $path
     * @return array
     */
    public function getChildrenByPath($path){
        $files = UserFile::model()->findAll('file_path like :file_path', array(':file_path'=>$path.'/%'));
        return $this->db2list($files);
    }
    /**
     * 获取最早和最晚的文件时间
     */
    public function getFileTime(){
        $criteria            = new CDbCriteria();
        $criteria->order     = "created_at asc";
        $begin               = UserFile::model()->find($criteria);
        $criteria2           = new CDbCriteria();
        $criteria2->order    = "created_at desc";
        $last                = UserFile::model()->find($criteria2);
        $data = array();
        $data['first_file'] = $this->db2Item($begin);
        $data['last_file']  = $this->db2Item($last);
        return $data;
    }

    /**
     * 获得特定时间内的文件数
     * @param date $wholeDate
     * @return array
     */
    public function getBeforeDateFiles($wholeDate){
        $totalNum   = array();
        $imgNum     = array();
        $recycleNum = array();
        $officeNum  = array();
        $data       = array();
        foreach($wholeDate as $date){
            $criteria                = new CDbCriteria();
            $criteria->condition     ="created_at < :date";
            $criteria->params        = array(':date'=>$date);
            $total                   = UserFile::model()->count($criteria);
            if($total == 0){
                $total =0;
            }

            $criteria2                = new CDbCriteria();
            $criteria2->condition     ="created_at < :date and mime_type like 'image%'";
            $criteria2->params        = array(':date'=>$date);
            $total2                   = UserFile::model()->count($criteria2);
            if($total2 == 0){
                $total2 =0;
            }

            $officeTypes              = Yii::app()->params['officeType'];
            $officeConditions         = "'".implode("','",array_values($officeTypes))."'";
            $criteria3                = new CDbCriteria();
            $criteria3->condition     ="created_at < :date and mime_type in($officeConditions)";

            $criteria3->params        = array(':date'=>$date);
            $total3                   = UserFile::model()->count($criteria3);
            if($total3 == 0){
                $total3 =0;
            }
            $criteria4                = new CDbCriteria();
            $criteria4->condition     ="created_at < :date and is_deleted = 1";
            $criteria4->params        = array(':date'=>$date);
            $total4                   = UserFile::model()->count($criteria4);
            if($total4 == 0){
                $total4 = 0;
            }
            array_push($totalNum,(int)$total);
            array_push($imgNum,(int)$total2);
            array_push($officeNum,(int)$total3);
            array_push($recycleNum,(int)$total4);
        }
        $data['totalNum']   = $totalNum;
        $data['imgNum']     = $imgNum;
        $data['officeNum']  = $officeNum;
        $data['recycleNum'] = $recycleNum;
        return $data;
    }

    /**
     * 设置为公共目录
     * @param string $filePath
     * @return array
     */
    public function  setToPublic($filePath){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "file_path=:file_path";
        $criteria->params        = array(
            "file_path"=>$filePath
        );
        $item = UserFile::model()->find($criteria);
        if(!empty($item)){
            $item->file_type='4';
            $item->save();
            return array('success'=>true);
        }else{
            return array('success'=>false);
        }
    }

    /**
     * 取消设置为公共目录
     * @param string $filePath
     * @return array
     */
    public function  cancelPublic($filePath){
        $criteria                = new CDbCriteria();
        $criteria->condition     = "file_path=:file_path";
        $criteria->params        = array(
            "file_path"=>$filePath
        );
        $item = UserFile::model()->find($criteria);
        if(!empty($item)){
            $item->file_type='1';
            $item->save();
            return array('success'=>true);
        }else{
            return array('success'=>false);
        }
    }

    /**
     * 处理创建文件信息及事件
     * @param string $folderPath
     * @param int $parentFileId
     * @param boolean $hadFileDelete
     * @param int $userId
     * @return array
     */
    private function createFileMeta($folderPath, $parentFileId, $hadFileDelete,$userId){
        $fileName                       = MUtils::get_basename($folderPath);
        // 组装对象信息
        $fileDetail                     = array();
        $fileDetail["file_create_time"] = time();
        $fileDetail["file_update_time"] = time();
        $fileDetail["file_name"]        = $fileName;
        $fileDetail["file_path"]        = $folderPath;
        $fileDetail["file_size"]        = 0;
        $fileDetail["file_type"]        = MConst::OBJECT_TYPE_DIRECTORY;
        $fileDetail["parent_file_id"]   = $parentFileId;
        $fileDetail["event_uuid"]       = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
        $fileDetail["mime_type"]        = NULL;
        // 保存文件元数据
        if ($hadFileDelete)
        {
            $updates                      = array();
            $updates["file_update_time"]  = time();
            $updates["is_deleted"]        = intval(false);
            $updates["file_type"]         = MConst::OBJECT_TYPE_DIRECTORY;
            $updates["event_uuid"]        = $fileDetail["event_uuid"];
            // 存在已被删除的数据，只需更新
            $this->updateByPath($folderPath, $updates);
        }
        else
        {
            // 不存在数据，添加
            $file = $this->getByPath($folderPath);
            if(empty($file)){
                if(!empty($fileDetail["file_name"])){
                    $this->create($fileDetail, $userId);
                    $device                   = MUserManager::getInstance()->getCurrentDevice();
                    $event_action             = MConst::CREATE_DIRECTORY;
                    MiniEvent::getInstance()->createEvent(
                        $userId,
                        $device["device_id"],
                        $event_action,
                        $fileDetail["file_path"],
                        $fileDetail["file_path"],
                        $fileDetail["event_uuid"],
                        0
                    );
                }
            }
        }
        return $fileDetail;
    }

    /**
     * 处理检查文件父目录是否存在，不存在将递归依次创建
     * @param string $folderPath
     * @param int $userId
     * @return mixed
     * @throws MFileopsException
     */
    private function handlerParentFolder($folderPath,$userId){
        $file = $this->getByPath($folderPath);
        $hadFileDelete  = false;
        if (!empty($file))
        {
            // 检查该记录是否已被删除
            if ($file["is_deleted"] == false)
            {
                return $file["id"];
            }
            // 记录已被删除
            $hadFileDelete = true;
        }
        if(count(explode('/',$folderPath)) <= 3){
            $parentFileId = 0;
        }else{
            $parentPath = dirname($folderPath);
            $parentFileId = $this->handlerParentFolder($parentPath,$userId);
        }
        $this->createFileMeta($folderPath, $parentFileId, $hadFileDelete,$userId);
        $file   = $this->getByPath($folderPath);
        if ($file === NULL)
        {
            throw new MFileopsException(
                Yii::t('api','Internal Server Error'),
                MConst::HTTP_CODE_500);
        }
        return $file["id"];
    }
    /**
     * 创建目录
     * @param string $folderPath
     * @param int $userId
     * @return array
     */
    public function createFolder($folderPath,$userId){
        $hadFileDelete       = false;
        if(count(explode('/',$folderPath)) <= 3){
            $parentFileId =  0;
        }else{
            $parentFileId = $this->handlerParentFolder($folderPath,$userId);
        }
        $fileDetail          = $this->createFileMeta($folderPath, $parentFileId, $hadFileDelete,$userId);
        return $fileDetail;
    }
}