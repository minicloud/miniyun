<?php
/**
 * Miniyun 所有的接口框架
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */ 
interface MICache
{
    /**
     * 保存特定的值到指定的key中
     * 如果缓存中存在对应的key，则需要覆盖原始值并且替换超时时间
     *
     * @param string $key 需要保存指定值的关键字
     * @param mixed $value 需要保存的值
     * @param integer $expire 超时的时间，单位秒
     * @return boolean 保存成功返回true，否则返回false
     */
    public function set($key, $value, $expire = 0);
    
    /**
     * 通过指定的key获取对应的cache值.
     * @param string $key 需要获取cahce值的key
     */
    public function get($key);

    /**
     * 批量从缓存中获取指定关键字列表的值
     * 
     * @param array $keys 关键字列表
     * @return array list 关键子列表对应的值列表，该数组是一个(key, value)的键值对
     */
    public function mget($keys);

    /**
     * 删除指定key的缓存内容
     * 
     * @param string $key 需要删除的key
     * @return boolean 删除成功返回true，否则false
     */
    public function delete($key);

    /**
     * 清空缓存中所有的内容
     * 
     * @return boolean 清空是否操作成功
     */
    public function clear();
}