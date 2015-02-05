<?php
/*
* Plugin Name: 私有云
* Plugin Type: offline
* hidden:true
* Plugin URI: http://www.miniyun.cn
* Description: 迷你云私有云版本，启用后不在依赖Miniyun.cn静态资源
* Author: MiniYun
* Version: 1.0.0
* Author URI: http://www.miniyun.cn
*/
/**
 * 私有云版本
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class OfflineModule extends MiniPluginModule { 
    /**
     *
     * @see CModule::init()
     */
    public function init()
    {
        $this->setImport(array( 
        ));
        add_filter("plugin_info",array($this, "setPluginInfo")); 
    }
    /**
     *获得插件信息
     * @param $plugins 插件列表
     * {
        "miniDoc":{}
     * }
     * @return array
     */
    function setPluginInfo($plugins){
        if(empty($plugins)){
            $plugins = array();
        }
        array_push($plugins,
            array(
               "name"=>"offline",
            ));
        return $plugins;
    } 
}

