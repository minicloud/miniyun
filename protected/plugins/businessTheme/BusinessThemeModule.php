<?php
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
            "businessTheme.biz.*",
            "businessTheme.cache.*",
            "businessTheme.service.*",
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
        //读取商业主题设置参数
        $key = "plugin_"."businessTheme";
        $defaultParams = PluginBusinessThemeOption::getDefaultParams();
        $pluginData = unserialize(MiniOption::getInstance()->getOptionValue($key));
        if(empty($pluginData)){
            $pluginData = $defaultParams;
        }
        if(empty($pluginData['logo'])){
            $pluginData['logo'] = $defaultParams['logo'];
        }
        if(empty($pluginData['carouselImagesUrl'])){
            $pluginData['carouselImagesUrl'] = $defaultParams['carouselImagesUrl'];
        }
        if(empty($pluginData['companyEnglishName'])){
            $pluginData['companyEnglishName'] = $defaultParams['companyEnglishName'];
        }
        if(empty($pluginData['helpName'])){
            $pluginData['helpName'] = $defaultParams['helpName'];
        }
        if(empty($pluginData['helpUrl'])){
            $pluginData['helpUrl'] = $defaultParams['helpUrl'];
        }
        array_push($plugins,
            array(
                "name"=>"businessTheme",
                "data"=>$pluginData
            ));
        return $plugins;
    }
}

