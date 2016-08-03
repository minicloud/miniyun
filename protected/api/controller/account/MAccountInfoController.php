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
        $spaceInfo = MiniUser::getInstance()->getSpaceInfo($user);
        $data = array();
        $data['user_name']         = $user["user_name"];
        $data['display_name']      = $user["nick"];
        $data['id']                = $user["id"];
        $data['uid']               = $user["user_uuid"];
        $data['space']             = (double)$$spaceInfo["space"];
        $data['used_space']        = (double)$$spaceInfo["usedSpace"];
        $data['email']             = $user["email"];
        $data['phone']             = $user["phone"];
        $data['avatar']            = $user["avatar"];
        $data['mult_user']         = false;
        $data['site_id']           = MiniSiteUtils::getSiteID();
        $data["device_id"]         = $device["id"];


        $value  = MiniOption::getInstance()->getOptionValue('site_company');
        if (isset($value)){
            $license['company']  = $value;
        }else{
            $license['company']  = "";
        }
        $license['licensestr']   = "";//免费版本
        $license['is_vip']       = false;

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
        $uploadMaxFileSize = CUtils::return_bytes(ini_get('upload_max_filesize'));
        
        $min = $postMaxSize > $uploadMaxFileSize ? $uploadMaxFileSize : $postMaxSize;
        
        $data['block_size'] = $min > $blockSize ? $blockSize : $min;
        if ($data['block_size'] == $postMaxSize && $data['block_size'] == $uploadMaxFileSize) {
            $data['block_size'] = $data['block_size'] - 104858;
        }

        echo json_encode($data);
    }

}