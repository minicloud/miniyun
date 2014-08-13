<?php
/**
 * Miniyun Direct Filesystem.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */


/**
 * Miniyun Filesystem Class for direct PHP file and folder manipulation.
 *
 * @since 1.0
 * @package Miniyun
 * @subpackage Filesystem
 * @uses WP_Filesystem_Base Extends class
 */
class MFilesystemDirect extends MFilesystemBase {
    /**
     * constructor
     *
     * @param mixed $arg ignored argument
     */
    function __construct() {
        $this->method = 'direct';
    }
    /**
     * connect filesystem.
     *
     * @return bool Returns true on success or false on failure (always true for WP_Filesystem_Direct).
     */
    function connect() {
        return true;
    }

    /**
     *
     * 设置block_path的路径
     *
     * @since 1.1.2
     */
    function documentRootBlock($path) {
        $block_path = apply_filters("reset_block_path", $path);
        if ($block_path === $path || $block_path === false){
            return DOCUMENT_ROOT_BLOCK;
        }
        return $block_path;
    }

    /**
     * Reads entire file into a string
     *
     * @param string $file Name of the file to read.
     * @return string|bool The function returns the read data or false on failure.
     */
    function get_contents($file, $type = '', $resumepos = 0) {
        if ($resumepos == 0){
            return @file_get_contents($file);
        }

        $fp = fopen ( $file, "rb" );
        fseek ( $fp, $resumepos );
        $contents = "";
        while ( ! feof ( $fp )) {
            //设置文件最长执行时间
            set_time_limit ( 0 );
            $contents = $contents . fread ( $fp, 1024 * 8 ) ; //输出文件
        }
        fclose ( $fp );
        return $contents;
    }

    /**
     * Reads entire file into a string
     *
     */
    function render_contents($file, $type = '', $resumePosition = 0) {
        if (file_exists($file) == FALSE) return FALSE;
        $fp = fopen ( $file, "rb" );
        set_time_limit(0);
        $dstStream = fopen('php://output', 'wb');
        $chunkSize = 4096;
        $offset = $resumePosition;
        $file_size = $this->size($file);
        while(!feof($fp) && $offset < $file_size) {
            $last_size = $file_size - $offset;
            if ($chunkSize > $last_size && $last_size > 0) {
                $chunkSize = $last_size;
            }
            $offset += stream_copy_to_stream($fp, $dstStream, $chunkSize, $offset);
        }
        fclose($dstStream);
        fclose ( $fp );
        return true;
    }

    /**
     * Reads entire file into a file
    */
    function get($from, $to, $resume=0) {
        if ($resume==0){
            return $this->copy($from, $to);
        }

        //读取指定长度内容
        $fp = fopen ( $from, "rb" ); // 打开文件
        fseek ( $fp, $resume );

        $contents = "";
        while ( ! feof ( $fp )) {
            //设置文件最长执行时间
            set_time_limit ( 0 );
            $contents = fread ( $fp, 1024 * 8 ) ; //输出文件
        }
        fclose ( $fp );

        //写入新文件中
        $fp = @fopen($to, 'w');
        @fwrite($fp, $contents);
        @fclose($fp);

        return true;
    }

    /**
     * Reads entire file into an array
     *
     * @param string $file Path to the file.
     * @return array|bool the file contents in an array or false on failure.
     */
    function get_contents_array($file) {
        return @file($file);
    }

    /**
     * 本地上传到远程
     */
    function put($source, $destination, $mode = false ) {
        return $this->copy($source, $destination, true);
    }

