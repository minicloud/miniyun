<?php
/**
 * 迷你云所有服务的基类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniService{
    /**
     * @var 当前API请求的Path
     */
    protected  $path = null;
    /**
     * @var 当前API请求的module name
     */
    protected  $module = null;
    /**
     * @var 如果不是就标记为其它的动作
     */
    protected  $action = null;
    protected  $root;

    /**
     * 可匿名访问actionList
     */
    protected function anonymousActionList(){
        return array();
    }
    /**
     * 只有管理员才能访问actionList
     */
    protected function adminActionList(){
        return array();
    }
    /**
     * 初始化用户会话信息
     */
    protected  function initSession(){
        //初始化用户信息
        $filter = new MUserFilter();
        $filter->oauth2Judge();
    }
    public function invoke($uri = NULL){
        $routePath = MiniHttp::getParam("route","");
        //新接口模式
        if(!empty($routePath)){
            //如果接口不支持匿名访问，则通过access_token初始化用户信息
            $canAnonymous = false;
            $actions = $this->anonymousActionList();
            foreach($actions as $subAction){
                if($subAction===$uri){
                    $canAnonymous = true;
                }
            }
            if($canAnonymous===false){
                $this->initSession();
            }
            //如接口需管理员才能访问，则需安全过滤
            $canAdmin = false;
            $actions = $this->adminActionList();
            foreach($actions as $subAction){
                if($subAction===$uri){
                    $canAdmin = true;
                }
            } 
            if($canAdmin){
                $user = MUserManager::getInstance()->getCurrentUser(); 
                if(!$user["is_admin"]){
                    throw new MiniException(100001);
                }
            }
            //通过反射方式调用对应的action
            $this->action = $uri;
            if($this->action==="list"){
                return $this->getList();
            }else{
                $action = $this->action;
                return $this->$action();
            }
        }else{
            //老接口模式
            //$uri = /link/create/test/MySQL-python-1.2.3.tar
            $info = explode("/",$uri);
            $this->module = $info[1];
            $action = $info[2];
            //兼容GET方式请求数据
            $pos = strpos($action,"?");
            if($pos!==false){
                $action = substr($action,0,$pos);
            }
            $this->action = $action;
            //parse path
            $path = "";
            for($i=3;$i<count($info);$i++){
                $path = MiniUtil::joinPath($path,$info[$i]);
            }
            $this->path = $path;

            if($this->action==="list"){
                return $this->getList($uri);
            }else{
                $action = $this->action;
                return $this->$action($uri);
            }
        }

    }
}