<?php
/**
 * 迷你文档控制台指令
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
/**
 * 向迷你文档发送转换请求Convert
 * Class DocConvertCommand
 */
class PluginDocCommand extends CConsoleCommand{
    /**
     *
     * 场景1：针对1.5升级到1.7的场景
     * 每次做多提交80个转换任务
     * 使用方式：手动执行
     */
    public function actionConvertOldFile()
    { 
    	$versions = PluginMiniDocVersion::getInstance()->getDocConvertList(0);
        if(empty($versions)) {
            echo("没有需要转换的文档了");
            Yii::log("no doc to convert!",CLogger::LEVEL_INFO,"miniDoc");
        }else{
            echo("本次转换文件有:".count($versions)."个\n");
            PluginMiniDocVersion::getInstance()->pushConvert($versions);
        }
    }
    /**
     * 场景1：针对处理超时的文件，重新提交转换请求
     * 场景2：新上传的文件时，迷你文档服务器不可用
     * 使用方式：手动执行/定时2点执行一次
     */
    public function actionConvertTimeoutFile(){
        Yii::log("handle miniDoc timeout file ",CLogger::LEVEL_INFO,"miniDoc");
        $fileCount = 0;
        $versions = PluginMiniDocVersion::getInstance()->getDocConvertList(0,true);
        $fileCount+=count($versions);
        if(!empty($versions)) {
            PluginMiniDocVersion::getInstance()->pushConvert($versions);
        }
        $versions = PluginMiniDocVersion::getInstance()->getDocConvertList(1);
        $fileCount+=count($versions);
        echo("第二次转换文件有:".$fileCount."个\n");
        PluginMiniDocVersion::getInstance()->pushConvert($versions);
    }
    /**
     * 场景1：针对处理错误的文件，重新提交转换请求，这种场景针对的是文件转换中，CPU超时导致的文件转换失败
     * 本次操作将一次性把系统中所有的转换失败的文件转换一次
     * 使用方式：手动执行
     */
    public function actionConvertFailFile(){
        $versions = PluginMiniDocVersion::getInstance()->getDocConvertList(-1);
        echo("第二次转换文件有:".count($versions)."个\n");
        foreach($versions as $version){
            echo($version["file_signature"]."\n");
        }
        PluginMiniDocVersion::getInstance()->pushConvert($versions);
    }
    /**
     * 定时任务入口
     * 场景1：检查各个迷你云节点状态
     * 使用方式：每隔15秒执行一次
     */
    public function actionCheckNodeStatus(){
        PluginMiniDocNode::getInstance()->checkNodesStatus();
    }
}