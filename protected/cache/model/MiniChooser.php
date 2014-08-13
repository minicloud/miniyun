<?php
/**
 * 缓存miniyun_choosers表的记录，V1.2.0该类接管所有miniyun_的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniChooser extends MiniCache{
    const LEN_KEY     = 14;
    public static $ANDROID_TYPE = "2";
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.MiniChooser";

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
     *      数据库值序列化
     */
    private function db2list($items){
        $data=array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }
    public function db2Item($item){
        if(empty($item)) return NULL;
        $value                     = array();
        $value["id"]               = $item->id;
        $value["name"]             = $item->name;
        $value["app_key"]          = $item->app_key;
        $value["type"]             = $item->type;
        $value['created_at']       = $item->created_at;
        $value["updated_at"]       = $item->updated_at;
        return $value;
    }
    /**
     * @param $id /根据id来获取当前数据表中的数据
     * @return mixed
     */
    public function getById($id){
        $item  = Chooser::model()->findByPk($id);
        return $this->db2Item($item);
    }
    /**
     *
     * 根据page,pageSize获取list
     */
    public function getPageList($pageSize,$currentPage,$type){
        $criteria = new CDbCriteria();
        $criteria->condition="type=:type";
        $criteria->params=array(':type'=>$type);
        $criteria->limit=$pageSize;
        $criteria->offset=($currentPage-1)*$pageSize;
        $criteria->order="id desc";
        $items = Chooser::model()->findAll($criteria);
        return $this->db2List($items);
    }
    /**
     * @return string 获得随机的字符串
     */
    private function getAppKey(){
        $key = MiniUtil::getEventRandomString( MUtils::LEN_TIME );
        $appKey = substr($key,0,self::LEN_KEY);
        return $appKey;
    }
    /**
     * 创建新的选择器节点的信息
     */
    public function create($name,$type){
        // 删除 cache
        $this->deleteCache(MiniChooser::$CACHE_KEY);
        $chooser =  Chooser::model()->find("name=:name and type=:type",array(":name" => $name,":type"=>$type));
        if (empty($chooser)) {
            $chooser = new Chooser();
        }
        $chooser["type"]     = $type;
        $chooser["name"]     = $name;
        $chooser['app_key']  = $this->getAppKey();
        $chooser->save();
        return true;
    }
    /**
     * 获取当前总记录数
     */
    public function getTotal($type){
        $total = Chooser::model()->count("type=:type",array(":type"=>$type));
        return $total;
    }
    /**
     * 根据app_key获得数据
     */
    public function getByKey($appKey){
        $criteria  = new CDbCriteria();
        $criteria->condition='app_key=:appKey';
        $criteria->params=array(':appKey'=>$appKey);
        $item = Chooser::model()->find($criteria);
        return $this->db2Item($item);
    }
    /**
     * 根据app_key获得数据
     */
    public function getByName($name){
        $criteria  = new CDbCriteria();
        $criteria->condition='name=:name';
        $criteria->params=array(':name'=>$name);
        $item = Chooser::model()->find($criteria);
        return $this->db2Item($item);
    }
    /**
     * 根据类型获得chooser
     */
    public function getByType($appKey,$type){
        $criteria  = new CDbCriteria();
        $criteria->condition='app_key=:appKey and type=:type';
        $criteria->params=array(':appKey'=>$appKey,':type'=>$type);
        $item = Chooser::model()->find($criteria);
        return $this->db2Item($item);
    }
}