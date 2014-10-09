<?php
/**
 * 后台文件管理服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class FileManageService extends MiniService{
    /**
     * 分页获取获取所有文件列表
     */
    public function getList()
    {
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $model       = new FileManageBiz();
        $data        = $model->getFiles($pageSize,$currentPage);
        return $data;
    }

    public function create(){
        $departmentName = MiniHttp::getParam('department_name','');
//        $parentDepartmentId = MiniHttp::getParam('parent_department_id','-1');
//        $biz = new DepartmentBiz();
//        $result = $biz->create($departmentName,$parentDepartmentId);
        return $departmentName;
    }
    /**
     * 根据文件类型分页获取文件列表
     */
    public function getFileByType(){
        $fileType    = MiniHttp::getParam('file_type','office');
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $model       = new FileManageBiz();
        $data        = $model->getFileByType($fileType,$pageSize,$currentPage);
        return $data;
    }

    /**
     * 根据文件名模糊查询文件
     */
    public function searchFilesByName(){
        $fileType    = MiniHttp::getParam('file_type','all');
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $fileName    = MiniHttp::getParam('file_name','');
        $model       = new FileManageBiz();
        $data        = $model->searchFilesByName($fileType,$fileName,$pageSize,$currentPage);
        return $data;
    }
    /**
     * 根据路径下载文件
     */
    public function download() {
        $path  = MiniHttp::getParam("path","");
        $model = new FileManageBiz();
        $model->download($path);
    }
    /**
     * 获取指定目录下的文件
     */
    public function getFilesByParentPath(){
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $path  = MiniHttp::getParam("path","");
        $model = new FileManageBiz();
        $data  = $model->getFilesByParentPath($path,$pageSize,$currentPage);
        return $data;
    }
    /**
     * 获取各类文件数
     */
    public function getFileCount(){
        $model = new FileManageBiz();
        $data  = $model->getFileCount();
        return $data;
    }
    /**
     * 获得默认时间跨度
     */
    public function getFileTime(){
        $model = new FileManageBiz();
        $data  = $model->getFileTime();
        return $data;
    }
    /**
     * 获得传入时间内的文件数
     */
    public function getGraphBeforeDateFiles(){
        $wholeDate = MiniHttp::getParam('wholeDate',"");
        $model     = new FileManageBiz();
        $data      = $model->getGraphBeforeDateFiles($wholeDate);
        return $data;
    }
    /**
     * 获取系统节约空间
     */
    public function getSaveSpace(){
        $model     = new FileManageBiz();
        $saveSpace = $model->getSaveSpace();
        return $saveSpace;
    }
    /**
     * 获取该文件的信息
     */
    public function getFileInfo(){
        $filePath = MiniHttp::getParam('file_path',"");
        return $filePath;
    }
    /**
     * 设置为公共目录
     */
    public function setToPublic(){
        $filePath = MiniHttp::getParam('file_path',"");
        $model     = new FileManageBiz();
        return $model->setToPublic($filePath);
    }
    /**
     * 设置为公共目录
     */
    public function cancelPublic(){
        $filePath = MiniHttp::getParam('file_path',"");
        $model     = new FileManageBiz();
        return $model->cancelPublic($filePath);
    }
    /**
     * 设置公共目录权限
     */
    public function setPrivilege(){
        $filePath = MiniHttp::getParam('file_path',"");
        $privilege = MiniHttp::getParam('privilege',"");
        $model     = new FileManageBiz();
        return $model->setPrivilege($filePath,$privilege);
    }
}