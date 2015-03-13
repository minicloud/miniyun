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
     * 定时任务入口
     * 先获得要转换的version列表
     * 然后提交到迷你文档服务器进行转换，提交成功后修改该文档的状态
     * 迷你文档转换成功后，将异步方式给迷你云发送成功信息
     */
    public function actionAgain()
    { 
    	$versions = PluginMiniDocVersion::getInstance()->getDocConvertList();
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