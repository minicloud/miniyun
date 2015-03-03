<?php
/**
 * 迷你搜索命名行
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
/**
 * 获得所有转换成功的word/excel/pdf的内容到数据库
 * Class PullTxtCommand
 */
class PluginSearchCommand extends CConsoleCommand
{

    /**
     * 获得所有转换成功的word/excel/pdf的内容到数据库
     */
    public function actionIndex()
    { 
    	$versions = PluginMiniDocVersion::getInstance()->getDocConvertList(2);
        if(empty($versions)) {
            echo("no doc to pull txt!");
            return;
        }
        $count = 0;
        foreach($versions as $version){
            $signature = $version["file_signature"];
            //先判断文件的signature是否在search_file存在记录，如果不存在才从迷你文档上拉文本内容
            $searchFile = MiniSearchFile::getInstance()->getBySignature($signature);
            if(!empty($searchFile)){
                continue;
            }
            $node = PluginMiniDocNode::getInstance()->getConvertNode($signature);
            //TODO 需要处理文件不存在的情况
            $url = $node["host"]."/".$signature."/".$signature.".txt";
            $http = new HttpClient();
            $http->get($url);
            $status = $http->get_status();
            if($status=="200"){
                $content = $http->get_body();
                MiniSearchFile::getInstance()->create($signature,$content);
                $count ++;
            }else{
                Yii::log($signature."get txt error",CLogger::LEVEL_ERROR,"doc.convert");
            }
        }
        Yii::log("save txt:".$count." records",CLogger::LEVEL_INFO,"doc.convert");

    }
    /**
     * 定时任务入口
     * 任务1：检查各个迷你搜索节点状态，如果访问失败，则把该节点拉下并把报警
     */
    public function actionStatus(){
        PluginMiniSearchNode::getInstance()->checkNodesStatus();
    }
    /**
     * 定时12点为新节点生成索引库
     * 这里也可手工执行
     */
    public function actionTask(){
        PluginMiniSearchBuildTask::getInstance()->backupCreateTask();
    }
    /**
     * 定时把任务推送到迷你搜索服务器
     */
    public function actionPushTask(){
        PluginMiniSearchBuildTask::getInstance()->pushTask();
    }
}