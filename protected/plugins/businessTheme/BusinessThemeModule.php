<?php
/*
* Plugin Name: 商业版主题
* Plugin Type: businessTheme
* Plugin URI: http://www.miniyun.cn
* Description: 商业版用户主题
* Author: MiniYun
* Version: 1.0.0
* Author URI: http://www.miniyun.cn
*/
/**
 * 商业版主题
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class BusinessThemeModule extends MiniPluginModule {
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
               "name"=>"businessTheme",
            ));
        return $plugins;
    }
}

