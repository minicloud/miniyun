<?php
/**
 * 迷你存储控制台指令
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
/**
 * 向迷你文档发送转换请求Convert
 * Class DocConvertCommand
 */
class PluginStoreCommand extends CConsoleCommand{
    /**
     *
     * 场景1：针对1.5升级到1.7的场景
     * 把系统已有文件冗余备份到其它至多2个节点
     * 使用方式：手动执行
     */
    public function actionReplicateOldFile()
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
     * 场景1：把文件做至多3份备份
     * 使用方式：每隔30分钟执行一次
     */
    public function actionReplicateTimeoutFile()
    {
    	PluginMiniReplicateTask::getInstance()->replicate();
    }
    /**
     * 场景1：把文件做至多3份备份
     * 使用方式：每隔1分钟执行一次
     */
    public function actionReplicateFile()
    {
        PluginMiniReplicateTask::getInstance()->createReplicateTask();
        PluginMiniReplicateTask::getInstance()->replicate();
    }
    /**
     * 定时任务入口
     * 场景1：检查各个迷你云节点状态
     * 使用方式：每隔15秒执行一次
     */
    public function actionCheckNodeStatus(){
        PluginMiniStoreNode::getInstance()->checkNodesStatus();
    }
}