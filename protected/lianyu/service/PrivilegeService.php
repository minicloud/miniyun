<?php
/**
 * 权限服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class PrivilegeService extends MiniService
{
    /**
     * 获取已有权限好友列表
     * @return array
     */
    public function getList()
    {
        $filePath = MiniHttp::getParam("file_path", '');
        $privilege = new PrivilegeBiz();
        return $privilege->getPrivilegeList($filePath);
    }

    /**
     * 存储好友权限
     */
    public function create()
    {
        $filePath = MiniHttp::getParam("file_path", '');
        $data = MiniHttp::getParam("data", array());
        var_dump($data);exit;
        if(count($data)<1){
            return false;
        }
        $privilege = new PrivilegeBiz();
        $privilege->save($filePath, $data);
        return array('success'=>true);
    }
    /**
     * 根据文件路径查找对应用户权限
     */
    public function getPrivilege(){
        $filePath = MiniHttp::getParam("file_path",'');
        $item = new PrivilegeBiz();
        $privilege = $item->get($filePath);
        return $privilege;
    }
    /**
     * 取消共享，删除权限
     */
    public function delete(){
        $filePath = MiniHttp::getParam("file_path",'');
        $privilege = new PrivilegeBiz();
        $privilege ->delete($filePath);
        return array('success'=>true);
    }
}