    /**
     * Write a string to a file
     *
     */
    function put_contents($file, $contents, $mode = false ) {
        if ( ! ($fp = @fopen($file, 'w')) )
        return false;
        @fwrite($fp, $contents);
        @fclose($fp);
        $this->chmod($file, $mode);
        return true;
    }
    /**
     * Gets the current working directory
     *
     * @return string|bool the current working directory on success, or false on failure.
     */
    function cwd() {
        return @getcwd();
    }
    /**
     * Change directory
     *
     * @param string $dir The new current directory.
     * @return bool Returns true on success or false on failure.
     */
    function chdir($dir) {
        return @chdir($dir);
    }
    /**
     * Changes file group
     *
     * @param string $file Path to the file.
     * @param mixed $group A group name or number.
     * @param bool $recursive (optional) If set True changes file group recursively. Defaults to False.
     * @return bool Returns true on success or false on failure.
     */
    function chgrp($file, $group, $recursive = false) {
        if ( ! $this->exists($file) )
        return false;
        if ( ! $recursive )
        return @chgrp($file, $group);
        if ( ! $this->is_dir($file) )
        return @chgrp($file, $group);
        //Is a directory, and we want recursive
        $file = $this->trailingslashit($file);
        $filelist = $this->dirlist($file);
        foreach ($filelist as $filename)
        $this->chgrp($file . $filename, $group, $recursive);

        return true;
    }
    /**
     * Changes filesystem permissions
     */
    function chmod($file, $mode = false, $recursive = false) {
        if ( ! $mode ) {
            if ( $this->is_file($file) )
            $mode = FS_CHMOD_FILE;
            elseif ( $this->is_dir($file) )
            $mode = FS_CHMOD_DIR;
            else
            return false;
        }

        if ( ! $recursive || ! $this->is_dir($file) )
        return @chmod($file, $mode);
        //Is a directory, and we want recursive
        $file = $this->trailingslashit($file);
        $filelist = $this->dirlist($file);
        foreach ( (array)$filelist as $filename => $filemeta)
        $this->chmod($file . $filename, $mode, $recursive);

        return true;
    }
    /**
     * Changes file owner
     *
     * @param string $file Path to the file.
     * @param mixed $owner A user name or number.
     * @param bool $recursive (optional) If set True changes file owner recursively. Defaults to False.
     * @return bool Returns true on success or false on failure.
     */
    function chown($file, $owner, $recursive = false) {
        if ( ! $this->exists($file) )
        return false;
        if ( ! $recursive )
        return @chown($file, $owner);
        if ( ! $this->is_dir($file) )
        return @chown($file, $owner);
        //Is a directory, and we want recursive
        $filelist = $this->dirlist($file);
        foreach ($filelist as $filename) {
            $this->chown($file . '/' . $filename, $owner, $recursive);
        }
        return true;
    }
    /**
     * Gets file owner
     *
     * @param string $file Path to the file.
     * @return string Username of the user.
     */
    function owner($file) {
        $owneruid = @fileowner($file);
        if ( ! $owneruid )
        return false;
        if ( ! function_exists('posix_getpwuid') )
        return $owneruid;
        $ownerarray = posix_getpwuid($owneruid);
        return $ownerarray['name'];
    }
    /**
     * Gets file permissions
     *
     * FIXME does not handle errors in fileperms()
     *
     * @param string $file Path to the file.
     * @return string Mode of the file (last 4 digits).
     */
    function getchmod($file) {
        return substr(decoct(@fileperms($file)),3);
    }
    function group($file) {
        $gid = @filegroup($file);
        if ( ! $gid )
        return false;
        if ( ! function_exists('posix_getgrgid') )
        return $gid;
        $grouparray = posix_getgrgid($gid);
        return $grouparray['name'];
    }

    function copy($source, $destination, $overwrite = false, $mode = false) {
        if ( ! $overwrite && $this->exists($destination) )
        return false;

        $rtval = copy($source, $destination);
        if ( $mode )
        $this->chmod($destination, $mode);
        return $rtval;
    }

    function move($source, $destination, $overwrite = false) {
        if ( ! $overwrite && $this->exists($destination) )
        return false;

        // try using rename first.  if that fails (for example, source is read only) try copy
        if ( @rename($source, $destination) )
        return true;

        if ( $this->copy($source, $destination, $overwrite) && $this->exists($destination) ) {
            $this->delete($source);
            return true;
        } else {
            return false;
        }
    }

    function delete($file, $recursive = false, $type = false) {
        if ( empty($file) ) //Some filesystems report this as /, which can cause non-expected recursive deletion of all files in the filesystem.
        return false;
        $file = str_replace('\\', '/', $file); //for win32, occasional problems deleting files otherwise

        if ( 'f' == $type || $this->is_file($file) )
        return @unlink($file);
        if ( ! $recursive && $this->is_dir($file) )
        return @rmdir($file);

        //At this point its a folder, and we're in recursive mode
        $file = $this->trailingslashit($file);
        $filelist = $this->dirlist($file, true);

        $retval = true;
        if ( is_array($filelist) ) //false if no files, So check first.
        foreach ($filelist as $filename => $fileinfo)
        if ( ! $this->delete($file . $filename, $recursive, $fileinfo['type']) )
        $retval = false;

        if ( file_exists($file) && ! @rmdir($file) )
        $retval = false;
        return $retval;
    }

