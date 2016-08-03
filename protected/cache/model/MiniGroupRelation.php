<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-9-15
 * Time: 下午3:23
 */
class MiniGroupRelation extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.groupRelation";

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
        $value["id"]           = $item["id"];
        $value["group_id"]      = $item["group_id"];
        $value["parent_group_id"]    = $item["parent_group_id"];
        return $value;
    }
    /**
     * 根据用户组group_id获取parent_group_id
     */
    public function getByGroupId($groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=> $groupId);
        $item = GroupRelation::model()->find($criteria);
        if(empty($item)){
            return NULL;
        }
        return $this->db2Item($item);
    }
    public function getById($id){
        $criteria = new CDbCriteria();
        $criteria->condition = "id=:id";
        $criteria->params = array('id'=> $id);
        $item = GroupRelation::model()->find($criteria);
        if(empty($item)){
            return NULL;
        }
        return $this->db2Item($item);
    }
    /**
     * 根据用户组parent_group_id获取group_id
     */
    public function getByParentId($parentGroupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "parent_group_id=:parent_group_id";
        $criteria->params = array('parent_group_id'=> $parentGroupId);
        $items = GroupRelation::model()->findAll($criteria);
        return $this->db2list($items);
    }
    /**
     * 新建群组
     */
    public function create($groupId,$parentGroupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id and parent_group_id =:parent_group_id";
        $criteria->params = array('group_id'=> $groupId,'parent_group_id'=>$parentGroupId);
        $item = GroupRelation::model()->find($criteria);
        if (empty($item)){
            $group = new GroupRelation();
            $group['group_id']=$groupId;
            $group['parent_group_id']=$parentGroupId;
            $group->save();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'name existed');
        }
    }
    /**
     * 删除群组
     */
    public function delete($groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=> $groupId);
        $item = GroupRelation::model()->find($criteria);
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
    public function update($parentGroupId,$groupId){
        $criteria = new CDbCriteria();
        $criteria->condition = "group_id=:group_id";
        $criteria->params = array('group_id'=> $groupId);
        $oldGroup = GroupRelation::model()->find($criteria);;
        $oldGroup['parent_group_id']=$parentGroupId;
        $oldGroup->save();
        return array('success'=>true,'msg'=>'success');

    }
}