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
        return $result;
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
        $departmentId = MiniHttp::getParam('department_id','');
        $parentDepartmentId = MiniHttp::getParam('parent_department_id','');
        $biz = new DepartmentBiz();
        $result = $biz->move($parentDepartmentId,$departmentId);
        return $result;
    }
    /**
     * 未绑定用户列表
     */
    public function unBindUserList(){
        $biz = new DepartmentBiz();
        $result = $biz->unBindUserList();
        return $result;
    }
    /**
     * 导入部门
     */
    public function importDepartment(){
        $departmentData = MiniHttp::getParam('department_data',"");
        $errorList = array();
        $successList = array();
        foreach($departmentData as $department){//简单验证数据是否符合标准
            if(count($department)<2){
                $department[]="为空的数据请以“”填充";
                $errorList[] = $department;
//                echo $department[0].'1';
            }elseif($department[0]==$department[1]){
                $department[]="部门与分部门不能相同";
                $errorList[] = $department;
            }else{
                    $successList[] = $department;
            }
        }
        $userList = array();
        $userList['success'] = $successList;
        $userList['total'] = count($departmentData);
        $count = 0;
        foreach($successList as $key=> $item){
            $groupName=trim($item[1]);
            $result = MiniGroup::getInstance()->getByGroupName($groupName);
            if($result){
                $parentGroup= MiniGroupRelation::getInstance()->getByGroupId($result['id']);
                $firstGroupName = MiniGroup::getInstance()->getById($parentGroup['parent_group_id']);
                $parentGroupName = $item[0];
                if(substr($item[0],'|')){
                    $arr = explode('|',trim($item[0]));
                    foreach($arr as $val){
                        if(strlen($val)==0){
                            $item[] = "不能用||，或不能以结尾.";
                            $errorList[] = $item;
                            continue;
                        }
                    }
                    $parentGroupName = $arr[count($arr)-1];
                }
                if($parentGroupName==$firstGroupName){
                    $count++;
                    $item[] = "数据库中已经有相同的数据出现";
                    $errorList[] = $item;
                    continue;
                }
            }
            $groupInfo = MiniGroup::getInstance()->getByGroupName($item[0]);
            if(trim($item[0]) == '“”'){
                $parentGroupId = -1;
            }else{
                $parentGroupId = $groupInfo['id'];
            }

            MiniGroup::getInstance()->create($groupName,-1,$parentGroupId);
        }
        $userList['error'] = $errorList;
        $tempUrl ="upload/temp/error.csv";
        $fp = fopen($tempUrl, 'w+');
        if($fp){
            foreach($errorList as $item){
                fputcsv($fp,$item);
            }
        }
        fclose($fp);
        $userList['duplicateCount']=$count;
        return $userList;
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