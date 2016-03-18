<?php
/** 
 * 系统管理
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.8
 */
class SystemManageBiz extends MiniBiz{
    public  $enabledEmail;//是否开启电子邮件发送功能
    public  $enabledMailFun;//使用系统默认mail函数
    public  $smtpServer;//smtp Server地址
    public  $port;//smtp 端口
    public  $sender;//发件人地址
    public  $checkAuth;//是否验证发件人身份
    public  $name;//发件人用户名
    public  $passwd;//发件人密码
    public  $receiver;//收件人地址
    /**
     * 保存邮箱设置
     */
    public function saveMail(){
        MiniOption::getInstance()->setOptionValue("mail_enabled_email",$this->enabledEmail);
        MiniOption::getInstance()->setOptionValue("mail_enabled_mail_fun",$this->enabledMailFun);
        MiniOption::getInstance()->setOptionValue("mail_smtp_server",empty($this->smtpServer)?"":$this->smtpServer);
        MiniOption::getInstance()->setOptionValue("mail_port",$this->port);
        MiniOption::getInstance()->setOptionValue("mail_sender",empty($this->sender)?"":$this->sender);
        MiniOption::getInstance()->setOptionValue("mail_check_auth",$this->checkAuth);
        MiniOption::getInstance()->setOptionValue("mail_name",empty($this->name)?"":$this->name);
        MiniOption::getInstance()->setOptionValue("mail_password",empty($this->sender)?MiniUtil::encrypt(""):MiniUtil::encrypt($this->passwd));
    }
    /**
     * 测试邮件
     */
    public function testMail(){
        //TODO 合法性检测
        $this->saveMail();
    }
    /**
     * 清除过期事件
     */
    public function handleCleanEvents($limit) {
        $sql_str = "select user_id, min(meta_value) as min  from ";
        $sql_str .= UserDeviceMeta::model()->tableName() ." where meta_name = 'event_id' group by user_id,meta_name ";
        $sql     = Yii::app()->db->createCommand($sql_str);
        $user_devices_list = $sql->queryAll();
        foreach ($user_devices_list as $val){
            $e_user_id = $val['user_id'];//用户id
            $event_id  = $val['min'];//事件id
            $events = MiniEvent::getInstance()->queryAllbyCondition($e_user_id,$event_id,$limit);
            foreach ($events as $event){
                $event->delete();
            }
        }
    }
    /**
     * 计算过期事件条数
     */
    public function getCountEvents(){
        $sql_str     = "select count(*) as count  from ";
        $sql_str    .= UserDeviceMeta::model()->tableName() ." where meta_name < 'event_id' group by user_id,meta_name";

        $sql          = Yii::app()->db->createCommand($sql_str);
        $user_devices = $sql->queryAll();
        if (empty($user_devices)){
            return 0;
        }
        return $user_devices[0]['count'];
    }
    /**
     * 统计文件的缓存,不统计清除
     */
    public function countCache() {
        $sql_str = 'SELECT file_size FROM ' . Yii::app()->params['tablePrefix'] . 'file_versions WHERE ref_count<=0';
        $sql = Yii::app()->db->createCommand($sql_str);
        $versions = $sql->queryAll();
        //计算总大小
        $sum_size = 0;
        foreach ($versions as $version){
            $sum_size += $version["file_size"];
        }
        $biz = new HomePageBiz();
        $tempDirectory = $biz->getDirectorySize(BASE.'temp');
        $tempSize = $tempDirectory['size'];
        return $tempSize+$sum_size;
    }

    /**
     * 处理meta信息
     * @param $metas
     */
    private function handleMeta($metas) {
        $list = array();
        if(empty($metas)) {
            return ;
        }
        $deleted = 0;
        $last = $metas[count($metas) - 1];
        //
        // 如果最后一次操作是删除 则去掉
        //
        if ($last['type'] == MConst::DELETE) {
            $deleted = $last['version_id'];
        }

        foreach ($metas as $index => $meta) {
            // 防止出现只有一条记录的情况,产生场景重命名
            if ($deleted == $meta['version_id'] && count($metas) > 1) {
                $deleted = 0;
                continue;
            }
            switch ($meta['type']) {
                case MConst::CREATE_FILE:
                case MConst::MODIFY_FILE:
                case CConst::WEB_RESTORE:
                    array_push($list, $meta['version_id']);
                    break;
                default:
                    break;
            }
        }
        if (empty($list)) {
            return ;
        }
        FileVersion::model()->updateRefCountByIds($list);
    }

