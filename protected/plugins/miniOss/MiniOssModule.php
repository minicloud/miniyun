<?php
/**
 * 阿里云OSS
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
require_once __DIR__.'/aliyun-oss-php-sdk/autoload.php';
use OSS\OssClient;
class MiniOssModule extends MiniPluginModule { 
    /**
     *
     * @see CModule::init()
     */
    public function init()
    {
        $this->setImport(array(
            "miniOss.biz.*",
            "miniOss.cache.*",
            "miniOss.models.*",
            "miniOss.service.*",
        ));
        add_filter("plugin_info",array($this, "setPluginInfo")); 
        //文件下载
        add_filter("file_download_url",array($this, "fileDownloadUrl"));
        //获得文件内容
        add_filter("file_content",array($this, "fileContent"));
        //图片缩略图
        add_filter("image_path",array($this,"imagePath"));
        //文件上传
        add_filter("upload_start",array($this,"start"));
        //文件秒传
        add_filter("upload_sec",array($this,"sec"));
        //文件上传结束
        add_filter("upload_end",array($this,"end"));
    }
    /**
     * 获得文件的缩略图
     * @param array $params
     * @return string
     */
    public  function imagePath($params){
        $signature = $params["signature"];
        $saveFolder = MINIYUN_PATH."/assets/miniOss/";
        $filePath = $saveFolder.$signature;
        if(!file_exists($filePath)){
            if(!file_exists($saveFolder)){
                mkdir($saveFolder);
            }
            //把文件下载到本地
            $url = PluginMiniStoreNode::getInstance()->getDownloadUrl($signature,"image.jpg","application/octet-stream",1);
            file_put_contents($filePath,file_get_contents($url));
        }
        return $filePath;
    }
    /**
     * 获得文件下载地址
     * @param array $params
     * @return string
     */
    public function fileDownloadUrl($params){         
        $hash = $params["signature"];
        $fileName = $params["file_name"];
        $mimeType = $params["mime_type"];
        $forceDownload = $params["force_download"];
        $version = MiniVersion::getInstance()->getBySignature($hash);
        if($version){
            $bucketHostMeta = MiniVersionMeta::getInstance()->getMeta($version['id'],'bucket_host');
            $bucketPathMeta = MiniVersionMeta::getInstance()->getMeta($version['id'],'bucket_path'); 
            if($bucketHostMeta && $bucketPathMeta){
                $endpoint = 'oss-cn-hangzhou.aliyuncs.com';
                $path = $bucketPathMeta['meta_value'];
                $accessKeyId = "WPkFoYluMvInP9Eu";
                $accessKeySecret = "967NOdLushFCkkbKcnnnWi7J1S5lAy"; 
                try {
                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                } catch (OssException $e) {
                    print $e->getMessage();exit;
                }
                $signedUrl = $ossClient->signUrl('minicloud-test', $path, 3600);
                //下载文件
                header( "HTTP/1.1 ".MConst::HTTP_CODE_301." Moved Permanently" );
                header( "Location: ". $signedUrl );
                exit; 
            }
        } 
    }
    /**
     * 获得文件内容
     * 把迷你存储的文件缓存到本地
     * @param string $signature
     * @return string
     */
    public function fileContent($signature){
        $saveFolder = MINIYUN_PATH."/assets/miniStore/";
        $filePath = $saveFolder.$signature;
        if(!file_exists($filePath)){
            if(!file_exists($saveFolder)){
                mkdir($saveFolder);
            }
            //把文件下载到本地
            $url = PluginMiniStoreNode::getInstance()->getDownloadUrl($signature,"image.jpg","application/octet-stream",1);
            file_put_contents($filePath,file_get_contents($url));
        }
        return file_get_contents($filePath);
    }
     
    /**
     *获得插件信息
     * @param $plugins 插件列表
     * {
     *   "miniDoc":{}
     * }
     * @return array
     */
    public function setPluginInfo($plugins){
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
               "type"=>'oss',
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
                $version = MiniVersion::getInstance()->create($hash, $size, $type);
                $source = MiniHttp::getParam('source','oss');
                if($source==='oss'){
                    $bucketHost = MiniHttp::getParam('bucket_host','');
                    $bucketPath = MiniHttp::getParam('bucket_path','');
                    MiniVersionMeta::getInstance()->create($version["id"],"bucket_host",$bucketHost);
                    MiniVersionMeta::getInstance()->create($version["id"],"bucket_path",$bucketPath);
                }else{
                    //TODO来自迷你存储
                } 
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
        $user = MUserManager::getInstance()->getCurrentUser();  
        $path = MiniHttp::getParam('path','/'); 
        $token = MiniHttp::getParam('access_token','');
        //存储路径
        $miniyunPath = $path;
        $bucketPath = '迷你云/'.$user['user_name'].$path;
        //OSS相关信息
        $id= 'WPkFoYluMvInP9Eu';
        $key= '967NOdLushFCkkbKcnnnWi7J1S5lAy';
        $bucketHost = 'minicloud-test.oss-cn-hangzhou.aliyuncs.com'; 
        $callbackUrl = MiniHttp::getMiniHost()."/api.php"; 
        //回调地址是阿里云接收文件成功后，反向调用迷你云的地址报竣
        //其中access_token/route/bucket_url是回调需要的地址
        //TODO 需要进行签名
        $callback_param = array('callbackUrl'=>$callbackUrl, 
                     'callbackBody'=>'source=oss&access_token='.$token.'&route=upload/end&bucket_host='.$bucketHost.'&path='.$miniyunPath.'&bucket_path=${object}&size=${size}&hash=${etag}&mime_type=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}', 
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
        $uploadContext['host'] = 'https://'.$bucketHost;
        $uploadContext['policy'] = $base64_policy;
        $uploadContext['signature'] = $signature;
        $uploadContext['expire'] = $end;
        $uploadContext['callback'] = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $uploadContext['path'] = $bucketPath;

        //文件秒传上传策略
        $uploadSecContext = array();
        $uploadSecContext['url'] = MiniHttp::getMiniHost()."/api.php?route=upload/sec&access_token=".$token;
        $uploadSecContext['path'] = $miniyunPath;
        $response['upload_context'] = $uploadContext;
        $response['sec_context'] = $uploadSecContext;
        return $response;
    }
}

