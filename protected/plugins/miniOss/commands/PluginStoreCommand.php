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
        $count = PluginMiniReplicateTask::getInstance()->replicateFile();
        if($count==0) {
            echo("没有需要冗余备份的文件了");
        }else {
            echo("本次冗余备份文件有:" . $count . "个\n");
        }
    }
    /**
     * 场景1：把文件做至多3份备份
     * 使用方式：每隔30分钟执行一次
     */
    public function actionReplicateTimeoutFile()
    {
        $count = PluginMiniReplicateTask::getInstance()->pushTimeoutTask();
        echo("本次备份文件有:" . $count . "个\n");
    }
    /**
     * 场景1：把文件做至多3份备份
     * 使用方式：每隔1分钟执行一次
     */
    public function actionReplicateFile()
    {
        $count = PluginMiniReplicateTask::getInstance()->replicateFile();
        echo("本次备份文件有:" . $count . "个\n");
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