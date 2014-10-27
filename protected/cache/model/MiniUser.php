<?php
/**
 * 缓存miniyun_users表的记录，V1.2.0该类接管所有miniyun_users的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniUser extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.User";
    /**
     * Options表存储userId的Key
     * @var string
     */
    public static $OPTION_KEY = "user_delete_record";
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
     * 按照id逐一放入内存
     * @param $id
     * @return string
     */
    private function getCacheKey($id){
        return MiniUser::$CACHE_KEY."_".$id;
    }

    /**
     * 通过db获得记录
     * @param $id
     * @return array|null
     */
    private function get4Db($id){
        $item =  User::model()->find("id=:id",array("id"=>$id));
        return $this->db2Item($item);
    }
    /**
     * 把db对象转换为array
     * @param object $item
     * @return array|null
     */
    private function db2Item($item){
        if(isset($item)){
            $user                   = array();
            $user["id"]             = $item->id;
            $user["user_id"]        = $item->id;
            $user["user_uuid"]      = $item->user_uuid;
            $user["user_name"]      = $item->user_name;
            $user["user_pass"]      = $item->user_pass;
            $user["user_status"]    = $item->user_status==0?false:true;
            $user["user_pass"]      = $item->user_pass;
            $user["user_status"]    = $item->user_status;
            $user["salt"]           = $item->salt;
            $user["created_at"]     = $item->created_at;
            $user["updated_at"]     = $item->updated_at;
            //查询用户Meta信息
            $user["avatar"]         = Yii::app()->params["defaultAvatar"];
            $user["nick"]           = $user["user_name"];
            $user["phone"]          = "";
            $user["email"]          = "";
            $user["space"]          = MUtils::defaultTotalSize();
            $user["is_admin"]       = false;
            $metas                  = MiniUserMeta::getInstance()->getUserMetas($user["id"]);
            foreach ($metas as $key=>$value){
                if($key==="nick"){
                    $user["nick"]    = $value;
                }
                if($key==="phone"){
                    $user["phone"]   = $value;
                }
                if($key==="email"){
                    $user["email"]   = $value;
                }
                if($key==="space"){
                    $user["space"]   = $value;
                }
                if($key==="is_admin"){
                    $user["is_admin"] = ($value==="1"?true:false);
                }
                if($key==="avatar"){
                    if(strpos($value,"http")==0){
                        $user["avatar"] = $value;
                    }else{
                        $user["avatar"] = MiniHttp::getMiniHost()."static/thumbnails/avatar/".$value;
                    }
                }
            }
            return  $user;
        }
        return NULL;
    }
    /**
     * 根据id获得User完整信息
     * @param $id
     * @return array|mixed|null
     */
    public function getUser($id){
        if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
            $user                  = $this->get4Db($id);
            $user["usedSpace"]     = $this->getUsedSize($id);//查询当前用户已经消耗的空间
            //如系统做了总空间控制，且空间已经使用完毕，则进行提示
            if(CUtils::hasOverSysSpace()===false){
                $user["space"] = $user["usedSpace"]-1;
            }
            return $user;
        }
        //先判断是否已经缓存，否则进行直接缓存
        $dataStr     = $this->get($this->getCacheKey($id));
        if($dataStr===false){
            $user    = $this->get4Db($id);
            if($user===NULL) return NULL;
            Yii::trace(MiniUser::$CACHE_KEY." set cache userId:".$id,"miniyun.cache1");
            $this->set($this->getCacheKey($id),serialize($user));
        }else{
            Yii::trace(MiniUser::$CACHE_KEY." get cache userId:".$id,"miniyun.cache1");
            $user      = unserialize($dataStr);
            //补偿，如果返回值为NULL，则重新向DB请求
            if($user===NULL){
                $user   = $this->get4Db($id);
                Yii::trace(MiniUser::$CACHE_KEY." set cache userId:".$id,"miniyun.cache1");
                $this->set($this->getCacheKey($id),serialize($user));
            }
        }
        $user["usedSpace"]= $this->getUsedSize($id);//查询当前用户已经消耗的空间，这里需动态计算
        //如系统做了总空间控制，且空间已经使用完毕，则进行提示
        if(CUtils::hasOverSysSpace()===false){
            $user["space"] = $user["usedSpace"]-1;
        }
        return $user;
    }
    /**
     * 获得指定用户密码输错的次数
     * @param $userName
     * @return bool|int
     */
    public function getPasswordErrorCount($userName){
        $user = $this->getUserByName($userName);
        if(empty($user)){
            return 0;
        }
        $criteria            = new CDbCriteria();
        $criteria->condition = 'user_id=:user_id and meta_key="password_error_count"';
        $criteria->params    = array('user_id'=> $user["id"]);
        $userMeta = UserMeta::model()->find($criteria);
        if(isset($userMeta)){
            return (int)$userMeta->meta_value;
        }
        return 0;
    }
    /**
     * 用户密码又输入错误了
     * @param $userName
     * @return bool|int
     */
    public function setPasswordError($userName){
        $user = $this->getUserByName($userName);
        if(empty($user)){
            return false;
        }
        $criteria            = new CDbCriteria();
        $criteria->condition = 'user_id=:user_id and meta_key="password_error_count"';
        $criteria->params    = array('user_id'=> $user["id"]);
        $userMeta = UserMeta::model()->find($criteria);
        $errorCount = 1;
        if(!isset($userMeta)){
            $userMeta = new UserMeta();
            $userMeta->user_id = $user["id"];
            $userMeta->meta_key = "password_error_count";
        }else{
            $errorCount = (int)$userMeta->meta_value+1;
        }
        $userMeta->meta_value = $errorCount;
        $userMeta->save();
        return true;
    }
    /**
     * 把用户密码错误次数清0
     * @param $userName
     * @return bool|int
     */
    public function cleanPasswordError($userName){
        $user = $this->getUserByName($userName);
        if(empty($user)){
            return false;
        }
        $criteria            = new CDbCriteria();
        $criteria->condition = 'user_id=:user_id and meta_key="password_error_count"';
        $criteria->params    = array('user_id'=> $user["id"]);
        $userMeta = UserMeta::model()->find($criteria);
        if(isset($userMeta)){
            $userMeta->meta_value = 0;
            $userMeta->save();
        }
        return true;
    }
    /**
     * 当前用户是否锁定
     * @param $userName
     * @return bool|int
     */
    public function isLock($userName){
        $user = $this->getUserByName($userName);
        if(empty($user)){
            return false;
        }
        $criteria            = new CDbCriteria();
        $criteria->condition = 'user_id=:user_id and meta_key="password_error_count"';
        $criteria->params    = array('user_id'=> $user["id"]);
        $userMeta = UserMeta::model()->find($criteria);
        if(isset($userMeta)){
            $errorCount = (int)$userMeta->meta_value;
            $date1 = new DateTime($userMeta->updated_at);
            //密码错误到第5次，则进行锁定30分钟，30分钟后进行可重新尝试输入
            if($errorCount>4){
                $date2 = new DateTime("now");
                $interval = $date2->diff($date1);
                $diffTime = $interval->format('%i');
                if($diffTime<=30){
                    return true;
                }else{
                    $userMeta->meta_value = 0;
                    $userMeta->save();
                    return false;
                }
            }
        }
        return false;
    }
    /**
     * 根据name获得User对象
     * @param $name
     * @return array|mixed|null
     */
    public function getUserByName($name){
        $user =  User::model()->find("user_name=:user_name",array("user_name"=>$name));
        if(isset($user)){
            return $this->getUser($user["id"]);
        }
        return NULL;
    }
    /**
     * 是否需要重新计算用户的空间值
     * @param int $userId
     * @return bool
     */
    private function isRebuidUserSpace($userId){
        $userEventKey       = "cach.model.user.event.max.user_".$userId;
        $cacheUserMaxId     = $this->get($userEventKey);
        if($cacheUserMaxId===false){//当2者之一都不存在记录，重置缓存
            $cacheUserMaxId = MiniEvent::getInstance()->getMaxIdByUser($userId);
            $this->set($userEventKey, $cacheUserMaxId);
            //TODO 标识用户数据发生变化，应该做对于历史缓存数据的清理，与是否是网页或客户端状态没有关系
            return true;
        }
        $userMaxId         = MiniEvent::getInstance()->getMaxIdByUser($userId);
        if($cacheUserMaxId!=$userMaxId){
            //TODO 标识用户数据发生变化，应该做对于历史缓存数据的清理，与是否是网页或客户端状态没有关系
            return true;
        }
        return false;
    }
    /**
     * 获取用户使用的空间大小
     * 在计算用户空间这里，
     * @param $userId 用户ID
     * @return bool|mixed
     */
    private function getUsedSize($userId) {
        $userSpaceKey  = "cach.model.user.usedSpace_".$userId;
        if($this->isRebuidUserSpace($userId)){
            $usedSpace = MiniFile::getInstance()->getUsedSize($userId);
            $this->set($userSpaceKey, $usedSpace);
            return $usedSpace;
        }
        return $this->get($userSpaceKey);
    }
    /**
     * 更新用户密码，同时清理该用户的缓存
     * 仅用于actionChangePasswd忘记密码链接-->重置新密码的场景
     * @param $userId
     * @param $newPassword
     * @return array|bool|mixed|null
     */
    public function updatePassword($userId,$newPassword){
        if($newPassword == ""){
            return false;
        }
        //更新用户的db信息
        $user              = User::model()->findByPk($userId);
        if(!$user){
            return false;
        }
        $user["user_pass"] = MiniUtil::signPassword($newPassword, $user["salt"]);
        $user->save();
        //删除用户自身的缓存
        $this->cleanCache($userId);
        //清空与当前用户相关的Token
        MiniToken::getInstance()->cleanByUserId($userId);
        //返回最新的值
        return $this->getUser($userId);
    }

    /**
     * @param $userId
     * @param $oldPassword
     * @param $newPassword
     * @return array|bool|mixed|null|string
     * 增加判断老密码是否正确
     */
    public function updatePassword2($userId,$oldPassword,$newPassword){
        if($newPassword == ""){
            return false;
        }
        //更新用户的db信息
        $user              = User::model()->findByPk($userId);
        if(!$user){
            return false;
        }
        $dbPassword = $user['user_pass'];
        if($dbPassword==MiniUtil::signPassword($oldPassword, $user["salt"])){
            $user["user_pass"] = MiniUtil::signPassword($newPassword, $user["salt"]);
            $user->save();
        }else{
            return 'oldPassWrong';
        }
        //删除用户自身的缓存
        $this->cleanCache($userId);
        //清空与当前用户相关的Token
        MiniToken::getInstance()->cleanByUserId($userId);
        //返回最新的值
        return $this->getUser($userId);
    }
    /**
     * 更新用户状态，同时清理该用户的缓存
     * @param $userId
     * @param $status
     * @return array|bool|mixed|null
     */
    public function updateStatus($userId,$status){
        //更新用户的db信息
        $user                = User::model()->findByPk($userId);
        if(!$user){
            return false;
        }
        $user["user_status"] = $status;
        $user->save();
        if($this->hasCache===true){
            $this->cleanCache($userId);
        }
        if($status==0){//冻结帐号，清理Token
            MiniToken::getInstance()->cleanByUserId($userId);
        }
        //返回最新的值
        return $this->getUser($userId);
    }
    /**
     * 清理User自己的缓存，这里不清理Meta的缓存，如清理则导致死循环
     * 仅用于Meta或用户状态更改的场景
     * @param int $userId
     */
    public function cleanCache($userId){
        if($this->hasCache===true){
            $user              = User::model()->findByPk($userId);
            if(isset($user)){
                //清空缓存以用户Id为主键的cache
                $userCacheId       = $this->getCacheKey($userId);
                $this->deleteCache($userCacheId);
                //清空缓存以用户name为主键的cache
                $userCacheName     = $this->getCacheKey($user["user_name"]);
                $this->deleteCache($userCacheName);
            }
        }
    }
    /**
     * 把用户disabled
     * @param int $userId
     */
    public function disableUser($userId){
        $user                    = User::model()->findByPk($userId);
        if(isset($user)){
            $user["user_status"] = 0;
            $user->save();
            //清空cache
            $this->cleanCache($userId);
            //清理Token
            MiniToken::getInstance()->cleanByUserId($userId);
        }
    }
    /**
     * 把用户enabled
     * @param int $userId
     */
    public function enableUser($userId){
        $user                    = User::model()->findByPk($userId);
        if(isset($user)){
            $user["user_status"] = 1;
            $user->save();
            //清空cache
            $this->cleanCache($userId);
            //清理Token
//            MiniToken::getInstance()->cleanByUserId($userId);
        }
    }
    /**
     * 设置管理员
     */
    public function setAdministrator($userId){
        $metas   = MiniUserMeta::getInstance()->getUserMetas($userId);
        $user = $this->getUser($userId);
        foreach ($metas as $key=>$value){
            if($key==="is_admin"){
                $metas["is_admin"] = "1";
            }
            if($key==="space"){
                $metas["space"] = $metas["space"]/1024/1024;
            }
        }
        $userMetas=array();
        $userMetas['extend']=$metas;
        MiniUserMeta::getInstance()->create($user,$userMetas);
    }
    /**
     * 设置为普通用户
     */
    public function normalizeUser($userId){
        $metas   = MiniUserMeta::getInstance()->getUserMetas($userId);
        $user = $this->getUser($userId);
        foreach ($metas as $key=>$value){
            if($key==="is_admin"){
                $metas["is_admin"] = "0";
            }
            if($key==="space"){
                $metas["space"] = $metas["space"]/1024/1024;
            }
        }
        $userMetas=array();
        $userMetas['extend']=$metas;
        MiniUserMeta::getInstance()->create($user,$userMetas);
    }
    /**
     * 把用户删除
     * @param int $userId
     */
    public function deleteUser($userId){
        $user   = User::model()->findByPk($userId);
        if(isset($user)){
            //清理Cache
            $this->cleanCache($userId);
            //删除用户元数据信息
            MiniUserMeta::getInstance()->deleteMeta($userId);
            //删除用户权限数据
            MiniUserPrivilege::getInstance()->deletePrivilege($userId);
            //删除用户设备信息
            MiniUserDevice::getInstance()->deleteDeviceByUser($userId);
            //把用户所有的事件信息删除
            MiniEvent::getInstance()->deleteByUserId($userId);
            //把userId资源暂存到Options表中
            $this->temporary2Option($userId);
            //删除自己，这里不能修改为sql模式，因为用户ID在删除的时候，自动将ID记录到了Options表中
            $user->delete();
        }
    }
    /**
     * 把设备ID转移到Options表中，所有执行Detlete操作的动作都需调用该接口
     */
    private function temporary2Option($id){
        $ids   = array();
        $ids[] = $id;
        //把ID记录到Options表中，以便授权控制。如果是删除记录，先从Options表补充ID到User
        $value = MiniOption::getInstance()->getOptionValue(MiniUser::$OPTION_KEY);
        if (isset($value)){
            if(!(empty($value) || strlen(trim($value))==0)){
                $oriIds = explode(",",$value);
                $ids    = $this->mergeIds(array_merge($oriIds,$ids));
            }
        }
        MiniOption::getInstance()->setOptionValue(MiniUser::$OPTION_KEY,implode(",",$ids));
    }
    /**
     * 获得被删除的ID，如果没有删除的记录，则返回空值
     */
    public function getTemporaryId(){
        $key   = MiniUser::$OPTION_KEY;
        $value = MiniOption::getInstance()->getOptionValue($key);
        $id    = "";
        if(isset($value) && !empty($value)){
            $ids     = explode(",", $value);
            if(count($ids)>0){
                $id  = $ids[0];
                unset($ids[0]);
                //把新值保存到db中
                MiniOption::getInstance()->setOptionValue($key,implode(",", $ids));
            }
        }
        return $id;
    }

    /**
     * 合并主键，剔除重复的,避免因为主键而导致的系统紊乱
     * @param array $oirIds
     * @return array
     */
    private function mergeIds($oirIds){
        $newIds = array();
        foreach ($oirIds as $id){
            $exist = false;
            foreach($newIds as $id1){
                if($id==$id1){
                    $exist = true;
                    break;
                }
            }
            if($exist==false){
                array_push($newIds, $id);
            }
        }
        return $newIds;
    }

    /**
     *
     * 存储用户名、密码及meta信息数据
     * @param $userData 系统不存在的用户名及其meta信息
     * @return \User
     */
    public function create($userData){
        $name = $userData['name'];
        $user = User::model()->find("user_name=?", array($name));

        if (empty($user)){
            $user = new User();
            $user["user_uuid"]   = uniqid();
            $user["user_name"]   = $name;
            //
            //如果传递salt和password 则存储用户密码和salt到迷你云自有数据库
            //
            if (!array_key_exists('salt', $userData)){
                $salt = MiniUtil::genRandomString(6);
            }else{
                $salt = $userData['salt'];
            }
            if (!array_key_exists('password', $userData)){
                $password  = MiniUtil::genRandomString(16);
            }else{
                $password  = $userData['password'];
            }
            $user["salt"]        = $salt;
            $user["user_pass"]   = MiniUtil::signPassword($password, $salt);
            $user["user_status"] = 1;
            $user->save();
            if(array_key_exists('group', $userData)){
                do_action("add_user_to_group",$userData['group'],$user['id']);
            }
            //存储用户扩展信息
            MiniUserMeta::getInstance()->create($user, $userData);
            $userData['user_id'] = $user['id'];
            do_action('analyze_add_group', $userData);
            return $this->db2Item($user);
        }else{
            //更新扩展信息
            MiniUserMeta::getInstance()->create($user, $userData);
            $userData['user_id'] = $user['id'];
            do_action('analyze_add_group', $userData);
            return $this->db2Item($user);
        }
    }
    /**
     * 把数据库值序列化
     * @param array $items db的List
     * @return array 把db的list转换位可序列化的列表
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }
    /**
     * 获得所有可用用户的总数
     * @return array 获得可用的用户总数
     */
    public function getEnableCount(){
        return User::model()->count('user_status=1');
    }
    public function getById($id){
        $criteria                = new CDbCriteria();
        $criteria->condition="id = :id";
        $criteria->params    = array('id'=>$id);
        $data =  User::model()->find($criteria);
        return $this->db2Item($data);

    }
    /**
     * 网页版显示好友列表，对其进行分页
     * @param mixed $userId 用户ID，用于排除自己
     * @param string $order 排序方式
     * @param int $limit 每页的大小
     * @param int $start 开始记录Id
     * @return array 返回用户列表
     */
    public function getPageList($userId,$order,$limit = 20, $start = 0){
        $condition = "user_status=1 and id<>".$userId;
        $items     = User::model()->findAll(
            array(

                'condition' => $condition,
                'order'     => $order,
                'limit'     => $limit,
                'offset'    => $start,
            )
        );
        return $this->db2list($items);
    }

    /**
     * 根据key模糊搜索name与nick
     * @param mixed $userId 查询用户的ID，这里用于排除自己
     * @param mixed $key 模糊搜索的关键字
     * @return array 用户列表
     */
    public function searchByName($userId,$key){
        $aimIds        = array();
        //通过UserName进行检索
        $condition     = "user_status=1 and id<>:userId and user_name like :userName";
        $params        = array('userId'=>$userId, 'userName'=>"%" . $key . "%");
        $items         = User::model()->findAll(
            array(
                'condition' => $condition,
                'params'    => $params,
            )
        );
        foreach($items as $item){
            $aimIds[$item["id"]] = $item["id"];
        }
        //根据拼音搜索
        $condition     = "user_status=1 and id<>:userId and user_name_pinyin like :userName";
        $params        = array('userId'=>$userId, ':userName'=>"%" . $key . "%");
        $items         = User::model()->findAll(
            array(
                'condition' => $condition,
                'params'    => $params,
            )
        );
        foreach($items as $item){
            $aimIds[$item["id"]] = $item["id"];
        }
        //通过Nick进行检索
        $condition     = "meta_key='nick' and meta_value like :userNick";
        $params        = array('userNick'=>"%" . $key . "%");
        $items         = UserMeta::model()->findAll(
            array(
                'condition' => $condition,
                'params'    => $params,
            )
        );
        foreach($items as $item){
            $aimIds[$item["user_id"]] = $item["user_id"];
        }
        if(count($aimIds)>0){
            //最后的用户
            $criteria            = new CDbCriteria();
            $criteria->condition = 'user_status=1 and id<>:userId';
            $criteria->params    = array(':userId'=>$userId);
            $criteria->addInCondition('id',array_keys($aimIds));
            $criteria->order     = "id desc";
            $items               = User::model()->findAll($criteria);
            return $this->db2list($items);
        }
        return array();
    }

    /**
     * 搜索所有用户
     */
    public function searchUsers($name,$currentPage,$pageSize){
        $criteria                = new CDbCriteria();
        $criteria->condition="user_name like :userName";
        $criteria->params=array('userName'=>"%" . $name . "%");
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="id desc";
        $items              	=User::model()->findAll($criteria);
        $total              	=User::model()->count($criteria);
        $data = array();
        $data['list'] = $this->db2list($items);
        $data['total'] = $total;
        return $data;
    }

    /**
     * 验证账号与密码是否正确
     * @param $name
     * @param $password
     * @return bool
     */
    public function valid($name,$password){
        $user = $this->getUserByName($name);
        if($user!==NULL){
            $password = MiniUtil::signPassword($password, $user["salt"]);
            if($password===$user["user_pass"]){
                return true;
            }
        }
        return false;
    }
    /**
     * 分页所有用户列表
     */
    public function ajaxGetUsers($currentPage,$pageSize){
        $criteria                = new CDbCriteria();
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="id desc";
        $items              	=User::model()->findAll($criteria);
        $total              	=User::model()->count($criteria);
        $data = array();
        if($total == 0){
            return null;
        }else{
            $data['total'] = $total;
            $data['list'] = $this->db2list($items);
            return $data;
        }
    }
    /**
     * 分页所有用户列表
     */
    public function ajaxGetAdmins($currentPage,$pageSize){
        $criteria                = new CDbCriteria();
        $criteria->order="id desc";
        $items              	=User::model()->findAll($criteria);
        $total              	=User::model()->count($criteria);
        $arr = array();
        foreach($items as $item){
            $metas = MiniUserMeta::getInstance()->getUserMetas($item["id"]);
            foreach ($metas as $key=>$value){
                if($key==="is_admin" && $value==="1"){
                    array_push($arr,$item);
                }
            }
        }
        array_slice($arr,($currentPage-1)*$pageSize,$pageSize);
        $data = array();
        $data['total']=count($arr);
        $data['list']=$this->db2list($arr);
        if($total == 0){
            return null;
        }else{
            return $data;
        }
    }
    /**
     * 分页所有用户列表
     */
    public function ajaxGetDisabled($currentPage,$pageSize){
        $criteria                = new CDbCriteria();
        $criteria->condition="user_status = 0";
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="id desc";
        $items              	=User::model()->findAll($criteria);
        $total              	=User::model()->count($criteria);
        $data = array();
        if($total == 0){
            return null;
        }else{
            $data['total'] = $total;
            $data['list'] = $this->db2list($items);
            return $data;
        }
    }
    /***
     * 获取所有用户信息
     */
    public function getAllUsers(){
        $data = array();
        $criteria                = new CDbCriteria();
        $criteria->order="id desc";
        $criteria->limit="1";
        $maxItem              	=User::model()->find($criteria);
        $data[] = $maxItem;
        $criteria->order="id asc";
        $criteria->limit="1";
        $minItem              	=User::model()->find($criteria);
        $data[] = $minItem;
        return $this->db2list($data);
    }
    /**
     * 获得特定时间之前注册用户数
     */
    public function getBeforeDateUsers($wholeDate){
        $num = array();
        foreach($wholeDate as $date){
            $criteria                = new CDbCriteria();
            $criteria->condition="created_at < :date";
            $criteria->params    = array(':date'=>$date);
            $total              	=User::model()->count($criteria);
            if($total == 0){
                $total =0;
            }
            array_push($num,(int)$total);
        }
        return $num;
    }
    /**
     * 后台创建用户
     */
    public function adminCreateUser($userData){

        if($this->validateName($userData["user_name"])){
                //用户验证随机数
                $salt = MiniUtil::genRandomString(6);
                //存储User数据
                $user = new User();
                $user["user_uuid"]   = uniqid();
                $user["user_name"]   = trim($userData["user_name"]);
                $user["salt"]        = $salt;
                $user["user_status"] = 1;
                $user["user_pass"]   = MiniUtil::signPassword($userData["password"], $salt);
                $user->save();
                //存储UserMeta数据
                if(strlen($userData["email"])){
                    //email
                    $userMeta = new UserMeta();
                    $userMeta["user_id"]=$user["id"];
                    $userMeta["meta_key"]="email";
                    $userMeta["meta_value"]=$userData["email"];
                    $userMeta->save();
                }
                if(strlen($userData["nick"])){
                    //nick
                    $userMeta = new UserMeta();
                    $userMeta["user_id"]=$user["id"];
                    $userMeta["meta_key"]="nick";
                    $userMeta["meta_value"]=$userData["nick"];
                    $userMeta->save();
                }
                $userMeta = new UserMeta();//管理员
                $userMeta["user_id"]=$user["id"];
                $userMeta["meta_key"]="is_admin";
                $userMeta["meta_value"]=$userData["is_admin"];
                $userMeta->save();
                $userMeta = new UserMeta();//空间数
                $userMeta["user_id"]=$user["id"];
                $userMeta["meta_key"]="space";
                $userMeta["meta_value"]=$userData["space"];
                $userMeta->save();
                //更新用户的拼音信息
                MiniUser::getInstance()->updateUserNamePinYin($user["id"]);
                return true;
            }
        return 'exist';
        }
    /**
     *
     * 检查用户名是否已经存在
     */
    private function validateName($name){
        $user = User::model()->find("user_name=?",array(trim($name)));
        if(isset($user)){
            return false;
        }
        return true;
    }
    /**
     * 查询未分组的用户
     */
    public function unbindUsers(){
        $items=UserGroupRelation::model()->findAll();
        $value                 = array();
        if(isset($items)){
            foreach($items as $item) {
                $group = MiniGroup::getInstance()->findById($item->group_id);
                if(isset($group)){
                    if($group['user_id']>0){
                        continue;
                    }
                }
                $value[]           = $item->user_id;
            }
        }
        $criteria                = new CDbCriteria();
        $criteria->addNotInCondition('id',$value);
        $data = User::model()->findAll($criteria);
        return $this->db2list($data);

    }
    /**
     *通过OpenId获得用户
     */
    public function getUserByOpenId($openId){
        $user = User::model()->find("user_uuid=?",array(trim($openId)));
        if(isset($user)){
            return $this->db2Item($user);
        }
        return NULL;
    }

    /**
     * 更新用户名的拼音信息
     * @param $id
     */
    public function updateUserNamePinYin($id){
        $item  = User::model()->findByPk($id);
        if(!empty($item)){
            //把登陆名转化为拼音
            $name = $item->user_name;
            //把昵称转化为拼音
            $nick = "";
            $criteria            = new CDbCriteria();
            $criteria->condition = "user_id=:user_id and meta_key='nick'";
            $criteria->params    = array(":user_id"=>$item->id);
            $meta = UserMeta::model()->find($criteria);
            if(!empty($meta)){
                $nick = $meta->meta_value;
            }
            $item->user_name_pinyin = $this->getPinYinByName($name,$nick);
            $item->save();
        }
    }
    /**
     * 根据用户名与昵称获得拼音　
     * @param $name
     * @param $nick
     * @return string
     */
    private function getPinYinByName($name,$nick){
        $py = new PinYin();
        $allPY = $py->getAllPY($name);
        $firstPY = $py->getFirstPY($name);
        $namePY = $allPY."|".$firstPY;
        $allPY = $py->getAllPY($nick);
        $firstPY = $py->getFirstPY($nick);
        $nickPY = $allPY."|".$firstPY;
        return $namePY."|".$nickPY;
    }
    /**
     * 把系统中的中文用户名转化为拼音
     */
    public function updateAllUserNamePinyin(){
        $criteria            = new CDbCriteria();
        $criteria->order     = "id desc";
        $items               = User::model()->findAll($criteria);

        foreach($items as $item){
            if(empty($item->user_name_pinyin)){
                //把登陆名转化为拼音
                $name = $item->user_name;
                //把昵称转化为拼音
                $nick = "";
                $criteria            = new CDbCriteria();
                $criteria->condition = "user_id=:user_id and meta_key='nick'";
                $criteria->params    = array(":user_id"=>$item->id);
                $meta = UserMeta::model()->find($criteria);
                if(!empty($meta)){
                    $nick = $meta->meta_value;
                }
                $item->user_name_pinyin = $this->getPinYinByName($name,$nick);
                $item->save();
            }
        }
    }
}
