<?php
/**
 * 迷你文档插件
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class MiniDocModule extends MiniPluginModule { 
    /**
     *
     * @see CModule::init()
     */
    public function init()
    {
        $this->setImport(array(
            "miniDoc.biz.*",
            "miniDoc.cache.*",
            "miniDoc.models.*",
            "miniDoc.service.*",
        )); 
    }     
}

