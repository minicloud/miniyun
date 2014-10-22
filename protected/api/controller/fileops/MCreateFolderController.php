<?php
/**
 * Miniyun create_folder服务主要入口地址, 创建文件夹
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MCreateFolderController extends MApplicationComponent implements MIController{
	
    public $_user_device_id   = null;
    public $_user_id          = null;
    private $_init            = false;
    private $_parentFilePath  = null;
    public $share_filter      = null;
    public $isOutput          = true;
    /**
     * 控制器执行主逻辑函数
     *
     */
    public function invoke($uri=null)
    {
        $this->setAction(MConst::CREATE_DIRECTORY);
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $params = $_REQUEST;
        // 检查参数
        if (isset($params) === false || $params == null) {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request'),
                                        MConst::HTTP_CODE_400);
        }
        // 获取用户数据，如user_id
        $user                     = MUserManager::getInstance()->getCurrentUser();
        $device                   = MUserManager::getInstance()->getCurrentDevice();
        
        $this->_user_id           = $user["user_id"];
        $this->_user_device_id    = $device["device_id"];
        
        // 文件大小格式化参数
        $locale = "bytes";
        if (isset($params["locale"])) {
            $locale = $params["locale"];
        }
        if (isset($params["root"]) === false || isset($params["path"]) === false) {
            throw new MFileopsException(
                                        Yii::t('api','Bad Request'),
                                        MConst::HTTP_CODE_400);
        }
        $root = $params["root"];
		// dataserver 增加创建返回path,用于导航
		// by Kindac 
		// since 2013/06/25
        $path               = $params["path"];
        $fileName          = MUtils::get_basename($path);
        if ($fileName === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','The folder name is invalid'),
                                        MConst::HTTP_CODE_400);
        }
        // 检查文件名是否有效
        $isInvalid        = MUtils::checkNameInvalid($fileName);
        if ($isInvalid)
        {
            throw new MFileopsException(
                                        Yii::t('api','The folder name is invalid'),
                                        MConst::HTTP_CODE_400);
        }
        // 转换路径分隔符，便于以后跨平台，如：将 "\"=>"/"
        $path           = MUtils::convertStandardPath($path);
        if ($path == false)
        {
            throw new MFileopsException(
                                        Yii::t('api','The folder name is invalid'),
                                        MConst::HTTP_CODE_400);
        }
        
        // 检查是否在共享目录
        $this->share_filter = MSharesFilter::init();
        if ($this->share_filter->handlerCheck($this->_user_id, $path, MConst::CREATE_DIRECTORY)) {
            $this->_user_id = $this->share_filter->master;
            $path           = $this->share_filter->_path;
        }
        if($params['is_root']=="/"){
            $path               = "/".$this->_user_id.$path;
        }
        $parentPath        = dirname($path);
        if(dirname(MiniUtil::getRelativePath($path)) == "/"){
            $permission = "111111111";
        }else{
            $permissionModel = new UserPermissionBiz($parentPath,$this->_user_id);
            $permissionArr = $permissionModel->getPermission($parentPath,$this->_user_id);
            if(!isset($permissionArr)){
                $permission = "111111111";
            }else{
                $permission = $permissionArr['permission'];
            }
        }
        $miniPermission = new MiniPermission($permission);
        $canCreateFolder = $miniPermission->canCreateFolder();
        if(!$canCreateFolder){
            throw new MFileopsException( Yii::t('api','no permission'),MConst::HTTP_CODE_432);
        }
        // 查询其是否存在 信息

        $file               = MiniFile::getInstance()->getByPath($path);

        
        // 是否存在相同文件路径, 且被删除的记录
        $hadFileDelete    = false;
        if (isset($file))
        {
            if ($file["is_deleted"] == false)
            {
                $code = $file["file_type"]==MConst::OBJECT_TYPE_FILE ? MConst::HTTP_CODE_402 : MConst::HTTP_CODE_403;
                if ( MUserManager::getInstance()->isWeb() === true){
                    throw new MFileopsException(Yii::t('api','There is already a item at the given destination'), $code);
                }
                $uuid = $file["event_uuid"];
                // 已经存在,403 error
                throw new MFileopsException($code);
            }
            $hadFileDelete = true;
        }
        $this->_parentFilePath = "/{$this->_user_id}";
        // 检查父目录
        $parentFileId        = $this->handlerParentFolder($parentPath);
        $fileDetail           = $this->createFile($path, $parentFileId, $hadFileDelete);
        // 处理不同端，不同返回值
        if (MUserManager::getInstance()->isWeb() === true)
        {
            if ($this->isOutput) {
                $this->buildWebResponse($fileName, $path);
            }
            return ;
        }
        $response                   = array();
        $response["size"]           = "0";
        $response["thumb_exists"]   = false;
        $response["bytes"]          = 0;
        $response["modified"]       = MUtils::formatIntTime($fileDetail["file_update_time"]);
        $path                       = CUtils::removeUserFromPath("{$this->_parentFilePath}/{$fileName}");
        if ($this->share_filter->is_shared) {
            $path                   = $this->share_filter->src_path;
            $path_info              = MUtils::pathinfo_utf($path);
            $path                   = MUtils::convertStandardPath($path_info['dirname']."/".$fileName);
        }
        $response["path"]           = $path;
        $response["is_dir"]         = true;
        $response["icon"]           = "folder";
        $response["root"]           = $root;
        $response["revision"]       = 0; // 版本
        // 增加返回事件uuid，便于客户端进行事件对比逻辑
        $response["event_uuid"]     = $fileDetail["event_uuid"];
        echo json_encode($response);
    }
    
    /**
     * 处理web端输出数据
     */
    public function buildWebResponse($file_name, $path)
    {
        $aid = 0;
        if (isset($_REQUEST['aid'])) {
            $aid = $_REQUEST['aid'];
        }
        // 查询数据库，找出对应id 
        $file = MiniFile::getInstance()->getByPath($path);
        if (!isset($file))
        {
            throw new MFileopsException(
                                        Yii::t('api','Can not find the folder'),
                                        MConst::HTTP_CODE_404);
        }
        $result            = array();
        $result["state"]   = true;
		$result["path"]    = $path;
        $result["code"]    = 0;
        $result["message"] = Yii::t('api_message', 'create_folder_success');
        $result["cname"]   = $file_name;
        $result['aid']     = $aid;
        $result['cid']     = $file["id"];
        echo json_encode($result);
    }
    
    /**
     * 处理检查文件父目录是否存在，不存在将递归依次创建
     * @param string $path          注意：path为 没有加上用户id的正常路径
     * @throws MFileopsException
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function handlerParentFolder($path)
    {
        // TODO: 其他平台
        // windows 平台下，使用dirname "/a" => "\"
        if ($path == "\\" || $path == "/{$this->_user_id}" || $path == "/{$this->_user_id}/")
        {
            return 0;
        }
        // 数据库里面 file_path对应记录为 /user_id/$path
        $file               = MiniFile::getInstance()->getByPath($path);
        $had_file_delete    = false;
        if (isset($file))
        {
            // 检查父目录是否为文件夹
            if ($file["file_type"] == MConst::OBJECT_TYPE_FILE)
            {
                throw new MFileopsException(
                                        Yii::t('api','The parent folder can not be a file'),
                                        MConst::HTTP_CODE_500);
            }
            // 赋值
            if ($this->_init === false)
            {
                $this->_parentFilePath = $file["file_path"];
                $this->_init           = true;
            }
            // 检查该记录是否已被删除
            if ($file["is_deleted"] == false)
            {
                return $file["id"];
            }
            // 记录已被删除
            $had_file_delete = true;
        }

        //创建父目录时进行权限判断,当前文件有权限才进行父目录的创建
        if ($this->share_filter->is_shared) {
            $this->share_filter->hasPermissionExecute($path, MPrivilege::FOLDER_CREATE);
        }
        // 父目录不存在，继续处理父目录
        $parent_path = dirname($path);
        // 赋值
        if ($this->_init === false)
        {
            $this->_parentFilePath = $path;
            $this->_init           = true;
        }
        $parent_file_id = $this->handlerParentFolder($parent_path);
        $this->createFile($path, $parent_file_id, $had_file_delete);
        $file           = MiniFile::getInstance()->getByPath($path);
        if ($file === NULL)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        return $file["id"];
    }
    
    /**
     * 处理创建文件信息及事件
     */
    private function createFile($path, $parent_file_id, $had_file_delete)
    {
        $file_name                       = MUtils::get_basename($path);
        // 组装对象信息
        $file_detail                     = array();
        $file_detail["file_create_time"] = time();
        $file_detail["file_update_time"] = time();
        $file_detail["file_name"]        = $file_name;
        $file_detail["file_path"]        = $path;
        $file_detail["file_size"]        = 0;
        $file_detail["file_type"]        = MConst::OBJECT_TYPE_DIRECTORY;
        $file_detail["parent_file_id"]   = $parent_file_id;
        $file_detail["mime_type"]        = NULL;
        // 保存文件元数据
        if ($had_file_delete)
        {
            $file_detail["event_uuid"]    = MiniUtil::getEventRandomString(MConst::LEN_EVENT_UUID);
            $updates                      = array();
            $updates["file_update_time"]  = time();
            $updates["is_deleted"]        = intval(false);
            $updates["file_type"]         = MConst::OBJECT_TYPE_DIRECTORY;
            $updates["event_uuid"]        = $file_detail["event_uuid"];
            // 存在已被删除的数据，只需更新
            $ret_value                    = MiniFile::getInstance()->updateByPath($path, $updates);
        }
        else 
        {
            $pathArr =explode("/",$path);
            if($this->_user_id!=$pathArr[1]){
                $this->_user_id = $pathArr;
            }
            // 不存在数据，添加
            $ret_value                    = MiniFile::getInstance()->create($file_detail, $this->_user_id);
        }
        if ($ret_value === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        // 保存事件
        $event_action                    = MConst::CREATE_DIRECTORY;
        $ret_value                       = MiniEvent::getInstance()->createEvent(
										        								   $this->_user_id,
										                                           $this->_user_device_id,
										                                           $event_action,
										                                           $file_detail["file_path"],
										                                           $file_detail["file_path"],
										                                           $file_detail["event_uuid"],
										                                           $this->share_filter->type
					                                           					);
        if ($ret_value === false)
        {
            throw new MFileopsException(
                                        Yii::t('api','Internal Server Error'),
                                        MConst::HTTP_CODE_500);
        }
        // 为每个共享用户创建事件
        $this->share_filter->handlerAction($event_action, $this->_user_device_id, $file_detail["file_path"], $file_detail["file_path"]);
        return $file_detail;
    }
}