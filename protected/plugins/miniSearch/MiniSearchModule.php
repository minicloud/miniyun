<?php
/**
 *
 * 微云搜索插件
 *
 */
class MiniSearchModule extends MiniPluginModule {
    /**
     *
     * @see CModule::init()
     */
    public function init()
    {
        $this->setImport(array(
            "miniSearch.biz.*",
            "miniSearch.cache.*",
            "miniSearch.service.*",
            "miniSearch.models.*",
        ));
        add_filter("plugin_info",array($this, "setPluginInfo"));
        //在文件上传成功后，如果文件是文本文件，需要建立索引
        //微云文档转换文件成功后，需要建立拉取文本内容到数据中，建立索引
        add_action("pull_text_search",array($this, "pullText"));
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
               "name"=>"miniSearch",
            ));
        return $plugins;
    }
    /**
     *根据文件的Hash值下载内容
     *@param string $signature 文件hash值
     *@return array
     *
     */
    function pullText($signature){
        $version = PluginMiniDocVersion::getInstance()->getBySignature($signature);
        if(isset($version)){
            $mimeTypeList = array("text/plain","text/html","application/javascript","text/css","application/xml");
            foreach($mimeTypeList as $mimeType){
                if($mimeType===$version["mime_type"]){
                    PluginMiniSearchFile::getInstance()->create($signature);
                    return;
                }
            }
            $mimeTypeList = array("application/mspowerpoint","application/msword","application/msexcel","application/pdf","application/vnd.openxmlformats-officedocument.wordprocessingml.document","application/vnd.ms-powerpoint","application/vnd.openxmlformats-officedocument.presentationml.presentation","application/vnd.ms-excel","application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            foreach ($mimeTypeList as $mimeType){
                if($mimeType===$version["mime_type"]){
                    //文档类增量转换
                    //doc/ppt/xls/pdf全文检索需要通过微云文档拉取文本内容
                    PluginMiniSearchFile::getInstance()->create($signature);
                }
            }

        }

    }

}

