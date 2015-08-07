<?php
/**
 * 缓存miniyun_group表的记录，V1.2.0该类接管所有miniyun_group的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class MiniGroup extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.groups";

    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    static private $_instance = null;

    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        parent::MiniCache();
    }
    /**
     * 静态方法, 单例统一访问入口
     * @return object  返回对象的唯一实例
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * 把数据库值序列化
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }

    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                     = array();
        $value["id"]           = $item->id;
//        if($item->user_id!=-1){
            $value["user_id"]      = $item->user_id;
//        }
        $value["group_name"]    = $item->name;
        $value["description"]   = $item->description;
        return $value;
    }
    /**
     * 得到用户群组列表
     */
    public function getList($userId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id";
        $criteria->params = array('user_id'=> $userId);
        $criteria->order = "id ";
        $items = Group::model()->findAll($criteria);
        if(!empty($items)){
            return array('success'=>true,'msg'=>'success','list'=> $this->db2list($items));
        }else{
            return array('success'=>false,'msg'=>'fail','list'=> array());
        }

    }
    /**
     * 新建群组
     */
    public function create($groupName,$userId,$parentGroupId){
        $groupName = trim($groupName);
        $criteria = new CDbCriteria();
        if(!isset($parentGroupId)){
            $parentGroupId = -1;
        }
        $criteria->condition = "user_id=:user_id and name =:group_name and parent_group_id =:parent_group_id";
        $criteria->params = array('user_id'=> $userId,'group_name'=>$groupName,'parent_group_id'=>$parentGroupId);
        $item = Group::model()->find($criteria);
        if (empty($item)){
            $group = new Group();
            $group['name']=$groupName;
            $group['user_id']=$userId;
            if($userId==-1){
                $group['parent_group_id'] = $parentGroupId;
            }else{
                $group['parent_group_id'] = -1;
            }
            $group['description']='';
            $group->save(); 
            return $group->id;
        }else{
            return NULL;
        }
    }
     /**
     * 新建群组
     */
    public function create4Ldap($groupName,$userId,$parentGroupId,$departmentOu=""){
        $groupName = trim($groupName);
        $criteria = new CDbCriteria();
        if(!isset($parentGroupId)){
            $parentGroupId = -1;
        }
        $criteria->condition = "user_id=:user_id and parent_group_id =:parent_group_id and description =:description";
        $criteria->params = array('user_id'=> $userId,'parent_group_id'=>$parentGroupId,'description'=>$departmentOu);
        $item = Group::model()->find($criteria);
        if (empty($item)){
            $group = new Group();
            $group['name']=$groupName;
            $group['user_id']=$userId;
            if($userId==-1){
                $group['parent_group_id'] = $parentGroupId;
            }else{
                $group['parent_group_id'] = -1;
            }
            $group['description']=$departmentOu;
            $group->save();            
            return $group->id;
        }else{
            $item->name = $groupName;
            $item->save();
            return $item->id;
        }
    }
    /**
     * 删除群组
     */
    public function delete($groupName,$userId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and name =:group_name";
        $criteria->params = array('user_id'=> $userId,'group_name'=>$groupName);
        $item = Group::model()->find($criteria);
        if(!empty($item)){
            $item->delete();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'not existed');
        }
    }
    /**
     * 根据groupId删除群组
     */
    public function deleteByGroupId($groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "id=:id";
        $criteria->params = array('id'=> $groupId);
        $item = Group::model()->find($criteria);
        if(!empty($item)){
            $item->delete();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'not existed');
        }
    }
    /**
     * by department_id 删除群组
     */
    public function deleteByDepartmentId($departmentId,$userId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and id =:id";
        $criteria->params = array('user_id'=> $userId,'id'=>$departmentId);
        $item = Group::model()->find($criteria);
        if(!empty($item)){
            $item->delete();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'not existed');
        }
    }
    /**
     * 群组更名
     */
    public function rename($oldGroupName,$newGroupName,$userId){
        $newGroupName = trim($newGroupName);
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and name =:group_name";
        $criteria->params = array('user_id'=> $userId,'group_name'=>$oldGroupName);
        $oldGroup = Group::model()->find($criteria);//查到老群组对象，用以修改名称。
        $criteria->condition = "user_id=:user_id and name =:group_name";
        $criteria->params = array('user_id'=> $userId,'group_name'=>$newGroupName);
        $newGroup = Group::model()->find($criteria);//判断新群组名是否重复。
        if(empty($newGroup)){
            $oldGroup['name']=$newGroupName;
            $oldGroup->save();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'name existed');
        }
    }
    /**
     * 根据Id获取group
     */
    public function getById($id){
        $criteria = new CDbCriteria();
        $criteria->condition = "id=:id";
        $criteria->params = array('id'=> $id);
        $group = Group::model()->find($criteria);
        return $this->db2Item($group);
    }

    /**
     * 获取目录树
     * @param $parentGroupId
     * @param bool $showUser
     * @return array
     */
    public function getTreeNodes($parentGroupId,$showUser=true){
        $relations = MiniGroupRelation::getInstance()->getByParentId($parentGroupId);
        $userRelations = MiniUserGroupRelation::getInstance()->getByGroupId($parentGroupId);
        if(isset($relations)){
            foreach($relations as $relation){
                $group = $this->getById($relation['group_id']);
                $newGroup[] = $group['id'];
                $newGroup[] = $group['group_name'];
                $groups[] =  $group;
            }
        }
        if(0 < count($groups))
        {
            for($i = 0; $i < count($groups); $i++)
            {
                $groups[$i]['nodes'] = $this->getTreeNodes($groups[$i]['id'],$showUser);
                if($groups[$i]['nodes']==NULL){
                    $groups[$i]['nodes']=array();
                }
            }

        }
        if($showUser){
            if($userRelations){
                foreach($userRelations as $userRelation){
                    $user = array();
                    $userInfo = MiniUser::getInstance()->getById($userRelation['user_id']);
                    $user['id'] = $userInfo['id'];
                    $user['user_name']= $userInfo['nick'];
                    $user['group_id']=$parentGroupId;
                    $groups[] = $user;
                }
            }
        }

        return $groups;
    }
    /**
     * 根据group_name获取数据
     */
    public function getByGroupName($groupName){
        $criteria = new CDbCriteria();
        $criteria->condition = "name=:group_name";
        $criteria->params = array('group_name'=> $groupName);
        $group = Group::model()->find($criteria);
        return $this->db2Item($group);
    }
    /**
     * 根据id查找group
     */
    public function findById($groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "id=:group_id";
        $criteria->params = array('group_id'=> $groupId,);
        $group = Group::model()->find($criteria);
        if(!empty($group)){
            return $this->db2Item($group);
        }else{
            return false;
        }
    }
}