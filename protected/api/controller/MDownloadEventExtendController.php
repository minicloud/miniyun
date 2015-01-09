<?php
/**
 * Miniyun windows客户端事件机制获取地址
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MDownloadEventExtendController extends MApplicationComponent implements MIController {
	private $cache;
	/**
	 * (non-PHPdoc)
	 * @see MIController::invoke()
	 */
	public function invoke($uri = null) {
		// 调用父类初始化函数，注册自定义的异常和错误处理逻辑
		parent::init ();
		$params = $_REQUEST;
		// 获取用户数据，如user_id
		$user                  = MUserManager::getInstance ()->getCurrentUser ();
		$device                = MUserManager::getInstance ()->getCurrentDevice ();
		$this->_user_id        = $user["user_id"];
		$this->_user_device_id = $device["device_id"];

		$this->_file_limit = 10000;
		if (isset ( $params ["file_limit"] )) {
			$this->_file_limit = ( int ) $params ["file_limit"];
		}

		//
		// 最大返回值超过10000，则产生406错误
		//
		if ($this->_file_limit > 10000) {
			throw new Exception ( Yii::t('api',MConst::NOT_ACCEPTABLE ), MConst::HTTP_CODE_406 );
		}

		$this->_locale = "bytes";
		if (isset ( $params ["locale"] )) {
			$this->_locale = $params ["locale"];
		}
		// 当前event
		$this->_event = "0";
		if (isset ( $params ["event"] )) {
			$this->_event = $params ["event"];
		}
		//从MemCache读取状态，如果状态不满足则直接从cache读取状态
		$this->cache = new ClientEventCache();
		if($this->cache->check2Db($this->_user_id,$this->_user_device_id,$this->_event)){
			$this->build_events ();
		}else{
			echo json_encode($this->cache->getValue());
		}
	}

	/**
	 * 获取用户事件
	 *
	 * @since 1.0.7
	 */
	public function build_events() {
		
		$event_id = intval($this->_event); 
		
		// 更新device_neta数据库表中 用户的pc端请求event最小值
		// 没有设置返回值，创建和更新记录发挥值无用
		if ($event_id > 0) {
			$user_id   = $this->_user_id;
			$device_id = $this->_user_device_id;
			$meta      = MiniUserDeviceMeta::getInstance()->getEvent($device_id);
			if ($meta === NULL){
				//不存在记录，重新创建
				MiniUserDeviceMeta::getInstance()->create($user_id, $device_id, "event_id", $event_id);
			} elseif ($event_id > $meta['meta_value']) {
				//存在记录且大于数据库的值  则更新
				MiniUserDeviceMeta::getInstance()->updateEvent($device_id,$event_id);
			} 
		}
		$events    = MiniEvent::getInstance()->getAll($this->_user_id,$event_id,$this->_file_limit);
		$response  = array ();
		$contents  = array ();

		if ($events === NULL || count ( $events ) ===0) {
			//Cache到MemCache中
			$this->cache->setPoint($this->_user_id,$this->_user_device_id,$this->_event);

			$response ["contents"] = array ();
			echo json_encode ( $response );
			return ;
		}

		// 组装返回值
		foreach ( $events as $event ) {
			$file_path = CUtils::removeUserFromPath($event["file_path"]);
			$action    = (int)$event ["action"];
			$context   = $event ["context"];

            //进行event事件权限判断， 判断CREATE_FILE, CREATE_DIRECTORY是否有权限读取
            if ($action == MConst::CREATE_FILE || $action == MConst::CREATE_DIRECTORY){
                $shareFilter = MSharesFilter::init();
                $is_shared = false;
                try {
                    $is_shared = $shareFilter->handlerCheck($this->_user_id, $file_path);
                } catch (Exception $e) {
                }
                if ($is_shared){
                    try {
                        $shareFilter->hasPermissionExecute($event["file_path"], MPrivilege::RESOURCE_READ);
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }

            //判断event是否正常，不正常抛出异常排除掉
            try {
                do_action("check_event", $event);
            } catch (Exception $e) {
                continue;
            }
            
            //在swith中是否需要继续执行
            $needContinue = true;
            switch ($action) {
                case MConst::UPDATE_SPACE_SIZE:
                    // 老数据 是 /userid + 空间大小
                    $context = str_replace("/".$this->_user_id,"", $context);
                    break;
                case MConst::CREATE_FILE:
                case MConst::MODIFY_FILE:
                    $context = unserialize($context);
                    if ($context == FALSE) continue;
                    if (!isset($context['update_time']))
                    $context['update_time'] = time();
                    if (!isset($context['create_time']))
                    $context['create_time'] = time();
                    $mime_type = CUtils::mime_content_type($file_path);
                    $context["thumb_exists"] = MUtils::isExistThumbnail($mime_type, (int)$context['bytes']);
                    break;
                case MConst::DEFAULT_PERMISSION_CHANGE_TO_CAN_NOT_READ:
                case MConst::DEFAULT_PERMISSION_CHANGE_TO_CAN_READ:
                    $needContinue = $this->checkDefaultPermissionChange($this->_user_id, $event["file_path"]);
                    break;
                case MConst::CANCEL_SHARED:
                    if ($this->_user_id != $event['user_id']) {
                        $action = MConst::DELETE;
                    }
                case MConst::SHARE_FOLDER:
                    $action = apply_filters('share_folder_action_adjust', $action, $this->_user_id, $event);
                    if ($this->_user_id == $event['user_id'] && $action == MConst::SHARE_FOLDER) {
                        $device = new MUserDevice();
                        if ($device->queryUserDeviceById($event['user_device_id'])) {
                            if ($this->_user_id == $device->user_id) {
                                $action = MConst::SHARED_ICON;
                            }
                        }
                    }
                default:
                    $context  = CUtils::removeUserFromPath($context);
                    break;
            }
            if (!$needContinue){
                continue;
			}
			// 添加hook，修改meta值
			$context            = apply_filters('meta_add', $context);
            $content = array ();
            $content["action"]  = $action;
            $content["path"]    = $file_path;
            $content["context"] = $context;
            //支持类s3数据源的文件下载
            $data = array("hash" => $context["hash"], "event" => $event);
            $download_param = apply_filters("event_params", $data);
            if ($download_param !== $data){
                if (is_array($download_param)){
                    $content = array_merge($content, $download_param);
                }
			}
			// 从0.9.3版本开始事件机制取消event_uuid逻辑，直接返回整型的id
			$content["event"]       = $event["id"];
			$content['device_id']   = $event["user_device_id"];
			// 从0.9.9版本开始增加返回event_uuid，便于客户端进行文件同步排除逻辑
			$content["event_uuid"]  = $event["event_uuid"];
			array_push ( $contents, $content );
		}
		$response["contents"] = $contents;
		echo json_encode($response);
	}

    /**
     *
     * 检查默认权限变更情况下的是否需要返回事件
     * return true 需要返回事件， false 不需要返回事件
     * 
     * @since 1.0.7
     */
    public function checkDefaultPermissionChange($user_id, $path){
        //属于自己则排除
        $own_user_id = CUtils::getUserFromPath($path);
        if ($user_id == $own_user_id){
            return false;
        }
        //如果用户对该文件夹存在直接权限则， 不用理会默认权限的设置
        $permission = Yii::app()->privilege->getUserFilePrivilegeOnly($user_id, $path);
        if ($permission){
            return false;
        }
        return true;
    }
}
?>