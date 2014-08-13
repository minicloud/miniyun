<?php
/**
 * 迷你云业务逻辑基类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniException extends CHttpException
{
    public function __construct($code){
        //303是HTTP标准的see other error错误码
        //通过它的message返回详情业务错误码
        parent::__construct(403,$code,0);
    }
}