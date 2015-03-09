<?php
/**
 * Miniyun 处理文件共享
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MSharesFilter
{
    // 标示是否属于共享文件夹
    public $is_shared = false;
    // 共享文件夹所有者id
    public $master = NULL;
    // 共享文件夹使用者的id
    public $slaves = array();
    public $operator = NULL;
    public $master_nick = null;
    public $_path;
    public $_shared_path;
    // 是共享目录自身操作
    public $_is_shared_path = false;
    public $_file;
    public $src_path;
    public $file_type = 0; // metadata使用
    /**
     *
     * @var 导航停止id
     */
    public $stop = 0;
    /* 查询出临时存储 */
    public $tmp_file;
    /* 类型，区别共享和公共目录 */
    public $type = 0;
    /**
     * 初始化过滤器
     *
     */
    public static function init() {
        return new MSharesFilter();
    }

    /**
     * 构造函数初始化异常处理信息
     */
    public function __construct () {
    }

    /**
     * 根据file检查是否是属于共享
     */
    public function handlerCheckByFile($userId, $file) {
        $this->is_shared = false;
        $this->operator  = $userId;
        $this->master    = $file['user_id'];
        $path            = $file['file_path'];
        $paths = CUtils::assemblyPaths($path);

        $access     = new SharesAccessFilter();
        $sharePaths = $access->handleGetAllSharesFolder($userId);

        $sharedPath = '';
        foreach ($sharePaths as $sharePath) {
            if (isset($paths[$sharePath])) {
                $sharedPath = $sharePath;
            }
        }
        $file_meta = FileMeta::model()->findByAttributes(array('meta_key' => 'shared_folders', 'file_path' => $sharedPath));
        if (empty($file_meta)) {
            return false;
        }

        $meta_value   = unserialize($file_meta['meta_value']);
        $this->master = $meta_value['master'];
        $this->slaves = $meta_value['slaves'];
        // 共享者的共享目录path
        $this->_shared_path = $meta_value['path'];
        $path = '/' . $this->operator . $path;
        $this->_path = substr_replace($path, $this->_shared_path, 0, strlen($sharePath));
        $this->_path = substr_replace($this->_path, '', 0, strlen('/' . $this->master));
        //
        // 如果这两个地址相同，则表示是共享目录本身的操作
        //
        if ($this->_shared_path == '/' . $this->master . $this->_path) {
            $this->_is_shared_path = true;
        }

        $user = User::model()->findByPk($this->master);
        if (! $user) {
            throw new Exception(Yii::t('api','Internal Server Error'), '500');
        }
        $this->master_nick = $user['user_name'];
        $this->is_shared = true;
        return $this->is_shared;
    }

    /**
     *
     *
     * @param int $action
     * @param int $device_id
     * @param string $path
     * @param mixed context  -
     */
    public function handlerAction ($action, $device_id, $path, $context = '')
    {
        //
        // 如果不是在共享文件夹中做操作，则不做处理
        //
        if ($this->is_shared == false) {
            return;
        }
        if (!$path) {
            return;
        }
        $index     = strlen ( "{$this->_shared_path}" );
        $path      = substr_replace($path, "", 0, $index);
        // 根据不同的事件处理
        switch ($action) {
            case 3:
            case 4:
                $context = serialize($context);
                break;
            default:
                $context = substr_replace($context, "", 0, $index);
                break;
        }

        $text = $context;
        // 为每个成员添加事件
        foreach ($this->slaves as $slaves_id => $v) {
            if ($action != 3 && $action != 4) {
                $text = $v . $context;
            }
            MiniEvent::getInstance()->createEvent($slaves_id, $device_id, $action, $v.$path, $text, MiniUtil::getEventRandomString(46));
        }
    }

    /**
     * 如果是在根目录下，则获取用户的共享目录
     */
    public function handlerGetShared($user_id, $include_deleted = false, $root = 'miniyun', $contents = array()) {
        $search = '/'. $user_id . '/';
        $file_metas = FileMeta::model()->findAll("meta_key=:type1 and file_path like :type2",
        array(':type1'=>'shared_folders', ':type2'=>$search."%"));
        //
        // 组装conents
        //

        foreach ($file_metas as $meta) {
            $file_path    = $meta['file_path'];
            $meta_value   = unserialize($meta['meta_value']);
            $this->master = $meta_value['master'];
            $index     = strlen ( "/{$user_id}" );
            $path      = substr_replace($file_path, "", 0, $index);

            $attr = array('file_path' => '/' . $this->master . $path);
            if (!$include_deleted) {
                $attr['is_deleted'] = 0;
            }
            $file = UserFile::model()->findByAttributes($attr);
            if (!$file || $file['file_type'] == 0) {
                continue;
            }

            $content = array();
            $content["size"]                   = '0 bytes';
            $content["bytes"]                  = 0;
            $content["path"]                   = $path;
            $content["modified"]               = CUtils::formatIntTime($file["file_update_time"]);
            $content["revision"]               = intval($file["version_id"]);
            $content["rev"]                    = strval($file["version_id"]);
            $content["root"]                   = $root;
            $content["hash"]                   = "";
            $content["event"]                  = $file["event_uuid"];
            $content["is_deleted"]             = $file['is_deleted'];
            $content["is_dir"]                 = true;
            array_push($contents, $content);
        }
        return $contents;
    }

    /**
     *
     * 重命名共享目录
     * @param string $from_path
     * @param string $to_path
     */
    public function handlerRenameShared($from_path, $to_path) {
        $share_manager = new ShareManager();
        $share_manager->_userId = $this->operator;
        $share_manager->_from   = $from_path;
        $share_manager->_to     = $to_path;
        $share_manager->invoke(ShareManager::RENAME_SHARED);
    }

    /**
     *
     * 根据id检查是否是共享目录
     */
    public function handlerCheckById($userId, $id) {
        $this->stop     = 0;
        $file           = MiniFile::getInstance()->getById($id);
        if ($file === NULL) {
            return false;
        }
        $this->tmp_file = $file;

        $paths = CUtils::assemblyPaths($file['file_path']);
        $keys  = array_keys($paths);
        // 去掉 /{user_id}
        if (count($keys) > 0) {
            unset($paths[$keys[0]]);
        }
        $access = new SharesAccessFilter();
        $sharePaths = $access->handleGetAllSharesFolder($userId);

        $sharedPath = '';
        foreach ($sharePaths as $sharePath) {
            if (isset($paths[$sharePath])) {
                $sharedPath = $sharePath;
            }
        }
        $meta        = MiniFileMeta::getInstance()->getFileMeta($sharedPath,'shared_folders');
        if ($meta ===NULL ) {
            return false;
        }
        $meta_value  = unserialize($meta['meta_value']);
        $slaves      = $meta_value['slaves'];
        if ($userId === $meta_value['master']) {
            return $file;
        }
        if (!isset($slaves[$userId])) {
            return false;
        }

        $this->_file = UserFile::model()->findByAttributes(array('file_path' => $slaves[$userId]));
        if ($file['file_type'] == 3 || $file['file_type'] == 4) {
            $file = UserFile::model()->findByAttributes(array('file_path' => $meta_value['path']));
            $this->stop = $file['parent_file_id'];
        }
        elseif ($file['file_type'] == 2) {
            $this->stop = $file['parent_file_id'];
        } else {
            $share = UserFile::model()->findByAttributes(array('file_path' => $meta_value['path']));
            if ($share) {
                $this->stop = $share['parent_file_id'];
            }
        }

        return $file;
    }

    /**
     *
     * 统计共享入口，将用户所有共享中的文件(夹查询出来),被共享的部分
     */
    public function handlerFilterDocuments($user_id, $type = NULL) {
        $this->operator = $user_id;
        $conditions = "file_path in (SELECT file_path FROM ". UserFile::model()->tableName();
        $conditions .= " WHERE user_id=:user_id AND file_type > :file_type)";
        $params = array('user_id'=>$user_id, ':file_type'=>2);
        $metas  = FileMeta::model()->findAllByAttributes(array('meta_key'=>ShareManager::SHARED_META_FLAG), $conditions, $params);
        $ids = array();
        $condition = 'SELECT id FROM ' . UserFile::model()->tableName() . ' WHERE file_path like :file_path';
        $params = array();
        if (!empty($type)) {
            $condition .= ' AND file_type=:file_type';
            $params[':file_type'] = $type;
        }

        foreach ($metas as $meta) {
            $meta_value = unserialize($meta['meta_value']);
            $path = $meta_value['path'];
            if ($meta_value['master'] == $user_id) {
                continue;
            }
            $params[':file_path'] = $path . '/%';
            $files = UserFile::model()->findAllBySql($condition, $params);
            foreach ($files as $file) {
                array_push($ids, $file['id']);
            }
        }


        return join(',', $ids);
    }

    /**
     * 根据path找到共享
     */
    public function handlerFindSlave($user_id, $path)
    {
        $meta = FileMeta::model()->findByAttributes(array('file_path' => $path, 'meta_key'=>ShareManager::SHARED_META_FLAG));
        if (!$meta) {
            return false;
        }

        $meta_value = unserialize($meta['meta_value']);

        $slaves = $meta_value['slaves'];
        $slaves[$meta_value['master']] = $meta_value['path'];
        if (isset($slaves[$user_id])) {
            $file = UserFile::model()->findByAttributes(array('file_path' => $meta_value['path']));
            if ($file) {
                return $file['id'];
            }
        }

        return false;
    }

    /**
     * 根据path检查是否属于共享
     * @param integer $userId
     * @param string $path        -  不含user_id的路径
     */
    public function handlerCheck($userId, $path, $action = false) {
        $this->is_shared = false;
        $this->operator  = $userId;
        $this->master    = $userId;
        $this->src_path  = $path;
        $this->_path     = $path;
        $parts           = explode('/', $path);
        // 去掉空值
        $parts           = array_filter($parts);
        // 组装路径
        $paths           = array();
        for ($i = 0; $i < count($parts); $i++) {
            $tarray      = array_slice($parts, 0, $i + 1);
            $tmp         = join('/', $tarray);
            $paths['/' . $userId . '/' . $tmp] = $tmp;
        }
        //根据用户查询属于用户的共享
        $files           = MiniFile::getInstance()->getShares($userId);
        $sharedPath      = '';
        foreach ($files as $file) {
            //表示这是共享目录
            if (isset($paths[$file['file_path']])) {
                $sharedPath = $file['file_path'];
                break;
            }
        }
        if ($sharedPath == '') {
            return false;
        }
        $file_meta = MiniFileMeta::getInstance()->getFileMeta($sharedPath,"shared_folders");
        if ($file_meta ===NULL ) {
            return $this->is_shared;
        }
        $meta_value         = unserialize($file_meta['meta_value']);
        $this->master       = $meta_value['master'];
        $this->slaves       = $meta_value['slaves'];
        // 共享者的共享目录path
        $this->_shared_path = $meta_value['path'];
        $path               = '/' . $this->operator . $path;
        $this->_path        = substr_replace($path, $this->_shared_path, 0, strlen($sharedPath));
        $this->_path        = substr_replace($this->_path, '', 0, strlen('/' . $this->master));
        // 如果这两个地址相同，则表示是共享目录本身的操作
        if ($this->_shared_path == '/' . $this->master . $this->_path) {
            $this->_is_shared_path = true;
        }

        // 判断用户是否对共享文件夹下面的文件拥有权限
        $this->hasPermission($this->_shared_path, $action);

        $this->file_type = $file['file_type'];

        $muser = User::model()->findByPk($this->master);
        if (! $muser) {
            throw new Exception(Yii::t('api','Internal Server Error'), '500');
        }
        $this->master_nick = $muser['user_name'];
        $this->is_shared   = true;
        return $this->is_shared;
    }

    /**
     * 检查是否在同一个共享目录下移动
     * @param integer $from_master
     * @param integer $to_master
     * @param string $from_path
     * @param string $to_path
     * @return boolean
     */
    public function handlerCheckMove($from_master, $to_master, $from_path, $to_path, $to_is_shared = FALSE) {
        if ($this->is_shared == false && $to_is_shared == false)
        return false;

        //
        // 在共享目录内部拷贝
        //
        $to = '/' . $to_master . $to_path;
        if (!empty($this->_shared_path) && strpos($to, $this->_shared_path . '/') === 0) {
            return false;
        }
        $from_parent = CUtils::pathinfo_utf($from_path);
        $to_parent   = CUtils::pathinfo_utf($to_path);
        if ($from_master == $to_master && $to_parent['dirname'] == $from_parent['dirname']) {
            return false;
        }

        return true;
    }

    /**
     *
     * 判断是否具有相应动作的权限，如果不具备则抛出异常 409
     * @since 1.0.7
     * @param string $path
     * @param integer $action
     */
    public function hasPermission($path, $action, $type = NULL) {
        if ($action === false) {
            return;
        }

        $privilege = '';
        switch ($action) {
            case MConst::CREATE_DIRECTORY:
                $privilege = MPrivilege::FOLDER_CREATE;
                break;
            case MConst::DELETE:
                ;
                break;
            case MConst::MOVE:
                ;
                break;
            case MConst::CREATE_FILE:
                $privilege = MPrivilege::FILE_CREATE;
                break;
            case MConst::MODIFY_FILE:
                $privilege = MPrivilege::FILE_MODIFY;
                break;
            default:
                ;
                break;
        }
        if (empty($privilege)){
            return;
        }
        return $this->hasPermissionExecute($path, $privilege);
    }

    /**
     *
     * 判断是否具有相应动作的权限
     * @since 1.0.7
     * @param string $path
     * @param integer $action
     */
    public function hasPermissionExecute($path, $privilege, $operateName = null) {
        $hasPermission = Yii::app()->privilege->hasPermission($path, $privilege);
        if (empty($operateName)){
            $operateName   = Yii::app()->privilege->operateName;
        }
        if (!$hasPermission) {
            throw new MException(Yii::t('api_message', 'no permission', array("{operateName}"=>$operateName)), MConst::HTTP_CODE_409);
        }
    }
}
