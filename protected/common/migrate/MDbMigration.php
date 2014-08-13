<?php
/**
 *
 * 数据迁移
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MDbMigration extends CDbMigration
{
    function __construct(){
        defined('DB_PREFIX') or define('DB_PREFIX', 'miniyun');
    }
}
