<?php
/**
 * 
 * 文件hash计算，为插件提供计算文件hash值的接口，
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
abstract class MHashAlgorithm {
    /* 计算的hash */
    public $hash  = FALSE;
    
    /* 文件path */
    public $path;
    
    /**
     * 
     * 初始化对象参数
     */
    public function __construct($path = NULL) {
        $this->path = $path;
    }
    
    /**
     * 计算文件hash值并返回结果
     * @return string $hash Retuns a string if true or return false
     */
    abstract public function calculate();
}