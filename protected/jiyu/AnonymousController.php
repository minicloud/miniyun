<?php
/**
 * Created by PhpStorm.
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class AnonymousController{
    /**
     * 获得白名单
     */
    protected function getWhiteList(){
        return array(
           "linkAccess",
           "site",
           "module",
        );
    }
    /**
     * 判断是否是App应用发送的请求
     * 这类请求不用进行用户过滤
     * @return bool
     */
    private function isPluginSendRequest(){
        $uri = $_SERVER['REQUEST_URI'];
        $key = "/module/";
        $pos = strpos($uri,$key);
        if($pos){
            return true;
        }
        return false;
    }
    public function invoke()
    {
        //插件的接口访问形式如下
        //{http://t.miniyun.cn/a.php/1/module/miniDoc/download?hash=xxx}
        //执行该类的方式是PluginMiniDocService.download方法
        if($this->isPluginSendRequest()){
            //查询是否是来自插件的请求
            $uri = $this->getFilterUrl();
            $info = explode("/",$uri);
            $pluginName = $info[4];
            $className = "Plugin".ucfirst($pluginName)."Service";
            $service = new $className;
            $newUri = "/".implode("/", array_slice($info,4));
            $result = $service->invoke($newUri);
            echo(json_encode($result));exit;
        }else{
            $uri = $this->getFilterUrl();
            $info = explode("/",$uri);
            $module = $info[3];
            $whiteList = $this->getWhiteList();
            foreach($whiteList as $item){
                if($module===$item){
                    $newUri = "/".implode("/", array_slice($info,3));
                    $class = ucfirst($module)."Service";
                    $service = new $class;
                    $result = $service->invoke($newUri);
                    echo(json_encode($result));exit;
                }
            }
        }
    }

    /**
     *获得过滤的url地址
     * 兼容二级域名/yun/c.php/abc的情况
     */
    private function getFilterUrl(){
        $uri = $_SERVER['REQUEST_URI'];
        $keys = array("c.php","a.php");
        foreach($keys as $key){
            $pos = strpos($uri,$key);
            if($pos!==false){
                $uri = "/".substr($uri,$pos,strlen($uri));
                break;
            }
        }
        return $uri;
    }
}