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
     *
     * 场景1：针对1.5升级到1.7的场景
     * 把系统中的文本文件，提交到文件索引节点，每次做多提交80个转换任务
     * 使用方式：手动执行
     */
    public function actionBuildOldFile()
    {
        $versions = PluginMiniSearchVersion::getInstance()->getTxtBuildList();
        if(empty($versions)) {
            echo("没有需要索引的文本文档了");
        }else {
            foreach($versions as $version){
                $signature = $version["file_signature"];
                PluginMiniSearchFile::getInstance()->create($signature);
            }
            echo("本次索引的文本文件有:" . count($versions) . "个\n");
        }
    }
    /**
     * 场景1：针对处理超时的文件，重新提交编制索引请求
     * 场景2：新上传的文件时，迷你搜索服务器不可用
     * 使用方式：手动执行/定时24点执行一次
     */
    public function actionBuildTimeoutFile(){
        Yii::log("handle miniSearch timeout file ",CLogger::LEVEL_INFO,"miniSearch");
        $count = PluginMiniSearchBuildTask::getInstance()->buildTimeoutTask();
        echo("本次索引的文件有:" . $count . "个\n");
    }
    /**
     * 场景1：新拉上迷你搜索节点时，系统中已有文件，通过这个指令为新节点编制所有文件的索引
     * 使用方式：手动执行
     */
    public function actionBuildNewNode(){
        $count = PluginMiniSearchBuildTask::getInstance()->buildNewNode();
        echo("本次索引的文件有:" . $count . "个\n");
    }
    /**
     * 场景1：检查各个迷你云节点状态
     * 使用方式：每隔15秒执行一次
     */
    public function actionCheckNodeStatus(){
        PluginMiniSearchNode::getInstance()->checkNodesStatus();
    }
}