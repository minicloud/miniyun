<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-9-10
 * Time: 上午9:26
 */
class DepartmentService extends MiniService{
    /**
     * 添加部门
     */
    public function create(){
        $departmentName = MiniHttp::getParam('department_name','');
        $parentDepartmentId = MiniHttp::getParam('parent_department_id','-1');
        $biz = new DepartmentBiz();
        $result = $biz->create($departmentName,$parentDepartmentId);
        if(is_null($result)){
            return array('success'=>false,'msg'=>'name existed');
        }else{
            return array('success'=>true,'msg'=>'success');
        }
    }
    /**
     * 删除部门
     */
    public function delete(){
        $departmentId = MiniHttp::getParam('department_id','');
        $biz = new DepartmentBiz();
        $result = $biz->delete($departmentId);
        return $result;
    }
    /**
     * 更改部门名称
     */
    public function rename(){
        $departmentId = MiniHttp::getParam('department_id','');
        $newDepartmentName = MiniHttp::getParam('department_name','');
        $biz = new DepartmentBiz();
        $result = $biz->rename($departmentId, $newDepartmentName);
        return $result;
    }
    /**
     * 部门列表
     */
    public function getList(){
        $biz = new DepartmentBiz();
        $result = $biz->getList();
        return $result;
    }
    /**
     * 移动部门
     */
    public function move(){
        $sourceId = MiniHttp::getParam('source_id','');
        $parentDepartmentId = MiniHttp::getParam('parent_department_id','');
        $sourceType = MiniHttp::getParam('source_type','');
        $biz = new DepartmentBiz();
        $result = $biz->move($parentDepartmentId,$sourceId,$sourceType);
        return $result;
    }
    /**
     * 未绑定用户列表
     */
    public function unBindUserList(){
        $key = MiniHttp::getParam('key','');
        $currentPage = MiniHttp::getParam('current_page','1');
        $pageSize = MiniHttp::getParam('page_size','10');
        $biz = new DepartmentBiz();
        $result = $biz->unBindUserList($currentPage,$pageSize,$key);
        return $result;
    }
    public function import(){
        $departmentData = MiniHttp::getParam('department_data',"");
        $biz = new DepartmentBiz();
        $result = $biz->import($departmentData);
        return $result;
    }
    /**
     * 将用户绑定到部门
     */
    public function bind(){
        $userId = MiniHttp::getParam('user_id','');
        $groupId = MiniHttp::getParam('group_id','');
        $biz = new DepartmentBiz();
        $result = $biz->bind($userId,$groupId);
        return $result;
    }
    /**
     * 从部门中的用户解除绑定
     */
    public function unbind(){
        $userId = MiniHttp::getParam('user_id','');
        $groupId = MiniHttp::getParam('group_id','');
        $biz = new DepartmentBiz();
        $result = $biz->unbind($userId,$groupId);
        return $result;
    }
    /**
     *修改用户绑定的用户组
     */
    public  function modifyBind(){
        $userId = MiniHttp::getParam('user_id','');
        $groupId = MiniHttp::getParam('new_group_id','');
        $biz = new DepartmentBiz();
        $result = $biz->modifyBind($userId,$groupId);
        return $result;
    }
    /**
     * 搜索部门
     */
    public function search(){

    }
}