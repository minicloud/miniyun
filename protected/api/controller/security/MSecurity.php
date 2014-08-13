<?php

/**
 * datasystem提供作为安全策略的考虑
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MSecurity {
    /**
     * 方法描述：验证请求是否有效
     * @param array $keys       - 参数列表
     * @param array $post       - http请求接收的参数
     * @return bool true or false
     */
    public static function verification($keys, $post) {
        Yii::trace(Yii::t('api','Begin to process {class}::{function}',
            array('{class}'=>"MSecurity", '{function}'=>__FUNCTION__)),"miniyun.api");
        $argv               = MSecurity::assemble_array($keys,$post);
        $expire_time        = @$post['expiration_date'];
        $digital_signature  = @$post['digital_signature'];
        Yii::trace(Yii::t('api',"end to process {class}::FUNCTION: $expire_time ----$digital_signature".__FUNCTION__),"miniyun.api");
        $security           = new MSecurityClass();
        return $security->verification($argv,$expire_time,$digital_signature);
    }
    /**
     * 使用参数key列表组装成需要验证的参数
     * @param array $keys       - 参数列表
     * @param array $post       - http请求接收的参数
     * @return array $argv      - 实际参数列表
     */
    public static function assemble_array($keys, $post) {
        $argv = array();
        foreach ($keys as $value) {
            if (array_key_exists($value,$post) == false) {
                return array();
            }
            $argv[$value] = $post[$value];
        }
        return $argv;
    }

}
?>