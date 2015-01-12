<?php
/**
 * Created by PhpStorm.
 * User: hengwei
 * Date: 14-9-10
 * Time: 上午9:26
 */
class DepartmentBiz extends MiniBiz{
    private  $userId = -1;//当为-1时，所有的用户均有群组
    private  $duplicateCount = 0;
    private  $errorList = array();
    /**
     * 添加部门
     */
    public function create($departmentName,$parentDepartmentId){
        $userId = $this->userId;
        $result = MiniGroup::getInstance()->create($departmentName,$userId,$parentDepartmentId);
        return $result;
    }
    /**
     * 删除部门
     */
    public function delete($departmentId){
        $userId = $this->userId;
        $result = MiniGroup::getInstance()->deleteByDepartmentId($departmentId,$userId);
        if($result['success']==true){
            $result = MiniGroupRelation::getInstance()->getByGroupId($departmentId);
            if(!empty($result)){
                MiniGroupPrivilege::getInstance()->deleteRelatedPrivilegeById($departmentId);
                $result = MiniGroupRelation::getInstance()->delete($departmentId);
            }
        }
        return $result;
    }
    /**
     * 更改部门名称
     */
    public function rename($departmentId,$newDepartmentName){
        $userId = $this->userId;
        $result = MiniGroup::getInstance()->getById($departmentId);
        $oldDepartmentName = $result['group_name'];
        $result = MiniGroup::getInstance()->rename($oldDepartmentName,$newDepartmentName,$userId);
        return $result;
    }
    /**
     * 部门列表
     */
    public function getList(){
        $data = MiniGroup::getInstance()->getTreeNodes(-1);
        return $data;
    }
    /**
     * 移动部门
     */
    public function move($parentDepartmentId,$sourceId,$sourceType){
        if($sourceType == "group"){
            $data = MiniGroupRelation::getInstance()->update($parentDepartmentId,$sourceId);
        }
        if($sourceType == "user"){
            $data = MiniUserGroupRelation::getInstance()->update($sourceId,$parentDepartmentId);
        }
        return $data;
    }
    public function import($data){
        $successList = array();
        $userList = array();
        foreach($data as $item){
            $departmentNames = array();
            $item[0]=str_replace('||','|',trim($item[0]));
            if(strpos($item[0],'|')){
                $departmentNames = explode('|',trim($item[0]));
                foreach($departmentNames as $key=>$departmentName){
                    if(empty($arr[$key])){
                        unset($arr[$key]);
                    }
                }
            }else{
                $departmentNames[] = $item[0];
            }
            $departmentId = $this->getId($item,$departmentNames);
            $this->saveUser($item,$departmentId);
            $successList[] = $item;
        }
        $userList['success'] = $successList;
        $userList['total'] = count($data);
        $userList['error'] = $this->errorList;
        $tempUrl ="upload/temp/error.csv";
        $fp = fopen($tempUrl, 'w+');
        if($fp){
            foreach($this->errorList as $item){
                $errorInfo = array();
                $errorInfo[] = $item[1];
                foreach($item[2] as $name){
                    $errorInfo[] = $name;
                }
                $errorInfo[] = $item[0];
                fputcsv($fp,$errorInfo);
            }

        }
        fclose($fp);
        $userList['duplicateCount']=$this->duplicateCount;
        return $userList;
    }
    /**
     * 未绑定用户列表
     */
    public function unBindUserList($currentPage,$pageSize,$key){
        $items = MiniUser::getInstance()->searchUnbindUsers(($currentPage-1)*$pageSize,$pageSize,$key);
        $total = $items['total'];
        $users = $items['users'];
        $data = array();
        foreach($users as $user){
            $item['id'] = $user['id'];
            $item['user_name'] = $user['nick'];
            $data[] = $item;
        }
        return array('success'=>true,'msg'=>'success','unbindUsers'=>$data,'total'=>$total);
    }

    /**
     * 将用户绑定到部门
     */
    public function bind($userId,$groupId){
        $userGroup = MiniUserGroupRelation::getInstance()->create($userId,$groupId);
        return $userGroup;
    }
    /**
     * 从部门中的用户解除绑定
     */
    public function unbind($userId,$groupId){
        $userGroup = MiniUserGroupRelation::getInstance()->delete($userId,$groupId);
        return $userGroup;
    }
    /**
     *修改用户绑定的用户组
     */
    public  function modifyBind($userId,$groupId){
        $result = MiniUserGroupRelation::getInstance()->update($userId,$groupId);
        return $result;
    }
    public function getId($item,$data,$parentId=-1,$index=0,$createCount=0){
        for($i=$index;$i<count($data);$i++){
            $groupRelations = MiniGroupRelation::getInstance()->getByParentId($parentId);
            $isExist = false;
            if(!empty($groupRelations)){
                foreach($groupRelations as $groupRelation){
                    $group = MiniGroup::getInstance()->findById($groupRelation['group_id']);
                    if($group['group_name']==$data[$i]){
                        $isExist = true;
                        $groupId = $groupRelation['group_id'];
                        break;
                    }
                }
            }
            if(!$isExist){
                $this->create($data[$i],$parentId);
                $id = Yii::app()->db->getLastInsertID();
                $groupRelation = MiniGroupRelation::getInstance()->getById($id);
                $groupId = $groupRelation['group_id'];
                $createCount++;
            }
            if($i==count($data)-1){
                if($createCount==0){
                    if(count($item)==1&&strlen(trim($item[0]))){
                        $error = array();
                        $this->duplicateCount = ($this->duplicateCount)+1;
                        $error[] = "数据库中已经有相同的数据出现";
                        $error[] = $item[0];
                        $this->errorList[] = $error;
                    }
                }
                return $groupId;
            }else{
                return $this->getId($item,$data,$groupId,$index+1,$createCount);
            }
        }
    }
    public function saveUser($item,$departmentId){
        $errorCount = 0;
        $errorName = array();
        for($i=1;$i<count($item);$i++){
            if(strlen(trim($item[$i]))==0){
                continue;
            }
            $user = MiniUser::getInstance()->getUserByName($item[$i]);
            if(empty($user)){
                $errorCount++;
                $errorName[] = $item[$i];
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
        if($errorCount>0){
            $data[] = "该用户不存在，您可以先导入用户，再进行添加";
            $data[] = $item[0];
            $data[] = $errorName;
            $this->errorList[] = $data;
        }
    }
}