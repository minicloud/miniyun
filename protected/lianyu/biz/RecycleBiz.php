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
    public function getFileList($page, $pageSize,$currentPath){
        $pageSet = ($page - 1) * $pageSize;
        $sessionUser = $this->user;
        $userId = $sessionUser["id"];
        if($currentPath==""){
            $parentFileId = 0;
        }
        $deleteList = MiniFile::getInstance()->getDeleteFile($userId, $pageSize, $pageSet ,$parentFileId);
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
        $userId = $sessionUser["id"];
        $filePath = "/" . $userId;
        $arrPath = explode("/", $path);
        $path = "/" . $userId.$path;
        return MiniFile::getInstance()->recoverDelete($path,$userId,$device);
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