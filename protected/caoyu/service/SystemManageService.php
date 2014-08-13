<?php
/**
 * 系统设置服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class SystemManageService extends MiniService{
    /**
     * 设置站点基本信息
     */
    public function settingSiteInfo(){
        $site['file']  = $_FILES['siteImage'];
        $site['siteTitle']            = MiniHttp::getParam('siteTitle','miniyun');
        $site['siteName']             = MiniHttp::getParam('siteName','miniCloud');
        $site['siteDefaultSpace']     = MiniHttp::getParam('siteDefaultSpace','1024');
        $site['siteCompany']          = MiniHttp::getParam('siteCompany','');
        $site['userRegisterEnabled']  = MiniHttp::getParam('userRegisterEnabled','1');
        $site['fileStorePath']        = MiniHttp::getParam('fileStorePath','');
        $model  = new SystemManageBiz();
        $result = $model->settingSiteInfo($site);
        if($result['success'] == false){
            setcookie("settingMsg",$result['msg'],time()+3600,"/");
        }else{
            setcookie("settingMsg",'',time()+3600,"/");
        }
        return $result;
    }
    /**
     *邮件服务器设置
     */
    public function settingEmail(){
        $mail['enabledMailFun']       = MiniHttp::getParam('mail_enabled_mail_fun','');
        $mail['enabledEmail']         = MiniHttp::getParam('mail_enabled_email','');
        $mail['smtpServer']           = MiniHttp::getParam('mail_smtp_server','');
        $mail['sender']               = MiniHttp::getParam('mail_sender','');
        $mail['checkAuth']            = MiniHttp::getParam('mail_check_auth','');
        $mail['name']                 = MiniHttp::getParam('mail_name','');
        $mail['password']             = MiniHttp::getParam('mail_password','');
        $mail['receiver']             = MiniHttp::getParam('setting_email_receiver','');
        $mail['port']                 = MiniHttp::getParam('mail_port','');
        $model  = new SystemManageBiz();
        $result = $model->settingMailInfo($mail);
        return $result;
    }
    /**
     * 高级选项，数据清理
     */
    public function cleanExcessData(){
        $events                  = MiniHttp::getParam('events','');
        $errors                  = MiniHttp::getParam('errors','');
        $files                   = MiniHttp::getParam('files','');
        $model  = new SystemManageBiz();
        $result = $model->cleanExcessData($events,$errors,$files);
        return $result;
    }
    /**
     * 授权许可
     */
    public function license(){
        $key                  = MiniHttp::getParam('key','');
        $model  = new SystemManageBiz();
        $result = $model->getLicenseInfo($key);
        return $result;
    }
    /**
     * 用户自定义注册与找回密码地址
     */
    public function customUrl(){
        $userCreateUrl                 = MiniHttp::getParam('user_create_url','');
        $userGetpwdUrl                 = MiniHttp::getParam('user_getpwd_url','');
        $model  = new SystemManageBiz();
        $result = $model->customUrl($userCreateUrl,$userGetpwdUrl);
        return $result;
    }
    /**
     * 获取缓存文件的大小
     */
    public function countCache(){
        $model  = new SystemManageBiz();
        $size   = $model->countCache();
        return $size;
    }
    /**
     * 获取站点信息
     */
    public function getSiteInfo(){
        $model = new SystemManageBiz();
        $data  = $model->getSiteInfo();
        return $data;
    }
}