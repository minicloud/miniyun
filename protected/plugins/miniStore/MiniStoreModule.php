<?php
/**
 * 迷你存储Store
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
class MiniStoreModule extends MiniPluginModule { 
    /**
     *
     * @see CModule::init()
     */
    public function init()
    {
        $this->setImport(array(
            "miniStore.biz.*",
            "miniStore.cache.*",
            "miniStore.models.*",
            "miniStore.service.*",
        ));
        add_filter("plugin_info",array($this, "setPluginInfo"));
        //文件秒传
        add_filter("file_sec",array($this, "fileSec"));
        //文件下载
        add_filter("file_download_url",array($this, "fileDownloadUrl"));
        //获得文件内容
        add_filter("file_content",array($this, "fileContent"));
        //图片缩略图
        add_filter("image_path",array($this,"cacheFile"));
        //文件上传
        add_filter("upload_start",array($this,"start"));
        //文件秒传
        add_filter("upload_sec",array($this,"sec"));
        //文件上传结束
        add_filter("upload_end",array($this,"end"));

    }
    private function getDownloadUrl($signature){
        return PluginMiniStoreNode::getInstance()->getDownloadUrl($signature,"image.jpg","application/octet-stream",1);     
    }
    /**
     * 获得文件的缩略图
     * @param array $params
     * @return string
     */
    function cacheFile($signature){ 
        $saveFolder = MINIYUN_PATH."/assets/miniStore/";
        $filePath = $saveFolder.$signature; 
        if(!file_exists($filePath)){
            if(!file_exists($saveFolder)){
                mkdir($saveFolder);
            } 
            //把文件下载到本地
            $url = $this->getDownloadUrl($signature);   
            file_put_contents($filePath,file_get_contents($url));
        }
        return $filePath;
    }
    /**
     * 获得文件下载地址
     * @param array $params
     * @return string
     */
    function fileDownloadUrl($params){
        $signature = $params["signature"];
        $fileName = $params["file_name"];
        $mimeType = $params["mime_type"];
        $forceDownload = $params["force_download"];
        return PluginMiniStoreNode::getInstance()->getDownloadUrl($signature,$fileName,$mimeType,$forceDownload);
    }
    /**
     * 获得文件内容
     * 把迷你存储的文件缓存到本地
     * @param string $signature
     * @return string
     */
    function fileContent($signature){
        $filePath = $this->cacheFile($signature);
        return file_get_contents($filePath);
    }

    /**
     * 秒传接口
     * @param array $params
     */
    function fileSec($params){  
        $signature = $params["signature"]; 

        $data['success'] = false; 
        $data['store_type'] = "miniStore"; 
        //查询断点文件表
        $node = null;
        $breakFile = PluginMiniBreakFile::getInstance()->getBySignature($signature);
        if(!empty($breakFile)){ 
            $node = PluginMiniStoreNode::getInstance()->getNodeById($breakFile["store_node_id"]); 
        } 
        //如果断点文件不存在或无效则重新分配一个存储节点
        if(empty($node)||$node["status"]===-1){

            $node = PluginMiniStoreNode::getInstance()->getUploadNode(); 
            //更新断点表该文件的状态
            PluginMiniBreakFile::getInstance()->create($signature,$node["id"]);
        }
        //回调地址
        $callbackUrl = MiniHttp::getMiniHost()."api.php?node_id=".base64_encode($node["id"]);
        foreach ($params as $key => $value) {
            $callbackUrl .="&".$key."=".base64_encode(urlencode($value));
        }
        $callbackUrl .="&encode=base64";
        $siteId   = MiniSiteUtils::getSiteID();
        $data['callback'] =  $callbackUrl;
        //兼容127.0.0.1
        $urlInfo = parse_url($node["host"]);
        if($urlInfo["host"]=="127.0.0.1"){
            //说明迷你存储在本机，直接把127.0.0.1替换为迷你存储端口
            $defaultHost  = MiniHttp::getMiniHost();
            $miniHostInfo = parse_url($defaultHost);
            $node['host'] = $miniHostInfo["scheme"]."://".$miniHostInfo["host"].":".$urlInfo["port"].$miniHostInfo["path"];
        }
        $data['url'] =  $node["host"]."/api.php";
        echo json_encode($data);exit;
    }
    /**
     *获得插件信息
     * @param $plugins 插件列表
     * {
     *   "miniDoc":{}
     * }
     * @return array
     */
    function setPluginInfo($plugins){
        if(empty($plugins)){
            $plugins = array();
        }
        $storeNode = PluginMiniStoreNode::getInstance()->getUploadNode();
        $data = array(
            "node"=>$storeNode
            );
        array_push($plugins,
            array(
               "name"=>"miniStore",
               "data"=>$data
            ));
        return $plugins;
    }
    private function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new DateTime($dtStr);
        $expiration = $mydatetime->format(DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
    /**
    *文件上传结束
    */
    public function end(){       
        $user = MUserManager::getInstance()->getCurrentUser();
        $hash = MiniHttp::getParam('hash','');
        $hash = strtolower($hash);
        //防止重复文件通过网页上传，生成多条记录
        if(!empty($hash)){
            $version = MiniVersion::getInstance()->getBySignature($hash); 
            //创建version/versionMeta数据 
            if(empty($version)){                
                $type =MiniHttp::getParam('mime_type','');
                $size = MiniHttp::getParam('size',0);
                $nodeId = MiniHttp::getParam('node_id',0);
                $version = MiniVersion::getInstance()->create($hash, $size, $type);
                MiniVersionMeta::getInstance()->create($version["id"],"store_id",$nodeId);
                //更新迷你存储节点状态，把新上传的文件数+1
                PluginMiniStoreNode::getInstance()->newUploadFile($nodeId);
            } 
            //创建用户相关元数据 执行文件秒传逻辑
            $filesController = new MFileSecondsController();
            $filesController->invoke(); 
        }   
    }
    /**
    *文件秒传
    */
    public function sec(){
        $user = MUserManager::getInstance()->getCurrentUser();
        $hash = MiniHttp::getParam('hash','');
        $hash = strtolower($hash);
        //防止重复文件通过网页上传，生成多条记录
        if(!empty($hash)){
            $version = MiniVersion::getInstance()->getBySignature($hash);
            //创建version/versionMeta数据 
            if(empty($version)){                
               return array("status"=>false);  
            } 
            //创建用户相关元数据 执行文件秒传逻辑
            $filesController = new MFileSecondsController();
            $filesController->invoke(); 
        } 
    }
    /**
    *文件开始上传，先要获得上传需要的上下文信息
    */
    public function start(){
        $storeNode = PluginMiniStoreNode::getInstance()->getUploadNode();

        $user = MUserManager::getInstance()->getCurrentUser();  
        $path = MiniHttp::getParam('path','/'); 
        $token = MiniHttp::getParam('access_token','');
        //存储路径
        $miniyunPath = $path;
        $bucketPath = '迷你云/'.$user['user_name'].$path;
        //OSS相关信息
        $id= 'WPkFoYluMvInP9Eu';
        $key= $storeNode['safe_code'];
        $bucketHost = $storeNode['host']; 
        $callbackUrl = MiniHttp::getMiniHost()."api.php"; 
        //回调地址是阿里云接收文件成功后，反向调用迷你云的地址报竣
        //其中access_token/route/bucket_url是回调需要的地址
        //TODO 需要进行签名
        $callback_param = array('callbackUrl'=>$callbackUrl, 
                     'callbackBody'=>'access_token='.$token.'&route=upload/end&node_id='.$storeNode['id'].'&bucket_host='.$bucketHost.'&path='.$miniyunPath.'&bucket_path=${object}&size=${size}&hash=${etag}&mime_type=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        
        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition; 

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$bucketPath);
        $conditions[] = $start; 


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions); 
        //return;
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        //上传策略信息
        $uploadContext = array();
        $uploadContext['accessid'] = $id;
        $uploadContext['host'] = $bucketHost;
        $uploadContext['policy'] = $base64_policy;
        $uploadContext['signature'] = $signature;
        $uploadContext['expire'] = $end;
        $uploadContext['callback'] = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $uploadContext['path'] = $bucketPath;

        //文件秒传上传策略
        $uploadSecContext = array();
        $uploadSecContext['url'] = MiniHttp::getMiniHost()."api.php?route=upload/sec&access_token=".$token;
        $uploadSecContext['path'] = $miniyunPath;
        $response['upload_context'] = $uploadContext;
        $response['sec_context'] = $uploadSecContext;
        return $response;
    }
}

