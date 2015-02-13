<?php
/**
 * 迷你存储业务层
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreBiz extends MiniBiz{
    /**
     *获得迷你存储节点信息列表
     */
    public function getNodeList(){
        return PluginMiniStoreNode::getInstance()->getNodeList();
    }
    /**
     * 创建迷你存储节点
     * @param $name 节点名称
     * @param $host 节点域名
     * @param $accessToken 节点访问的accessToken
     */
    public function createOrModifyNode($name,$host,$accessToken){

        return PluginMiniStoreNode::getInstance()->createOrModifyNode($name,$host,$accessToken);
    }
    /**
     * 修改迷你存储节点状态
     * @param $name 节点名称
     * @param $status 节点状态
     * @throws MiniException
     */
    public function modifyNodeStatus($name,$status){
        $item = StoreNode::model()->find("name=:name",array("name"=>$name));
        if(!isset($item)){
            throw new MiniException(100103);
        }
        //TODO 检查服务器状态，看看是否可以连接迷你存储服务器
        //throw new MiniException(100104);
        return PluginMiniStoreNode::getInstance()->modifyNodeStatus($name,$status);
    }

}