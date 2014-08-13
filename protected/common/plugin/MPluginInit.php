<?php
/**
 * 
 * Enter description here ...
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MPluginInit extends MDbInit{
    /**
     * (non-PHPdoc)
     * @see MDbInit::upgrade()
     */
    public function upgrade() {
        $this->beforeUpgradeDb();
        $state = $this->migrate();
        $this->afterUpgradeDb();
        return $state;
    }
}