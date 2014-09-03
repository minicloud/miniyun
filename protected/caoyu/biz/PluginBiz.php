<?php
/** 
 * 插件管理
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class PluginBiz extends MiniBiz{
    /**
     * 所有插件列表
     * @return mixed
     */
    public function allList(){
        return MiniPlugin::getInstance()->getAllPlugin();
    }
    /**
     * 启用插件列表
     * @return mixed
     */
    public function enabledList(){
        return MiniPlugin::getInstance()->getEnabledPlugins();
    }
    /**
     * 停用插件列表
     */
    public function disabledList(){
        return MiniPlugin::getInstance()->getDisabledPlugins();
    }
    /**
     * 启用插件
     * @param $id
     */
    public function enable($id){
        return MiniPlugin::getInstance()->enablePlugin($id);
    }
    /**
     * 停用插件
     * @param $id
     */
    public function disable($id){
        return MiniPlugin::getInstance()->disablePlugin($id);
    }
    /**
     * 删除插件
     * @param $id
     */
    public function delete($id){
        return MiniPlugin::getInstance()->deletePlugin($id);
    }
    /**
     * 搜索插件
     * @param $key
     */
    public function search($key){
        return MiniPlugin::getInstance()->searchPlugins($key);
    }
}