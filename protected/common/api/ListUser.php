<?php
/** 
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ListUser extends CApiComponent {

    public $search_key = '';
    public $containSelf = true;   //是否包含自己true：包含， false: 不包含
    public $selfId = -1;          //当前用户的id
    public $limit = 20;
    public $offset = 0;
    public $total  = 0;
    public $callback = NULL;    // 组装用户返回值，回调函数
    public $file_id;              //文件夹id
    public $file;                 //操作的目标文件的对象
    public $master;               //文件拥有者的id
    const ACTION_SEARCH       = 1;
    const ACTION_LIST_FRIENDS = 2;

    /**
     * 
     * 调用入口
     * @param unknown_type $action
     * 
     * @since 1.1.0
     */
    public function invoke($action) {
        if (!empty($this->file_id)){
            $this->file = UserFile::model()->findByAttributes(array('id'=>$this->file_id, 'is_deleted'=>0));
            if (!empty($this->file)){
                $this->master = CUtils::getUserFromPath($this->file["file_path"]);
            }
        }
        switch ($action) {
            case self::ACTION_SEARCH:
                $retval = $this->handlerSeach();
                break;
            case self::ACTION_LIST_FRIENDS:
                $retval = $this->handlerList();
            default:
                break;
        }
        return $retval;
    }

    /**
     *
     * 执行搜索操作
     */
    private function handlerList() {
        $conditions = User::model()->tableName().' as u left outer join ';
        $conditions .= UserMeta::model()->tableName();
        $conditions .= ' as m on m.user_id = u.id and m.meta_key="nick" ';
        $conditions .= ' WHERE 1';
        $count_sql = 'SELECT count(*) as count FROM '. $conditions;
        $this->total = User::model()->countBySql($count_sql);
        // 创建sql
        $query = 'SELECT u.*,m.meta_value as nick FROM '. $conditions;
        $query .= ' limit :offset, :limit';
        $command = Yii::app()->db->createCommand($query);
        $command->bindParam(':limit', $this->limit);
        $command->bindParam(':offset', $this->offset);
        $list = $command->queryAll();
        
        $users = array();
        //
        // 如果callback为空，则返回默认返回值
        //
        if ($this->callback) {
            $users = call_user_func($this->callback, $list, $this->total);
        } else {
            foreach ($list as $user) {
                $user_name = trim($user['nick']);
                $user_name = empty($user_name) ? $user['user_name'] : $user_name;
                $users[$user['id']] = array("id"=>$user['id'],"user_name"=>$user_name);
            }
            $users = CJSON::encode($this->_getHashByList($users));
        }
        return $users;
    }
    /**
     * 根据UserList返回前台需要的数据
     */
    private function _getHashByList($list){
        $retval = array();
        $data   = array();
        foreach ($list as $user) {
            if (!$this->containSelf && ($user['id'] == $this->selfId)){
                continue; 
            }else{
                $detail = array();
                $detail['user_id']   = $user['id'];
                $detail['user_name'] = $user['user_name'];
                $detail['avatar']    = '';
                $detail['remark']    = '';
                array_push($data, $detail);
            }
        }
        $retval['total']  = !$this->total ? 1 : $this->total; //这里仅为兼容Web的JS而设定
        $retval['data']   = $data;
        return $retval;
    }
    /**
     *
     * 执行搜索操作
     */
    private function handlerSeach() {
        $params = array();
        $params[':user_name'] = "%" . $this->search_key . "%";
        $params[':meta_value'] = "%" . $this->search_key . "%";
        //
        // 联合查询
        //
        $conditions = User::model()->tableName().' as u left outer join ';
        $conditions .= UserMeta::model()->tableName();
        $conditions .= ' as m on m.user_id = u.id and m.meta_key="nick" ';
        $conditions .= ' WHERE user_name like :user_name or meta_value like :meta_value';
        $count_sql = 'SELECT count(*) as count FROM '. $conditions;
        $this->total = User::model()->countBySql($count_sql,$params);
        
        // 创建sql
        $query = 'SELECT u.*,m.meta_value as nick FROM '. $conditions;
        $query .= ' limit :offset, :limit';
        $command = Yii::app()->db->createCommand($query);
        $command->bindParam(':user_name', $params[':user_name']);
        $command->bindParam(':meta_value', $params[':meta_value']);
        $command->bindParam(':limit', $this->limit);
        $command->bindParam(':offset', $this->offset);
        
        $userList = $command->queryAll();
        
        $users = array();
        if ($this->callback) {
            $users = call_user_func($this->callback, $userList, $this->total);
        } else {
            foreach ($userList as $user) {
                $user_name = trim($user['nick']); 
                $user_name = empty($user_name) ? $user['user_name'] : $user_name;
                $users[$user['id']] = array("id"=>$user['id'],"user_name"=>$user_name);
            }
            $users = CJSON::encode($this->_getHashByList($users));
        }
        return $users;
    }
}