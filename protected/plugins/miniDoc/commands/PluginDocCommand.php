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
     * 每次做多提交40个转换任务
     * 使用方式：手动执行
     */
    public function actionOldFile()
    { 
    	$versions = PluginMiniDocVersion::getInstance()->getDocConvertList(0);
        if(empty($versions)) {
            echo("没有需要转换的文档了");
            Yii::log("no doc to convert!",CLogger::LEVEL_INFO,"doc.convert");
            return;
        }
        PluginMiniDocVersion::getInstance()->pushConvert($versions);
    }
    /**
     * 场景1：针对处理超时的文件，重新提交转换请求
     * 场景2：新上传的文件时，迷你文档服务器不可用
     * 使用方式：手动执行/定时24点执行一次
     */
    public function actionAgainFile(){
        $versions = PluginMiniDocVersion::getInstance()->getDocConvertList(0,true);
        if(!empty($versions)) {
            PluginMiniDocVersion::getInstance()->pushConvert($versions);
        }
        $versions = PluginMiniDocVersion::getInstance()->getDocConvertList(1);
        if(empty($versions)) {
            echo("没有需要转换的文档了");
            Yii::log("no doc to convert!",CLogger::LEVEL_INFO,"doc.convert");
            return;
        }
        PluginMiniDocVersion::getInstance()->pushConvert($versions);
    }
    /**
     * 场景1：针对处理错误的文件，重新提交转换请求，这种场景针对的是文件转换中，CPU超时导致的文件转换失败
     * 本次操作将一次性把系统中所有的转换失败的文件转换一次
     * 使用方式：手动执行
     */
    public function actionFailFile(){
        $versions = PluginMiniDocVersion::getInstance()->getDocConvertList(-1);
        if(empty($versions)) {
            echo("没有需要转换的文档了");
            Yii::log("no doc to convert!",CLogger::LEVEL_INFO,"doc.convert");
            return;
        }
        PluginMiniDocVersion::getInstance()->pushConvert($versions);
    }
    /**
     * 定时任务入口
     * 任务1：检查各个迷你云节点状态，如果访问失败，则把该节点拉下并把报警
     */
    public function actionStatus(){
        PluginMiniDocNode::getInstance()->checkNodesStatus();
    }
}