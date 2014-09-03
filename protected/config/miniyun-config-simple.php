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

/** Miniyun 数据库的类型 */
define('DB_TYPE', '#dBType#');

/** Miniyun 数据库的名称 */
define('DB_NAME', '#dBName#');

/** MySQL 数据库用户名 */
define('DB_USER', '#dBUser#');

/** MySQL 数据库密码 */
define('DB_PASSWORD', '#dBPasswd#');

/** MySQL 端口号 */
define('DB_PORT', '#dBPort#');

/** MySQL 主机 */
define('DB_HOST', '#dBHost#'); 
/** MySQL 编码 */
define('DB_CHARSET', 'utf8');
/**#@-*/


/** SQLITE 数据库路径 */
define('DB_PATH', '#dBPath#'); 

/**
 * Miniyun 数据表前缀。
 *
 * 如果您有在同一数据库内安装多个 Miniyun 的需求，请为每个 Miniyun 设置不同的数据表前缀。
 * 前缀名只能为数字、字母加下划线。
 */
define('DB_PREFIX','#tablePrefix#');
/**
 * 
 * KEY是验证url安全性方面重要字符串，该字符串是随机生成的。 
 */
define('KEY',"#key#");
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
define('BASE',"#base#");
define('DOCUMENT_ROOT_BLOCK', BASE . "/upload_block/");
//
// 图片缩略图存储路径
//
define('THUMBNAIL_TEMP', dirname(__FILE__) . '/../../static/thumbnails/');
//
// 系统临时文件目录
//
define('DOCUMENT_TEMP', BASE . '/temp/');
define('DOCUMENT_CACHE', BASE . '/cache/');
//
// 插件的跟目录
//
define('PLUGIN_DIR', MINIYUN_PATH. DS . 'protected' . DS . 'plugins');//插件的文件目录
//
// 默认空间大小
//
define('DEFAULT_USER_SPACE', 100);
//
// 服务器send file 标志
//
define('X_SEND_FILE', FALSE);
//
// nginxsendfile配置/peizhi/
//
define('NGINX_SEND_FILE_TAG', '/download/');
//
// 是否支持去掉index.php前缀
//
define('SUPPORT_NO_INDEX', false);