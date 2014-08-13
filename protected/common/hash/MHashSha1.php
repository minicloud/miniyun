<?php
/**
 * 
 * 计算文件sha1值
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MHashSha1 extends MHashAlgorithm {
    /**
     * (non-PHPdoc)
     * @see MHashAlgorithm::calculate()
     */
    public function calculate() {
        if (file_exists($this->path)) {
            $this->hash = sha1_file($this->path);
        }
        return $this->hash;
    }

}