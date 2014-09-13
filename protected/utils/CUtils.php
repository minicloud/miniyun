<?php
/**
 *
 * 工具类,公共方法
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class CUtils{ 
    /**
     * 页面返回: json输出
     * @param bool $success 是否成功
     * @param string $msg 返回文案描述
     * @param string $signature 文件signature
     */
    public static function StandResult($success, $msg, $signature = "0") {
        $json = "";
        if ($signature == null || $signature == "0") {
            $json = array (
                "status" => $success,
                "msg" => $msg,

            );
        } else {
            $json = array (
                "status" => $success,
                "msg" => $msg,
                "signature" => $signature,

            );
        }

        echo json_encode($json);
    }

    /**
     * 创建文件夹
     * @param string $dir 文件夹路径
     * @param int $mode 权限
     * @param bool $recursive
     * @return mixed $value 返回最终需要执行完的结果
     */
    public static function MkDirs($dir, $mode = 0777, $recursive = true) {
        if (is_null($dir) || $dir == "") {
            return false;
        }
        if (is_dir($dir) || $dir == "/") {
            return true;
        }
        if (CUtils::MkDirs(dirname($dir), $mode, $recursive)) {
            return mkdir($dir, $mode);
        }
        return false;
    }

    /**
     *
     * 删除目录
     * 如果有子文件则不删除
     * @param string $dir 文件夹路径
     * @param integer $times - 默认最多只能执行四次
     */
    public static function delDirs($dir, $times = 4) {
        if ($times <= 0)
            return;
        $times -= 1;
        //
        // 判定是否是文件夹
        //
        if (!is_dir($dir)){
            return;
        }

        //
        // 如果没有子文件，可删除
        //
        if (@!rmdir($dir)){
            return;
        }

        // 获取父目录路径
        $parent_path = dirname($dir);

        // 递归调用
        self::delDirs($parent_path, $times);
    }

    /**
     * 删除临时文件
     * @param string $file_name 删除文件的全路径
     * @return mixed $value 返回最终需要执行完的结果
     */
    public static function RemoveFile($file_name) {
        if (strlen($file_name) == 0) {
            return false;
        }
        if (strpos($file_name, BASE) === false) {
            return false;
        }
        if (file_exists($file_name) == false) {
            return false;
        }

        if (unlink($file_name) == false) {
            return false;
        }
        // 如果文件夹为空，删除
        $dir = dirname($file_name);
        if (strpos($dir, BASE) === false) {
            return false;
        }

        if (is_dir($dir) == false) {
            return false;
        }
        $dh = opendir($dir);
        $nil = '';
        while ($file = readdir($dh)) {
            $nil .= $file;
        }
        if ($nil == "...") {
            closedir($dh);
            if (rmdir($dir)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 移动目录或者文件
     * @param string $src      - 源
     * @param string $dist     - 目标
     * @return mixed $value 返回最终需要执行完的结果
     */
    public static function Move($src, $dist) {
        if (strlen($src) == 0 || strlen($dist) == 0) {
            return false;
        }
        //
        if (is_bool(strpos($src, BASE )) || is_bool(strpos($dist, BASE))) {
            return false;
        }
        $flag = 1;
        exec("mv $src $dist",$retval,$flag);
        if ($flag == 1) {
            return false;
        }
        return true;
    }

    /**
     * 方法描述：根据文件路径获取文件的signature
     * 参数：
     *   $path         - 文件地址
     *   $python       - python路径
     * 返回值：
     *   $sig_cal      - 文件的signature
     */
    public static function getSignature($path,$python) {
        $sig_path = dirname(__FILE__) . "/../py/signature.py";
        $sig_cal  = "0";
        $res      = array ();
        $rc       = 0;
        exec($python . " " . $sig_path ." " . $path, $res, $rc);
        //
        // 检查返回值
        //
        if ($rc != 1) {
            return $sig_cal;
        }

        if (is_null($res[0])) {
            return $sig_cal;
        }
        $sig_cal = $res[0];
        return $sig_cal;
    }

    /**
     * 方法描述：检查传入的signature是否和计算出来的文件signature是否一致
     * 参数：
     *   $path        - 文件地址
     *   $python      - python可执行路径
     *   $signature   - 需要检查的signature
     */
    public static function checkSignature($path,$python,$signature) {
        //
        // 计算文件signature
        //
        $value = CUtils::getSignature($path,$python);
        if (strcmp($value, $signature) != 0) {
            return false;
        }
        return true;
    }

    /**
     * 组装文件需要的post参数
     * @param string $file_signature 文件块的signature
     * @param int $offset 文件索引
     * @return mixed $value 执行成功返回post，否则返回false
     */
    public static function getPost($file_signature, $offset=0) {
        $key               = substr($file_signature,0,2)."/".substr($file_signature,2,2);
        $key              .= "/".substr($file_signature,4,2)."/".substr($file_signature,6,2);

        //
        // TODO: 处理组装s3需要的参数
        //
        $input = array( "Filename" => "{$file_signature}",
            "key" => "$key/\${filename}");
        //
        // 生成数字签名,使用json格式，返回hash格式数据
        //
        $output = CUtils::getRequesSignature($input);
        $expiration_time   = $output["expiration_date"];
        $digital_signature = $output["digital_signature"];

        $post = array();
        $post["Filename"]               = $file_signature;
        $post["AWSAccessKeyId"]         = CConst::ACCESS_KEY_ID;
        $post["key"]                    = $file_signature;
        $post["expiration_date"]        = $expiration_time;
        $post["digital_signature"]      = $digital_signature;
        $post["offset"]                 = $offset;
        $post["success_action_status"]  = "201";

        $post["acl"]                    = "";
        $post["signature"]              = "";
        $post["policy"]                 = "";
        $post = json_encode($post);
        return $post;
    }

    /**
     * 获取签名
     * @param array $input 关联数组
     * @param int $expire 过期时间
     * @return mixed $value 执行成功返回签名，否则返回false
     */
    public static function getRequesSignature($input, $expire=21600) {
        if (is_array($input) == false) {
            return  false;
        }
        //计算过期时间
        $expired_date = time() + $expire;
        //
        // 对传入数组进行转换，将其key值转成小写，形成新的关联数组
        //
        $new_input = array();
        foreach ($input as $key=>$value) {
            // 转成小写
            $key = strtolower($key);
            $new_input[$key] = $value;
        }
        //
        // 获取新数组的key
        //
        $keys = array_keys($new_input);
        //
        // keys数组排序
        //
        natsort($keys);
        //
        // input 的key不区分大小写
        //
        $str = "";
        foreach ($keys as $key) {
            $str .= $key . $new_input[$key];
        }
        $str .= CConst::EXPIRATION_DATE . $expired_date;
        $signature = CUtils::getSha1Signature($str);
        //
        // 在传入数组后添加两个新值： expiration_date digital_signature
        //
        $input["expiration_date"] = $expired_date;
        $input["digital_signature"] = $signature;
        return $input;
    }

    /**
     * 对数据进行加密签名
     * @param string $str
     * @return mixed $value 执行成功返回签名，否则返回false
     */
    public static function getSha1Signature($str) {
        $str .= CConst::ACCESS_KEY;
        return SHA1($str);
    }

    /**
     * 组装本次操作是否成功的状态
     * @param object $status 状态对象
     * @param int $code 错误代码
     * @param bool $success true/false
     * @param string $msg 操作信息
     * @return mixed $value 执行成功返回$status，否则返回false
     */
    public static function buildStatus($status, $code, $success, $msg) {
        // 状态
        if ($status == null) {
            $status = new Status();
        }
        $status->set_code($code);
        $status->set_success($success);
        $status->set_msg($msg);
        return $status;
    }

    /**
     * 计算文件的sha256值,并返回
     *
     * @param string $file - 文件路径
     * @return string sha256 - 文件sha256计算的值
     */
    public static function getFileSha256($file) {
        return hash_file('sha256', $file);
    }
    /**
     * 计算字符串的sha256值
     * @param string $str
     * @return string
     */
    public static function getStrSha256($str) {
        return hash('sha256', $str);
    }

    /**
     * 计算字符串的sha256值
     * @param string $str
     * @return string
     */
    public static function getStrSha1($str) {
        return hash('sha1', $str);
    }
    /**
     * 将字符串按照每两个字符组成一个路径
     *
     * e.g 1234567890 => array('12','12/34,'12/34/56','12/34/56/78')
     * @param string $str
     * @return string $path
     *
     * @since 1.1.2
     */
    public static function getFoldersBySplitStr($str) {
        $folderArray = array();
        //
        // 每两个字符分割
        //
        $parts = str_split(substr($str,0,8), 2);
        $path = "";
        foreach ($parts as $part){
            $path .= $part . '/';
            $folderArray[] = $path;
        }
        return array_reverse($folderArray);
    }

    /**
     * 获取冲突文件名
     * @param string $name
     * @param array $names
     * @return string $file_name
     */
    public static function getConflictName($name, $names=array()) {
        //        $tmp = array();
        //        foreach ($names as $k => $v) {
        //            $tmp[$v] = $v;
        //        }
        $index = 1;
        $paths = self::pathinfo_utf($name);
        $file_name = $paths["filename"];
        $extension = $paths["extension"];

        $tmp_name = strtolower($name);

        while (isset($names[$tmp_name])) {
            $tmp_name = $file_name . "($index)";
            if ($extension) {
                $tmp_name .= ".$extension";
            }
            $index += 1;
            //
            // 限制循环次数
            //
            if ($index == 50) {
                break;
            }
        }

        $file_name = $tmp_name;
        return $file_name;
    }

    /**
     * 计算文件附加属性
     * @param string $deviceName
     * @param int $fileSize
     * @param int $versionId
     * @param int $action
     * @param int $userId
     * @param string $userNick
     * @param array|string $versions -- a:0:{}
     * 数组序列化后的字符串
     * @return array
     */
    public static function getFileVersions($deviceName, $fileSize, $versionId, $action, $userId, $userNick,$versions="a:0:{}") {
        $versions = is_null($versions)||empty($versions) ? "a:0:{}" : $versions;
        $version               = array();
        $version["type"]       = $action;
        $version["version_id"] = $versionId;
        $version["user_id"]    = $userId;
        $version["user_nick"]  = $userNick;
        $version["device_name"]= $deviceName;
        $version["file_size"]  = $fileSize;
        $version["datetime"]   = MiniUtil::getCurrentTime();
        $versions              = @unserialize($versions);
        if (!$versions) {
            $versions = array();
        }
        // 当文件历史版本超过一定数量后，扎断处理
        $count = count($versions);
        $fileMaxVersion = apply_filters("max_file_version_count", MConst::MAX_VERSION_CONUNT);
        if ($count >= $fileMaxVersion) {
            $limit    = $count - $fileMaxVersion + 1;
            $versions = CUtils::mergeFileMetaVersion($versions, $limit);
        }
        array_push($versions, $version);
        return serialize($versions);
    }

    /**
     * 计算size单位转换
     * @param string $locale
     * @param int $size
     * @return string
     */
    public static function getSizeByLocale($locale, $size) {
        $value = "$size bytes";
        if ($locale === "KB" || $locale === "kb") {
            $tmp = $size / 1024.0;
            $tmp = number_format($tmp, 2);
            $value = "$tmp$locale";
        } elseif ($locale === "mb" || $locale === "MB" || $locale === "M") {
            $divisor = 1048576.0;
            $tmp = $size / $divisor;
            $tmp = number_format($tmp, 2);
            $value = "$tmp$locale";
        } elseif ($locale === "GB" || $locale === "gb" || $locale === "G") {
            $divisor = 1073741824.0;
            $tmp = $size / $divisor;
            $tmp = number_format($tmp, 2);
            $value = "$tmp$locale";
        }
        return $value;
    }

    /**
     * 从文件附加属性中查询文件版本信息是否存在
     * @param int $rev
     * @param string $versions
     * @return true or false
     */
    public static function isExistReversion($rev, $versions) {
        if ($rev == 0) {
            return true;
        }
        $versions = unserialize($versions);
        foreach ($versions as $v) {
            if ($rev == $v["version_id"]) {
                return true;
            }
        }

        return false;
    }

    /**
     * 约定相对路径规范： /xx/xx,
     * 错误路径： \xx\xx   或者       xxx\xxx      xxx/xxx都是非法的
     * 将每次请求传入的路径参数，检查是否可跨平台的
     * @param string $path 传入路径
     * @return mixed $value 执行成功返回$status，否则返回false
     */
    public static function convertStandardPath($path)
    {
        if ($path == "")
        {
            return false;
        }
        //
        // 转换路径分隔符，便于以后跨平台，如：将 "\"=>"/"
        //
        $path = str_replace("\\", "/", $path);
        while (!(strpos($path, "//") === false)) {
            $path = str_replace("//", "/", $path);
        }

        //
        // 约定：首个字符为路径分隔符，如："/" "\"
        //
        if ($path[0] != "/")
        {
            $path = "/".$path;
        }

        //
        // 去掉最后一个 "/",如果不是只有一个字符
        //
        $len = strlen($path);
        if ($len > 1 && "/" == $path[$len - 1]) {
            $path = substr($path, 0, $len - 1);
        }

        return $path;
    }

    /**
     * 验证文件名是否合法
     * 不可使用  \ / : * ? " < > |
     * @param $file_name
     * @return mixed $value 包含非法字符返回true，否则返回false
     */
    public static function checkNameInvalid($file_name)
    {
        if ($file_name === "")
        {
            return true;
        }
        if ($file_name{strlen($file_name)-1} == ".") {
            return true;
        }
        return preg_match("/[\\/".preg_quote("|?*\\<\":>")."]/",$file_name);
    }

    /**
     * 将字符转成bool
     * @param string $value
     * @return mixed $value 包含非法字符返回true，否则返回false
     */
    public static function convertToBool($value)
    {
        if (is_string ( $value ) === true) {
            if (strtolower ( $value ) === "false") {
                $value = false;
            } elseif (strtolower ( $value ) === "true") {
                $value = true;
            }
        }
        return $value;
    }

    /**
     * 将int时间格式化字符串
     * @param $time
     * @param $format
     * @return date
     */
    public static function formatIntTime($time, $format="D, d M Y G:i:s O")
    {
        return date($format,$time);
    }

    /**
     * 取出文件名
     * @param $filePath
     * @return mixed $value，否则返回false
     */
    public static function get_basename($filePath)
    {
        // 注意：
        //  这个方法 preg_replace('/^.+[\\\\\\/]/', '', $filePath) 存在只有一层时，返回的文件名是错误的
        //  如： "/aa" => "/aa" 原样子返回
        // 而basename($filePath) 这个函数会存在中文问题,
        //
        $first_index = strrpos($filePath, "/");
        $second_index = strrpos($filePath, "\\");
        $index = $first_index;
        if ($first_index < $second_index)
        {
            $index = $second_index;
        }
        $file_name = substr($filePath, $index+1);
        if ($file_name === false)
        {
            return "";
        }
        return $file_name;
    }
    /**
     * 获得文件扩展类型
     * @param $filename
     * @param $path
     * @return string
     */
    public static function mime_content_type($filename, $path = null) {

        $mime_types = array(
            'txt'  => 'text/plain',
            'htm'  => 'text/html',
            'html' => 'text/html',
            'php'  => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'swf'  => 'application/x-shockwave-flash',
            'flv'  => 'video/x-flv',

            // images
            'bmp'  => 'image/bmp',
            'cod'  => 'image/cis-cod',
            'gif'  => 'image/gif',
            'ief'  => 'image/ief',
            'jpe'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'jfif' => 'image/pipeg',
            'svg'  => 'image/svg+xml',
            'tif'  => 'image/tiff',
            'tiff' => 'image/tiff',
            'ras'  => 'image/x-cmu-raster',
            'cmx'  => 'image/x-cmx',
            'ico'  => 'image/x-icon',
            'png'  => 'image/png',
            'pnm'  => 'image/x-portable-anymap',
            'pbm'  => 'image/x-portable-bitmap',
            'pgm'  => 'image/x-portable-graymap',
            'ppm'  => 'image/x-portable-pixmap',
            'rgb'  => 'image/x-rgb',
            'xbm'  => 'image/x-xbitmap',
            'xpm'  => 'image/x-xpixmap',
            'xwd'  => 'image/x-xwindowdump',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio
            'au'   => 'audio/basic',
            'snd'  => 'audio/basic',
            'mid'  => 'audio/mid',
            'rmi'  => 'audio/mid',
            'mp3'  => 'audio/mpeg',
            'aif'  => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'm3u'  => 'audio/x-mpegurl',
            'ra'   => 'audio/x-pn-realaudio',
            'ram'  => 'audio/x-pn-realaudio',
            'wav'  => 'audio/x-wav',
            'ape'  => 'audio/x-monkeys-audio',
            'wma'  => 'audio/x-ms-wma',
            'wvx'  => 'audio/x-ms-wvx',

            //video
            'mp4'   => 'video/mp4',
            'qt'    => 'video/quicktime',
            'mov'   => 'video/quicktime',
            '3gp'   => 'video/3gpp',
            'wmv'   => 'video/x-ms-wmv',
            'avi'   => 'video/x-msvideo',
            'mp2'   => 'video/mpeg',
            'mpa'   => 'video/mpeg',
            'mpe'   => 'video/mpeg',
            'mpeg'  => 'video/mpeg',
            'mpg'   => 'video/mpeg',
            'mpv2'  => 'video/mpeg',
            'mov'   => 'video/quicktime',
            'qt'    => 'video/quicktime',
            'lsf'   => 'video/x-la-asf',
            'lsx'   => 'video/x-la-asf',
            'asf'   => 'video/x-ms-asf',
            'asr'   => 'video/x-ms-asf',
            'asx'   => 'video/x-ms-asf',
            'avi'   => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',
            'rmvb'  => 'video/vnd.rn-realvideo',
            'rm'    => 'video/vnd.rn-realvideo',
            'viv'   => 'video/vnd.vivo',
            'vivo'  => 'video/vnd.vivo',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai'  => 'application/postscript',
            'eps' => 'application/postscript',
            'ps'  => 'application/postscript',

            // ms office
            'doc'  => 'application/msword',
            'docx' => 'application/msword',
            'rtf'  => 'application/rtf',
            'xls'  => 'application/msexcel',
            'xlsx' => 'application/msexcel',
            'ppt'  => 'application/mspowerpoint',
            'pptx' => 'application/mspowerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            //  apk
            'apk' => 'application/vnd.android.package-archive',
        );
        $ext = explode('.',$filename);
        $ext = strtolower(array_pop($ext));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open') && !empty($path)) {
            $fileInfo = finfo_open(FILEINFO_MIME);
            $mimeType = finfo_file($fileInfo, $path);
            finfo_close($fileInfo);
            return $mimeType;
        }
        else {
            return 'application/octet-stream';
        }
    }
    /**
     * 判断是否存在缩略图
     * @param string $type
     * @param int $size
     * @return bool
     */
    public static function isExistThumbnail($type, $size) {
        if ($size > CConst::MAX_IMAGE_SIZE || $size <= 0) {
            return false;
        }
        foreach ( Thumbnail::$_support_types as $value ) {
            if ($value == $type) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * 解析路径
     * @param string $path
     * @return array
     */
    public static function pathinfo_utf($path) {
        $path = self::convertStandardPath($path);
        $value = array (
            'dirname' => "",
            'basename' => "",
            'extension' => "",
            'filename' => ""
        );
        if (strpos ( $path, '/' ) !== false) {
            $parts = explode ( '/', $path );
            $basename = end ( $parts );
        }

        if (empty ( $basename ))
            return $value;

        $dirName = substr ( $path, 0, strlen ( $path ) - strlen ( $basename ) - 1 );

        if (strpos ( $basename, '.' ) !== false) {
            $ext_parts = explode ( '.', $path );
            $extension = end ( $ext_parts );
            $filename = substr ( $basename, 0, strlen ( $basename ) - strlen ( $extension ) - 1 );
        } else {
            $extension = '';
            $filename = $basename;
        }

        return array (
            'dirname' => $dirName,
            'basename' => $basename,
            'extension' => $extension,
            'filename' => $filename
        );
    }


    /**
     * 从数据库中查询， 判断用户名是否重复
     * @param int $user_id
     * @param int $parent_id
     * @param string $file_name
     * @return string
     */
    public static function getConflictFileName($user_id, $parent_id, $file_name) {
        $files =  UserFile::model()->getByParentID($user_id, $parent_id);
        $names = array ();
        foreach ( $files as $k => $v ) {
            $names [$v ["file_name"]] = $v ["file_name"];
        }
        $file_name = CUtils::getConflictName($file_name, $names);
        return $file_name;
    }


    /**
     * 方法描述：输出文件流，使用lighttpd x-sendfile方式
     * 参数：
     *   $path         - 文件绝对路径
     *   $content_type - 文件输出类型
     *   $output_name  - 文件输出名称
     */
    public static function output($file_path, $ctype, $file_name) {
        $size = filesize ( $file_path );
        // 输入文件标签
        Header ( "Content-type: $ctype" );
        Header ( "Cache-Control: public" );
        Header ( "Content-length: " . $size );
        $encoded_filename = urlencode ( $file_name );
        $encoded_filename = str_replace ( "+", "%20", $encoded_filename );
        $ua = isset($_SERVER ["HTTP_USER_AGENT"]) ? $_SERVER ["HTTP_USER_AGENT"] : NULL;
        // 处理下载的时候的文件名
        if (preg_match ( "/MSIE/", $ua )) {
            header ( 'Content-Disposition: attachment; filename="' . $encoded_filename . '"' );
        } elseif (preg_match ( "/Firefox\/8.0/", $ua )){
            header ( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
        } else if (preg_match ( "/Firefox/", $ua )) {
            header ( 'Content-Disposition: attachment; filename*="utf8\'\'' . $file_name . '"' );
        } else {
            header ( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
        }
        $fp = fopen ( $file_path, "rb" ); // 打开文件
        if (isset ( $_SERVER ['HTTP_RANGE'] ) && ($_SERVER ['HTTP_RANGE'] != "") && preg_match ( "/^bytes=([0-9]+)-/i", $_SERVER ['HTTP_RANGE'], $match ) && ($match [1] < $size)) {
            $range = $match [1];
            fseek ( $fp, $range );
            header ( "HTTP/1.1 206 Partial Content" );
            header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s", filemtime ( $file_path ) ) . " GMT" );
            header ( "Accept-Ranges: bytes" );
            $rangesize = ($size - $range) > 0 ? ($size - $range) : 0;
            header ( "Content-Length:" . $rangesize );
            header ( "Content-Range: bytes " . $range . '-' . ($size - 1) . "/" . $size );
        } else {
            header ( "Content-Length: $size" );
            header ( "Accept-Ranges: bytes" );
            $range = 0;
            header ( "Content-Range: bytes " . $range . '-' . ($size - 1) . "/" . $size );
        }
        //
        // 下载输出文件内容
        //
        set_time_limit(0);
        $dstStream = fopen('php://output', 'wb');
        $chunksize = 4096;
        $offset = $range;
        while(!feof($fp) && $offset < $size) {
            $offset += stream_copy_to_stream($fp, $dstStream, $chunksize, $offset);
        }
        fclose($dstStream);
        fclose ( $fp );
        exit ();
    }
    /**
     *
     * 随机生成key secret
     * @param bool $unique
     * @return string
     */
    public static function generateKey ( $unique = false )
    {
        $key = md5(uniqid(rand(), true));
        if ($unique)
        {
            list($usec,$sec) = explode(' ',microtime());
            $key .= dechex($usec).dechex($sec);
        }
        return $key;
    }

    /**
     *获得显示字符串，支持中文英文的自动截取
     *
     */
    public static function getShowSubStr($string, $length, $dot = '...',$charset='utf-8') {
        if(strlen($string) <= $length) {
            return $string;
        }

        $string = str_replace(array('　',' ', '&', '"', '<', '>'), array('','','&', '"', '<', '>'), $string);

        $strCut = '';
        if(strtolower($charset) == 'utf-8') {

            $n = $tn = $noc = 0;
            while($n < strlen($string)) {

                $t = ord($string[$n]);
                if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1; $n++; $noc++;
                } elseif(194 <= $t && $t <= 223) {
                    $tn = 2; $n += 2; $noc += 2;
                } elseif(224 <= $t && $t < 239) {
                    $tn = 3; $n += 3; $noc += 2;
                } elseif(240 <= $t && $t <= 247) {
                    $tn = 4; $n += 4; $noc += 2;
                } elseif(248 <= $t && $t <= 251) {
                    $tn = 5; $n += 5; $noc += 2;
                } elseif($t == 252 || $t == 253) {
                    $tn = 6; $n += 6; $noc += 2;
                } else {
                    $n++;
                }

                if($noc >= $length) {
                    break;
                }
            }
            if($noc > $length) {
                $n -= $tn;
            }
            $strCut = substr($string, 0, $n);
        } else {
            for($i = 0; $i < $length; $i++) {
                $strCut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
            }
        }

        return $strCut.$dot;
    }
    /**
     * utf-8 转unicode
     *
     * @param string $name
     * @return string
     */
    public static function utf8Unicode($name){
        $name = iconv('UTF-8', 'UCS-2BE', $name);
        $len  = strlen($name);
        $str  = '';
        for ($i = 0; $i < $len - 1; $i = $i + 2){
            $c  = $name[$i];
            $c2 = $name[$i + 1];
            if (ord($c) > 0){   //两个字节的文字
                $str .= '\u'.base_convert(ord($c), 10, 16).str_pad(base_convert(ord($c2), 10, 16), 2, 0, STR_PAD_LEFT);
                //$str .= base_convert(ord($c), 10, 16).str_pad(base_convert(ord($c2), 10, 16), 2, 0, STR_PAD_LEFT);
            } else {
                $str .= '\u'.str_pad(base_convert(ord($c2), 10, 16), 4, 0, STR_PAD_LEFT);
            }
        }
        return $str;
    }

    /**
     * unicode 转 utf-8
     *
     * @param string $name
     * @return string
     */
    public static function unicodeDecode($name)
    {
        $name = strtolower($name);
        // 转换编码，将Unicode编码转换成可以浏览的utf-8编码
        $pattern = '/([\w]+)|(\\\u([\w]{4}))/i';
        preg_match_all($pattern, $name, $matches);
        if (!empty($matches))
        {
            $name = '';
            for ($j = 0; $j < count($matches[0]); $j++)
            {
                $str = $matches[0][$j];
                if (strpos($str, '\\u') === 0)
                {
                    $code = base_convert(substr($str, 2, 2), 16, 10);
                    $code2 = base_convert(substr($str, 4), 16, 10);
                    $c = chr($code).chr($code2);
                    $c = iconv('UCS-2', 'UTF-8', $c);
                    $name .= $c;
                }
                else
                {
                    $name .= $str;
                }
            }
        }
        return $name;
    }

    /**
     *
     * @param string $val
     * @return double
     */
    public static function return_bytes($val)
    {
        $val = trim($val);
        $last = strtolower(substr($val, -1));
        switch ($last) {
            case 'g':
                $val = $val*1024*1024*1024;
                break;
            case 'm':
                $val = $val*1024*1024;
                break;
            case 'k':
                $val = $val*1024;
            default:
                $val = -1;
                break;
        }
        return $val;
    }


    public static function cflush($str){
        print $str;
        ob_flush();
        flush();
    }
    /**
     * 将一个n级目录的路径组装成n个路径
     * @example /a/b/c  => /a, /a/b , /a/b/c
     * @param string $path
     * @return array
     */
    public static function assemblyPaths($path) {
        $parts           = explode('/', $path);
        // 去掉空值
        $parts = array_filter($parts);
        // 组装路径
        $paths = array();
        for ($i = 0; $i < count($parts); $i++) {
            $tarray = array_slice($parts, 0, $i + 1);
            $tmp = '/' . join('/', $tarray);
            $paths[$tmp] = $tmp;
        }

        return $paths;
    }

    /**
     * send file 下载文件,服务器必须支持send file模式
     * @since 0.9.5
     * @param string $filePath   - 文件绝对路径
     * @param array $options
     * @return mix
     */
    public static function xSendFile($filePath, $options) {
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
    public static function X_SendFile($filePath, $options) {
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

    /**
     *
     * 返回系统配置信息
     * @since 1.1.0
     * @return array
     */
    public static function apiInfo() {
        $response = array();
        $response['version']     = APP_VERSION;
        $response['status']      = "done";
        $response['appname']     = "迷你云";
        $response['defaultsize'] = 100;
        $response['enableReg']   = 0;
        $response['mult_user']   = 0;
        $value                   = MiniOption::getInstance()->getOptionValue('muti_clients');
        if (isset($value)) {
            $response['mult_user'] = intval($value) == 1 ? true : false;
        }
        //判断系统是否可以注册
        $response['regurl'] =  Yii::app()->params['app']['absoluteUrl']."/index.php/site/register";
        $enableReg          = MiniOption::getInstance()->getOptionValue("user_register_enabled");
        if (isset($enableReg)){
            $response['enableReg'] = 1;
            $retReg = MiniOption::getInstance()->getOptionValue("user_create_url");
            if (isset($retReg) && !empty($retReg)){
                $response['regurl'] = $retReg;
            }
        }

        // 32M
        $blockSize         = 4 * 1024 * 1024;
        // 内存配置需要
        $mem_limit         = CUtils::return_bytes(ini_get('memory_limit'));
        if ($mem_limit < 4 * $blockSize) {
            $blockSize = $mem_limit / 4;
        }
        $postMaxSize       = CUtils::return_bytes(ini_get('post_max_size'));
        $uploadMaxFilesize = CUtils::return_bytes(ini_get('upload_max_filesize'));

        $min = $postMaxSize > $uploadMaxFilesize ? $uploadMaxFilesize : $postMaxSize;

        $response['block_size'] = $min > $blockSize ? $blockSize : $min;
        if ($response['block_size'] == $postMaxSize && $response['block_size'] == $uploadMaxFilesize) {
            $response['block_size'] = $response['block_size'] - 104858;
        }
        // 获取忘记密码使用短信口子地址
        $getpwUrl = apply_filters('user_forgetpasswd');
        if (!empty($getpwUrl)) {
            $response['forgetPwUrl'] = Yii::app()->params['app']['absoluteUrl'].$getpwUrl;
        }
        return $response;
    }

    /**
     *
     * 从path中将用户的userid去掉
     * @since 0.9.5
     * @param string $path  - /{user_id}/path
     * @return string       - /path
     */
    public static function removeUserFromPath($path) {
        $list = array_slice(explode('/', $path), 2);
        return '/' . join('/', $list);
    }

    /**
     *
     * 从path中获取用户id
     *
     * @param string $path  - /{user_id}/path
     * @return integer       - user_id
     *
     * @since 1.0.7
     */
    public static function getUserFromPath($path) {
        $list = explode('/', $path);
        return intval($list[1]);
    }

    /**
     *
     * 判断系统中是否存在基本的ico图标
     * @param string $ext
     * @return bool
     */
    public static function isSupportIco ($ext)
    {
        $icoList = array("access", "chm", "excel", "exe", "flash", "image", "folder", "music", "pdf", "ppt", "rar", "txt", "video", "word");
        return in_array($ext, $icoList);
    }
    /**
     * 获取版本MID
     * 升级请求增量包时我们需要MID，但是在二级目录下，通过params['app']['mid']获取的mid会产生混乱
     * 故用一下方法去获取MID
     * @return string $mid
     */
    public static function getMID(){
        $mid = "6c646b6e64676c63";
        $option = Option::model()->find("option_name='mid'");
        if(empty($option)){
            return $mid;
        }
        return $option['option_value'];
    }


    /**
     * 系统开关,标志是否开启统计功能
     * @since 0.9.6
     */
    public static function isAdvancedStat() {
        if (defined('ADVANCED_STAT') && ADVANCED_STAT == true)
            return true;
        return false;
    }

    /**
     * 系统开关,标志是否开启升级检查统计功能
     * @since 0.9.6
     */
    public static function isAdvancedUpgrade() {
        if (defined('ADVANCED_UPGRADE') && ADVANCED_UPGRADE == true)
            return true;
        return false;
    }


    /**
     * @param $url
     * @return bool
     */
    public static function checkUrl( $url ) {
        if (empty($url)){
            return false;
        }
        if (strlen($url) < 8) {
            return false;
        }
        $prefix = substr($url, 0, 7);
        if ($prefix == "http://"){
            return true;
        }
        $prefix = substr($url, 0, 8);
        if ($prefix == "https://"){
            return true;
        }
        return false;
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
     *
     * 根据操作类型翻译
     * @param integer $action   操作类型
     * @param string $message
     * @param integer $code
     * @return string
     */
    public static function transtalte($action, $message, $code) {
        $option = '';
        switch ($action) {
            case MConst::CREATE_DIRECTORY:
                $option = 'create folder';
                break;
            case MConst::CREATE_FILE:
                $option = 'upload file';
                break;
            case MConst::MODIFY_FILE:
                $option = 'modify file';
                break;
            case MConst::DELETE:
                $option = 'delete file or folder';
                break;
            case MConst::MOVE:
                $option = 'move file or folder';
                break;
            case MConst::RENAME:
                $option = 'rename file or folder';
                break;
            case MConst::COPY:
                $option = 'copy file or folder';
                break;
            default:
                $option = 'action';
                break;
        }

        $suffix = ' failure';
        switch ($code) {
            case MConst::HTTP_CODE_500:
            case MConst::HTTP_CODE_411:
                break;
            case MConst::HTTP_CODE_400:
                if (strtolower($message) != 'bad request' && $message != MConst::PARAMS_ERROR) {
                    return $message;
                }
                break;
            case MConst::HTTP_CODE_200:
                $suffix = 'success';
                break;
            default:
                return $message;
        }

        $message = $option . $suffix;
        return $message;
    }
    /**
     * 转换sql字符串
     * @param string $inp
     * @return string
     * @since 1.0.3
     */
    public static function real_escape_string($inp) {
        if(is_array($inp))
            return array_map(__METHOD__, $inp);

        if(!empty($inp) && is_string($inp)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\Z'), $inp);
            //            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
        }

        return $inp;
    }

    /**
     *
     * 函数作用，只替换一个字符.
     * @param String $needle 需要替换的字符
     * @param String $replace 替换成什么字符
     * @param String $haystack 需要操作的字符串
     * @return string
     * @since 1.0.7
     */
    public static function str_replace_once($needle, $replace, $haystack) {
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }


    /**
     *
     * 获取访问地址
     * @since 1.0.7
     */
    public static function getBaseUrl() {
        $pieces = explode('/', $_SERVER['SCRIPT_NAME']);
        $pieces = array_slice($pieces ,0, count($pieces) - 1);
        return join('/', $pieces);
    }

    /**
     *
     * 删除指定元素的数组
     *
     * @since 1.0.7
     */
    public static function arrayRemove($array, $value) {
        foreach($array as $k=>$v){
            if($v == $value){
                unset($array[$k]);
                return $array;
            }
        }
        return $array;
    }
    /**
     *
     * 合并历史版本
     * @param array $metas
     * @param int $limit
     * @return array
     * @since 1.0.7
     */
    public static function mergeFileMetaVersion($metas, $limit = 0) {
        $list = array();
        if(empty($metas)) {
            return $metas;
        }
        $offset = $limit;
        $limit = $limit == 0 ? count($metas) : $limit;
        foreach ($metas as $index => $meta) {
            if ($index > $limit - 1) {
                break;
            }
            switch ($meta['type']) {
                case MConst::CREATE_FILE:
                case MConst::MODIFY_FILE:
                case CConst::WEB_RESTORE:
                    array_push($list, $meta['version_id']);
                    break;
                default:
                    break;
            }
        }
        //
        // 去掉前$offset个meta
        //
        $metas = array_slice($metas, $offset);
        if (empty($list)) {
            return $metas;
        }

        FileVersion::model()->updateRefCountByIds($list);
        return $metas;
    }

    /**
     *
     * 判断指定字符串在路径中出现的次数
     * @since 1.0.7
     * @param string $path  - /{user_id}/path
     * @param string $string  - string
     * @return string       - /path
     */
    public static function strCount($path, $string) {
        $count = 0;
        $list = explode('/', $path);
        foreach ($list as $str){
            if ($str == $string){
                $count = $count + 1;
            }
        }
        return $count;
    }

    /**
     * 判断是否处于最后一个位置
     *
     *@since 1.0.7
     */
    public static function isLast($file_path, $string) {
        $list = explode('/', $file_path);
        $ddd= end($list);
        if (end($list) == $string){
            return true;
        }
        return false;
    }

    /**
     *
     * 获取国际化信息
     * @since 1.0.7
     * @param MPluginModule $module
     * @param string $category
     * @return mix
     */
    public static function inc($module, $category) {

        $value = array();
        if (empty($module)) {
            $language = Yii::app()->language;
            $basePath = Yii::app()->basePath;
            $path = $basePath."/messages/".$language."/".$category.'.php';
            if (file_exists($path)) {
                return include($path);
            }
        }else{
            $path = $module->getBasePath() . '/messages/' . Yii::app()->language . '/' . $category . '.php';
            if (file_exists($path)) {
                return include $path;
            }
        }
        return $value;
    }

    /**
     *
     * 重新组装url地址
     * @param mixed $parsed_url @see parse_url()
     * @return mix
     */
    public static function unparse_url($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * 系统空间是否还有剩余
     */
    public static function hasOverSysSpace(){
        $value            = MiniOption::getInstance()->getOptionValue("site_sys_space");
        if(isset($value) && $value>0){
            $usedSpace    = MiniVersion::getInstance()->getTotalSize();
            $overSpace    = $value*1024*1024-$usedSpace;
            if($overSpace<0){
                return false;
            }
        }
        return true;
    }

    /**
     * 显示操作日志内容
     * @param mix $context 操作日志
     * @param int|string $file_type
     * @return mixed|string
     */
    public static function contextDetails($context, $file_type = MConst::OBJECT_TYPE_DIRECTORY) {
        //
        // 初始化
        //
        $contextArray = array(
            MConst::CREATE_DIRECTORY => Yii::t("front_common", "shared_be_created"),
            MConst::DELETE => Yii::t("front_common", "shared_be_deleted"),
            MConst::MOVE => Yii::t("front_common", "shared_be_moved"),
            MConst::CREATE_FILE => Yii::t("front_common", "shared_be_created"),
            MConst::MODIFY_FILE => Yii::t("front_common", "shared_be_modify"),
            MConst::SHARE_FOLDER => Yii::t("front_common", "log_beshared_file"),
            MConst::CANCEL_SHARED => Yii::t("front_common", "be_canceled_shared"),
            MConst::READONLY_SHARED => Yii::t("front_common", "be_shared_readonly_file"),
            MConst::UPDATE_SPACE_SIZE => Yii::t("front_common", "be_updated_space"),
            MConst::CAN_READ => Yii::t("front_common", "be_canread_file"),
            MConst::CAN_NOT_READ => Yii::t("front_common", "be_cannotread_file"),
            MConst::DEFAULT_PERMISSION_CHANGE_TO_CAN_READ => Yii::t("front_common", "log_canread_file"),
            MConst::DEFAULT_PERMISSION_CHANGE_TO_CAN_NOT_READ => Yii::t("front_common", "log_cannotread_file"),
            MConst::SHARED_ICON => Yii::t("front_common", "log_shared_file"),
            MConst::GROUP_MOVE => Yii::t("front_common", "group_be_move"),
        );
        $notes1 = explode('/',$context[0]);
        $notes2 = explode('/',$context[1] );
        $action = $context[2];
        $count  = count($notes2);
        if ($count == 3) {
            $parent = '';
        } else {
            $parent = $notes2[$count-2];
        }
        $oldName = $notes1[$count-1];
        $newName = $notes2[$count-1];


        if ($file_type == MConst::OBJECT_TYPE_DIRECTORY) {
            //
            // 操作日志描述
            //
            $contextArray = array(
                MConst::CREATE_DIRECTORY => Yii::t("front_common", "log_create"),
                MConst::DELETE => Yii::t("front_common", "log_delete_file"),
                MConst::MOVE => Yii::t("front_common", "log_move_file"),
                MConst::CREATE_FILE => Yii::t("front_common", "log_create"),
                MConst::MODIFY_FILE => Yii::t("front_common", "log_modify_file"),
                MConst::SHARE_FOLDER => Yii::t("front_common", "log_shared_file"),
                MConst::CANCEL_SHARED => Yii::t("front_common", "log_cancel_shared_file"),
                MConst::READONLY_SHARED => Yii::t("front_common", "log_shared_readonly_file"),
                MConst::UPDATE_SPACE_SIZE => Yii::t("front_common", "log_update_space"),
                MConst::CAN_READ => Yii::t("front_common", "log_canread_file"),
                MConst::CAN_NOT_READ => Yii::t("front_common", "log_cannotread_file"),
                MConst::DEFAULT_PERMISSION_CHANGE_TO_CAN_READ => Yii::t("front_common", "log_canread_file"),
                MConst::DEFAULT_PERMISSION_CHANGE_TO_CAN_NOT_READ => Yii::t("front_common", "log_cannotread_file"),
                MConst::SHARED_ICON => Yii::t("front_common", "log_shared_file"),
                MConst::GROUP_MOVE => Yii::t("front_common", "log_group_move"),
            );
            if(strlen($parent)>10){
                $parent  = CUtils::getShowSubStr($parent, 10);
            }
            if(strlen($oldName)>20){
                $oldName = CUtils::getShowSubStr($oldName, 20);
            }
            if(strlen($newName)>20){
                $newName = CUtils::getShowSubStr($newName, 20);
            }
            $parent  = "<span class='log'>&nbsp;" . $parent . "&nbsp;</span>";
            $oldName = "<span class='log'>&nbsp;" . $oldName . "&nbsp;</span>";
            $newName = "<span class='log'>&nbsp;" . $newName . "&nbsp;</span>";
        }
        $text = $contextArray[$action];
        $text = str_replace("{parent}", $parent, $text);
        $text = str_replace("{old_file}", $oldName, $text);
        $text = str_replace("{file}", $newName, $text);

        // 16代表公共目录
        if ($file_type == 16) {
            // 公共目录
            $text = Yii::t("js_message_list","public_folder").$text;
        } else if($file_type == MConst::OBJECT_TYPE_SHARED || $file_type == MConst::OBJECT_TYPE_BESHARED) {
            // 共享目录
            $text = Yii::t("front_common","shared_folder").$text;
        }
        return $text;
    }
    /**
     * 检查服务器是否有效
     * @param $host
     * @param $port
     * @return bool
     */
    public static function validServer($host,$port){
        $timeout = 0.1;
        $fp = @fsockopen($host, $port, $errorNumber, $errorStr, $timeout);
        if (!$fp) {
            return false;
        }else{
            return true;
        }
    }
}