<?php
/**
 * Miniyun Meta异常处理类
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MMetaException extends MException
{
    /**
     * 构造函数
     * @param string $message 错误消息
     * @param integer $code 错误代码
     */
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
    }
}
?>