    /**
     * 清理file meta中多余的值
     * @param $limit
     */
    private function handleCleanFileMeta($limit) {
        $condition = 'meta_key="version" and file_path not in (';
        $condition .= 'select file_path from ';
        $condition .= UserFile::model()->tableName();
        $condition .= ' where file_type = 0 ';
        $condition .= ') limit '.$limit;
        $fileMetas = FileMeta::model()->findAll($condition);
        foreach ($fileMetas as $fileMeta) {
            $metas = unserialize($fileMeta['meta_value']);
            $this->handleMeta($metas);
            $fileMeta->delete();
        }
    }

    /**
     * 清理全部的缓存,包括多余的file_meta
     * @param $limit
     */
    public function cleanCache($limit) {
        //data源处理对象
        $dataObj = Yii::app()->data;
        // 回收站插件: -1保留值 0正常 1删除
        $this->handleCleanFileMeta($limit);
        // 清理ref_count等于0的文件
        $versions = MiniVersion::getInstance()->getCleanFiles(100);
        foreach ($versions as $version){
            $files = UserFile::model()->findAll('version_id=?', array($version['id']));
            // 如果$file存在此version_id,不删除
            if (!empty($files)){
                for($i=0;$i<count($files);$i++){
                    MiniVersion::getInstance()->updateRefCount($version["id"]);
                }
                continue;
            }

            // 如果不存在的话，删除流文件，删除该条version记录
            $signature    = $version['file_signature'];
            $signaturePath = MiniUtil::getPathBySplitStr($signature);
            // 判断文件是否存在
            if ($dataObj->exists($signaturePath) === false){
                MiniVersion::getInstance()->deleteById($version["id"]);
                continue;
            }
            // 删除文件
            $dataObj->delete($signaturePath);
            //删除空的文件夹
            $parts = CUtils::getFoldersBySplitStr($signature);
            foreach ($parts as $part) {
                $dataObj->delete($part);
            }
            // 删除version记录
            MiniVersion::getInstance()->deleteById($version["id"]);

        }
        MiniUtil::deleteDir(BASE.'temp');
    }

    /**
     * 计算错误日志条数
     */
    public function getCountErrors(){
        $log =new APIErrorLog();
        $count = $log->count();
        return $count;
    }
    /**
     * 验证网址格式，如不全，添加http://
     * @param $userCreateUrl
     * @param $userGetpwdUrl
     * @return array
     */
    public function checkUrl($userCreateUrl,$userGetpwdUrl){
        $url = array();
        if(preg_match("/(https?):\/\//",$userCreateUrl)==0){
            $userCreateUrl = "http://".$userCreateUrl;
        }
        if(preg_match("/(https?):\/\//",$userGetpwdUrl)==0){
            $userGetpwdUrl = "http://".$userGetpwdUrl;
        }
        $url['userCreateUrl'] = $userCreateUrl;
        $url['userGetpwdUrl'] = $userGetpwdUrl;
        return $url;
    }

    /**
     * 设置文件存储路径
     * @param $newFilePath
     */
    public function setStorePath($newFilePath){
        $conf_filePath   =  dirname(__FILE__).'/../../config/miniyun-config.php';
        $conf_content    =  file_get_contents($conf_filePath);
        $str2 = preg_replace("/\('BASE',.*\)/" , "('BASE',\"".utf8_encode($newFilePath).'")' , $conf_content );
        file_put_contents($conf_filePath, $str2);
    }

