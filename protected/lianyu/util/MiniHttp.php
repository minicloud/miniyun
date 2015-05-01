<?php
/**
 * 网络工具类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniHttp{
    /**
     * 获得request的函数
     * @param $paramKey
     * @param $default
     * @return mixed
     */
    public static function getParam($paramKey,$default){
        return Yii::app()->request->getParam($paramKey,$default);
    }

    /**
     * 获得系统参数
     * @param $key
     * @return mixed
     */
    public static function getSystemParam($key){
        return Yii::app()->params["app"][$key];
    }
    /**
     * 判断是否是PC客户端
     */
    public static function isPCClient(){
        $pos = strpos($_SERVER["HTTP_USER_AGENT"],"miniClient");
        if($pos){
            return true;
        }
        return false;
    }
    /**
     * 判断是否在控制台下
     */
    public static function isConsole(){
        if(empty($_SERVER)){
            return true;
        }else{
            return false;
        };
    }
    /**
     * 判断是否是iPhone客户端
     */
    public static function isiPhone(){
        if(MiniHttp::isPCClient()){
            return false;
        }
        $pos = strpos($_SERVER["HTTP_USER_AGENT"],"Darwin");
        if($pos){
            return true;
        }
        return false;
    }
    /**
     * 判断是否windows电脑
     */
    public static function isWindowsOS(){
        $pos = strpos($_SERVER["HTTP_USER_AGENT"],"Windows");
        if($pos){
            return true;
        }
        return false;
    }
    /**
     * 判断是否苹果电脑
     */
    public static function isMacOS(){
        $pos = strpos($_SERVER["HTTP_USER_AGENT"],"Macintosh");
        if($pos){
            return true;
        }
        return false;
    }
    /**
     * 判断是否是Web浏览器
     * 通过referer判断是否是浏览器客户端
     */
    public static function clientIsBrowser(){
        //如是PC客户端，则也遵循浏览器的逻辑
        if(MiniHttp::isPCClient()){
            return true;
        }
        if(array_key_exists("client_id",$_REQUEST)){
            if($_REQUEST["client_id"]==="JsQCsjF3yr7KACyT"){
                return true;
            }else{
                return false;
            }
        }
        if(array_key_exists("HTTP_REFERER",$_SERVER)){
            $refer = $_SERVER["HTTP_REFERER"];
            if(!empty($refer)){
                return true;
            }
        }
        if(array_key_exists("PHPSESSID",$_COOKIE)){
            return true;
        }
        return false;
    }
    /**
     * 获得迷你云Host
     */
    public static function getMiniHost(){
        $serverPort = $_SERVER["SERVER_PORT"];
        $url = "http://";
        if($serverPort==="443"){
            $url = "https://";
        }
        $serverName = $_SERVER["SERVER_NAME"];
		if($serverName==="demo.miniyun.cn"){
		   $serverName = $_SERVER["HTTP_HOST"];
		}
        $url .=$serverName; 
        //计算相对地址
        $documentRoot  = $_SERVER["DOCUMENT_ROOT"];
        $scriptFileName = $_SERVER["SCRIPT_FILENAME"];
        $relativePath = substr($scriptFileName,strlen($documentRoot),strlen($scriptFileName)-strlen($documentRoot));
        $path = dirname($relativePath);
        //兼容Windows服务器，把右斜杠替换为左边斜杠
        $path = str_replace("\\","/",$path);
        if($path!=="/"){
            $path.="/";
        } 
        return $url.$path;
    }
    /**
     * 获得RequestUri,如果是二级目录、三级目录则自动去掉路径前缀
     * @return string
     */
    public static function getRequestUri(){
        $host = self::getMiniHost();
        $host = str_replace("http://","",$host);
        $host = str_replace("https://","",$host);
        $host = str_replace("//","/",$host);
        $info = explode("/",$host);
        $relativePath = "";
        for($i=1;$i<count($info);$i++){
            $relativePath .= "/".$info[$i];
        }
        $requestUri = $_SERVER["REQUEST_URI"];
        //增加过滤//的URL地址
        $requestUri = str_replace("//","/",$requestUri);
        return substr($requestUri,strlen($relativePath),strlen($requestUri)-strlen($relativePath));

    }
    /**
     * 标示是否是离线
     * @return bool
     */
    public static function isOffline(){
        $syncTime = MiniHttp::getCookie("syncTime");
        if(!empty($syncTime) && $syncTime==="-1"){
            return ture;
        }
        return false;
    }

    /**
     * 创建基于index.php的绝对地址
     * @param $url
     * @return string
     */
    public static function createUrl($url){
        $host = self::getMiniHost();
        return $host."index.php/".$url;
    }

    /**
     * 创建基于api.php的绝对地址
     * @param $url
     * @return string
     */
    public static function createApiUrl($url){
        $host = self::getMiniHost();
        return $host."api.php/".$url;
    }

    /**
     * 创建基于a.php的绝对地址
     * @param $url
     * @return string
     */
    public static function createAnonymousUrl($url){
        $host = self::getMiniHost();
        return $host."a.php/1/".$url;
    }

    /**
     * 创建基于a.php的绝对地址
     * @param $url
     * @return string
     */
    public static function createConsoleUrl($url){
        $host = self::getMiniHost();
        return $host."c.php/1/".$url;
    }
    /**
     * 创建基于static的绝对地址
     * @param $url
     * @param $module
     * @return string
     */
    public static function createStaticUrl($url,$module=""){
        $host = self::getMiniHost();
        if(empty($module)){
            return $host."static/".$url;
        }else{
            return $module->assetsUrl."/".$url;
        }
    }

    /**
     * 创建基于static的i18n绝对地址
     * @param $appName
     * @return string
     */
    public static function createI18nUrl($appName){
        $host = self::getMiniHost();
        $language = Yii::app()->language;
        if(!($language=="zh_tw"||$language=="zh_cn"||$language=="en")){
            $language="zh_cn";
        }
        $t = YII_DEBUG ? time() : APP_VERSION;
        return $host."static/liyu/i18n/".$language."/".$appName.".js?t=".$t;
    }

    /**
     * 获得系统代码物理根路径
     * 假如迷你云安装在/Users/jim/code/miniyun/yun/
     * 执行该函数就会获得/Users/jim/code/miniyun/yun/
     */
    public static function getPhysicalRoot(){
        $indexFile = $_SERVER["SCRIPT_FILENAME"];
        $serverPath = dirname($indexFile)."/";
        return $serverPath;
    }

    /**
     * 根据浏览器的版本返回语言
     * @param $name
     * @return null
     */
    public static  function getCookie($name){
        $value = null;
        if(array_key_exists($name,$_COOKIE)){
            $value = $_COOKIE[$name];
        }
        return $value;
    }
    /**
     * 根据文件名获得适配该文件名的icon地址
     * @param $fileName
     * @return string
     */
    public static function getIcon4File($fileName){
        $iconName = "page_white.png";
        $ext = MiniUtil::getFileExtension($fileName);

            if(!empty($ext)){
                $type = "page_white";
                if($ext==="pdf"){
                    $type = "page_white_acrobat";
                }
                if($ext==="psd"){
                    $type = "page_white_paint";
                }
                $mimeTypes = array("c","c++","m","php","java","h","py","js","css","xml","sql");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_code";
                    }
                }
                $mimeTypes = array("zip","rar","7z","gz");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_compressed";
                    }
                }
                $mimeTypes = array("iso");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_dvd";
                    }
                }
                $mimeTypes = array("xls","xlsx");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_excel";
                    }
                }
                $mimeTypes = array("mp4","rm","avi","rmvb","mov","asf","wmv","3gp","flv");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_film";
                    }
                }
                $mimeTypes = array("exe","dll");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_gear";
                    }
                }
                $mimeTypes = array("jpg","jpeg","png","bmp","gif");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_picture";
                    }
                }
                $mimeTypes = array("ppt","pptx");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_powerpoint";
                    }
                }
                $mimeTypes = array("mp3","mid","wav","ape","flac");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_sound";
                    }
                }
                $mimeTypes = array("doc","docx");
                foreach($mimeTypes as $code){
                    if($ext===$code){
                        $type = "page_white_word";
                    }
                }
                $iconName = $type.".png";
            }
        return $iconName;
    }
}