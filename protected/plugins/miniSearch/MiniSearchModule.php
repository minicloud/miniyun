<?php
/*
* Plugin Name: 迷你搜索
* Plugin Type: miniSearch
* Plugin URI: http://www.miniyun.cn
* Description: 支持doc/xls/ppt/pdf/文本文件内容搜索
* Author: MiniYun
* Version: 1.0.0
* Author URI: http://www.miniyun.cn
*/
/**
 *
 * 迷你搜索插件
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
            "miniSearch.utils.*",
            "miniSearch.models.*",
        ));
        add_filter("plugin_info",array($this, "setPluginInfo"));
        //在文件上传成功后，如果文件是文本文件，需要建立索引
        //迷你文档转换文件成功后，需要建立拉取文本内容到数据中，建立索引
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
     *@param $signature 文件hash值
     *@throws
     *@return array
     *
     */
    function pullText($signature){
        $version = PluginMiniDocVersion::getInstance()->getBySignature($signature);
        if(isset($version)){
            $mimeTypeList = array("text/plain","text/html","application/javascript","text/css","application/xml");
            foreach($mimeTypeList as $mimeType){
                if($mimeType===$version["mime_type"]){
                    //文本类文件直接把内容存储到数据库中，便于全文检索
                    //文本类文件直接把内容存储到数据库中，便于全文检索
                    $filePath = MiniUtil::getPathBySplitStr ($signature);
                    //data源处理对象
                    $dataObj = Yii::app()->data;
                    if ($dataObj->exists( $filePath ) === false) {
                        throw new MFilesException ( Yii::t('api',MConst::NOT_FOUND ), MConst::HTTP_CODE_404 );
                    }
                    $content = $dataObj->get_contents($filePath);
                    MiniSearchFile::getInstance()->create($signature,$content);
                    return;
                }
            }
            $mimeTypeList = array("application/mspowerpoint","application/msword","application/msexcel","application/pdf");
            foreach ($mimeTypeList as $mimeType){
                if($mimeType===$version["mime_type"]){
                    //文档类增量转换
                    //doc/ppt/xls/pdf全文检索需要通过迷你文档拉取文本内容
                    $url = PluginMiniDocOption::getInstance()->getMiniDocHost()."/".$signature."/".$signature.".txt";
                    $http = new HttpClient();
                    $http->get($url);
                    $status = $http->get_status();
                    if($status=="200"){
                        $content = $http->get_body();
                        MiniSearchFile::getInstance()->create($signature,$content);
                        Yii::log($signature." get txt success",CLogger::LEVEL_INFO,"doc.convert");
                    }else{
                        Yii::log($signature." get txt error",CLogger::LEVEL_ERROR,"doc.convert");
                    }
                }
            }

        }

    }

}

