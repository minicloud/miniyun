<?php
/**
 * Miniyun 所有的接口框架
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

interface MIApplicationComponent
{
    /**
     * 初始化程序模块
     */
    public function init();

    /**
     * @return boolean 方法是否是完成了初始化操作.
     */
    public function isInitialized();
}