    /**
     * 设置站点信息
     * @param $site
     * @return array
     */
    public function settingSiteInfo($site){
        //迷你存储没有启用，将判断用户存储路径的完整性
        $storeData = MiniUtil::getPluginMiniStoreData();
        if(empty($storeData)){
            $fileStorePath = $site['fileStorePath'];
            //文件存储路径的合法性检测
            if(is_dir($fileStorePath)== false){
                return array('success'=>false,'msg'=>'dir_is_not_exist');
            }
            //
            // 判断父目录是否存在
            //
            if (file_exists(dirname($fileStorePath)) == false){
                return array('success'=>false,'msg'=>'parent_dir_is_not_exist');
            }
            //
            // 文件不存在
            //
            if (file_exists($fileStorePath) == false){
                mkdir($fileStorePath);
                chmod($fileStorePath, 0755);
            }
            //
            // 文件夹不可写
            //
            if (is_writable($fileStorePath) == false){
                return array('success'=>false,'msg'=>'dir_is_not_writable');
            }
            //修改文件存储配置
            $this->setStorePath($site['fileStorePath']);
        }
        MiniOption::getInstance()->setOptionValue("miniyun_host", $site['miniyun_host']);
        MiniOption::getInstance()->setOptionValue("site_title", $site['siteTitle']);
        MiniOption::getInstance()->setOptionValue("site_name", $site['siteName']);
        MiniOption::getInstance()->setOptionValue("site_default_space", $site['siteDefaultSpace']);
        MiniOption::getInstance()->setOptionValue("site_company", $site['siteCompany']);
        MiniOption::getInstance()->setOptionValue("user_register_enabled", $site['userRegisterEnabled']);
        MiniOption::getInstance()->setOptionValue("upload_policy_white_list", $site['upload_policy_white_list']);
        MiniOption::getInstance()->setOptionValue("upload_policy_black_list", $site['upload_policy_black_list']);
        MiniOption::getInstance()->setOptionValue("upload_policy_file_size", $site['upload_policy_file_size']);
        //如果是混合云版，则调整迷你存储的访问地址
        if(MiniUtil::isMixCloudVersion()){
            $plugins = MiniUtil::getActivedPluginsInfo();
            if(!empty($plugins)){
                foreach ($plugins as $plugin) { 
                    if($plugin["name"]==="miniStore"){
                        //创建默认迷你存储站点
                        PluginMiniStoreNode::getInstance()->createDefault();
                    }
                }
            }
            
        }
        return array('success'=>true);
    }

    /**
     * 设置邮件服务顺
     * @param $mail
     * @return array
     */
    public function settingMailInfo($mail){
        if($mail['enabledEmail'] == 0){
            $this->enabledEmail     = 0;
            $this->enabledMailFun   = 0;
            $this->smtpServer       = null;
            $this->sender           = null;
            $this->receiver         = null;
            $this->name             = null;
            $this->password         = null;
            $this->port             = null;
            $this->checkAuth        = 0;
            $this->saveMail();
        }else{
            $this->enabledEmail     = 1;
            $this->enabledMailFun   = $mail['enabledMailFun'];
            $this->smtpServer       = $mail['smtpServer'];
            $this->sender           = $mail['sender'];
            $this->receiver         = $mail['receiver'];
            $this->checkAuth        = $mail['checkAuth'];
            $this->name             = $mail['name'];
            $this->password         = $mail['password'];
            $this->port             = $mail['port'];
            $this->testMail();
            return array('success'=>true);
        }
    }

    /**
     * 高级选项，清理多余数据
     * @param $events
     * @param $errors
     * @param $files
     * @return array
     */
    public function cleanExcessData($events,$errors,$files){
        $limit  = 1000;
        //TODO 更新客户端多账号登陆
//            $this->updateMultiClients();
        if ( (empty($events) && empty($errors) && empty($files))){
            return false;
        }
        $result= array();
        if ($events == 1){//删除过期事件
            $this->handleCleanEvents($limit);
            $result['events'] = $this->getCountEvents();//查询过期事件条数
        }

//        if ($errors == '1'){//删除错误日志
//            $errors = APIErrorLog::model()->findAll(array('limit'=>$limit));/**/
//            foreach ($errors as $error){
//                $error->delete();
//            }
//            $result['errors'] = $this->getCountErrors();//查询错误日志条数
//        }

        if ($files == '1'){//删除无关联文件
            $this->cleanCache($limit);
            $result['cache']  = $this->countCache();//查询无关联文件的大小
        }

        if (empty($result['events']) && empty($result['errors']) && empty($result['cache']) ){
            return array('success'=>true);
        }
        return array('success'=>false);
    }

