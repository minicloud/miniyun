<?php
/**
 * Factory class for Miniyun Filesystem.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
defined('FS_CHMOD_DIR') or define('FS_CHMOD_DIR', 0755);
defined('FS_CHMOD_FILE') or define('FS_CHMOD_FILE', 0644);
/**
 * Factory class Miniyun Filesystem class for which Filesystem implementations use
 *
 * @since 1.0
 */
class MFilesystemBase{
    /**
     * Whether to display debug data for the connection.
     *
     * @since 1.0
     * @access public
     * @var bool
     */
    var $verbose = false;
    /**
     * Cached list of local filepaths to mapped remote filepaths.
     *
     * @since 2.7
     * @access private
     * @var array
     */
    var $cache = array();

    /**
     * The Access method of the current connection, Set automatically.
     *
     * @since 1.0
     * @access public
     * @var string
     */
    var $method = '';


    function str_replace_once($needle, $replace, $haystack) {

        $pos = strpos($haystack, $needle);

        if ($pos === false) {

            return $haystack;

        }

        return substr_replace($haystack, $replace, $pos, strlen($needle));

    }


    /**
     * Returns the path on the remote filesystem of ABSPATH
     *
     * @since 2.7
     * @access public
     * @return string The location of the remote path.
     */
    function basePath($path) {
        return $this->str_replace_once($this->documentRootBlock(),"",$path);
    }


    /**
     * 
     * 获取本地路径
     * @param string $path
     * 
     * @since 1.1.2
     */
    function localPath($path) {
        $store_path = apply_filters("reset_local_path", $path);
        if ($store_path === $path || $store_path === false){
            return DOCUMENT_ROOT_BLOCK.$path;
        }
        return $store_path;
    }


    /**
     * Returns the path on the remote filesystem of WP_CONTENT_DIR
     *
     * @since 2.7
     * @access public
     * @return string The location of the remote path.
     */
    function wp_content_dir() {
        return $this->find_folder(WP_CONTENT_DIR);
    }
    /**
     * Returns the path on the remote filesystem of WP_PLUGIN_DIR
     *
     * @since 2.7
     * @access public
     *
     * @return string The location of the remote path.
     */
    function wp_plugins_dir() {
        return $this->find_folder(WP_PLUGIN_DIR);
    }
    /**
     * Returns the path on the remote filesystem of the Themes Directory
     *
     * @since 2.7
     * @access public
     *
     * @return string The location of the remote path.
     */
    function wp_themes_dir() {
        return $this->wp_content_dir() . 'themes/';
    }
    /**
     * Returns the path on the remote filesystem of WP_LANG_DIR
     *
     * @since 3.2.0
     * @access public
     *
     * @return string The location of the remote path.
     */
    function wp_lang_dir() {
        return $this->find_folder(WP_LANG_DIR);
    }

    /**
     * Locates a folder on the remote filesystem.
     *
     * Deprecated; use WP_Filesystem::abspath() or WP_Filesystem::wp_*_dir() methods instead.
     *
     * @since 1.0
     * @deprecated 2.7
     * @access public
     *
     * @param string $base The folder to start searching from
     * @param bool $echo True to display debug information
     * @return string The location of the remote path.
     */
    function find_base_dir($base = '.', $echo = false) {
        _deprecated_function(__FUNCTION__, '2.7', 'WP_Filesystem::abspath() or WP_Filesystem::wp_*_dir()' );
        $this->verbose = $echo;
        return $this->abspath();
    }
    /**
     * Locates a folder on the remote filesystem.
     *
     * Deprecated; use WP_Filesystem::abspath() or WP_Filesystem::wp_*_dir() methods instead.
     *
     * @since 1.0
     * @deprecated 2.7
     * @access public
     *
     * @param string $base The folder to start searching from
     * @param bool $echo True to display debug information
     * @return string The location of the remote path.
     */
    function get_base_dir($base = '.', $echo = false) {
        _deprecated_function(__FUNCTION__, '2.7', 'WP_Filesystem::abspath() or WP_Filesystem::wp_*_dir()' );
        $this->verbose = $echo;
        return $this->abspath();
    }

    /**
     * Locates a folder on the remote filesystem.
     *
     * Assumes that on Windows systems, Stripping off the Drive letter is OK
     * Sanitizes \\ to / in windows filepaths.
     *
     * @since 2.7
     * @access public
     *
     * @param string $folder the folder to locate
     * @return string The location of the remote path.
     */
    function find_folder($folder) {

        if ( strpos($this->method, 'ftp') !== false ) {
            $constant_overrides = array( 'FTP_BASE' => ABSPATH, 'FTP_CONTENT_DIR' => WP_CONTENT_DIR, 'FTP_PLUGIN_DIR' => WP_PLUGIN_DIR, 'FTP_LANG_DIR' => WP_LANG_DIR );
            foreach ( $constant_overrides as $constant => $dir )
            if ( defined($constant) && $folder === $dir )
            return trailingslashit(constant($constant));
        } elseif ( 'direct' == $this->method ) {
            $folder = str_replace('\\', '/', $folder); //Windows path sanitisation
            return trailingslashit($folder);
        }

        $folder = preg_replace('|^([a-z]{1}):|i', '', $folder); //Strip out windows drive letter if it's there.
        $folder = str_replace('\\', '/', $folder); //Windows path sanitisation

        if ( isset($this->cache[ $folder ] ) )
        return $this->cache[ $folder ];

        if ( $this->exists($folder) ) { //Folder exists at that absolute path.
            $folder = trailingslashit($folder);
            $this->cache[ $folder ] = $folder;
            return $folder;
        }
        if ( $return = $this->search_for_folder($folder) )
        $this->cache[ $folder ] = $return;
        return $return;
    }

