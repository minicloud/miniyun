<?php
/**
 * 回收站业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class RecycleBiz extends MiniBiz
{
    /**获得假删除文件分页信息
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getFileList($page, $pageSize)
    {
        $pageSet = ($page - 1) * $pageSize;
        $sessionUser = $this->user;
        $userId = $sessionUser["id"];
        $deleteList = MiniFile::getInstance()->getDeleteFile($userId, $pageSize, $pageSet);
        $deleteCount = MiniFile::getInstance()->getDeleteFileCount($userId);
        $list = array();
        $data = array();
        foreach ($deleteList as $value) {
            $data["file_name"] = $value["file_name"];
            $data["file_size"] = $value["file_size"];
            $data["file_path"] = MiniUtil::getRelativePath($value["file_path"]);
            $data["create_time"] = $value["file_create_time"];
            $data["is_deleted"] = $value["is_deleted"];
            $data["type"] = $value["file_type"];
            $list[] = $data;
        }
        $dataList['list'] = $list;
        $dataList['total'] = $deleteCount;
        return $dataList;

    }

    /**按恢复文件
     * @param $path
     */
    public function recover($path)
    {
        $sessionUser = $this->user;
        $device                   = MUserManager::getInstance()->getCurrentDevice();
        $paths = array();
        $userId = $sessionUser["id"];
        if(strlen($path)==0){
            $paths = MiniFile::getInstance()->getDeleteFile($userId);
        }else{
            $data['file_path'] = $path;
            $paths[] = $data;
        }
        for($i=0;$i<count($paths);$i++){
            $path = $paths[$i]['file_path'];
//            $filePath = "";
//            $arrPath = explode("/", $path);
//            for ($i = 1; $i < count($arrPath); $i++) {
                $filePath = $path;
                $file    = MiniFile::getInstance()->getByPath($filePath);
                $version = FileVersion::model()->findByPk($file["version_id"]);
                $context = array(
                    "hash"        => $version["file_signature"],
                    "rev"         => (int)$file['version_id'],
                    "bytes"       => (int)$file['file_size'],
                    "update_time" => (int)$file['file_update_time'],
                    "create_time" => (int)$file['file_create_time']
                );
                $action = 3;
                $context = serialize($context);
                MiniFile::getInstance()->recoverDelete($filePath);
                if($file['file_type'] == 1){
                    $context = $filePath;
                    $action  = 0;
                }
                MiniEvent::getInstance()->createEvent(
                    $userId,
                    $device['device_id'],
                    $action,
                    $filePath,
                    $context,
                    MiniUtil::getEventRandomString( MConst::LEN_EVENT_UUID ),
                    MSharesFilter::init()
                );
//            }
        }

    }

    /** 永久删除文件
     * @param $path
     * @return mixed
     */
    public function delete($path)
    {
        $sessionUser = $this->user;
        $userId = $sessionUser["id"];
        $filePath = "/" . $userId . $path;
        $list = MiniFile::getInstance()->getByPath($filePath);
        $result = MiniFile::getInstance()->deleteFile($list["id"]);
        return array("success"=>$result);
    }

    /** 按照文件名查找假删除文件
     * @param $fileName
     * @return mixed
     */
    public function search($fileName){
        $sessionUser = $this->user;
        $user_id = $sessionUser["id"];
        $deleteList=MiniFile::getInstance()->getFileByNameRecycle( $user_id, $fileName);
        $list = array();
        $data = array();
        foreach ($deleteList as $value) {
            $data["file_name"] = $value["file_name"];
            $data["file_size"] = $value["file_size"];
            $data["file_path"] = MiniUtil::getRelativePath($value["file_path"]);
            $data["create_time"] = $value["file_create_time"];
            $data["is_deleted"] = $value["is_deleted"];
            $data["type"] = $value["file_type"];
            $list[] = $data;
        }
        $dataList['list'] = $list;
        return $dataList;

    }
}