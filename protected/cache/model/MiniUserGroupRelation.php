<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-9-15
 * Time: 下午3:23
 */
class MiniUserGroupRelation extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.userGroupRelations";

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
//        $value["id"]           = $item->id;
        $value["group_id"]      = $item->group_id;
        $value["user_id"]    = $item->user_id;
        return $value;
    }
    /**
     * 根据用户组group_id获取用户与群组的关系
     */
    public function getByGroupId($groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=> $groupId);
        $item = UserGroupRelation::model()->findAll($criteria);
        return $this->db2list($item);
    }
    /**
     * 根据用户user_id获取用户与群组的关系
     */
    public function getByUserId($groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id";
        $criteria->params = array('user_id'=> $groupId);
        $item = UserGroupRelation::model()->find($criteria);
        return $this->db2Item($item);
    }
    /**
     * 新建用户与群组的关系
     */
    public function create($userId,$groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id and user_id =:user_id";
        $criteria->params = array('group_id'=> $groupId,'user_id'=>$userId);
        $item = UserGroupRelation::model()->find($criteria);
        if (empty($item)){
            $group = new UserGroupRelation();
            $group['group_id']=$groupId;
            $group['user_id']=$userId;
            $group->save();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'name existed');
        }
    }
    /**
     * 删除用户与群组的关系
     */
    public function delete($userId,$groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and group_id=:group_id";
        $criteria->params = array('user_id'=> $userId,'group_id'=>$groupId);
        $item = UserGroupRelation::model()->find($criteria);
        if(!empty($item)){
            $item->delete();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'not existed');
        }
    }
    /**
     * 删除该群组所有的用户关联信息
     */
    public function deleteRelatedRelations($groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=>$groupId);
        UserGroupRelation::model()->deleteAll($criteria);
    }
    /**
     * 更改用户与群组的关系
     */
    public function update($userId,$groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id";
        $criteria->params = array('user_id'=> $userId);
        $oldGroup = UserGroupRelation::model()->find($criteria);;
        $oldGroup['group_id']=$groupId;
        $oldGroup->save();
        return array('success'=>true,'msg'=>'success');

    }
    /**
     * 获得群组下好友列表
     */
    public function getList($groupId){
        $groupId = (int)$groupId;
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=> $groupId);
        $criteria->order = "id ";
        $items = UserGroupRelation::model()->findAll($criteria);
        if(!empty($items)){
            return array('success'=>true,'msg'=>'success','list'=> $this->db2list($items));
        }else{
            return array('success'=>false,'msg'=>'fail','list'=> array());
        }
    }
    /**
     * 获得群组下好友列表（带分页）
     */
    public function getPageList($groupId,$currentPage,$pageSize){
        $groupId = (int)$groupId;
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=> $groupId);
        $criteria->offset = ($currentPage-1)*$pageSize;;
        $criteria->limit  = $pageSize;
        $criteria->order = "id ";
        $items = UserGroupRelation::model()->findAll($criteria);
        if(!empty($items)){
            return array('success'=>true,'msg'=>'success','list'=> $this->db2list($items));
        }else{
            return array('success'=>false,'msg'=>'fail','list'=> array());
        }
    }
    /**
     * 获得群组下好友人数
     */
    public function count($groupId){
        $groupId = (int)$groupId;
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=> $groupId);
        $criteria->order = "id ";
        $count = UserGroupRelation::model()->count($criteria);
        return $count;
    }
    /**
     * 用户与群组绑定
     */
    public function bind($userId,$groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and group_id =:group_id";
        $criteria->params = array('user_id'=> $userId,'group_id'=>$groupId);
        $item = UserGroupRelation::model()->find($criteria);
        if (empty($item)){
            $group = new UserGroupRelation();
            $group['user_id']=$userId;
            $group['group_id']=$groupId;
            $group->save();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'name existed');
        }
    }
    /**
     * 用户与群组解除绑定
     */
    public function unbind($userId,$groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and group_id =:group_id";
        $criteria->params = array('user_id'=> $userId,'group_id'=>$groupId);
        $item = UserGroupRelation::model()->find($criteria);
        if (!empty($item)){
            $item->delete();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'no user to delete');
        }
    }
    /**
     * 通过user_id查出用户所在的好友组(需要排除部门)
     */
    public function findUserGroup($userId){
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id";
        $criteria->params = array('user_id'=> $userId);
        $items = UserGroupRelation::model()->findAll($criteria);
        $items=$this->db2list($items);
        $list = array();
        foreach($items as $item){
            $group = array();
            $groupId = $item['group_id'];
            $result = MiniGroup::getInstance()->findById($groupId);
            $group['id'] = $result['id'];
            $group['user_id'] = $result['user_id'];
            $group['group_name'] = $result['name'];
            array_push($list,$group);
        }
        return $list;
    }
}