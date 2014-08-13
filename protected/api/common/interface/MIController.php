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
 interface MIController
{
    /**
     * 控制器执行主逻辑函数
     */
    public function invoke($uri=null);
}