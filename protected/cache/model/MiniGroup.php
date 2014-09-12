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
    private static $CACHE_KEY = "cache.model.group";

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
     * @param array $items
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            $value                 = array();
            $value["id"]           = $item->id;
            $value["user_id"]      = $item->user_id;
            $value["group_name"]    = $item->name;
            array_push($data, $value);
        }
        return $data;
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
    public function create($groupName,$userId){
        $groupName = trim($groupName);
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id=:user_id and name =:group_name";
        $criteria->params = array('user_id'=> $userId,'group_name'=>$groupName);
        $item = Group::model()->find($criteria);
        if (empty($item)){
            $group = new Group();
            $group['name']=$groupName;
            $group['user_id']=$userId;
            $group['description']='';
            $group->save();
            return array('success'=>true,'msg'=>'success');
        }else{
            return array('success'=>false,'msg'=>'name existed');
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
}