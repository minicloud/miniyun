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
    public function getParentId($parentArr,$id=-1,$index=0){
      for($i=$index;$i<count($parentArr);$i++){
          $groupRelations = MiniGroupRelation::getInstance()->getByParentId($id);
          if(!isset($groupRelations)){
              return NULL;
          }
          $parentDepartmentExist = false;
          foreach($groupRelations as $groupRelation){
              $group = MiniGroup::getInstance()->findById($groupRelation['group_id']);
              if($group['group_name']==$parentArr[$i]){
                  $parentDepartmentExist = true;
                  $groupId = $groupRelation['group_id'];
                  break;
              }
          }
          if($parentDepartmentExist){
              if($i==count($parentArr)-1){
                  return $groupId;
              }else{
                  return $this->getParentId($parentArr,$groupId,$index+1);
              }
          }else{
              return NULL;
          }
      }
    }
    private function isDepartmentNameExist($departmentId,$departmentName){
        $groupRelations = MiniGroupRelation::getInstance()->getByParentId($departmentId);
        $departmentExist = false;
        foreach($groupRelations as $groupRelation){
            $department = MiniGroup::getInstance()->findById($groupRelation['group_id']);
            if($department['name']==$departmentName){
                $departmentExist = true;
                break;
            }
        }
        return $departmentExist;
    }
    /**
     * 导入部门
     */
    public function importDepartment(){
        $departmentData = MiniHttp::getParam('department_data',"");
        $errorList = array();
        $successList = array();
        $userList = array();
        $count = 0;
        $isTrue = false;//用来判断父名称是否有'|';
        $isFalse = false;//用来标识不满足条件的变量
        foreach($departmentData as $item){
            if(count($item)<2){
                $item[]="为空的数据请以“”填充";
                $errorList[] = $item;
                continue;
            }
            $departmentName=trim($item[1]);
            if(strpos($item[0],'|')){
                $arr = explode('|',trim($item[0]));
                foreach($arr as $val){
                    if(strlen($val)==0){
                        $isFalse = true;
                        break;
                    }
                }
                if($isFalse){
                    $item[] = "不能用||，或不能以结尾.";
                    $errorList[] = $item;
                    continue;
                }
                $isTrue = true;
                $parentDepartmentId = $this->getParentId($arr);
                if(empty($parentDepartmentId)){
                    $item[] = "该条数据的子部门查询不到父部门";
                    $errorList[] =$item;
                    continue;
                }
            }
            if($this->isDepartmentNameExist($parentDepartmentId,$departmentName)){
                $addUsers = array();
                for($j=2;$j<count($item);$j++){
                    if(strlen(trim($item[$j]))==0){
                        continue;
                    }
                    $addUsers[] = $item[$j];
                }
                if(count($addUsers)==0){
                    $count++;
                    $item[] = "数据库中已经有相同的数据出现";
                    $errorList[] = $item;
                    continue;
                }else{
                    $departmentId = $this->getParentId(array($departmentName),$parentDepartmentId);
                    $this->saveUser($item,$departmentId);
                    continue;
                }
            }
            if(!$isTrue){
                if(trim($item[0]) == '“”'){
                    $parentDepartmentId = -1;
                }else{
                    $parentDepartmentId = $this->getParentId(array(trim($item[0])));
                    if(empty($parentDepartmentId)){
                        $item[] = "该条数据的子部门查询不到父部门";
                        $errorList[] =$item;
                        continue;
                    }
                }
            }
            MiniGroup::getInstance()->create($departmentName,-1,$parentDepartmentId);
            $departmentId = $this->getParentId(array($departmentName),$parentDepartmentId);
            $this->saveUser($item,$departmentId);
            $successList[] = $item;
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
    public function saveUser($item,$departmentId){
        for($i=2;$i<count($item);$i++){
            if(strlen(trim($item[$i]))==0){
                continue;
            }
            $user = MiniUser::getInstance()->getUserByName($item[$i]);
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
            $group = MiniGroup::getInstance()->findById($departmentId);
            if($isExist){
                MiniUserGroupRelation::getInstance()->update($user['id'],$group['id']);
            }else{
                MiniUserGroupRelation::getInstance()->create($user['id'],$group['id']);
            }

        }
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