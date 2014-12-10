<?php
/**
 * 工具类 
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniUtil{
    /**
     * 连接路径,并格式化为标准路径
     * @return string
     */
    public static function joinPath(){
        $args = func_get_args();
        $path = "/".implode("/",$args);
        $path = str_replace("\\","/",$path);
        while(!($pos=strpos($path,"//")===false)){
            $path = str_replace("//","/",$path);
        }
        return $path;
    }

    /**
     * 获得相对根目录的路径
     * /1/abc/bcd/1.png转化为/abc/bcd/1.png
     * @param $path
     * @return string
     */
    public static function getRelativePath($path){
        $parts = explode("/",$path);
        $path = "/".implode("/",array_slice($parts,2));
        return $path;
    }
    /**
     * 根据文件路径获得文件名称
     * @param $path
     * @return string
     */
    public static function getFileName($path){
        $info = pathinfo($path);
        return $info["basename"];
    }
    /**
     * 获得相对根目录的绝对路径
     * /abc/bcd/1.png转化为/1/abc/bcd/1.png
     * @param $userId
     * @param $path
     * @return string
     */
    public static function getAbsolutePath($userId,$path){
       return self::joinPath($userId,$path);
    }
    /**
     * 根据文件名获得扩展名
     * @param $fileName
     * @return string
     */
    public static function getFileExtension($fileName){
        $parts = explode(".",$fileName);
        if(count($parts)>1){
            $ext = $parts[count($parts)-1];
            $ext = strtolower($ext);
            return $ext;
        }
        return NULL;
    }
    /**
     * 创建url地址
     * @param $url
     * @return string
     */
    public static function createUrl($url){
        return Yii::app()->createUrl($url);
    }

    /**
     * 获得随机字符串
     * @param int $length
     * @return string
     */
    public static function randomString($length = 40){
        static $str = "abcdefghijklmnopqrstuvwxyz0123456789";
        $rand= "";
        for($i=0; $i<$length; $i++) {
            $rand.= $str[mt_rand() % strlen($str)];
        }
        return $rand;
    }
    /**
     * 生成随机数
     */
    public static function random($length, $pool = '') {
        $random = '';

        if (empty($pool)) {
            $pool    = 'abcdefghijklmnopqrstuvwxyz';
            $pool   .= '0123456789';
            $pool   .= 'ABCDEFGHIJKLMNPOPQRSTUVWXYZ';
        }

        srand ((double)microtime()*1000000);

        for($i = 0; $i < $length; $i++)
        {
            $random .= substr($pool,(rand()%(strlen ($pool))), 1);
        }

        return $random;
    }
    /**
     * 计算文件的sha1值,并返回
     *
     * @param string $file - 文件路径
     * @return string sha1 - 文件sha1计算的值
     */
    public static function getFileHash($file) {
        $hashAlgorithm = new MHashComponent($file);
        return $hashAlgorithm->getHash();
    }
    /**
     *
     * 按照密码规则进行签名操作
     */
    public static function signPassword($password, $salt){
        $md5password =  md5(md5($password).$salt);
        return $md5password;
    }
    /**
     * 對稱加密
     */
    public static function encrypt($text, $key = KEY){
        $crypt_des = new Crypt_DES();
        $crypt_des->setKey($key);
        $crypt_des->setIV($key);
        return base64_encode($crypt_des->encrypt($text));
    }
    /**
     * 将int(10) 转 时间
     * 5分钟前 / 3小时前 / 3天前
     * @param string $time
     * @param string $format
     * @return string
     */
    public static function formatTime ($time, $format="Y-m-d H:i:s")
    {
        //1320198779
        // 86400
        $language = Yii::app()->getLanguage();
        if($language && $language == 'en'){
            $format = $format == "Y-m-d H:i:s" ? "m/d/Y h:iA" : "Y-m-d H:i:s";
        }
        $time = (int) $time;
        $now = time();
        $ctime = $now - $time;
        if ($ctime >= 0) {
            //
            // 早些的时间
            //
            if (($ctime - 3600) < 0) {
                //
                // 小于5分钟
                //
                if($ctime>60){
                    $data = (int) ($ctime /
                        60);
                    $data === 0 ? $data = Yii::t('front_common', 'cutil_just_a_minute_ago', array('{how}'=>'0')) : $data = Yii::t('front_common', 'cutil_minute_ago', array('{how}'=>$data));
                }else{
                    $data =  Yii::t('front_common', 'cutil_second_ago', array('{how}'=>$ctime));
                }
            } else
                if (($ctime - 86400) < 0) {
                    //
                    // 小于24小时
                    //
                    $data = (int) ($ctime /
                        3600);
                    $data === 0 ? $data = Yii::t('front_common', 'cutil_hour_ago', array('{how}'=>'1')) : $data = Yii::t('front_common', 'cutil_hour_ago', array('{how}'=>$data));
                } else
                    if (($ctime - 259200) < 0) {
                        //
                        // 小于3天
                        //
                        $data = (int) ($ctime /86400);
                        $data === 0 ? $data = Yii::t('front_common', 'cutil_day_ago', array('{how}'=>'1')) : $data = Yii::t('front_common', 'cutil_day_ago', array('{how}'=>$data));;
                    } else {
                        $data = date($format, $time);
                    }
        } else {
            $data = date($format, $time);
        }


        return $data;
    }
    /**
     * 将byte 转为大小
     * kb mb gb
     * @param int $size
     * @param int $dec
     * @return string
     */
    public static function formatSize ($size = 1024, $dec = 2)
    {
        $a = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
        $pos = 0;
        if ($size < 1024) {
            return $size . " " . $a[$pos];
        }
        while ($size >= 1024) {
            $size /= 1024;
            $pos ++;
        }
        return round($size, $dec) . " " . $a[$pos];
    }
    /**
     * 获得随机字符串
     */
    public static function genRandomString ($length = 64)
    {
        $characters = "0123456789QWERTYUIASDFGHJKLZXCVBNM:abcdefghijklmnopqrstuvwxyz!@#[]|";
        $string = "";
        for ($p = 0; $p < $length; $p ++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
    /**
     * 方法描述：随机生成文件明
     */
    public static function getRandomName($length = 40){
        return MiniUtil::getEventRandomString($length);
    }
    /**
     * 方法描述：随机生成字符串
     */
    public static function getEventRandomString($length = 40){
        static $str = "abcdefghijklmnopqrstuvwxyz0123456789";
        $rand= "";
        if ($length > 14)
            $length -= 14;

        for($i=0; $i<$length; $i++) {
            $rand.= $str[mt_rand() % strlen($str)];
        }

        // 时间长度为14位
        $t     = microtime(true)*10000;
        $rand .= sprintf("%014.0f", $t);
        return $rand;
    }
    /**
     *
     * 递归删除目录
     */
    public  static function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
    /**
     * 生成设备UUID
     */
    public static function getDeviceUUID($deviceInfo, $deviceType, $deviceName, $userId) {
        return md5("{$userId}_{$deviceType}_{$deviceInfo}_{$deviceName}_RGeavfnK8GMjBjDQ");
    }
    /**
     * 获取当前的时间信息
     * @return 当前日期信息
     */
    public static function getCurrentTime() {
        $datetime = date("Y-m-d H:i:s");
        return $datetime;
    }
    public static function sendResponse($status = 200, $body = '', $content_type = 'text/html')
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . self::getStatusCodeMessage($status);
        // 设置状态
        header($status_header);
        // 设置Content-type
        header('Content-type: ' . $content_type);

        // 如果body不为空则返回body
        if($body != '')
        {
            // 发送返回值
            echo $body;
            exit;
        }
        // 需要创建返回值，当body为空的时候
        else
        {
            // 创建body的信息
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch($status)
            {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // servers don't always have a signature turned on (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

            // this should be templatized in a real-world solution
            $body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
            <html>
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
            <title>' . $status . ' ' . self::getStatusCodeMessage($status) . '</title>
            </head>
            <body>
            <h1>' . self::getStatusCodeMessage($status) . '</h1>
            <p>' . $message . '</p>
            <hr />
            <address>' . $signature . '</address>
            </body>
            </html>';

            echo $body;
            exit;
        }
    }
    /**
     * 将字符串按照每两个字符组成一个路径
     *
     * e.g 1234567890 => 12/34/56/78/1234567890
     * @param string $str
     * @return string $path
     */
    public static function getPathBySplitStr($str) {
        //
        // 每两个字符分割
        //
        $parts = str_split(substr($str,0,8), 2);

        $path = join("/", $parts);
        $path = $path . "/" . $str;
        return $path;
    }
    /**
     * 方法描述：输出文件流
     * 参数：
     *   $path         - 文件绝对路径
     */
    public static function outContent($filePath, $contentType, $fileName,$forceDownload=true) {
        $options = array();
        $options['saveName']  = $fileName;
        $options['mimeType']  = $contentType;
        $options['terminate'] = false;
        if (self::xSendFile($filePath, $options)) {
            return true;
        }

        $dataObj = Yii::app()->data;
        $size = $dataObj->size( $filePath );
        //兼容iPhone/iPad直接打开
        if($contentType==="application/mspowerpoint"){
            $contentType = "application/powerpoint";
        }
        if($contentType==="application/msexcel"){
            $contentType = "application/excel";
        }
        if($contentType==="application/msword"){
            $contentType = "application/msword";
        }
        Header ( "Content-type: $contentType" );
        Header ( "Cache-Control: public" );
        Header ( "Content-length: " . $size );
        $encodedFileName = urlencode ( $fileName );
        $encodedFileName = str_replace ( "+", "%20", $encodedFileName );
        $ua = isset($_SERVER ["HTTP_USER_AGENT"]) ? $_SERVER ["HTTP_USER_AGENT"] : NULL;
        // 处理下载的时候的文件名
        if($forceDownload){
            if (preg_match ( "/MSIE/", $ua )) {
                header ( 'Content-Disposition: attachment; filename="' . $encodedFileName . '"' );
            } elseif (preg_match ( "/Firefox\/8.0/", $ua )){
                header ( 'Content-Disposition: attachment; filename="' . $fileName . '"' );
            } else if (preg_match ( "/Firefox/", $ua )) {
                header ( 'Content-Disposition: attachment; filename*="utf8\'\'' . $fileName . '"' );
            } else {
                header ( 'Content-Disposition: attachment; filename="' . $fileName . '"' );
            }
        }
        if (isset ( $_SERVER ['HTTP_RANGE'] ) && ($_SERVER ['HTTP_RANGE'] != "") && preg_match ( "/^bytes=([0-9]+)-/i", $_SERVER ['HTTP_RANGE'], $match ) && ($match [1] < $size)) {
            $range = $match [1];
            header ( "HTTP/1.1 206 Partial Content" );
            header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s", $dataObj->mtime ( $filePath ) ) . " GMT" );
            header ( "Accept-Ranges: bytes" );
            $rangeSize = ($size - $range) > 0 ? ($size - $range) : 0;
            header ( "Content-Length:" . $rangeSize );
            header ( "Content-Range: bytes " . $range . '-' . ($size - 1) . "/" . $size );
        } else {
            header ( "Content-Length: $size" );
            header ( "Accept-Ranges: bytes" );
            $range = 0;
            header ( "Content-Range: bytes " . $range . '-' . ($size - 1) . "/" . $size );
        }
        // 下载输出文件内容
        return $dataObj->render_contents($filePath, "", 0);
    }
    /**
     * send file 下载文件,服务器必须支持send file模式
     * @since 0.9.5
     * @param string $filePath   - 文件绝对路径
     * @param array $options
     * @return mix
     */
    private static function xSendFile($filePath, $options) {
        $infoList = explode('/', $_SERVER['SERVER_SOFTWARE']);
        if (empty($infoList)) {
            return FALSE;
        }

        if (defined('X_SEND_FILE') == FALSE || !X_SEND_FILE)
            return FALSE;

        $dataObj = Yii::app()->data;
        $filePath = $dataObj->get_local_path($filePath);

        if(!isset($options['saveName']))
            $options['saveName']=basename($filePath);

        //
        // 不同服务器,不同的
        //
        switch (strtolower($infoList[0])) {
            case 'nginx':
                $options['xHeader'] = 'X-Accel-Redirect';
                $offset   = strlen(BASE);
                $filePath = substr_replace($filePath, '', 0, $offset);
                $filePath = NGINX_SEND_FILE_TAG . $filePath;
                break;
            case 'lighttpd':
                // 1.4的lighttpd使用X-LIGHTTPD-send-file
                if (strpos($infoList[0], '1.4') !== FALSE) {
                    $options['xHeader'] = 'X-LIGHTTPD-send-file';
                    break;
                }
            default:
                $options['xHeader'] = 'X-Sendfile';
                break;
        }
        $addHeaders = array();
        //
        // 处理浏览器兼容
        //
        $encoded_filename = urlencode ( $options['saveName'] );
        $encoded_filename = str_replace ( "+", "%20", $encoded_filename );
        $ua = isset($_SERVER ["HTTP_USER_AGENT"]) ? $_SERVER ["HTTP_USER_AGENT"] : NULL;
        if (preg_match ( "/MSIE/", $ua )) {
            $addHeaders['Content-Disposition'] = 'attachment; filename="' . $encoded_filename . '"' ;
        } elseif (preg_match ( "/Firefox\/8.0/", $ua )){
            $addHeaders['Content-Disposition'] = 'attachment; filename="' . $options['saveName'] . '"' ;
        } else if (preg_match ( "/Firefox/", $ua )) {
            $addHeaders['Content-Disposition'] = 'attachment; filename*="utf8\'\'' . $options['saveName'] . '"';
        } else {
            $addHeaders['Content-Disposition'] = 'attachment; filename="' . $options['saveName'] . '"' ;
        }
        $options['addHeaders'] = $addHeaders;

        self::X_SendFile($filePath, $options);

        return TRUE;
    }

    /**
     *
     * send file {@see CHttpRequest::xSendFile}
     * @since 0.9.5
     * As this header directive is non-standard different directives exists for different web servers applications:
     * <ul>
     * <li>Apache: {@link http://tn123.org/mod_xsendfile X-Sendfile}</li>
     * <li>Lighttpd v1.4: {@link http://redmine.lighttpd.net/wiki/lighttpd/X-LIGHTTPD-send-file X-LIGHTTPD-send-file}</li>
     * <li>Lighttpd v1.5: X-Sendfile {@link http://redmine.lighttpd.net/wiki/lighttpd/X-LIGHTTPD-send-file X-Sendfile}</li>
     * <li>Nginx: {@link http://wiki.nginx.org/XSendfile X-Accel-Redirect}</li>
     * <li>Cherokee: {@link http://www.cherokee-project.com/doc/other_goodies.html#x-sendfile X-Sendfile and X-Accel-Redirect}</li>
     * </ul>
     * @param string $filePath
     * @param array $options
     *
     */
    private static function X_SendFile($filePath, $options) {
        if(!isset($options['forceDownload']) || $options['forceDownload'])
            $disposition='attachment';
        else
            $disposition='inline';

        if(!isset($options['saveName']))
            $options['saveName']=basename($filePath);

        if(!isset($options['mimeType']))
        {
            if(($options['mimeType']=CFileHelper::getMimeTypeByExtension($filePath))===null)
                $options['mimeType']='text/plain';
        }

        if(!isset($options['xHeader']))
            $options['xHeader']='X-Sendfile';

        if($options['mimeType'] !== null)
            header('Content-type: '.$options['mimeType']);
        header('Content-Disposition: '.$disposition.'; filename="'.$options['saveName'].'"');
        if(isset($options['addHeaders']))
        {
            foreach($options['addHeaders'] as $header=>$value)
                header($header.': '.$value);
        }
        header(trim($options['xHeader']).': '.$filePath);

        if(!isset($options['terminate']) || $options['terminate'])
            Yii::app()->end();
    }

}