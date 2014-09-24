<?php
/**
 * 通用模块
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MUtils
{

    /**
     * 页面返回: json输出
     * @param bool $success 是否成功
     * @param string $msg 返回文案描述
     * @param string $signature 文件signature
     */
    public static function StandResult($success, $msg, $signature = "0") {
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
        if (MUtils::MkDirs(dirname($dir), $mode, $recursive)) {
            return Yii::app()->data->mkdir($dir, $mode);
        }
        return false;
    }

    /**
     * 创建本地文件夹
     * @param string $dir 文件夹路径
     * @param int $mode 权限
     * @param bool $recursive
     * @return mixed $value 返回最终需要执行完的结果
     */
    public static function MkDirsLocal($dir, $mode = 0777, $recursive = true) {
        if (is_null($dir) || $dir == "") {
            return false;
        }
        if (is_dir($dir) || $dir == "/") {
            return true;
        }
        if (MUtils::MkDirsLocal(dirname($dir), $mode, $recursive)) {
            return mkdir($dir, $mode);
        }
        return false;
    }

    /**
     * 创建文件夹根据指定的对象
     */
    public static function MkDirsOject($fileObject, $dir, $mode = 0777, $recursive = true) {
        if (is_null($dir) || $dir == "") {
            return false;
        }
        if (is_dir($dir) || $dir == "/") {
            return true;
        }
        if (MUtils::MkDirsOject($fileObject, dirname($dir), $mode, $recursive)) {
            return $fileObject->mkdir($dir, $mode);
        }
        return false;
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
     * 上传保存文件操作
     */
    public static function create($root, $post, $file, $is_need_check_signature=false) {
        Yii::trace(Yii::t('api','Begin to process {class}::{function}',
        array('{class}'=>"MUtils", '{function}'=>__FUNCTION__)),"miniyun.api");
        ob_start();
        ob_end_clean();
        $key = $post["key"];
        $file_name = $post["Filename"];
        // 检查错误，在此之前，已经通过post请求将参数传入到此
        if ($file["file"]["error"] > 0) {
            Yii::log(Yii::t("api","Request is Error, file:'{$file["file"]["error"]}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            return false;
        }
        // 参数检查
        if (strlen(trim($key)) <= 0 || strlen(trim($file_name)) <= 0) {
            Yii::log(Yii::t("api","Request is Error, file_name:'{$file_name}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            return false;
        }
        // $key 必须包含 / , ${filename}
        if (is_bool(strpos(trim($key), "/")) || is_bool(strpos(trim($key), "\${filename}"))) {
            Yii::log(Yii::t("api","Request is Error, file_name:'{$file_name}'"), CLogger::LEVEL_ERROR,"miniyun.api");
            return false;
        }

        // 全路径
        $path = $root . str_replace("\${filename}", $file_name, $key);

        // 确保需要合并的文件不存在
        if (file_exists($path)) {
            return true;
        }
        // 目录不存在，就创建
        $dir = dirname($path);
        if (file_exists($dir) == false) {
            $dirname = dirname($path);
            MUtils::MkDirs($dirname);
        }

        // 移动临时文件
        move_uploaded_file($file["file"]["tmp_name"], $path);
        //
        // 检查文件signature是否与传入的一致
        //
        if ($is_need_check_signature) {
            if (MUtils::checkSignature($path, PYTHON_PATH, $file_name) == false) {
                MUtils::RemoveFile($path);
                Yii::log(Yii::t("api","signature is not same , file_name:'{$file_name}'"), CLogger::LEVEL_ERROR,"miniyun.api");
                return false;
            }
        }

        Yii::trace(Yii::t('api','end to process {class}::{function}',
        array('{class}'=>"MUtils", '{function}'=>__FUNCTION__)),"miniyun.api");
        return true;
    }

    /**
     * 方法描述：输出文件流，使用lighttpd x-sendfile方式
     * 参数：
     *   $path         - 文件绝对路径
     *   $content_type - 文件输出类型
     *   $output_name  - 文件输出名称
     */
    public static function output($path, $content_type, $output_name) {
        // 检查是否已经输出
        if (headers_sent()) {
            exit;
        }
        // 判断是否输出图片
        $contents = explode("/", $content_type);
        // 检查文件名称是否存在，若不存在，则生成随机字符串
        if (strlen(trim($output_name)) <= 0) {
            $output_name = md5(date("Y-m-d G:i:s")); // 时间压缩的md5值
        }

        $encoded_filename = urlencode($output_name);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        $ua = isset($_SERVER ["HTTP_USER_AGENT"]) ? $_SERVER ["HTTP_USER_AGENT"] : NULL;
        header("Cache-Control:");
        header("Cache-Control: public");
        header("Content-Type: " . $content_type);
        if (strcmp($contents[0], 'image') != 0) {
            if (preg_match("/MSIE/", $ua)) {
                header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
            } elseif (preg_match ( "/Firefox\/8.0/", $ua )){
                header ( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
            }
            elseif (preg_match("/Firefox/", $ua)) {
                header('Content-Disposition: attachment; filename*="utf8\'\'' . $output_name . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $output_name . '"');
            }
        }
        header("Accept-Ranges: none");
        header("Content-Length: " . filesize($path));
        header("X-LIGHTTPD-send-file: " . $path);
        exit;
    }

    /**
     * 方法描述：以断点续传的方式进行下载
     * 参数：
     *   $path         - 文件地址
     *   $output_name  - 下载文件名
     *   $content_type - 输出类型
     */
    public static function output_ranges($path, $output_name, $content_type) {
        // 检查是否已经输出
        if (headers_sent()) {
            exit;
        }
        $size = filesize($path);
        //$size = 2209112064;
        $seek_start = 0;
        $seek_range = substr($_SERVER['HTTP_RANGE'], 6);
        $range = explode('-', $seek_range);

        // 获取ranges的值
        if ($range[0] > 0) {
            $seek_start = floatval($range[0]);
        }
        if ($range[1] > 0) {
            $seek_end = floatval($range[1]);
        }
        $length = $seek_end - $seek_start;

        // 检查文件名称是否存在，若不存在，则生成随机字符串
        if (strlen(trim($output_name)) <= 0) {
            $output_name = md5(date("Y-m-d G:i:s")); // 时间压缩的md5值
        }
        // 判断是否输出图片
        $contents = explode("/", $content_type);

        // 将名称编码
        $encoded_filename = urlencode($output_name);
        $encoded_filename = str_replace("+", "%20", $output_name);
        $ua = isset($_SERVER ["HTTP_USER_AGENT"]) ? $_SERVER ["HTTP_USER_AGENT"] : NULL;

        header("HTTP/1.1 206 Partial Content");
        header("Content-Range: bytes $seek_start-$seek_end/$size");
        header("Cache-Control:");
        header('Cache-Control: public');
        //设置输出浏览器格式
        header('Content-Type: ' . $content_type);
        if (strcmp($contents[0], 'image') != 0) {
            if (preg_match("/MSIE/", $ua)) {
                header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
            } elseif (preg_match ( "/Firefox\/8.0/", $ua )){
                header ( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
            }
            elseif (preg_match("/Firefox/", $ua)) {
                header('Content-Disposition: attachment; filename*="utf8\'\'' . $output_name . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $output_name . '"');
            }
        }
        header("Accept-Ranges: bytes");
        header("Content-Length: " . $length); //输出总长

        // 通过执行python脚本完成对应的下载代码逻辑
        $python = PYTHON_PATH;
        $read_path = dirname(__FILE__) . "/../py/readfile.py";
        $str = "$python $read_path $path $seek_start $length";
        Yii::trace($str,"miniyun.api");
        $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        2 => array("file", "/tmp/error-output.log", "a") // stderr is a file to write to
        );
        $process = proc_open("$python $read_path $path $seek_start $length", $descriptorspec, $pipes);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // Any error output will be appended to /tmp/error-output.txt

            fwrite($pipes[0], 'hello world');
            fclose($pipes[0]);

            set_time_limit(0);
            echo stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            flush();
            ob_flush();

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
            if ($return_value != 0) {
                header("HTTP/1.1 500 Internal Server Error");
            }
        }
        exit;
    }

    /**
     * 方法描述：下载，根据是否设置了断点续传来选择下载方法
     * 参数：
     *   $path         - 文件地址
     *   $output_name  - 下载文件名
     *   $content_type - 输出类型
     */
    public static function download($path, $content_type, $output_name) {
        // 判断是否是断点续传
        if (isset ($_SERVER['HTTP_RANGE'])) {
            MUtils::output_ranges($path, $content_type, $output_name);
        } else {
            MUtils::output($path, $content_type, $output_name);
        }
    }

    /**
     * 方法描述：根据文件路径获取文件的signature
     * 参数：
     *   $path         - 文件地址
     *   $pyhon        - python路径
     * 返回值：
     *   $sig_cal      - 文件的signature
     */
    public static function getSignature($path,$python) {
        $sig_path = dirname(__FILE__) . "/../py/signature.py";
        $sig_cal  = "0";
        $res      = array ();
        $rc       = 0;
        exec($python . " " . $sig_path ." " . $path, $res, $rc);
        // 检查返回值
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
        // 计算文件signature
        $retval = MUtils::getSignature($path,$python);
        if (strcmp($retval, $signature) != 0) {
            return false;
        }
        return true;
    }

    /**
     * 组装文件需要的post参数
     */
    public static function getPost($file_signature, $offset=0) {
        $key               = substr($file_signature,0,2)."/".substr($file_signature,2,2);
        $key              .= "/".substr($file_signature,4,2)."/".substr($file_signature,6,2);

        // TODO: 处理组装s3需要的参数
        $input = array( "Filename" => "{$file_signature}",
                        "key" => "$key/\${filename}");
        // 生成数字签名,使用json格式，返回hash格式数据
        $output = MUtils::getRequesSignature($input);
        $expiration_time   = $output["expiration_date"];
        $digital_signature = $output["digital_signature"];

        $post = array();
        $post["Filename"]               = $file_signature;
        $post["AWSAccessKeyId"]         = MConst::ACCESS_KEY_ID;
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
        // 对传入数组进行转换，将其key值转成小写，形成新的关联数组
        $new_input = array();
        foreach ($input as $key=>$value) {
            // 转成小写
            $key = strtolower($key);
            $new_input[$key] = $value;
        }
        // 获取新数组的key
        $keys = array_keys($new_input);
        // keys数组排序
        natsort($keys);
        // input 的key不区分大小写
        $str = "";
        foreach ($keys as $key) {
            $str .= $key . $new_input[$key];
        }
        $str .= MConst::EXPIRATION_DATE . $expired_date;
        $signature = MUtils::getSha1Signature($str);
        // 在传入数组后添加两个新值： expiration_date digital_signature
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
        $str .= MConst::ACCESS_KEY;
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
     */
    public static function getStrSha256($str) {
        return hash('sha256', $str);
    }
    /**
     * 计算字符串的sha256值
     */
    public static function getStrSha1($str) {
        return hash('sha1', $str);
    }
    /**
     * 获取冲突文件名
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
            // 限制循环次数
            if ($index == 500) {
                break;
            }
        }

        $file_name = $tmp_name;
        Yii::trace("function: '{__FUNCTION__}',conflict_name:'{$file_name}'","miniyun.api");
        return $file_name;
    }

    /**
     * 计算文件附加属性
     */
    public static function getFileVersions($deviceName, $fileSize, $version_id, $action, $user_id, $user_nick,$versions="a:0:{}") {
        $versions = is_null($versions)||empty($versions) ? "a:0:{}" : $versions;
        $version               = array();
        $version["type"]       = $action;
        $version["version_id"] = $version_id;
        $version["user_id"]    = $user_id;
        $version["user_nick"]  = $user_nick;
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
     */
    public static function getSizeByLocale($locale, $size) {
        $retval = "$size bytes";
        if ($locale === "KB" || $locale === "kb") {
            $tmp = $size / 1024.0;
            $tmp = number_format($tmp, 2);
            $retval = "$tmp$locale";
        } elseif ($locale === "mb" || $locale === "MB" || $locale === "M") {
            $divisor = 1048576.0;
            $tmp = $size / $divisor;
            $tmp = number_format($tmp, 2);
            $retval = "$tmp$locale";
        } elseif ($locale === "GB" || $locale === "gb" || $locale === "G") {
            $divisor = 1073741824.0;
            $tmp = $size / $divisor;
            $tmp = number_format($tmp, 2);
            $retval = "$tmp$locale";
        }
        return $retval;
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
        // 转换路径分隔符，便于以后跨平台，如：将 "\"=>"/"
        $path = str_replace("\\", "/", $path);
        while (!(strpos($path, "//") === false)) {
            $path = str_replace("//", "/", $path);
        }

        // 约定：首个字符为路径分隔符，如："/" "\"
        if ($path[0] != "/")
        {
            $path = "/".$path;
        }

        // 去掉最后一个 "/",如果不是只有一个字符
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
     * 判断是否存在缩略图
     */
    public static function isExistThumbnail($type, $size) {
        if ($size > MConst::MAX_IMAGE_SIZE || $size <= 0) {
            return false;
        }
        foreach ( MThumbnailBase::$_support_types as $value ) {
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
        $retval = array (
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
            return $retval;

            $dirname = substr ( $path, 0, strlen ( $path ) - strlen ( $basename ) - 1 );

            if (strpos ( $basename, '.' ) !== false) {
                $ext_parts = explode ( '.', $path );
                $extension = end ( $ext_parts );
                $filename = substr ( $basename, 0, strlen ( $basename ) - strlen ( $extension ) - 1 );
            } else {
                $extension = '';
                $filename = $basename;
            }

            return array (
            'dirname' => $dirname, 
            'basename' => $basename, 
            'extension' => $extension, 
            'filename' => $filename 
            );
    }
    /**
     *
     * 获取默认总空间大小
     */
    public static function defaultTotalSize(){
        $total      = DEFAULT_USER_SPACE *1024*1024;
        $value      = MiniOption::getInstance()->getOptionValue("site_default_space");
        if (isset($value)){
            $total  = doubleval($value)*1024*1024;
        }
        return $total;
    }

    /**
     *
     * 获取权限数组
     *
     * @since 1.1.0
     */
    public static function getPermissionArray($perm){
        if (strlen($perm) != 9){
            return false;
        }
        $read = $perm[0];
        if ($perm != "000000000"){
            $read = 1;
        }
        $permission = Yii::app()->privilege->generatePermission($read,intval($perm[1]),intval($perm[2]),intval($perm[3]),intval($perm[4]),intval($perm[5]),intval($perm[6]),intval($perm[7]),intval($perm[8]));
        return $permission;
    }

    /**
     *
     * 判断是否属于共享类型的目录
     *
     * @since 1.0.7
     */
    public static function isShareFolder($type){
        if ($type == MConst::OBJECT_TYPE_BESHARED){
            return true;
        }
        $is_share_folder = apply_filters("is_share_folder", $type);
        if ($is_share_folder === true){
            return true;
        }
        return false;
    }
}