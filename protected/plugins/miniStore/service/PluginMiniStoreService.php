<?php
/**
 * 迷你存储接口
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreService extends MiniService{
    protected function anonymousActionList(){
        return array( 
        );
    }
    protected function adminActionList(){
        return array( 
            "nodeList",
            "node",
            "nodeStatus",
        );
    }
    
    /**
     * 获得迷你存储节点列表
     */
    public function nodeList() {
        $biz = new PluginMiniStoreBiz();
        return $biz->getNodeList();
    }
    /**
     * 创建或修改新的迷你存储节点
     */
    public function node(){
        $name = MiniHttp::getParam("name","");
        $host = MiniHttp::getParam("host","");
        $accessToken = MiniHttp::getParam("node_access_token","");
        if(empty($name)||empty($host)||empty($accessToken)){
            throw new MiniException(100101);
        }
        $biz = new PluginMiniStoreBiz();
        return $biz->createOrModifyNode($name,$host,$accessToken);
    }
    /**
     * 修改迷你存储节点的状态
     */
    public function nodeStatus(){
        $name = MiniHttp::getParam("name","");
        $status = MiniHttp::getParam("status","0");
        if(empty($name)){
            throw new MiniException(100102);
        }
        $biz = new PluginMiniStoreBiz();
        return $biz->modifyNodeStatus($name,$status);
    }
}