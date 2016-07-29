<?php
/** 
 * Miniyun 基础配置文件。
 *
 * 本文件包含以下配置选项：MySQL 设置、数据库表名前缀
 *
 * 这个文件用在于安装程序自动生成 Miniyun-config.php 配置文件，
 * 您可以手动复制这个文件，并重命名为“Miniyun-config.php”，然后输入相关信息。
 *
 * @package Miniyun
 */


/** Miniyun 数据库的名称 */
define('DB_NAME', 'miniyun_m1');

/** MySQL 数据库用户名 */
define('DB_USER', 'root');

/** MySQL 数据库密码 */
define('DB_PASSWORD', '123456');

/** MySQL 端口号 */
define('DB_PORT', '3306');

/** MySQL 主机 */
define('DB_HOST', '127.0.0.1'); 
/** MySQL 编码 */
define('DB_CHARSET', 'utf8');
/**#@-*/


/**
 * Miniyun 数据表前缀。
 *
 * 如果您有在同一数据库内安装多个 Miniyun 的需求，请为每个 Miniyun 设置不同的数据表前缀。
 * 前缀名只能为数字、字母加下划线。
 */
define('DB_PREFIX','miniyun');
/**
 * 
 * KEY是验证url安全性方面重要字符串，该字符串是随机生成的。 
 */
define('KEY',"6LmDoDdM611iWps@9V5#FRRZjwisj55Uew[vvbnNU0qdcvj#Suv48teHRqNNT1@l");
 /**
 * 
 * 基本存储路径 
 *
 */
define('DS', DIRECTORY_SEPARATOR);
//
// 定义整个系统的工程目录
//
define('MINIYUN_PATH', dirname(dirname(dirname(__FILE__))));
define('UPGRADE_PATH', MINIYUN_PATH. DS . 'protected' . DS . 'upgrade');
//
// 重新定义文件存储路径
//
define('BASE',"/usr/local/miniyun/miniyun/protected/../upload/");

