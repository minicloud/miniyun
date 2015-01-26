<?php
/*
* Plugin Name: 迷你文档
* Plugin Type: miniDoc
* Plugin URI: http://www.miniyun.cn
* Description: 支持doc/xls/ppt/pdf在线浏览
* Author: MiniYun
* Version: 1.0.0
* Author URI: http://www.miniyun.cn
*/
/**
 *
 * 迷你文档插件
 *
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
            "miniDoc.service.*",
        ));
        add_filter("plugin_info",array($this, "setPluginInfo"));
        //文件上传成功后,发送信息给迷你文档服务器，让其进行文档转换
        //每次文件上传成功后都要调用外部指令，会有性能开销
        add_action("file_upload_after",array($this, "fileUploadAfter"));
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
               "name"=>"miniDoc",
            ));
        return $plugins;
    }
	/**
     * 
     * 文件上传成功后，向迷你文档服务器发送文档转换请求
     */
    function fileUploadAfter(){
        $cmd = MINIYUN_PATH."/console PluginDocConvert";
        shell_exec($cmd);
        return true;
    }
}

