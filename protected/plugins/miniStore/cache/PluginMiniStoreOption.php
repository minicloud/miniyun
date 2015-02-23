<?php
/**
 * 缓存miniyun_options表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class PluginMiniStoreOption extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniStoreOption";

    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    static private $_instance = null;

    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        parent::MiniCache();
    }

    /**
     * 静态方法, 单例统一访问入口
     * @return object  返回对象的唯一实例
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * 获得迷你云服务器地址
     * 如果用户在options表中定义了{miniyun_host:xxxx}，优选它
     * 否则默认为http://127.0.0.1
     */
    public function getMiniyunHost(){
        $value = MiniOption::getInstance()->getOptionValue("miniyun_host");
        if(empty($value)){
            $value = "http://127.0.0.1";
        }
        return $value;
    }
}