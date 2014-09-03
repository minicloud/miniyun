<?php
/*
* Plugin Name: 迷你存储节点管理
* Plugin URI: http://www.miniyun.cn
* Description: 管理多个迷你存储节点的插件，包括：新增、修改、拉上/拉下迷你存储节点。什么是迷你存储，请<a href="http://help.miniyun.cn/ministor" style="color:#21759B;" target="_blank">“点击这里”</a><br> 前往 <a href="Yii::t('miniStoreModule.I18N', '#href')" style="color:#21759B;">“迷你存储节点设置”</a>页面进行设置
* Author: MiniYun
* Version: 1.2.0
* Author URI: http://www.miniyun.cn
*/


/**
 *
 * Mini Store
 *
 */
class MiniStoreModule extends MiniPluginModule {
    /**
     * 存储到db中的标志，这里还是保留了store_id，避免对db结构的冲击
     * @var string
     */
    public static $versionMetaKey = "store_id";
    /**
     *
     * @see CModule::init()
     */
    public function init()
    {
        $this->setImport(array(
                'miniStore.service.*',
        		'miniStore.models.*',
                'miniStore.cache.model.*',
        ));

        //为miniStore存储源方式增加参数
        add_filter("account_info_add",           array($this, "getMiniStoreInfo"));
        //为miniStore存储源方式增加参数
        add_filter("api_info_add",               array($this, "getMiniStoreInfo"));
        // 返回web端上传地址
        add_filter("upload_url",                 array($this, "getUploadUrl"));
        // 返回web端上传地址
        add_filter("third_store_upload_url",     array($this, "getThirdStoreUploadUrl"));
        // 返回客户端上传所需要的参数
        add_filter("upload_params",              array($this, "getClientUploadParams"));
        // web端下载地址
        add_filter("web_download_url",           array($this, "getWebDownloadUrl"));
        add_filter("location_down_load",         array($this, "getWebDownloadUrl"));
        // 返回缩略图地址
        add_filter("image_thumbnails_exist",     array($this, "getImageThumbnails"));
         // 组装缩略图地址
        add_filter("image_thumbnails",           array($this, "getImageThumbnails"));
        // meta返回值二次处理，针对客户端上传扩展缩略图信息
        add_filter('meta_add',                   array($this, 'createFileMeta'));
        //附加磁盘空间大小显示
        add_action("show_local_disk_space",      array($this, "showLocalDiskSpace"));
    }
    /**
     *
     * 增加info返回值, 用于客户端api/info时, 适配上传下载逻辑
     *   hash :md5 表示用md5计算hash
     *   hashWhole:true表示是否计算整个文件的hash值
     *   source:s3 表示用的是什么源
     *   isBreakpoint表示服务器是否支持断点下载
     */
    public function getMiniStoreInfo($accountInfo){
        $dataMode                  = array();
        $dataMode["source"]        = "miniStor";
        $dataMode["disabled"]      = 0;
        $dataMode["isBreakpoint"]  = intval(true);
        $dataMode["hash"]          = "sha1";
        $dataMode["hashWhole"]     = intval(true);
        $accountInfo["dataMode"]   = $dataMode;
        return $accountInfo;
    }
    /**
     * 返回上传地址
     * @return string
     */
    public function getThirdStoreUploadUrl(){

        $node = MiniStoreNode::getInstance()->getBestNode();
        if (empty($node)) {
            return NULL;
        }
        $source = "miniStore";
        $host   = "http://" . $node["ip"].":".$node["port"] . "/api.php/1/browser_files";
        return array("source"=>$source,"host"=>$host);
    }
	/**
     * 返回上传地址
     * @return string
     */
    public function getUploadUrl(){ 
        
        $node = MiniStoreNode::getInstance()->getBestNode();
        if (empty($node)) {
            return NULL;
        }
        
        $uri  = "http://" . $node["ip"].":".$node["port"] . "/api.php/1/web_files";
        return $uri;
    }
    /**
     * 获取创建文件实际用户路径及权限验证
     * @param string $uri
     * @return string $file_path
     * @throws MFilesException
     */
    private function checkUserPathAndPermission($uri) {
        
        $uri           = str_replace("/api.php/1", "", $uri);
        $url_manager   = new MUrlManager();
        $path          = $url_manager->parsePathFromUrl($uri);
        $root          = $url_manager->parseRootFromUrl($uri);
        if ($path == false || $root == false) {
            Yii::log(MConst::PATH_ERROR . 'path : ' . __FILE__, CLogger::LEVEL_WARNING, "miniyun.miniStor");
            throw new MFilesException(MConst::PATH_ERROR, MConst::HTTP_CODE_411);
        }
        $path          = "/" . $path;
        // 检查是否在共享文件夹内， 如果在共享文件夹内，则进行权限检查
        //
        $user          = MUserManager::getInstance()->getCurrentUser ();
        $user_id       = $user["user_id"];
        $share_filter  = MSharesFilter::init();
        if ($share_filter->handlerCheck($user_id, $path)){
            $user_id   = $share_filter->master;
            $path      = $share_filter->_path;
            $file_path = "/".$user_id.$path;
            $share_filter->hasPermissionExecute($file_path, MPrivilege::FILE_CREATE);
        } else {
            $file_path = "/".$user_id.$path;
        }
        
        return $file_path;
    }
    
