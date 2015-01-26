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
            "miniDoc.model.*",
            "miniDoc.service.*",
        ));
        //附加磁盘空间大小显示
        add_action("file_upload_after",array($this, "fileUploadAfter"));
    } 

	/**
     * 
     * 文件上传成功动作
     * 
     * @since 1.0.0
     */
    function fileUploadAfter(){
        
        return false;
    }
}

