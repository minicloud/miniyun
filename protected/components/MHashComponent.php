<?php
/**
 * 
 * 计算文件hash值的组件
 * 增加hook file_hash_algorithm，返回文件hash值计算
 *  
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MHashComponent extends CApplicationComponent {
    /* 计算hash值的对象 */
    public $algorithm;
    
    /* 文件路径 */
    public $path;
    /**
     * 构造函数，初始化参数
     * @param string $path
     */
    public function __construct($path = NULL) {
        $this->path = $path;
        $this->algorithm  = apply_filters("file_hash_algorithm", NULL);
        if (empty($this->algorithm) || $this->algorithm == NULL) {
            $this->algorithm = new MHashSha1();
        }
        $this->algorithm->path = $this->path;
    }
    /**
     * 
     * 返回文件的hash值
     * @since 1.0.0
     * @return string Returns a string if success or false
     */
    public function getHash() {
        return $this->algorithm->calculate();
    }
}