    /**
     * 通过device id和uri, 为dataSystem编码签名, 用于验证客户端, 签名为了miniyun验证用户设备信息
     */
    private function getSign($uri, $access_token) {

        Yii::trace('deal client sign ,path : ' . __FILE__, "miniyun.miniStore");
        $token = MiniToken2::getInstance()->getAccessInfo2($access_token);
        if (empty($token)) {
            return;
        }
        $client = MiniClient2::getInstance()->getClient2($token["client_id"]);
        $client_id = $token['client_id'];
        $client_secret = $client['client_secret'];
        //
        // iis服务器，处理编码
        //
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'iis') !== false)
        {
            // iis  urlEncode() 或者 rawurlencode()，二者的区别是前者把空格编码为 '+'，而后者把空格编码为 '%20'
            $uri = $this->urlDecode($uri);
            $uri = mb_convert_encoding($uri, "UTF-8", "gbk");
        }
        else {
            $uri = $this->urlDecode($uri);
        }

        $index = strpos($uri, "?");
        if ($index){
            $uri = substr($uri, 0, $index);
        }
        $uri = $this->urlEncode($uri);
        $uri = str_replace("%2F", "/", $uri);
        $uri = str_replace("%2f", "/", $uri);

        $url = "{$uri}?access_token={$access_token}&client_id={$client_id}&client_secret={$client_secret}";
        Yii::trace('make client sign ,path : ' . $url . __FILE__, "miniyun.miniStor");
        return md5($url);
    }
    /**
     * Decode a string according to RFC3986.
     * Also correctly decodes RFC1738 urls.
     *
     * @param string s
     * @return string
     */
    private function urlDecode ( $s )
    {
        if ($s === false)
        {
            return $s;
        }
        else
        {
            return rawurldecode($s);
        }
    }
    /**
     * Encode a string according to RFC3986.
     * Also correctly decodes RFC1738 urls.
     *
     * @param string s
     * @return string
     */
    private function urlEncode ( $s )
    {
        if ($s === false)
        {
            return $s;
        }
        else
        {
            return str_replace('~', '%7E', rawurlencode($s));
        }
    }
    /**
     *
     * api部分上传参数
     */
    public function getClientUploadParams(){

        $response = array();
        $uri      = $_SERVER['REQUEST_URI'];
        // 去问号
        if ($pos = strpos($uri, '?')){
            $uri = substr($uri, 0, $pos);
        }
        // 完整路径
        $filePath = $this->checkUserPathAndPermission($uri);

        // 多server处理
        $node = MiniStoreNode::getInstance()->getBestNode();;
        if (empty($node)){
            # 这里由于storage异常, 返回异常,阻止上传
            throw new MFilesException(MConst::NOT_FOUND, MConst::HTTP_CODE_442);
        }
        // request 返回param data参数
        $request = array(); 
        $request['access_token']         = $_REQUEST['access_token'];
        // dataSystem 验证机制
        $request['data_token']           = $node['safe_code'];
        $request['hash']                 = $_REQUEST['hash'];
        $request['size']                 = $_REQUEST['size'];
        $request['offset']               = $_REQUEST['offset'];
        $request['overwrite']            = $_REQUEST['overwrite'];
        $request['parent_rev']           = $_REQUEST['parent_rev'];
        $request['host']                 = Yii::app()->params['app']['absoluteUrl'];
        $url                             = str_replace('/api.php/1/paramsdata/miniyun', '/c.php/1/plugin/miniStore/appFileMeta', $uri);
        $sign                            = $this->getSign($url, $request['access_token']);
        
        $request["path"]                 = $filePath;
        $request["sign"]                 = $sign;
        $response["request"]             = $request;
        //针对二级域名，把二级目录进行替换
        $pos = strpos($uri,"/api.php");
        $uri = substr($uri,$pos,strlen($uri)-$pos);
        $response["uri"]                 = "http://" . $node['ip'].":".$node['port'] . str_replace('paramsdata', 'files_put', $uri);
        $response["data_id"]             = $node['id'];
        $response["uploadType"]          = "POST";
        $response["isBreakpoint"]        = intval(true);
        $response["source"]              = "miniStor";
        
        
        $response["header"]              = Array();
        $analyze = array();
        $analyze["style"]                = "code";    //第三方源返回值的类型 xml,json,code
        $success = array();
        $success["successCode"]          = "200";     //成功的code如果是多个成功标志（200|201）
        
        $analyze["status"]               = $success;
        $response["analyzeResult"]       = $analyze;
        return $response;
    }
    /**
     *
     * 生成web端下载地址
     */
    public function getWebDownloadUrl($value){
        $hash          = $value["hash"];
        $filename      = rawurlencode(rawurldecode($value["filename"]));
        // 获取文件data url
        $version       = MiniVersion::getInstance()->getBySignature($hash);
        if ($version === NULL) {
            Yii::log(MConst::NOT_FOUND . 'path : ' . __FILE__, CLogger::LEVEL_WARNING, "miniyun.miniStore");
            throw new MFilesException(MConst::NOT_FOUND, MConst::HTTP_CODE_404);
        }
        // 输出0字节文件
        if ($version['file_size'] == 0) {
            return;
        }
        // $verMeta 为 miniStore
        $versionMeta     = MiniVersionMeta::getInstance()->getMeta($version["id"],MiniStoreModule::$versionMetaKey);
        if ($versionMeta === NULL) {
            return;
        }
        $node = MiniStoreNode::getInstance()->getByID($versionMeta['meta_value']);
        if ($node === NULL) {
            Yii::log(MConst::NOT_FOUND . 'path : ' . __FILE__, CLogger::LEVEL_WARNING, "miniyun.miniStor");
            throw new MFilesException(MConst::NOT_FOUND, MConst::HTTP_CODE_404);
        }
        #这里是MiniStore的下载入口地址
        $host = $node["ip"] . ":" . $node["port"];
        $path = "http://" . $host . "/api.php/1/files/miniyun";
		$uri  = rawurlencode("name") . '=' . rawurlencode($filename) . '&';
        $uri .= rawurlencode("hash") . '=' . rawurlencode($hash) . '&';
        $uri .= rawurlencode("safe_code") . '=' . rawurlencode($node['safe_code']). '&';
        $forceDownload = 1;
        $key = "forceDownload";
        if(array_key_exists($key,$value)){
            if($value[$key]===false){
                $forceDownload = 0;
            }
        }
        $uri .= rawurlencode("force_download") . '=' . rawurlencode($forceDownload);
        return $path . '?' . urldecode($uri);
    }

    /**
     * 适配迷你存储解析缩略图的规则
     * @param $width
     * @param $height
     * @return string
     */
    private  function getThumbnailSize( $width, $height ) {
        if ($width == "48"){
            $size = "small";
        } elseif ($width == "80"){
            $size = "normal";
        } elseif ($width == "128"){
            $size = "large";
        }else{
            $size = $width."X".$height;
        }
        return $size;
    }
    /**
     * dataSystem 返回缩略图地址
     */
    public function getImageThumbnails($value){
        
        Yii::trace('return thumbnail path ,path : ' . __FILE__, "miniyun.miniStor");
        $hash        = $value["hash"];
        if (empty($value["size"])) {
            $width   = $value["width"];
            $height  = $value["height"];
            $size    = $this->getThumbnailSize($width, $height);
        } else {
            $size    = $value["size"];
        }
        $version     = MiniVersion::getInstance()->getBySignature($hash);
        // 获取miniStore的id
        $versionMeta = MiniVersionMeta::getInstance()->getMeta($version["id"], MiniStoreModule::$versionMetaKey);
        if ($versionMeta === NULL) {
            return null;
        }
        $node       = MiniStoreNode::getInstance()->getByID($versionMeta['meta_value']);
        if ($node === NULL) {
            Yii::trace(MConst::NOT_FOUND . 'path : ' . __FILE__, "miniyun.miniStor");
            return NULL;
        }
        $url = 'http://' .$node["ip"].":".$node["port"]."/api.php/1/thumbnail?hash=".$value["hash"]."&size=".$size;
        return $url;
    }
    /**
     * 创建文件meta信息,缩略图,地理信息等等
     */
    public function createFileMeta($response) {
        
        $versionId       = Null;
        $hash            = Null;
        $latitude        = Null;
        $longitude       = Null;
        
        $safeCode           = $_REQUEST["safe_code"];
        if (empty($safeCode)){
            return $response;
        }
        
        if (isset($_REQUEST["hash"])) {
            $hash         = $_REQUEST["hash"];
        }
        
        if (isset($response['rev'])) {
            $versionId   = $response["rev"];
        }
        
        if (empty($versionId) || empty($hash)) {
            return $response;
        }
        
        if (isset($_REQUEST['thumbnail'])) {
            $thumbnail     = $_REQUEST["thumbnail"];
        }
        
        if (isset($_REQUEST['latitude'])) {
            $latitude     = $_REQUEST["latitude"];
        }
        
        if (isset($_REQUEST['longitude'])) {
            $longitude    = $_REQUEST["longitude"];
        }

        $node            = MiniStoreNode::getInstance()->getBySafeCode($safeCode);
        // 给version信息加入地址信息
        MiniVersionMeta::getInstance()->create($versionId, MiniStoreModule::$versionMetaKey, $node["id"]);
        if (empty($thumbnail) === false) {
            $this->createExif($versionId, $latitude, $longitude);
        }
        
        return $response;
    }
    /**
     *
     * 保存图片经纬度信息，如果请求参数存在
     */
    private function createExif($versionId, $latitude, $longitude) {
        if (empty($versionId) || empty($latitude) || empty($longitude)) {
            return;
        }
        MiniExif::getInstance()->create($versionId, $latitude, $longitude);
    }


	/**
     * 
     * 显示磁盘大小分析
     * 
     * @since 1.0.0
     */
    function showLocalDiskSpace(){
         return false;
    }
}

