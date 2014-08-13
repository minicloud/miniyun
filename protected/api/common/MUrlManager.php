<?php
/**
 * 通用的url地址管理模块
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MUrlManager
extends MApplicationComponent
{
    private static  $API_VERSION;
    /**
     * 获得二级目录
     * 可支持http://www.miniyun.cn:88/abc/api/xxxx的访问形式
     */
    private function _getSubFolder(){
        $scriptName = $_SERVER["SCRIPT_NAME"];
        return rtrim(dirname($scriptName),'\\/');
    }
    /**
     * 获得当前API接口版本号
     */
    public static function getAPIVersion(){
        return self::$API_VERSION;
    }
    /**
     * 从url请求解析对应的action名称
     *  比如www.xx.cn/meta/service_upload_meta，返回的是service_upload_meta
     * @return string 返回的是对应action操作
     */
    public function parseActionFromUrl()
    {
        //
        // TODO: 针对不同的php server进行部署逻辑处理
        //
        $uri = $_SERVER['REQUEST_URI'];
        //
        // iis服务器，处理编码
        //
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'iis') !== false)
        {
            // iis  urlencode() 或者 rawurlencode()，二者的区别是前者把空格编码为 '+'，而后者把空格编码为 '%20'
            $uri = rawurldecode($uri);
            $uri = mb_convert_encoding($uri, "UTF-8", "gbk");
        }
        else {
            $uri = urldecode($uri);
        }
        //
        // 姜松 20120419，前置假设用户不会将服务部署在/api.php/1/文件夹下
        // 也就是不会存在这种请求：api.php/1/api.php/1/info
        // 查找对应api,便于取出控制器
        //
        $index      = strpos($uri, "api.php/1/");
        if($index!=FALSE){
            $uri  = substr($uri, $index+strlen("api.php/1/"));
            self::$API_VERSION = 1;
        }
        
        // 转换为标准格式的路径
        $uri  = MUtils::convertStandardPath($uri);
        $parts = array_slice(explode('/', $uri), 1);

        //
        // 确保只有一个对应的方法
        //
        if (count($parts) < 1)
        {
            return false;
        }
        $action  = $parts[0];
        if ($pos = strpos($action, '?'))
        {
            $action = substr($action, 0, $pos);
        }

        $array = array();
        $array["action"]    = $action;
        $array["uri"]       = $uri;
        return $array;
    }

    /**
     * 从url请求解析对应的&lt;path&gt;
     * <p> e.g www.xx.cn/api/files/&lt;root&gt;/&lt;path&gt;?param=val，返回值为  /&lt;path&gt;
     * </p>
     * @param $uri
     * @return string path
     */
    public function parsePathFromUrl($uri) {
        $path = $uri;
        //
        // 去掉"?"
        //
        if ($pos = strpos($path, '?'))
        {
            $path = substr($path, 0, $pos);
        }

        $parts = array_slice(explode('/', $path), 3);
        //
        // 确保存在path
        //
        if (count($parts) <= 0) {
            return false;
        }
        //
        //组装完整的path
        //
        $path = join("/", $parts);
        return $path;
    }

    /**
     * 从url请求解析对应的
     *
     */
    public function parseRootFromUrl($uri) {
        $start = strpos($uri,'?');
        $path  = $uri;
        if ($start !== FALSE) {
            $path  = substr_replace($uri, '', $start);
        }
        $parts = array_slice(explode('/', $path), 2);
        //
        // 确保存在path
        //
        if (count($parts) <= 0) {
            return false;
        }
        //
        //组装完整的path
        //
        return $parts[0];
    }
}