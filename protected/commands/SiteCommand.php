<?php
/**
 * 站点command
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
/**
 * 站点command
 * Class SiteCommand
 */
class SiteCommand extends CConsoleCommand{
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
        //拉上迷你存储
        MiniPlugin::getInstance()->enablePlugin("miniStore");
    }
}