<?php
/**
 * 迷你云入口
 * 1.7之后支持移动touch版本登录
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
function isWeixin(){
    $agent = $_SERVER["HTTP_USER_AGENT"];
    if(strpos($agent,"MicroMessenger")>0){
        return ture;
    }else{
        return false;
    }
}
function isLowerBrowser(){
    $agent =  $_SERVER['HTTP_USER_AGENT']; 
    if(strpos($agent,'MSIE 9.0')>0){
        return true;
    }
    if(strpos($agent,'MSIE 8.0')>0){
        return true;
    }
    if(strpos($agent,'MSIE 7.0')>0){
        return true;
    }
    if(strpos($agent,'MSIE 6.0')>0){
        return true;
    }
    return false;
}
if(isLowerBrowser()){
    header('Location:./oops.html');
    exit;
}
if(isWeixin()){
    define('STATIC_SERVER_HOST',"wxstatic.miniyun.cn");
    include "miniWeixin.php";
    $box = new MiniWeixin();
    $box->load();
}else{
    define('STATIC_SERVER_HOST',"static.miniyun.cn");
    include "miniBox.php";
    $box = new MiniBox();
    $box->load();
}
