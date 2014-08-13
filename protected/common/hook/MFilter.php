<?php
/**
 * 初始化系统默认filter
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFilter
{
    /**
     * 
     * 初始化系统自定义hook
     * 
     * @since 1.1.1
     */
    public function initFilter(){
        add_action('admin_init', array(Yii::app()->nav, "initAdminMenu"));
        add_action('front_classify_init', array(Yii::app()->nav, "initFrontClassify"));
        //前端功能菜单导航内容初始化
        add_action('front_function_init', array(Yii::app()->nav, "initFrontFunction"));
    }
}