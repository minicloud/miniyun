<?php
/**
 * 缓存mini_stores表的记录，V1.2.0该类接管所有mini_stores的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 ** @author jim
 *
 */
class MiniStoreNode extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.MiniStoreNode";
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
     * 将整个表缓存到Cache
     */
    private function db2Cache(){
        $data = $this->getAll4Db();
        $this->set(MiniStoreNode::$CACHE_KEY, serialize($data));
        return $data;
    }
    
    /**
     * 通过db获得记录 list
     */
    private function getAll4Db(){
        $items =  StoreNode::model()->findAll();
        return $this->db2List($items);
    }

    
    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                     = array();
        $value["id"]               = $item->id;
        $value["name"]             = $item->name;
        $value["run_status"]       = $item->run_status;
        $value["ip"]               = $item->ip;
        $value["port"]             = $item->port;
        $value["path"]             = $item->path;
        $value["safe_code"]        = $item->safe_code;
        $value["created_at"]       = $item->created_at;
        $value["updated_at"]       = $item->updated_at;
        
        return $value;
    }
    /**
     * 把db对象转换为array
     * @param object $items
     * @return array
     */
    private function db2List($items){
        $data                  = array();
        foreach($items as $item) {
            $data[$item["id"]] = $this->db2Item($item);
        }
        return $data;
    }

    /**
     * 获取所有server
     */
    public function getAllNodes(){
        if($this->hasCache===false){//如果没有初始化Cache则直接访问DB
            $data                  =  $this->getAll4Db();
            return $data;
        }
        //先判断是否已经缓存，否则进行直接缓存
        $dataStr    = $this->get(MiniStoreNode::$CACHE_KEY);
        if($dataStr === false){
            Yii::trace(MiniStoreNode::$CACHE_KEY." set cache Dataserver","miniyun.cache1");
            $data   = $this->db2Cache();
        }else{
            Yii::trace(MiniStoreNode::$CACHE_KEY." get cache Dataserver","miniyun.cache1");
            $data   = unserialize($dataStr);
        }
        return $data;
    }
    
    /**
     * 获得可用的miniStore,用于文件删除场景
     * 有可能某个文件会在多个miniStore存储
     */
    public function getEnableNodes(){
        $validList = array();
        $nodes = $this->getAllNodes();
        foreach ($nodes as $node){
            if($node["run_status"] == 1){
                array_push($validList, $node);
            }
        } 
        return $validList;
    }
    /**
     * 检测store的唯一性
     * @param string $ip
     * @param int $port
     * @return array
     */
    public function getByIPAndPort($ip,$port) {
        $nodes = $this->getAllNodes();
        foreach ($nodes as $key=>$node){
            if($node["ip"]==$ip && $node["port"]==$port){
                return $node;
            }
        } 
        return NULL;
    }
    /**
     * 根据token获取miniStore
     * @param string $safeCode
     * @return array
     */
    public function getBySafeCode($safeCode){
         $nodes = $this->getAllNodes();
         foreach ($nodes as $key=>$node){
            if($node["safe_code"]==$safeCode){
                return $node;
            }
         }
        return NULL;
    }

    /**
     * 检查节点状态，无法连接则拉下
     * @param $node
     * @return bool
     */
    private function checkNodeStatus($node){
        if(!CUtils::validServer($node["ip"],$node["port"])){
            $this->modifyStatus($node["id"]);
            return false;
        }
        return true;
    }
    /**
     * 获得最佳 MiniStore
     */
    public function getBestNode(){

        $this->deleteCache(MiniStoreNode::$CACHE_KEY);
        $nodes              = $this->getAllNodes();
        $validList           = array();
        foreach($nodes as $node){
            if($node["run_status"] == 1){
                //每次检查服务器状态，如果不行则拉下节点
                array_push($validList, $node);
            }
        }
        if(count($validList)>0){
            $index = time()%count($validList);
            return $validList[$index];
        }
        return NULL;
        
    }
    
    /**
     * 根据ID查询Store
     * @param string $id
     * @return object
     */
    public function getByID($id) {

        $nodes = $this->getAllNodes();
        if(key_exists($id, $nodes)){
          return $nodes[$id];
        }
        return NULL;
    }
     /**
     * 根据Name查询Store
     */
    public function getByName($name) {
        $nodes = $this->getAllNodes();
        foreach ($nodes as $key=>$node){
            if($node["name"]==$name){
                return $node;
            }
        }
        return NULL;
    }

    /**
     * 修改mini Store节点的状态
     */
    public function modifyStatus($id) {
        $this->deleteCache(MiniStoreNode::$CACHE_KEY);
        $model = StoreNode::model()->findByPk($id);
        if (!empty($model)) {
            $isOk = true;
            if($model->run_status==0){//说明用户准备把服务器拉上,判断目标服务器是否开启端口
                if(!CUtils::validServer($model->ip,$model->port)){
                    $isOk = false;
                }
            }
            if($isOk){
                $model->run_status = !$model->run_status;
                $model->save();
            }
            return $isOk;
        }
        return false;
    }
    /**
     * 创建或更新mini Store
     */
    public function create($name,$safeCode,$ip,$port,$path) {
        // 删除 cache
        $this->deleteCache(MiniStoreNode::$CACHE_KEY);
        $item  = StoreNode::model()->find("name=:name",array("name" => $name));
        if (empty($item)) {
            $item       = new StoreNode();
        }
        $item["name"]   = $name; 
        $item["ip"]     = $ip; 
        $item["port"]   = $port;
        $item["path"]   = $path; 
        $item["safe_code"]  = $safeCode;
        $item["run_status"] = 0;
        $item->save();
    }
}