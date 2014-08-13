<?php
/**
 * Miniyun web文件夹创建
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class CreateFolder extends CApiComponent {
    public $_path; // 创建目录path
    public $cid;   // 文件id
    public $cname; // 文件名
    public $aid;   // 标识
    
    // 创建文件夹事件
    public $_action = 0;
    public $share_filter;
    public $_operator;
    /**
     * 
     * 构造函数，初始化一些参数
     */
    public function __construct() {
        parent::init ();
        $this->result = array ();
        $this->result["state"] = false;
        $this->result["code"]  = 0;
        $this->result["message"] = "创建文件夹失败";
        $this->share_filter = MSharesFilter::init();
    }
    /**
     * 
     * 外部调用入口
     * @param bool $isParentId
     */
    public function invoke($isParentId = true) {
        // TODO 应该使用path创建
        $device = UserDevice::model()->find("user_id={$this->_userId} and user_device_type=1");
        $this->_deviceId = $device["id"];
        $this->_operator = $this->_userId;
        //
        // 空间检查
        //
        $this->handleSpace();
        
        if ($this->_parentId == 0) {
            $this->_path = "/{$this->_userId}/{$this->cname}";
        } elseif ($isParentId) {
            $parent = UserFile::model ()->findByPk ( $this->_parentId );
            if (empty ( $parent )) {
                $this->handleResult ( false, 3, "父目录不存在" );
                return;
            }
            
            if ($parent["file_type"] == 0) {
                $this->handleResult(false, 3, "父目录不存在");
                return ;
            }
            
            $this->_path = $parent["file_path"] . "/" . $this->cname;
            $this->_userId = $parent['user_id'];
        } else {
            $this->_path = "/{$this->_userId}/{$this->_path}";
        }
        //
        // 命名检查
        //
        if (CUtils::checkNameInvalid($this->_path) != 0) {
             $this->result["msg"] = "命名不能包含下列字符串： ^|?*\\<\":>";
             return;
        }
        //
        // 检查是否存在
        //
        $file = UserFile::model()->find(array('condition' => 'file_path=:file_path', 'params' => array(':file_path'=>$this->_path)));
        if ($file) {
            if ($file["is_deleted"] == 0) {
                $this->result["message"] = "文件夹已经存在";
                return ;
            }
        }
        $this->_path = CUtils::convertStandardPath($this->_path);
        //
        // 共享检查
        //
        $index     = strlen ( "/{$this->_userId}" );
        $path      = substr_replace($this->_path, "", 0, $index);
        if ($this->share_filter->handlerCheck($this->_userId, $path)) {
            $this->_userId = $this->share_filter->master;
            $this->_path   = '/'. $this->_userId . $this->share_filter->_path;
        }
        
        $this->cid = $this->handleCreateByPath($this->_path);
        $this->result["state"] = true;
        $this->result["code"]  = 0;
        $this->result["message"] = "创建文件夹成功";
        $this->result["cname"] = $this->cname;
        $this->result['aid']   = $this->aid;
        $this->result['cid']   = $this->cid;
    }
    
    /**
     * 
     * 根据路径创建目录
     */
    public function handleCreateByPath($path) {
        if ($path == "/{$this->_userId}" || $path == "\\" || $path == "/{$this->_userId}/") {
            return 0;
        }
        
        $event_uuid = MiniUtil::getEventRandomString(46);
        $file = UserFile::model()->find(array('condition' => 'file_path=:file_path', 'params' => array(':file_path'=>$path)));
        if (empty($file) || is_null($file)) {
            $pathInfo  = CUtils::pathinfo_utf($path);
            $parenPath = $pathInfo["dirname"];
            $fileName  = $pathInfo["basename"];
            $parentId  = $this->handleCreateByPath($parenPath);
            $file      = new UserFile();
            $file['user_id']          = $this->_userId;
            $file['file_type']        = 1;
            $file['parent_file_id']   = $parentId;
            $file['file_create_time'] = time();
            $file['file_update_time'] = time();
            $file['file_name']        = $fileName;
            $file['file_path']        = $path;
            $file['is_deleted']       = 0;
            $file['version_id']       = 0;
            $file['file_size']        = 0;
            $file["event_uuid"]       = $event_uuid;
            $file->save();
            $file['sort']             = $file['id'];
            $file->save();
            
            // 创建事件
            MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, $this->_action, $path, $path, $event_uuid, $this->share_filter->type);
            $this->share_filter->handlerAction($this->_action, $this->_deviceId, $path, $path);
        } else {
            //
            // 回收站插件: -1保留值 0正常 1删除
            // 这里由is_deleted==1 特别修改为 is_deleted!=0
            // By Kindac 2012/11/5
            //
            if ($file["is_deleted"] != 0) {
                $file["is_deleted"]       = 0;
                $file["file_update_time"] = time();
                $file["event_uuid"]       = $event_uuid;
                //
                // 递归创建父目录
                //
                $pathInfo  = CUtils::pathinfo_utf($path);
                $parenPath = $pathInfo["dirname"];
                $this->handleCreateByPath($parenPath);
                
                $file->save();
                MiniEvent::getInstance()->createEvent($this->_userId, $this->_deviceId, $this->_action, $path, $path, $event_uuid,$this->share_filter->type);
                $this->share_filter->handlerAction($this->_action, $this->_deviceId, $path, $path);
            }
        }
        
        return $file["id"];
    }
    
    
    /**
     * (non-PHPdoc)
     * @see CApiComponent::handleException()
     */
    public function handleException($exception) {
        echo CJSON::encode ( $this->result );
    }

}
?>