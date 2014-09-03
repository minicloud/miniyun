<?php
/**
 * 迷你云插件入口
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ModuleService extends MiniService{
    /**
     * 插件执行机制
     * 自1.6开始迷你云的插件入口统一标准化
     * 插件接口分为3类
     * a、设置接口，诸如策略设置。用于网页客户端与插件之间通信。握手安全规则：access_token
     * b、内部接口，通过add_action/add_filter方式暴露。用于PHP与插件之间通信
     * c、应用间接口，用于第2方程序与插件之间通信。握手安全规则：safe_code
     * @param $uri
     * @return bool
     */
    public function invoke($uri = NULL){
        $key = "/plugin/";
        $pos = strpos($uri,$key);
        if($pos!==false){
            $temp = substr($uri,strlen($key));
            $info = explode("/",$temp);
            $moduleName = $info[0];
            $info  = explode("?",$info[1]);
            $action = $info[0];
            $class = ucfirst($moduleName)."Service";
            $service = new $class;
            return $service->$action();
        }
    }
}