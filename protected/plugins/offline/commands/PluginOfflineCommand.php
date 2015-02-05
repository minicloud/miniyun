<?php
/**
 * 离线版制作
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
/**
 * 离线版制作
 * Class PluginOfflineCommand
 */
class PluginOfflineCommand extends CConsoleCommand
{
    /**
     * 初始化静态资源
     * @param $host 迷你云域名，任务将把http://static.miniyun.cn替换为当前传入的域名
     */
    public function actionInitStatic($host)
    {
        MiniOption::getInstance()->setOptionValue("miniyun_host",$host+"/");
        //替换static.miniyun.cn的静态资源，把js/css/html的static.miniyun.cn
        //替换为当前域的值
        $folderPath = MINIYUN_PATH."/statics/static/mini-box";
        $this->replace($folderPath,"http://static.miniyun.cn",$host."/statics");
    }
    /**
     * 初始化数据库
     */
    public function actionInitDB()
    {
        //初始化数据库
        $migration = new MiniMigration();
        $migration->up();
    }
    /**
     * 初始化插件
     */
    public function actionInitPlugin()
    {
        //拉上离线版插件
        MiniPlugin::getInstance()->enablePlugin("offline");
        //拉上商业版主题
        MiniPlugin::getInstance()->enablePlugin("businessTheme");
        //拉上迷你搜索
        MiniPlugin::getInstance()->enablePlugin("miniSearch");
        //拉上迷你文档
        MiniPlugin::getInstance()->enablePlugin("miniDoc");
    }

    /**
     * 替换内容
     * @param $folderPath 目录路径
     * @param $key 关键字
     * @param $host 新域名
     */
    private function replace($folderPath,$key,$host){
        $fileTypeList = array("html","css","js");
        if(($dir=@opendir($folderPath))) {
            while(($fileName=readdir($dir))!==false)
            {
                if($fileName=="."||$fileName==".."){
                    continue;
                }
                $filePath = $folderPath.DIRECTORY_SEPARATOR.$fileName;
                //如果是目录，进行递归循环
                if(is_dir($filePath)){
                    $this->replace($filePath,$key,$host);
                }
                //如果是文件，则判断类型是否是html/css/js
                $pathInfo = pathinfo($filePath);
                if(!array_key_exists("extension",$pathInfo)){
                    continue;
                }
                $isHtml = false;
                foreach($fileTypeList as $fileType){
                    if($pathInfo["extension"]==$fileType){
                        $isHtml = true;
                        break;
                    }
                }
                if($isHtml===false){
                    continue;
                }
                //读取内容，并替换
                $content = file_get_contents($filePath);
                $content = str_replace($key, $host, $content);
                file_put_contents($filePath, $content);

            }
            closedir($dir);
        }
    }
}