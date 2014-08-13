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

    public function invoke($uri = NULL){
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