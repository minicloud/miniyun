<?php
/**
 * 应用程序模块
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

interface IUserSourceInterface
{
    /**
     *
     * 获取用户信息
     * 包含用户信息的数组
     * @param mixed $userInfo
     * @return array
     */
    public function getUser($userInfo);
    
    /**
     *
     * 是否需要验证自身数据源
     */
    public function judgeSelf();
}