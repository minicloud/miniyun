<?php
/**
 * 站点信息
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class SiteBiz extends MiniBiz
{
    /**
     * 获得站点信息
     */
    public static function getSiteInfo()
    {
        $data = array();
        $data['version'] = APP_VERSION;
        $data['status'] = "done";
        $data['app_name'] = 100;
        $data['app_logo'] = MiniHttp::getSystemParam("absoluteUrl") . "/static/images/logo.png";
        $data['default_size'] = 100;
        $data['can_register'] = true;
        $data['register_url'] = "";
        //产品名称
        $value = MiniOption::getInstance()->getOptionValue('site_name');
        if (isset($value)) {
            $data['app_name'] = $value;
        }
        //站点ID
        $data['site_id'] = MiniSiteUtils::getSiteID();
        //产品Logo
        $value = MiniOption::getInstance()->getOptionValue('site_logo_url');
        if (isset($value)) {
            $data['app_logo'] = MiniHttp::getSystemParam("absoluteUrl") . $value;
        }
        //判断系统是否可以注册
        $enableReg = MiniOption::getInstance()->getOptionValue("user_register_enabled");
        if (isset($enableReg) && $enableReg == "0") {
            $data['can_register'] = false;
        }
        //系统注册入口是否在第3方
        $value = MiniOption::getInstance()->getOptionValue("user_create_url");
        if (isset($value) && !empty($value)) {
            $data['register_url'] = $value;
        }
        // 32M
        $blockSize = 4 * 1024 * 1024;
        // 内存配置需要
        $memoryLimit = CUtils::return_bytes(ini_get('memory_limit'));
        if ($memoryLimit < 4 * $blockSize) {
            $blockSize = $memoryLimit / 4;
        }
        $postMaxSize = CUtils::return_bytes(ini_get('post_max_size'));
        $uploadMaxFileSize = CUtils::return_bytes(ini_get('upload_max_filesize'));

        $min = $postMaxSize > $uploadMaxFileSize ? $uploadMaxFileSize : $postMaxSize;

        $data['block_size'] = $min > $blockSize ? $blockSize : $min;
        if ($data['block_size'] == $postMaxSize && $data['block_size'] == $uploadMaxFileSize) {
            $data['block_size'] = $data['block_size'] - 104858;
        }
        // 获取忘记密码使用短信口子地址
        $forgetPasswordUrl = apply_filters('user_forgetpasswd');
        if (empty($forgetPasswordUrl)) {
            $forgetPasswordUrl = MiniHttp::getSystemParam("absoluteUrl") . $forgetPasswordUrl;
        }
        $data['forget_password_url'] = $forgetPasswordUrl;
        return $data;
    }

    /**
     * 创建用户
     * @param $name
     * @param $password
     * @param $email
     * @throws Exception
     * @return array
     */
    public static function createUser($name, $password, $email)
    {
        //判断系统是否可以注册
        $enableReg = MiniOption::getInstance()->getOptionValue("user_register_enabled");
        if (isset($enableReg) && $enableReg == "0") {
            throw new MiniException(1000);
        }
        $data = array();
        //参数完整性检测
        if (empty($name) || strlen($name) > 255) {
            throw new MiniException(1000);
        }
        if (empty($password)) {
            throw new MiniException(1001);
        }
        if (!empty($email)) {
            //TODO check email rule
        }
        //唯一性检测
        $user = MiniUser::getInstance()->getUserByName($name);
        if (!empty($user)) {
            throw new MiniException(1002);
        }
        //创建用户
        $userData = array();
        $userData['name'] = $name;
        $userData['password'] = $password;
        if (!empty($email)) {
            $extend = array();
            $extend["email"] = $email;
            $userData["extend"] = $extend;
        }
        $user = MiniUser::getInstance()->create($userData);
        if (empty($user)) {
            return $data;
        }
        return $data;
    }

    /**
     * 创建外联（1.6将去掉）
     * @param $fileId
     * @param $status
     * @return array
     */
    public function createLink($fileId, $status)
    {
        if ($status == "0") {
            MiniLink::getInstance()->unlink($fileId);
            $data = array();
            $data["state"] = true;
            $data["code"] = 0;
            return $data;
        } else {
            //创建外链
            $link = MiniLink::getInstance()->create($this->user["id"], $fileId, "", -1);
            $data = array();
            $linkCode = array();
            $linkCode[$fileId] = $link["share_key"];
            $data["linkcode"] = $linkCode;
            $data["state"] = true;
            $data["code"] = 0;
            return $data;
        }
    }

    /**
     * 查看系统是否只有默认账号
     */
    public function onlyDefaultAccount()
    {
        $status = false;
        $name = "admin";
        $password = "admin";
        $count = MiniUser::getInstance()->getEnableCount();
        if ($count == 1) {
            $status = MiniUser::getInstance()->valid($name, $password);
        }
        return array("success"=>$status);
    }
}