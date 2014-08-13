<?php
/**
 * 获取用户基本信息的入口地址
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MAccountInfoController extends MApplicationComponent implements MIController{
    /**
     * 控制器执行主逻辑函数
     * @param $uri 地址
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null)
    {
        $user     = MUserManager::getInstance()->getCurrentUser();
        $device   = MUserManager::getInstance()->getCurrentDevice();
        $data = array();
        $data['user_name']         = $user["user_name"];
        $data['display_name']      = $user["nick"];
        $data['id']                = $user["id"];
        $data['uid']               = $user["user_uuid"];
        $data['space']             = (double)$user["space"];
        $data['used_space']        = (double)$user["usedSpace"];
        $data['email']             = $user["email"];
        $data['phone']             = $user["phone"];
        $data['avatar']            = $user["avatar"];
        $data['mult_user']         = false;
        $data['site_id']           = MiniSiteUtils::getSiteID();
        $data["device_id"]         = $device["id"];
        // 获取用户所在目录
        // by Kindac
        $value = apply_filters('get_user_path', $user["id"]);
        if ($value != $user["id"]) {
            $data['object_path'] = $value;
        } 
        // 获取用户所在目录 如果特殊phone,需要特殊处理路径
        // by Kindac
        // @since 2013/2/4
        $value = apply_filters('get_user_path_by_phone', $user["id"]);
        if ($value != $user["id"]) {
            $data['object_path'] = $value;
        }
        // 调用windows客户端多账号hook
        if(apply_filters("windows_mult_client")){
            $data['mult_user']     = 1;
            $data['os_user_first'] = 0;//TODO 加上是否优先OS账号登陆
        }

        $value  = MiniOption::getInstance()->getOptionValue('site_company');
        if (isset($value)){
            $license['company']  = $value;
        }else{
            $license['company']  = "";
        }
        $license['licensestr']   = "";//免费版本
        $license['is_vip']       = false;

        //jim 添加插件管理员自定义同步模式
        $syncModeConfig = apply_filters('client_sync_mode_config');
        if (!empty($syncModeConfig)) {
        	$data['syncModeConfig'] = $syncModeConfig;
        }
    	
        //jim add MINIYUN-1538 添加插件客户端参数
        $clientParameters = apply_filters('clientParameters');
        if (!empty($clientParameters)) {
        	$data['parameters'] = $clientParameters;
        }
        // 目前针对iis服务器，使客户端禁用put协议
        if (isset($_SERVER['SERVER_SOFTWARE']) && 
            strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'iis') !== false)
        {
            $data['disable_put']    = true;
        }
        
        // 32M
        $blockSize         = 4 * 1024 * 1024;
        // 内存配置需要
        $mem_limit         = CUtils::return_bytes(ini_get('memory_limit'));
        if ($mem_limit < 4 * $blockSize) {
            $blockSize = $mem_limit / 4;
        }
        $postMaxSize       = CUtils::return_bytes(ini_get('post_max_size'));
        $uploadMaxFilesize = CUtils::return_bytes(ini_get('upload_max_filesize'));
        
        $min = $postMaxSize > $uploadMaxFilesize ? $uploadMaxFilesize : $postMaxSize;
        
        $data['block_size'] = $min > $blockSize ? $blockSize : $min;
        if ($data['block_size'] == $postMaxSize && $data['block_size'] == $uploadMaxFilesize) {
            $data['block_size'] = $data['block_size'] - 104858;
        }
        
        $data = apply_filters("account_info_add", $data);
        $data = $this->iniCustomMenu($data);
        echo json_encode($data);
    }

    /**
     *为客户端初始化自定义菜单
     */
    private function iniCustomMenu($data){
        $config = apply_filters('custom_client_menu');
        if (!empty($config)) {
            $data["custom_client_menu"] = $config;
        }
        return $data;
    }
}