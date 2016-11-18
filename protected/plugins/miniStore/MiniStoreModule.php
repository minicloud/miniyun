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
        //文件上传
        add_filter("upload_start",array($this,"start"));
        //文件秒传
        add_filter("upload_sec",array($this,"sec"));
        //文件上传结束
        add_filter("upload_end",array($this,"end"));  
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
    *获得文件路径
    */
    private function getKey(){
        $policyBase64 = MiniHttp::getParam('policy','');
        if(empty($policyBase64)){
            return null;
        }
        $policyStr = base64_decode($policyBase64); 
        $policy = json_decode($policyStr);
        $conditions = $policy->{'conditions'};
        $bucketPath = $conditions[1][2]; 
        return $bucketPath;
    }
    /**
    *判断签名是否完整
    */
    private function validSignature(){
        //检查node是否正确
        $nodeKey = MiniHttp::getParam('node_key',''); 
        $node = PluginMiniStoreNode::getInstance()->getByKey($nodeKey);
        if(empty($node)){
            return null;
        } 
        //检查签名是否正确
        $paramSignature = MiniHttp::getParam('signature','');
        if(empty($paramSignature)){
            return null;
        } 
        //某些情况下base64中的+会替换为空格
        $paramSignature = str_replace(' ','+',$paramSignature);
        $policyBase64 = MiniHttp::getParam('policy','');
        $policyBase64 = str_replace(' ','+',$policyBase64);
        $signature = base64_encode(hash_hmac('sha1', $policyBase64, $node['secret'], true));
        if($paramSignature!==$signature){ 
            return null;
        } 
        return $node;
    }
    /**
    *文件上传结束
    */
    public function end(){       
        $user = MUserManager::getInstance()->getCurrentUser();
        $_SESSION['company_id'] = $user['company_id']; 
        $hash = MiniHttp::getParam('hash',''); 
        $hash = strtolower($hash);
        //防止重复文件通过网页上传，生成多条记录
        if(!empty($hash)){ 
            $node = $this->validSignature();
            if(!empty($node)){
                $version = MiniVersion::getInstance()->getBySignature($hash); 
                //创建version/versionMeta数据 
                if(empty($version)){
                    $nodeId = $node['id'];           
                    $type =MiniHttp::getParam('mime_type','');
                    $size = MiniHttp::getParam('size',0);                    
                    $bucketPath = $this->getKey();
                    $version = MiniVersion::getInstance()->create($hash, $size, $type);
                    MiniVersionMeta::getInstance()->create($version["id"],"store_id",$nodeId);
                    MiniVersionMeta::getInstance()->create($version["id"],"bucket_path",$bucketPath);
                    //更新迷你存储节点状态，把新上传的文件数+1
                    PluginMiniStoreNode::getInstance()->newUploadFile($node);                    
                }
                //创建用户相关元数据 执行文件秒传逻辑
                $filesController = new MFileSecondsController();
                $filesController->invoke();
                exit;
            }             
        } 
        //返回错误信息  
        http_response_code(409);
        $data = array();
        $data['code']=409;
        $data['error']="bad_request";
        $data['error_description']="invalid singnature";
        echo(json_encode($data));exit;
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
    *把/1/xxx 替换为 /xxx
    */ 
    private function getBucketPath($user,$path){ 
        $prefix = '/'.$user['id'].'/';
        $path = str_replace($prefix,'/', $path);
        $bucketPath = 'minicloud/'.$user['user_name'].$path;
        return $bucketPath;
    }
    /**
    *文件开始上传，先要获得上传需要的上下文信息
    */
    public function start(){
        $user = MUserManager::getInstance()->getCurrentUser();  
        $_SESSION['company_id'] = $user['company_id']; 

        $storeNode = PluginMiniStoreNode::getInstance()->getUploadNode();     
        $path = MiniHttp::getParam('path','/'); 
        $token = MiniHttp::getParam('access_token','');
        //存储路径
        $miniyunPath = $path;
        //把/1/xxx 替换为 /xxx
        $bucketPath = $this->getBucketPath($user,$path);
        //OSS相关信息  
        $bucketHost = $storeNode['host']; 
        $callbackUrl = MiniHttp::getMiniHost()."api.php"; 
        //回调地址是阿里云接收文件成功后，反向调用迷你云的地址报竣        
        $now = time()+$storeNode['time_diff']/1000;
        $expire = 24*60*60; //设置该policy超时时间是24小时. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        
        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition; 

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$bucketPath,3=>$end);
        $conditions[] = $start; 


        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);  
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $storeNode['secret'], true));

        $response = array();
        //其中access_token/route/bucket_url是回调需要的地址 
        $callback_param = array('callbackUrl'=>$callbackUrl, 
                     'callbackBody'=>'access_token='.$token.'&route=upload/end&node_key='.$storeNode['key'].'&signature='.$signature.'&policy='.$base64_policy.'&path='.$miniyunPath.'&key=${object}&size=${size}&hash=${etag}&mime_type=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded");
        //获得文档当前的hash值，当用户上传新版本，此前的文件将被标记为历史版本
        $file = MiniFile::getInstance()->getByFilePath($miniyunPath);
        if(!empty($file)){
            $versionId = $file['version_id'];
            $version = MiniVersion::getInstance()->getVersion($versionId);
            $callback_param['beforeVersionBody'] = 'operator_id='.$user['id'].'&file_id='.$file['id'].'&hash='.$version['file_signature'].'&signature='.$signature.'&policy='.$base64_policy.'&new_path=${path}';
        }
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
               
        //上传策略信息
        $uploadContext = array();
        $uploadContext['accessid'] = $id;
        $uploadContext['host'] = $bucketHost.'api/v1/file/upload?name='.$user['user_name'];
        $uploadContext['policy'] = $base64_policy;
        $uploadContext['signature'] = $signature;
        $uploadContext['expire'] = $end;
        $uploadContext['callback'] = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $uploadContext['path'] = $bucketPath;
        //添加文档转换回掉地址
        $isDoc = MiniUtil::isDoc($bucketPath);
        if($isDoc){
            $callbackParam = array('callbackUrl'=>'http://app.miniyun.cn/api/v1/doc/convert_start', 
                     'callbackBody'=>'token='.$token.'&signature='.$signature.'&policy='.$base64_policy.'&hash=${etag}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded");
            $callbackParamString = json_encode($callbackParam); 
            $uploadContext['doc_convert_start_callback'] = base64_encode($callbackParamString);

            $callbackParam = array('callbackUrl'=>'http://app.miniyun.cn/api/v1/doc/convert_end', 
                     'callbackBody'=>'token='.$token.'&signature='.$signature.'&policy='.$base64_policy.'&success=${success}&hash=${etag}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded"); 
            $callbackParamString = json_encode($callbackParam); 
            $uploadContext['doc_convert_end_callback'] = base64_encode($callbackParamString);
        }else{
            //添加视频转换回掉地址
            $isVideo = MiniUtil::isVideo($bucketPath);
            if($isVideo){
                $callbackParam = array('callbackUrl'=>'http://app.miniyun.cn/api/v1/video/convert_start', 
                     'callbackBody'=>'token='.$token.'&signature='.$signature.'&policy='.$base64_policy.'&hash=${etag}', 
                     'callbackBodyType'=>"application/x-www-form-urlencoded");
                $callbackParamString = json_encode($callbackParam); 
                $uploadContext['video_convert_start_callback'] = base64_encode($callbackParamString);

                $callbackParam = array('callbackUrl'=>'http://app.miniyun.cn/api/v1/video/convert_end', 
                         'callbackBody'=>'token='.$token.'&signature='.$signature.'&policy='.$base64_policy.'&hash=${etag}&success=${success}', 
                         'callbackBodyType'=>"application/x-www-form-urlencoded"); 
                $callbackParamString = json_encode($callbackParam); 
                $uploadContext['video_convert_end_callback'] = base64_encode($callbackParamString);
            }
        } 
        //文件秒传上传策略
        $uploadSecContext = array();
        $uploadSecContext['url'] = MiniHttp::getMiniHost()."api.php?route=upload/sec&access_token=".$token;
        $uploadSecContext['path'] = $miniyunPath;
        $response['upload_context'] = $uploadContext;
        $response['sec_context'] = $uploadSecContext;
        return $response;
    }
}

