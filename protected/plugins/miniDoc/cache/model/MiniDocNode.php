<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class MiniDocNode extends MiniCache{
    
    private static $CACHE_KEY = "cache.model.MiniServices";
    
    public static $SUCCESS = 1;
    
    public static $OPTION_KEY="mini_doc_limit_file_size";
    
    static private $_instance = null;

    
    private function  __construct()
    {
        parent::MiniCache();
    }

    
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function db2Cache(){
        $data = $this->getAll4Db();
        $this->set(MiniDocNode::$CACHE_KEY, serialize($data));
        return $data;
    }

    
    private function getAll4Db(){
        $items =  DocNode::model()->findAll();
        return $this->db2List($items);
    }

    private function db2Item($item){
        if(empty($item)) return NULL;
        $value                  = array();
        $value["id"]            = $item->id;
        $value["run_status"]    = $item->run_status;
        $value["ip"]            = $item->ip;
        $value["port"]            = $item->port;
        $value["created_at"]    = $item->created_at;
        $value["updated_at"]    = $item->updated_at;

        return $value;
    }

    
    private function db2List($items){
        $data = array();
        foreach($items as $item) {
            $data[] = $this->db2Item($item);
        }
        return $data;
    }

    /**
     * 修改迷你文档节点
     * @param $id
     * @param $ip
     * @param $port
     * @return bool
     */
    public function modify($id,$ip,$port){
        // 删除 cache
        $this->deleteCache(MiniDocNode::$CACHE_KEY);
        $ipNode =  $this->getByIP($ip);
        $node   =  DocNode::model()->findByPk($id);
        //如IP已经被其它节点使用
        if(isset($ipNode)){
            if($ipNode["id"]!=$node["id"]){
                return false;
            }
        }
        $node->ip    = $ip;
        $node->port  = (int)$port;
        $node->run_status = 0;
        $node->save();
        return true;
    }
    /**
     *
     *      根据page,pageSize获取list *
     */
    public function getLists(){
        $criteria = new CDbCriteria();
        $items =  DocNode::model()->findAll($criteria);
        return $this->db2List($items);
    }
    
    public function getAllService(){
        if($this->hasCache===false){            $data  =  $this->getAll4Db();
            return $data;
        }
                $datastr    = $this->get(MiniDocNode::$CACHE_KEY);
        if($datastr === false){
            Yii::trace(MiniDocNode::$CACHE_KEY." set cache server","miniyun.cache1");
            $data   = $this->db2Cache();
        }else{
            Yii::trace(MiniDocNode::$CACHE_KEY." get cache server","miniyun.cache1");
            $data   = unserialize($datastr);
        }
        return $data;
    }

    
    public function getByPort($port) {
        $services = $this->getAllService();
        foreach($services as $service){
            if($service["port"] == $port){
                return $service;
            }
        }
        return null;
    }
    
    public function getBestServer() {
        $services = $this->getValidList();
        if(count($services)>0){
            $index = time()%count($services);
            return $services[$index];
        }
        return NULL;
    }
    
    public function getValidList() {
        $retVal = array();
        $services = $this->getAllService();
        foreach($services as $service){
            if($service["run_status"]==1){
                $retVal[] = $service;
            }
        }
        return $retVal;
    }
    
    public function getByID($id) {
        $item = DocNode::model()->findByPk($id);
        return $this->db2Item($item);
    }

    
    public function modifyServerRunStatus($id,$runStatus) {
        if($runStatus == ''){
            return false;
        }
        $this->deleteCache(MiniDocNode::$CACHE_KEY);
        $model = DocNode::model()->findByPk($id);
        if (isset($model)) {
            if($runStatus==0){
                $model->run_status = 1;
            }else{
                $model->run_status = 0;
            }
            $model->save();
        }
        return true;
    }

    
    public function create($serverIP,$serverPort){
                $this->deleteCache(MiniDocNode::$CACHE_KEY);
        $item       =  DocNode::model()->find("ip=:ip and port=:port",array(":ip" => $serverIP,":port"=>(int)$serverPort));
        if (empty($item)) {
            $item = new DocNode();
        }
        $item["ip"]     = $serverIP;
        $item["port"]   = (int)$serverPort;
        $item["run_status"] = 0;
        $item->save();
        return true;
    }

    
    public function modifyServer($serverIP,$serverPort,$id){
                                $this->deleteCache(MiniDocNode::$CACHE_KEY);
        $value =  $this->getByIP($serverIP);
        if(!empty($value)){
            $item        =  DocNode::model()->findByPk($id);
            $item->port  = (int)$serverPort;
            if($item->run_status!=0){
                $item->run_status = !$item->run_status;
            }
            $item->save();
            return false;
        }else{
            $item        =  DocNode::model()->findByPk($id);
            $item->ip    = $serverIP;
            $item->port  = (int)$serverPort;
            if($item->run_status!=0){
                $item->run_status = !$item->run_status;
            }
            $item->save();
            return true;
        }
    }

    
    public function getByIP($ip){
        $item       =  DocNode::model()->find("ip=:ip",array(":ip" => $ip));
        if(empty($item)){
            return null;
        }else{
            return $item;
        }

    }
}