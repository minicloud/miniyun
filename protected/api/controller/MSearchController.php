<?php
/**
 * Miniyun 处理查找数据
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MSearchController extends MApplicationComponent implements MIController {
    private $_root = null;
    private $_user_id = null;
    private $_locale = null;
    /**
     * 控制器执行主逻辑函数
     */
    public function invoke($uri = null) {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        $params = $_REQUEST;
        // 检查参数
        if(isset($params) === false || $params == null) {
            throw new MAuthorizationException(Yii::t('api','Invalid parameters'));
        }
        $url_manager = new MUrlManager();
        $path = MUtils::convertStandardPath($url_manager->parsePathFromUrl($uri));
        $root = $url_manager->parseRootFromUrl($uri);
        // 去掉问号后面参数
        if($pos = strpos($this->_root, '?')) {
            $this->_root = substr($this->_root, 0, $pos);
        }
        if($pos = strpos($path, '?')) {
            $path = substr($path, 0, $pos);
        }
        // 获取用户数据，如user_id
        $user           = MUserManager::getInstance()->getCurrentUser();
        $this->_user_id = $user["user_id"];
        
        $query = "";
        if(isset($params["query"])) {
            $query = $params["query"];
        }
        $file_limit = 10000;
        if(isset($params["file_limit"])) {
            $file_limit = $params["file_limit"];
        }
        $include_deleted = false; // 处理删除的同样也需要返回
        if(isset($params["include_deleted"])) {
            $include_deleted = $params["include_deleted"];
            if(is_string($include_deleted) === true) {
                if(strtolower($include_deleted) === "false") {
                    $include_deleted = false;
                } elseif(strtolower($include_deleted) === "true") {
                    $include_deleted = true;
                }
            }
        }
        $this->_locale = "bytes";
        if(isset($params["locale"])) {
            $this->_locale = $params["locale"];
        }
        $callback     = null;
        if(isset($params["callback"])) {
            $callback = $params["callback "];
        }
        $path        = "/{$this->_user_id}{$path}";
        $path        = MUtils::convertStandardPath($path) . "/";
        if($query === "") {
            throw new MFileopsException(Yii::t('api','bad request'), MConst::HTTP_CODE_400);
        }
        // 查询其 信息
        $operator = $this->_user_id;
        $sharefilter = MSharesFilter::init();
        $sharefilter->handlerCheck($this->_user_id, CUtils::removeUserFromPath($path));
        if($sharefilter->is_shared) {
            $operator = $sharefilter->master;
            $qpath = '/' . $sharefilter->master . $sharefilter->_path;
            $query_db_file = MFiles::searchFilesByPath($qpath, $query, $sharefilter->master, $include_deleted);
            
            // 判断搜索出来的文件是否有权限访问
            foreach($query_db_file as $index => $file) {
                // 列表权限，如果没有列表权限，则不进行显示
                try {
                    $sharefilter->hasPermissionExecute($file['file_path'], MPrivilege::RESOURCE_READ);
                } catch(Exception $e) {
                    unset($query_db_file[$index]);
                    continue;
                }
            }
        } else {
            $query_db_file = MFiles::searchFilesByPath($path, $query, $this->_user_id, $include_deleted);
        }
        
        //
        // 查询根目录
        //
        $retval = $this->handleSearchRoot($path, $query);
        $query_db_file = array_merge($query_db_file, $retval);
        
        // if (count($query_db_file) > $file_limit)
        // {
        // throw new MFileopsException(
        // Yii::t('api','Too many file entries to return'),
        // MConst::HTTP_CODE_406);
        // }
        $keys = array();
        $response = array();
        foreach($query_db_file as $key => $db_file) {
            if ($key >= $file_limit)
                break;
            $file_array = array();
            $mime_type = null;
            if($db_file["file_type"] == MConst::OBJECT_TYPE_FILE) {
                $version = MiniVersion::getInstance()->getVersion($db_file["version_id"]);
                if($version) {
                    $mime_type = $version["mime_type"];
                }
            }
            $file_array = $this->assembleResponse($file_array, $db_file, $mime_type);
            #这里对数据进行了二次过滤，如果路径是一致的，则过滤掉
            #TODO 这里的冗余数据初步分析是权限导致的，二次重构要去掉这个代码
            $path = $file_array["path"];
            if(!array_key_exists($path, $keys)){
                $keys[$path] = $file_array;
                array_push($response, $file_array);
            }
        }
        echo json_encode($response);
    }
    
    /**
     *
     *
     * 搜索公共目录，共享目录
     *
     */
    public function handleSearchRoot($path, $query) {
        $path = MUtils::convertStandardPath($path);
        //
        // 搜索共享目录,根目录查询
        //
        if($path != '/' . $this->_user_id) {
            return array();
        }
        
        $access = new SharesAccessFilter();
        $sharedpaths = $access->handleGetAllSharesFolder($this->_user_id);
        $query = str_replace("%", "\\%", $query);
        $sql = ' file_name like "%' . $query . '%"';
        $retval = array();
        foreach($sharedpaths as $sharedpath) {
            $condition = $sql . ' and file_path like "' . $sharedpath . '/%" ';
            $files = MFiles::findAll($condition);
            if(empty($files)) {
                continue;
            }
            $retval = array_merge($retval, $files);
        }
        
        $var = apply_filters('documents_filter', null);
        if(is_array($var) && !empty($var)) {
            $ids = join(',', $var);
            $sql = ' file_name like "%' . $query . '%"';
            $sql .= ' and id in (' . $ids . ')';
            $files = MFiles::findAll($sql);
            $retval = array_merge($retval, $files);
        }
        
        // 判断搜索出来的文件是否有权限访问
        $share_filter = MSharesFilter::init();
        foreach($retval as $index => $ret) {
            // 列表权限，如果没有列表权限，则不进行显示
            if(MUtils::isShareFolder($ret['file_type'])) {
                try {
                    $share_filter->hasPermissionExecute($ret['file_path'], MPrivilege::RESOURCE_READ);
                } catch(Exception $e) {
                    unset($query_db_file[$index]);
                    continue;
                }
            }
        }
        
        return $retval;
    }
    
    /**
     * 处理组装请求元数据
     *
     */
    private function assembleResponse($response, $file_detail, $mime_type) {
        $file_path = $file_detail["file_path"];
        $share_filter = MSharesFilter::init();
        $share_filter->handlerCheck($file_detail['user_id'], $file_path);
        if($share_filter->is_shared && $share_filter->operator != $file_detail['user_id'] && $share_filter->type == 0) {
            $path = $this->share_filter->slaves[$this->_user_id];
            $index = strlen($this->share_filter->_shared_path);
            $file_path = substr_replace($file_path, $path, 0, $index);
            $index = strlen("/{$this->share_filter->operator}");
            $file_path = substr_replace($file_path, "", 0, $index);
        } else {
            $file_path = CUtils::removeUserFromPath($file_path);
        }
        $version = MiniVersion::getInstance()->getVersion($file_detail["version_id"]);
        $response["size"] = MUtils::getSizeByLocale($this->_locale, $file_detail["file_size"]);
        $response["bytes"] = (int)$file_detail["file_size"];
        $response["path"] = $file_path;
        $response["modified"] = MUtils::formatIntTime($file_detail["file_update_time"]);
        $response["rev"] = strval($file_detail["version_id"]);
        if(!empty($version)){
            $response["hash"] = $version["file_signature"];
        }
        $response["revision"] = intval($file_detail["version_id"]);
        $response["root"] = $this->_root;
        $response["thumb_exists"] = false;
        $response["sort"] = (int)$file_detail["sort"];
        
        $is_dir = true;
        if($file_detail["file_type"] == MConst::OBJECT_TYPE_FILE) {
            $is_dir = false;
        }
        $response["is_dir"] = $is_dir;
        if(!empty($mime_type)) {
            $response["mime_type"] = $mime_type;
        }
        $response['type']=$file_detail["file_type"];
        return $response;
    }
} 