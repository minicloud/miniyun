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
        );
    }
    public function invoke()
    {
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
                echo(json_encode($result));
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