    function exists($file) {
        return @file_exists($file);
    }

    function is_file($file) {
        return @is_file($file);
    }

    function is_dir($path) {
        return @is_dir($path);
    }

    function is_readable($file) {
        return @is_readable($file);
    }

    function is_writable($file) {
        return @is_writable($file);
    }

    function atime($file) {
        return @fileatime($file);
    }

    function mtime($file) {
        return @filemtime($file);
    }
    function size($file) {
        if ($this->exists($file) === false) {
            return false;
        }
        return @filesize($file);
    }

    function touch($file, $time = 0, $atime = 0) {
        if ($time == 0)
        $time = time();
        if ($atime == 0)
        $atime = time();
        return @touch($file, $time, $atime);
    }

    function mkdir($path, $chmod = false, $chown = false, $chgrp = false) {
        // safe mode fails with a trailing slash under certain PHP versions.
        $path = $this->untrailingslashit($path);
        if ( empty($path) )
        return false;

        if ($this->exists($path)){
            return true;
        }

        if ( ! $chmod )
        $chmod = FS_CHMOD_DIR;

        if ( ! @mkdir($path, $chmod, true) )
        return false;
        $this->chmod($path, $chmod);
        if ( $chown )
        $this->chown($path, $chown);
        if ( $chgrp )
        $this->chgrp($path, $chgrp);
        return true;
    }

    function rmdir($path, $recursive = false) {
        return $this->delete($path, $recursive);
    }

    function dirlist($path, $include_hidden = true, $recursive = false) {
        if ( $this->is_file($path) ) {
            $limit_file = basename($path);
            $path = dirname($path);
        } else {
            $limit_file = false;
        }

        if ( ! $this->is_dir($path) )
        return false;

        $dir = @dir($path);
        if ( ! $dir )
        return false;

        $ret = array();

        while (false !== ($entry = $dir->read()) ) {
            $struc = array();
            $struc['name'] = $entry;

            if ( '.' == $struc['name'] || '..' == $struc['name'] )
            continue;

            if ( ! $include_hidden && '.' == $struc['name'][0] )
            continue;

            if ( $limit_file && $struc['name'] != $limit_file)
            continue;

            $struc['perms'] 	= $this->gethchmod($path.'/'.$entry);
            $struc['permsn']	= $this->getnumchmodfromh($struc['perms']);
            $struc['number'] 	= false;
            $struc['owner']    	= $this->owner($path.'/'.$entry);
            $struc['group']    	= $this->group($path.'/'.$entry);
            $struc['size']    	= $this->size($path.'/'.$entry);
            $struc['lastmodunix']= $this->mtime($path.'/'.$entry);
            $struc['lastmod']   = date('M j',$struc['lastmodunix']);
            $struc['time']    	= date('h:i:s',$struc['lastmodunix']);
            $struc['type']		= $this->is_dir($path.'/'.$entry) ? 'd' : 'f';

            if ( 'd' == $struc['type'] ) {
                if ( $recursive )
                $struc['files'] = $this->dirlist($path . '/' . $struc['name'], $include_hidden, $recursive);
                else
                $struc['files'] = array();
            }

            $ret[ $struc['name'] ] = $struc;
        }
        $dir->close();
        unset($dir);
        return $ret;
    }

    /**
     * 将本地文件句柄内容插入到相对路径对应的文件制定的偏移量内
     */
    public function AppendFile($handle, $path, $offset) {
        $mode       = 'wb';
        $local_size = 0;
        if ($this->exists($path)) {
            $mode       = 'r+b';
            $local_size = $this->size($path);
        }

        //
        // 不支持偏移量大于实际文件逻辑
        //
        if ($local_size < $offset) {
            return false;
        }

        $file = fopen($path, $mode);
        fseek($file, $offset);
        while (!feof($handle)) {
            $contents = fread($handle, 8192);
            fwrite($file, $contents);
        }
        fclose($file);

        clearstatcache();
        return true;
    }
}
