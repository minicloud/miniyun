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
     * @param $name 需要拉上的插件名称
     */
    public function actionInitPlugin($name)
    {
        MiniPlugin::getInstance()->enablePlugin($name);
    }
}