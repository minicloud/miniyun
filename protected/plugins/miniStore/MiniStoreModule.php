<?php
/**
 * 迷你存储Store
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class MiniStoreModule extends MiniPluginModule { 
    /**
     *
     * @see CModule::init()
     */
    public function init()
    {
        $this->setImport(array(
            "miniStore.biz.*",
            "miniStore.cache.*",
            "miniStore.models.*",
            "miniStore.service.*",
        ));
        add_filter("plugin_info",array($this, "setPluginInfo"));
        //文件上传成功后,发送信息给迷你文档服务器，让其进行文档转换
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
               "name"=>"miniStore",
            ));
        return $plugins;
    }
}

