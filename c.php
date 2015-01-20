<?php
/**
 * 迷你云管理员信息访问入口.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
date_default_timezone_set("PRC");

define('STATIC_SERVER_HOST',"static.miniyun.cn");

defined('YII_DEBUG') or define('YII_DEBUG', false);
@ini_set('display_errors', '1');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// change the following paths if necessary
$config=dirname(__FILE__).'/protected/config/main.php';
$yii=dirname(__FILE__).'/yii/framework/yii.php';

// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',8);
// 客户端请求
defined('CLIENT_REQUEST_API') or define('CLIENT_REQUEST_API', TRUE);
require_once($yii);
Yii::createWebApplication($config);
header('Access-Control-Allow-Origin: http://static.miniyun.cn');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Methods: GET');
MiniAppParam::getInstance()->load();//初始化APP静态数据 
MiniPlugin::getInstance()->load();//加载插件
$meta = new ConsoleController();//通过meta的控制器进行跳转处理逻辑
$meta->invoke();