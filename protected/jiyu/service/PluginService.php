<?php
/**
 * 迷你云插件服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class PluginService extends MiniService{

    /**
     * 获得所有插件
     */
    public function getList(){
        $model = new PluginBiz();
        $data  = $model->allList();
        return $data;
    }
    /**
     * 获得所有启用插件
     */
    public function enabledList(){
        $model = new PluginBiz();
        $data  = $model->enabledList();
        $data = apply_filters("account_info_add", $data);
        return $data;
    }
    /**
     * 获得所有未启用的插件
     */
    public function disabledList(){
        $model = new PluginBiz();
        $data  = $model->disabledList();
        return $data;
    }
    /**
     * 启用插件
     */
    public function upload(){
        $name  = MiniHttp::getParam("name","");
        if(empty($name)){
            $data = array();
            $data["success"] = false;
        }else{
            $model = new PluginBiz();
            $data  = $model->upload($name);
        }
        return $data;
    }
    /**
     * 启用插件
     */
    public function enable(){
        $id  = MiniHttp::getParam("id","");
        if(empty($id)){
            $data = array();
            $data["success"] = false;
        }else{
            $model = new PluginBiz();
            $data  = $model->enable($id);
        }
        return $data;
    }
    /**
     * 停用插件
     */
    public function disable(){
        $id  = MiniHttp::getParam("id","");
        if(empty($id)){
            $data = array();
            $data["success"] = false;
        }else{
            $model = new PluginBiz();
            $data  = $model->disable($id);
        }
        return $data;
    }
    /**
     * 删除插件
     */
    public function delete(){
        $id  = MiniHttp::getParam("id","");
        if(empty($id)){
            $data = array();
            $data["success"] = false;
        }else{
            $model = new PluginBiz();
            $data  = $model->delete($id);
        }
        return $data;
    }
    /**
     * 删除插件
     */
    public function search(){
        $key  = MiniHttp::getParam("key","");
        if(empty($key)){
            $data = array();
            $data["success"] = false;
        }else{
            $model = new PluginBiz();
            $data  = $model->search($key);
        }
        return $data;
    }
}