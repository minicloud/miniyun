<?php
/**
 * 安全验证机制
 * 用作参数安全检查，签名和过期验证
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MSecurityClass {
    // 时间戳的值
    public $expire = 0;

    /**
     * 验证参数签名信息是否正确,时间戳是否过期
     * @param $list string      - 除时间戳和签名以外的其他参数
     * @param $expire_time      - 时间戳
     * @param $signature        - 签名信息
     * @return true or false
     */
    function verification($list,$expire_time,$signature) {
        if (!is_array($list)) {
            Yii::log(Yii::t('api',"list is not list"), CLogger::LEVEL_ERROR,"miniyun.api");
            return false;
        }
        $this->expire = (int)$expire_time;
        if ($this->virify_timestamp() == false) {
            Yii::log(Yii::t('api',"virify_timestamp verification error"), CLogger::LEVEL_ERROR,"miniyun.api");
            return  false;
        }
        
        $retval = $this->encrypt_param($list);
        if ($retval != $signature) { 
            Yii::log(Yii::t('api',"signature is not same, retval:'{$retval}', signature:'{$signature}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            return false;
        }
        return true;
    }

    /**
     * 验证时间是否过期
     * @param
     * @return true or false
     */
    function virify_timestamp() {
        $time_now = time();
        if ($time_now > $this->expire) {
            return false;
        }
        return true;
    }

    /**
     * 将传入的参数进行加密
     * @param $input array()
     * @return $retval  string
     */
    function encrypt_param($input=array()) {
        $retval = null;
        if (!is_array($input)) {
            return null;
        }
        $list = array_keys($input);
        $retval = $this->sort_and_assemble($list, $input);
        $retval = $this->assemble_timestamp($retval);
        $retval = $this->get_signature($retval);
        return $retval;
    }

    /**
     * 将参数加上时间戳
     * @param $input string
     * @return string
     */
    function assemble_timestamp($input) {
        return $input . EXPIRATION_DATE . $this->expire;
    }

    /**
     * 排序并将参数组装成需要的字符串
     * @param $list array
     * @param $map  array
     * @return $retval string
     */
    function sort_and_assemble($list,$map) {
        $retval = "";
        natcasesort($list);
        foreach ($list as $key) {
            $retval = $retval . $key . $map[$key];
        }
        return $retval;
    }

    /**
     * 生成签名算法
     * @param $input string
     * @return $retval string
     */
    function get_signature($input) {
        $retval = sha1($input . MConst::ACCESS_KEY);
        return $retval;
    }

}
?>