    /**
     * Locates a folder on the remote filesystem.
     *
     * Expects Windows sanitized path
     *
     * @since 2.7
     * @access private
     *
     * @param string $folder the folder to locate
     * @param string $base the folder to start searching from
     * @param bool $loop if the function has recursed, Internal use only
     * @return string The location of the remote path.
     */
    function search_for_folder($folder, $base = '.', $loop = false ) {
        if ( empty( $base ) || '.' == $base )
        $base = trailingslashit($this->cwd());

        $folder = untrailingslashit($folder);

        $folder_parts = explode('/', $folder);
        $last_path = $folder_parts[ count($folder_parts) - 1 ];

        $files = $this->dirlist( $base );

        foreach ( $folder_parts as $key ) {
            if ( $key == $last_path )
            continue; //We want this to be caught by the next code block.

            //Working from /home/ to /user/ to /Miniyun/ see if that file exists within the current folder,
            // If its found, change into it and follow through looking for it.
            // If it cant find Miniyun down that route, it'll continue onto the next folder level, and see if that matches, and so on.
            // If it reaches the end, and still cant find it, it'll return false for the entire function.
            if ( isset($files[ $key ]) ){
                //Lets try that folder:
                $newdir = trailingslashit(path_join($base, $key));
                if ( $this->verbose )
                printf( __('Changing to %s') . '<br/>', $newdir );
                if ( $ret = $this->search_for_folder( $folder, $newdir, $loop) )
                return $ret;
            }
        }

        //Only check this as a last resort, to prevent locating the incorrect install. All above procedures will fail quickly if this is the right branch to take.
        if (isset( $files[ $last_path ] ) ) {
            if ( $this->verbose )
            printf( __('Found %s') . '<br/>',  $base . $last_path );
            return trailingslashit($base . $last_path);
        }
        if ( $loop )
        return false; //Prevent this function from looping again.
        //As an extra last resort, Change back to / if the folder wasn't found. This comes into effect when the CWD is /home/user/ but WP is at /var/www/.... mainly dedicated setups.
        return $this->search_for_folder($folder, '/', true);

    }

