<?php
/**
 * 迷你云共享 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniShare
{
    /**
     * 当前登录的用户
     * @var
     */
    protected $user;
    /**
     * 当前登录的设备
     * @var
     */
    protected $device;

    public function MiniShare(){
        $this->user    = MUserManager::getInstance()->getCurrentUser();
        $this->device  = MUserManager::getInstance()->getCurrentDevice();
    }
    /**
     * 根据相对路径获得文件最小对象
     * @param $relativePath
     * @return Array|NULL
     */
    public function getMinFileMetaByPath($relativePath){
        $data = array();
        //如果是根目錄
        $absolutePath = MiniUtil::getAbsolutePath($this->user["id"], $relativePath);
        if(empty($relativePath)){
            $data["ori_path"] = $absolutePath;
            return $data;
        }
        $absolutePath = MiniUtil::getAbsolutePath($this->user["id"], $relativePath);
        $file = MiniFile::getInstance()->getByPath($absolutePath);
        if(!empty($file)){
            $data["mime_type"] = $file["mime_type"];
            $data["version_id"] = $file["version_id"];
            $data["ori_path"] = $file["file_path"];
            return $data;
        }
        $info = explode("/",$absolutePath);
        if(count($info)<2){
            return NULL;
        }
        //共享目录都在根目录下，在这里拼接出file_mates需要的file_path
        $shareFilePath = MiniUtil::joinPath($info[1],$info[2]);
        //查询所在的根目录是否是共享目录
        $meta = MiniFileMeta::getInstance()->getFileMeta($shareFilePath,"shared_folders");
        if($meta===NULL){
            return NULL;
        }
        $metaValue = $meta["meta_value"];
        //获得发起人所在的目录根目录信息，并且拼接出文件原始路径
        $shareDetail = unserialize($metaValue);
        $initiatorRootPath = $shareDetail["path"];
        $shareRelativePath = substr($absolutePath,strlen($shareFilePath));
        $oriPath = $initiatorRootPath.$shareRelativePath;
        $file = MiniFile::getInstance()->getByPath($oriPath);
        $data["mime_type"] = $file["mime_type"];
        $data["version_id"] = $file["version_id"];
        $data["ori_path"] = $file["file_path"];
        return $data;
    }
}