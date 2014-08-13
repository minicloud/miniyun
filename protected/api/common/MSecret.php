<?php
/**
 * 用户密码验证模块
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MSecret
{

    /**
     *
     * 按照密码规则进行签名操作
     */
    public static function passSign($password, $salt){
        $md5password =  md5(md5($password).$salt);
        return $md5password;
    }

    /**
     *
     * 进行des加密, 并转换为16进制
     */
    public static function encryptHex($key, $text){
        $cryptText = self::idtagDesEncode($key, $text);
        $cryptHex = self::strToHex($cryptText);
        return $cryptHex;
    }


    /**
     *
     * 进行des解密
     */
    public static function decryptHex($key, $text){
        $cryptText = self::idtagDesDecode($key, self::hexToStr($text));
        return $cryptText;
    }


    /**
     *
     * 字符串转16进制
     */
    public static function strToHex($string)
    {
        $hex="";
        for   ($i=0;$i<strlen($string);$i++)
        $hex.=dechex(ord($string[$i]));
        $hex=strtoupper($hex);
        return $hex;
    }


    /**
     *
     * 16进制转字符串
     */
    public static function hexToStr($hex)
    {
        $string="";
        for   ($i=0;$i<strlen($hex)-1;$i+=2)
        $string.=chr(hexdec($hex[$i].$hex[$i+1]));
        return $string;
    }

    public static function idtagDesDecode($key,$encrypted)
    {   //对使用 MIME base64 编码的数据进行解码
        $encrypted = base64_decode($encrypted);
        
        $crypt_des = new Crypt_DES();
        $crypt_des->setKey($key);
        $crypt_des->setIV($key);
        $plaintext = $crypt_des->decrypt($encrypted);
        
        return $plaintext;
    }

    public static function idtagDesEncode($key,$text)
    {

        $crypt_des = new Crypt_DES();
        $crypt_des->setKey($key);
        $crypt_des->setIV($key);
        $encrypted =$crypt_des->encrypt($text);

        return base64_encode($encrypted);
    }

    public static function pkcs5_pad($text,$block=8)
    {
        $pad = $block - (strlen($text) % $block);
        return $text . str_repeat(chr($pad), $pad);
    }


    public static function pkcs5_unpad($text)
    {
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) return $text;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return $text;
        return substr($text, 0, -1 * $pad);
    }
}