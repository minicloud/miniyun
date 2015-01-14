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
header('Access-Control-Allow-Origin: http://static.miniyun.cn');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Methods: GET');
$hash = $_GET["hash"];
$type = $_GET["type"];
$host = $_SERVER["HTTP_HOST"];
$path = "/temp/".$hash."/".$hash.".".$type;
echo(file_get_contents(dirname(__FILE__).$path));
