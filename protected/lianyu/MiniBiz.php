<?php

/**
 * 迷你云业务逻辑基类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniBiz
{
    /**
     * 当前登录的用户
     * @var
     */
    protected $user;
    /**
     * 当前登录的设备
     * @var
     */
    protected $device;

    public function MiniBiz(){
        $this->user    = MUserManager::getInstance()->getCurrentUser();
        $this->device  = MUserManager::getInstance()->getCurrentDevice();
    }
}