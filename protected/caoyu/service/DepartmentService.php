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
    /**
     * 导入部门
     */
    public function importDepartment(){
        $departmentData = MiniHttp::getParam('department_data',"");
        $errorList = array();
        $successList = array();
//        foreach($departmentData as $department){//简单验证数据是否符合标准
//            if(count($department)<2){
//                $department[]="为空的数据请以“”填充";
//                $errorList[] = $department;
//            }elseif($department[0]==$department[1]){
//                $department[]="部门与分部门不能相同";
//                $errorList[] = $department;
//            }else{
//                    $successList[] = $department;
//            }
//        }
        $userList = array();
        $count = 0;
        $isTrue = false;//用来判断父名称是否有'|';
        $isFalse = false;//用来标识不满足条件的变量
        foreach($departmentData as $department){
            if(count($department)<2){
                $department[]="为空的数据请以“”填充";
                $errorList[] = $department;
                continue;
            }elseif($department[0]==$department[1]){
                $department[]="部门与分部门不能相同";
                $errorList[] = $department;
                continue;
            }

            $groupName=trim($department[1]);
            $result = MiniGroup::getInstance()->getByGroupName($groupName);
            if(strpos($department[0],'|')){
                $arr = explode('|',trim($department[0]));
                foreach($arr as $val){
                    if(strlen($val)==0){
                        $isFalse = true;
                        break;
                    }
                }
                if($isFalse){
                    $department[] = "不能用||，或不能以结尾.";
                    $errorList[] = $department;
                    continue;
                }
                $isTrue = true;
                $parentGroupName = $arr[count($arr)-1];
            }
            if(!$isTrue){
                $parentGroupName = $department[0];
            }

            if(isset($result)){
                $parentGroup= MiniGroupRelation::getInstance()->getByGroupId($result['id']);
                $firstGroupName = MiniGroup::getInstance()->getById($parentGroup['parent_group_id']);
                if($parentGroupName==$firstGroupName){
                    $count++;
                    $department[] = "数据库中已经有相同的数据出现";
                    $errorList[] = $department;
                    continue;
                }
            }
            $groupInfo = MiniGroup::getInstance()->getByGroupName($parentGroupName);

            if(empty($groupInfo)){
                if(trim($department[0]) == '“”'){
                    $parentGroupId = -1;
                }else{
                    $department[] = "该条数据的子部门查询不到父部门";
                    $errorList[] = $department;
                    continue;
                }

            }else{
                $parentGroupId = $groupInfo['id'];
            }
            MiniGroup::getInstance()->create($groupName,-1,$parentGroupId);
            for($i=2;$i<count($department);$i++){
                if(strlen($department[$i])==0){
                    continue;
                }
                $user = MiniUser::getInstance()->getUserByName($department[$i]);
                if(empty($user)){
                    continue;
                }
                $userGroupRelations = MiniUserGroupRelation::getInstance()->getByUserId($user['id']);
                $isExist = false;//判断用户是否已经被导入，存在则修改
                if(!empty($userGroupRelations)){
                    foreach($userGroupRelations as $userGroupRelation){
                       $group = MiniGroup::getInstance()->getById($userGroupRelation['group_id']);
                       if(!empty($group)){
                           if($group['user_id']>0){
                               continue;
                           }else{
                               $isExist = true;
                               break;
                           }
                       }
                    }
                }
                $group = MiniGroup::getInstance()->getByGroupName($groupName);
                if($isExist){
                    MiniUserGroupRelation::getInstance()->update($user['id'],$group['id']);
                }else{
                    MiniUserGroupRelation::getInstance()->create($user['id'],$group['id']);
                }

            }
            $successList[] = $department;
        }
        $userList['success'] = $successList;
        $userList['total'] = count($departmentData);
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