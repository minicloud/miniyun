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
     * 定时任务入口
     * 任务1：为miniyun_file_versions.replicate_status=0的文件生成miniyun_replicat_tasks记录
     * 设置miniyun_file_versions.replicate_status=1
     * 任务2：把miniyun_replicate_tasks.status=0的30条记录推送到迷你存储服务器上
     * 设置miniyun_replicate_task.status=1
     */
    public function actionReplicate()
    { 
    	PluginMiniReplicateTask::getInstance()->createReplicateTask();
    }
}