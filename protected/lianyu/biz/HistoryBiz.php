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
        $permissionModel = new UserPermissionBiz($path,$this->user['id']);
        $permissionArr = $permissionModel->getPermission($path,$this->user['id']);
        if($item[1]!==$this->user['id']&&count($permissionArr)==0){
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }
        $file = MiniFile::getInstance()->getByPath($path);
        $version_id = $file['version_id'];
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($path,"version");
        $fileVersion = MiniVersion::getInstance()->getVersion($version_id);
        $currentSignature = $fileVersion['file_signature'];
        $historyArr = array_reverse(unserialize($fileMeta['meta_value']));
        // 去掉delete事件版本
        $histories = array();
        foreach ($historyArr as $item){
            $history = array();
            if ($item['type'] == CConst::DELETE){
                continue;
            }
            $history['type'] = $item['type'];
            $history['file_size'] = $item['file_size'];
            $history['user_nick'] = $item['user_nick'];
            $history['device_name'] = $item['device_name'];
            $history['datetime'] =  MiniUtil::formatTime(strtotime($item['datetime']));
            $fileVersion = MiniVersion::getInstance()->getVersion($item['version_id']);
            $history['signature'] = $fileVersion['file_signature'];
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