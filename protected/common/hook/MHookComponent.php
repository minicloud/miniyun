<?php
/**
 * hook变量存储
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MHookComponent extends CApplicationComponent
{
    public $params = array();
    public $wp_filter = array();
    public $merged_filters = array();

    public $wp_actions = array();
    public $wp_current_filter = array();

    public function init()
    {
        parent::init();
        $this->params["aa"]="aa";
    }

    public function set($data)
    {
       $this->params[$data]=$data;
    }

    public function get()
    {
       return $this->params;
    }
}