<?php
/** 
 * 文件管理
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class FileManageBiz extends MiniBiz{
    private $list = array();
    /**
     * 文件数据处理
     */
    public function dealFile($file){
        $user                = MiniUser::getInstance()->getUser($file['user_id']);
        $file['user_nick']   = $user['nick'];
        $parentPath = explode('/',$file['file_path']);
        array_pop($parentPath);
        array_shift($parentPath);
        array_shift($parentPath);
        $parentPath = '/'.implode('/',$parentPath);
        $file['parent_path'] = $parentPath;
        return $file;
    }
    /**
     * 分页获取文件数据
     */
    public function getFiles($pageSize,$currentPage){
        $files                = MiniFile::getInstance()->getAllFilesList($pageSize,$currentPage);
        $data                 = array();
        foreach($files as $file){
            $file = $this->dealFile($file);
            array_push($data,$file);
        }
        $this->list['list']   = $data;
        $this->list['total']  = UserFile::model()->count();
        return $this->list;
    }
    /**
     * 根据文件类型获取文件列表
     */
    public function getFileByType($fileType,$pageSize,$currentPage){
        $files          = MiniFile::getInstance()->getAllFileListByType($fileType,$pageSize,$currentPage);
        $list           = array();
        $data           = array();
        foreach($files['list'] as $file){
            $file = $this->dealFile($file);
            array_push($data,$file);
        }
        $list['list']   = $data;
        $list['total']  = $files['total'];
        return $list;
    }
    /**
     * 根据文件名模糊查找文件
     */
   public function searchFilesByName($fileType,$fileName,$pageSize,$currentPage){
        $files           = MiniFile::getInstance()->searchFilesByName($fileType,$fileName,$pageSize,$currentPage);
        $list           = array();
        $data           = array();
        foreach($files['list'] as $file){
            $file = $this->dealFile($file);
            array_push($data,$file);
        }
        $list['list']   = $data;
        $list['total']  = $files['total'];
        return $list;
   }
   /**
    *根据文件路径下载文件
    */
   public function download($path){
        MiniFile::getInstance()->download($path);
   }
   /**
    * 获取指定目录下的文件
    */
   public function getFilesByParentPath($path,$pageSize,$currentPage){
        $file    = MiniFile::getInstance()->getByPath($path);
        $pathArr = explode('/',$path);
        $userId  = $pathArr['1'];
        $files   = MiniFile::getInstance()->getAllChildrenByParentId($userId,$file['id'],$pageSize,$currentPage);
        $list           = array();
        $data           = array();
        foreach($files['list'] as $file){
            $file = $this->dealFile($file);
            array_push($data,$file);
        }
        $list['list']   = $data;
        $list['total']  = $files['total'];
        return $list;
   }
    /**
     * 获取各类文件数
     */
    public function getFileCount(){
        $fileModel = new UserFile();
        $data = array();
        $data['file_count'] = $fileModel->count();
        $data['office_count'] = $fileModel->officeCount();
        $data['image_count'] = $fileModel->imageCount();
        $data['deleted_count'] = $fileModel->deletedCount();
        $data['folders_count'] = $fileModel->foldersCount();
        $data['audio_count'] = $fileModel->audioCount();
        $data['vedio_count'] = $fileModel->vedioCount();
        return $data;
    }

    /**
     * 获取默认的文件时间跨度
     */
    public function getFileTime(){
        $data = MiniFile::getInstance()->getFileTime();
        $time = array();
        $time['first_file_time'] = $data['first_file']['created_at'];
        $time['last_file_time'] = $data['last_file']['created_at'];
        return $time;
    }
    /**
     * 获得对应时间内的文件数
     */
    public function getGraphBeforeDateFiles($wholeDate){
        $count = MiniFile::getInstance()->getBeforeDateFiles($wholeDate);
        return $count;
    }
    /**
     * 获取系统节约空间
     */
    public function getSaveSpace(){
        $saveSpace = MiniFile::getInstance()->getTotalSize() - MiniVersion::getInstance()->getTotalSize();
        return $saveSpace;
    }
    /**
     * 设置为公共目录
     */
    public function setToPublic($filePath){
        Minifile::getInstance()->setToPublic($filePath);//设置目录file_type为4(公共目录)
        MiniGroupPrivilege::getInstance()->create(-1,$filePath,'111111111');
        return array('success'=>true);
    }
    /**
     * 设置为公共目录
     */
    public function cancelPublic($filePath){
        Minifile::getInstance()->cancelPublic($filePath);//设置目录file_type为1(变成普通目录)
        MiniGroupPrivilege::getInstance()->deletePrivilege(-1, $filePath);
        return array('success'=>true);
    }
    /**
     * 设置公共目录权限
     */
    public function setPrivilege($filePath,$privilege){
        MiniGroupPrivilege::getInstance()->create(-1,$filePath,$privilege);
        return array('success'=>true);
    }
    /**
     * 获取根目录下文件夹
     */
    public function getFolders(){
        $userId = $this->user['id'];
        $folders = MiniFile::getInstance()->getChildrenFolderByParentId($userId,0,0);
        return $folders;
    }
}