    /**
     * Returns the *nix style file permissions for a file
     *
     * From the PHP documentation page for fileperms()
     *
     * @link http://docs.php.net/fileperms
     * @since 1.0
     * @access public
     *
     * @param string $file string filename
     * @return int octal representation of permissions
     */
    function gethchmod($file){
        $perms = $this->getchmod($file);
        if (($perms & 0xC000) == 0xC000) // Socket
        $info = 's';
        elseif (($perms & 0xA000) == 0xA000) // Symbolic Link
        $info = 'l';
        elseif (($perms & 0x8000) == 0x8000) // Regular
        $info = '-';
        elseif (($perms & 0x6000) == 0x6000) // Block special
        $info = 'b';
        elseif (($perms & 0x4000) == 0x4000) // Directory
        $info = 'd';
        elseif (($perms & 0x2000) == 0x2000) // Character special
        $info = 'c';
        elseif (($perms & 0x1000) == 0x1000) // FIFO pipe
        $info = 'p';
        else // Unknown
        $info = 'u';

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
        (($perms & 0x0800) ? 's' : 'x' ) :
        (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
        (($perms & 0x0400) ? 's' : 'x' ) :
        (($perms & 0x0400) ? 'S' : '-'));

        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
        (($perms & 0x0200) ? 't' : 'x' ) :
        (($perms & 0x0200) ? 'T' : '-'));
        return $info;
    }

    /**
     * Converts *nix style file permissions to a octal number.
     *
     * Converts '-rw-r--r--' to 0644
     * From "info at rvgate dot nl"'s comment on the PHP documentation for chmod()
     *
     * @link http://docs.php.net/manual/en/function.chmod.php#49614
     * @since 1.0
     * @access public
     *
     * @param string $mode string *nix style file permission
     * @return int octal representation
     */
    function getnumchmodfromh($mode) {
        $realmode = '';
        $legal =  array('', 'w', 'r', 'x', '-');
        $attarray = preg_split('//', $mode);

        for ($i=0; $i < count($attarray); $i++)
        if ($key = array_search($attarray[$i], $legal))
        $realmode .= $legal[$key];

        $mode = str_pad($realmode, 9, '-');
        $trans = array('-'=>'0', 'r'=>'4', 'w'=>'2', 'x'=>'1');
        $mode = strtr($mode,$trans);

        $newmode = '';
        $newmode .= $mode[0] + $mode[1] + $mode[2];
        $newmode .= $mode[3] + $mode[4] + $mode[5];
        $newmode .= $mode[6] + $mode[7] + $mode[8];
        return $newmode;
    }

    /**
     * Determines if the string provided contains binary characters.
     *
     * @since 2.7
     * @access private
     *
     * @param string $text String to test against
     * @return bool true if string is binary, false otherwise
     */
    function is_binary( $text ) {
        return (bool) preg_match('|[^\x20-\x7E]|', $text); //chr(32)..chr(127)
    }

    /**
     * Appends a trailing slash.
     *
     * Will remove trailing slash if it exists already before adding a trailing
     * slash. This prevents double slashing a string or path.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * @since 1.2.0
     * @uses untrailingslashit() Unslashes string if it was slashed already.
     *
     * @param string $string What to add the trailing slash to.
     * @return string String with trailing slash added.
     */
    function trailingslashit($string) {
        return $this->untrailingslashit($string) . '/';
    }

    /**
     * Removes trailing slash if it exists.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * @since 2.2.0
     *
     * @param string $string What to remove the trailing slash from.
     * @return string String without the trailing slash.
     */
    function untrailingslashit($string) {
        return rtrim($string, '/');
    }


    /**
     * Returns a filename of a Temporary unique file.
     * Please note that the calling function must unlink() this itself.
     *
     * The filename is based off the passed parameter or defaults to the current unix timestamp,
     * while the directory can either be passed as well, or by leaving  it blank, default to a writable temporary directory.
     *
     * @since 2.6.0
     *
     * @param string $filename (optional) Filename to base the Unique file off
     * @param string $dir (optional) Directory to store the file in
     * @return string a writable filename
     */
    function wp_tempnam($filename = '', $dir = '') {
        if ( empty($dir) )
        $dir = $this->get_temp_dir();
        $filename = basename($filename);
        if ( empty($filename) )
        $filename = time();

        $filename = preg_replace('|\..*$|', '.tmp', $filename);
        $filename = $dir . $this->wp_unique_filename($dir, $filename);
        touch($filename);
        return $filename;
    }

    /**
     *
     * tmp dir
     */
    function get_temp_dir(){
        return DOCUMENT_TEMP;
    }

    /**
     * Get a filename that is sanitized and unique for the given directory.
     *
     * If the filename is not unique, then a number will be added to the filename
     * before the extension, and will continue adding numbers until the filename is
     * unique.
     *
     * The callback is passed three parameters, the first one is the directory, the
     * second is the filename, and the third is the extension.
     *
     * @since 2.5.0
     *
     * @param string $dir
     * @param string $filename
     * @param mixed $unique_filename_callback Callback.
     * @return string New filename, if given wasn't unique.
     */
    function wp_unique_filename( $dir, $filename, $unique_filename_callback = null ) {
        // separate the filename into a name and extension
        $info = pathinfo($filename);
        $ext = !empty($info['extension']) ? '.' . $info['extension'] : '';
        $name = basename($filename, $ext);

        // edge case: if file is named '.ext', treat as an empty name
        if ( $name === $ext )
        $name = '';


        $number = '';

        // change '.ext' to lower case
        if ( $ext && strtolower($ext) != $ext ) {
            $ext2 = strtolower($ext);
            $filename2 = preg_replace( '|' . preg_quote($ext) . '$|', $ext2, $filename );

            // check for both lower and upper case extension or image sub-sizes may be overwritten
            while ( file_exists($dir . "/$filename") || file_exists($dir . "/$filename2") ) {
                $new_number = $number + 1;
                $filename = str_replace( "$number$ext", "$new_number$ext", $filename );
                $filename2 = str_replace( "$number$ext2", "$new_number$ext2", $filename2 );
                $number = $new_number;
            }
            return $filename2;
        }

        while ( file_exists( $dir . "/$filename" ) ) {
            if ( '' == "$number$ext" )
            $filename = $filename . ++$number . $ext;
            else
            $filename = str_replace( "$number$ext", ++$number . $ext, $filename );
        }

        return $filename;
    }


    public function isWindows(){
        if(strtoupper(substr(PHP_OS,0,3))=='WIN'){
            return true;
        }
        return false;
    }
    
    /**
     * 将本地文件句柄内容插入到相对路径对应的文件制定的偏移量内
     * 
     * @param $handle 本地文件句柄
     * @param $path 需要保存的文件路径
     * @param $offset 文件偏移量
     * 
     * @since 1.0.3
     * 
     */
    public function AppendFile($handle, $path, $offset) {
        return false;
    }

}

?>
