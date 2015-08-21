<?php
/**
 * 文件历史版本业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class HistoryBiz extends MiniBiz{
    /**
     * 获取当前文件版本列表
     * @param $path
     * @return mixed
     */
    public function getList($path){
        $item = explode("/",$path);
        $permissionArr = UserPermissionBiz::getInstance()->getPermission($path,$this->user['id']);
        if($item[1]!==$this->user['id']&&count($permissionArr)==0){
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }
        $file = MiniFile::getInstance()->getByPath($path);
        $version_id = $file['version_id'];
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($path,"versions");
        $fileVersion = MiniVersion::getInstance()->getVersion($version_id);
        $currentSignature = $fileVersion['file_signature'];
        $historyArr = json_decode($fileMeta['meta_value']);
        // 去掉delete事件版本
        $histories = array();
        foreach ($historyArr as $item){
            $hash = $item->{'hash'};
            $deviceId = $item->{'device_id'};
            $time = $item->{'time'};
            $history = array(); 
            $version = MiniVersion::getInstance()->getBySignature($hash);
            $deviceName = "";
            $userNick = "";
            $device = MiniUserDevice::getInstance()->getUserDevice($deviceId);
            if($device){
                $deviceName = $device['user_device_name'];
                $user = MiniUser::getInstance()->getUser($device["user_id"],false);
                if($user){
                    $userNick = $user['nick'];
                }
            }
            $history['file_size'] = $version['file_size'];
            $history['user_nick'] = $userNick;
            $history['device_name'] = $deviceName;
            $history['datetime'] =  MiniUtil::formatTime($time);
            $history['signature'] = $hash;
            array_push( $histories, $history);
        }
        $data['histories'] = $histories;
        $data['current_signature'] = $currentSignature;
        return $data;
    }
    /**
     * 恢复版本
     * @param $signature
     * @param $filePath
     * @return mixed
     */
    public function recover($signature,$filePath){
        $share = new MiniShare();
        $minFileMeta = $share->getMinFileMetaByPath($filePath);
        $filePath = $minFileMeta["ori_path"];
        return MiniFile::getInstance()->recover($this->user["id"],$filePath,$signature);
    }

}