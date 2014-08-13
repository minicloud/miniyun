<?php
/**
 * Miniyun 验证的接口
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
interface MIAuthrization
{
    /**
     * 验证访问token和secret
     */
    public function validAccessToken($token, $secret);
}
