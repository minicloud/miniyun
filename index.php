<?php
/**
 * 迷你云入口.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
define('YII_DEBUG',false);
define('STATICS_SERVER_HOST',"statics.miniyun.cn");
include "miniBox.php";
$box = new MiniBox();
$box->load();
