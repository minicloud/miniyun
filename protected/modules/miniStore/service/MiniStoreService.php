<?php
/**
 * 迷你存储接口
 */
class MiniStoreService{
    /**
     * 获得迷你存储节点信息
     */
    public function nodeList(){
        $nodes = MiniStoreNode::getInstance()->getAllNodes();
        $data = array();
        foreach($nodes as $node){
            $item = array();
            $item["name"] = $node["name"];
            $item["run_status"] = $node["run_status"];
            $item["ip"] = $node["ip"];
            $item["port"] = $node["port"];
            $item["path"] = $node["path"];
            $item["safe_code"] = $node["safe_code"];
            $item["created_at"] = $node["created_at"];
            $item["updated_at"] = $node["updated_at"];
            array_push($data,$item);
        }
        return $data;
    }
    /**
     * 创建节点
     */
    public function createNode(){
        $name = MiniHttp::getParam("name","");
        $safeCode = MiniHttp::getParam("safe_code","");
        $host = MiniHttp::getParam("host","");
        $port = MiniHttp::getParam("port","");
        $path = MiniHttp::getParam("path","");
        //valid name
        $nameError = false;
        if(empty($name)){
            $nameError = true;
        }
        if($nameError){
            throw new MiniException(1401);
        }
        //valid path
        if(empty($path)){
            throw new MiniException(1402);
        }
        //valid safeCode
        $safeCodeError = false;
        if(empty($safeCode)){
            $safeCodeError = true;
        }else{
            $node = MiniStoreNode::getInstance()->getBySafeCode($safeCode);
            if($node!==NULL && $node["name"]!=$name){
                $safeCodeError = true;
            }
        }
        if($safeCodeError){
            throw new MiniException(1403);
        }
        //valid host
        $hostError = false;
        if(empty($host) || empty($port)){
            $hostError = true;
        }else{
            $node = MiniStoreNode::getInstance()->getByIPAndPort($host,$port);
            if($node!==NULL && $node["name"]!=$name){
                $hostError = true;
            }
        }
        if($hostError){
            throw new MiniException(1404);
        }
        //create or modify node
        MiniStoreNode::getInstance()->create($name,$safeCode,$host,$port,$path);
        return true;
    }
    /**
     * 修改节点状态
     */
    public function modifyNodeStatus(){
        $name = MiniHttp::getParam("name","");
        //valid name
        $nameError = false;
        if(empty($name)){
            $nameError = true;
        }
        if($nameError){
            throw new MiniException(1401);
        }
        $node = MiniStoreNode::getInstance()->getByName($name);
        MiniStoreNode::getInstance()->modifyStatus($node["id"]);
        return true;
    }
    /**
     * 下载配置文件
     */
    public function downloadConfig(){
        $name = MiniHttp::getParam("name","");
        //valid name
        $nameError = false;
        if(empty($name)){
            $nameError = true;
        }
        if($nameError){
            throw new MiniException(1401);
        }
        $node = MiniStoreNode::getInstance()->getByName($name);
        $config = new StoreNodeConfig();
        $config->download($node["id"]);
    }

    /**
     * 存储meta信息,如函数头以APP开始，则表示是app向迷你云发送的请求
     * 这类请求，需要函数体自身负责用户身份的过滤与处理
     * 在新模式下，所有插件将没有controller
     * 这个接口是迷你存储服务器向迷你云提交的请求
     */
    public function appFileMeta(){
        // 解析控制器中对应操作名称
        $uri = $_SERVER['REQUEST_URI'];
        // iis服务器，处理编码
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'iis') !== false)
        {
            $uri = rawurldecode($uri);
            $uri = mb_convert_encoding($uri, "UTF-8", "gbk");
        }
        else {
            $uri = urldecode($uri);
        }
        //这里在兼容2级子目录存在问题
        $key = "/c.php/1/module/miniStore/fileMeta";
        $pos = strpos($uri,$key);
        $uri = substr($uri,$pos,strlen($uri));
        $parts = array_slice(explode('/', $uri), 4);
        if (empty($parts)) {
            throw new MException('do not parse from url');
        }
        $uri       = '/' . join('/', $parts);
        $filter       = new MUserFilter();
        $filter->invoke($uri);
        //api 上传中invoke 已经exit()
        $createFile = new MCreateFileController();
        $createFile->invoke($uri);
        //客户端将获得rev信息，而网页客户端没有rev信息
        $rev = MiniHttp::getParam("rev","");
        if(empty($rev)){
            //创建文件meta信息
            apply_filters('meta_add', array('rev'=>$createFile->version_id));
        }
    }
}