    /**
     * 授权信息
     * @param $key
     * @return array
     */
    public function getLicenseInfo($key){
        if($key == ''){
            $key = MiniOption::getInstance()->getOptionValue('license_info');
            if($key == ''){
                return array('success'=>false,'msg'=>'noLicense');
            }
        }
        $info = CSecurity::anaKey($key);
        $data = array();
        if (!$info){
            return array('success'=>false,'msg'=>'licenseWrong');
        }
        $data['organisation']   = isset($info->verdor)?$info->verdor:"";
        $data['datePurchased']  = isset($info->validFrom)?date("Y-m-d",$info->validFrom):"";
        $data['licenseType']    = isset($info->offer)?$info->offer:"";
        $data['validTo']        = ($info->validTo != 0)?date("Y-m-d",$info->validTo) : 'unlimited';
        $data['userLimit']      = ($info->users != 0)?$info->users : 'unlimited';
        $data['deviceLimit']    = ($info->devices != 0)?$info->devices : 'unlimited';
        $data['version']        = isset($info->version)?$info->version:"";
        $value                  = MiniOption::getInstance()->getOptionValue('license_server_name');//考虑到分布式集群，授权的IP可能存在多个，这里进行统一
        if(isset($value)){
            $data['serverName'] = $value;
        }else{
            $data['serverName'] = Yii::app()->request->serverName;
        }
        $data['hasLicense']     = true;
        $data['info']           = $info;
        MiniOption::getInstance()->setOptionValue("license_info", $key);
        MiniOption::getInstance()->setOptionValue("license_server_name", $data['serverName']);//考虑到分布式集群，授权的IP可能存在多个，这里进行统一
        return $data;
    }

    /**
     * 用户自定义注册与找回密码地址
     * @param $userCreateUrl
     * @param $userPwdUrl
     * @return array
     */
    public function customUrl($userCreateUrl,$userPwdUrl){
        $url = $this->checkUrl($userCreateUrl,$userPwdUrl);
        MiniOption::getInstance()->setOptionValue("user_create_url", $url['userCreateUrl']);
        MiniOption::getInstance()->setOptionValue("userGetpwdUrl", $url['userGetpwdUrl']);
        return array('success'=>true);
    }
    /**
     * 获取站点的基本信息
     */
    public function getSiteInfo(){
        $data                         = array();
        $data['siteTitle']            = MiniOption::getInstance()->getOptionValue('site_title');
        $data['siteName']             = MiniOption::getInstance()->getOptionValue('site_name');
        $data['siteDefaultSpace']     = MiniOption::getInstance()->getOptionValue('site_default_space');
        $data['siteCompany']          = MiniOption::getInstance()->getOptionValue('site_company');
        $data['userRegisterEnabled']  = MiniOption::getInstance()->getOptionValue('user_register_enabled');
        $policy1  = MiniOption::getInstance()->getOptionValue('upload_policy_white_list');
        $policy2  = MiniOption::getInstance()->getOptionValue('upload_policy_black_list');
        $policy3  = MiniOption::getInstance()->getOptionValue('upload_policy_file_size');
        
        if(empty($policy1)){
            $policy1 = '*';
        }
        if(empty($policy2)){
            $policy2 = '';
        }
        if(empty($policy3)){
            $policy3 = 102400;
        }
        $data['upload_policy_white_list'] = $policy1;
        $data['upload_policy_black_list'] = $policy2;
        $data['upload_policy_file_size'] = intval($policy3);
        $miniHost                     = MiniOption::getInstance()->getOptionValue('miniyun_host');
        if(empty($miniHost)){
            $miniHost = MiniHttp::getMiniHost();
            MiniOption::getInstance()->setOptionValue('miniyun_host',$miniHost);
        }
        $data['miniyun_host']         = $miniHost;
        $data['fileStorePath']        = BASE;
        return $data;
    }

    public function mateCodeData($meatArr){
        return MiniOption::getInstance()->setOptionValue("code", $meatArr);
    }
}