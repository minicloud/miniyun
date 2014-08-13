<?php
/**
 * 迷你云文件管理模块.
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
    return untrailingslashit($string) . '/';
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
 * 迷你云
 *
 * @since 2.5
 * @package WordPress
 * @subpackage Filesystem
 * @uses WP_Filesystem_Base Extends class
 */
class CFileSystem
{
    var $errors = null;
    /**
     * constructor
     *
     * @param mixed $arg ignored argument
     */
    function __construct() {
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
     * Reads entire file into a string
     *
     * @param string $file Name of the file to read.
     * @return string|bool The function returns the read data or false on failure.
     */
    function get_contents($file) {
        return @file_get_contents($file);
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
     * Write a string to a file
     *
     * @param string $file Remote path to the file where to write the data.
     * @param string $contents The data to write.
     * @param int $mode (optional) The file permissions as octal number, usually 0644.
     * @return bool False upon failure.
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
        $file = trailingslashit($file);
        $filelist = $this->dirlist($file);
        foreach ($filelist as $filename)
        $this->chgrp($file . $filename, $group, $recursive);

        return true;
    }
    /**
     * Changes filesystem permissions
     *
     * @param string $file Path to the file.
     * @param int $mode (optional) The permissions as octal number, usually 0644 for files, 0755 for dirs.
     * @param bool $recursive (optional) If set True changes file group recursively. Defaults to False.
     * @return bool Returns true on success or false on failure.
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
        $file = trailingslashit($file);
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

        $rtval = @copy($source, $destination);
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
        $file = trailingslashit($file);
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
        $path = untrailingslashit($path);
        if ( empty($path) )
        return false;

        if ( ! $chmod )
        $chmod = FS_CHMOD_DIR;

        if ( ! @mkdir($path) )
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

            $struc['perms']     = $this->getchmod($path.'/'.$entry);
            $struc['number']     = false;
            $struc['owner']        = $this->owner($path.'/'.$entry);
            $struc['group']        = $this->group($path.'/'.$entry);
            $struc['size']        = $this->size($path.'/'.$entry);
            $struc['lastmodunix']= $this->mtime($path.'/'.$entry);
            $struc['lastmod']   = date('M j',$struc['lastmodunix']);
            $struc['time']        = date('h:i:s',$struc['lastmodunix']);
            $struc['type']        = $this->is_dir($path.'/'.$entry) ? 'd' : 'f';

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
     * 复制文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    function copyDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir.'/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir.'/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            $this->mkdir($aimDir);
        }
        $dirHandle = opendir($oldDir);
        while(false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                $this->copy($oldDir . $file, $aimDir . $file,true);
            } else {
                $this->copyDir($oldDir . $file, $aimDir . $file,true);
            }
        }
        closedir($dirHandle);
        return true;
    }
    /**
     * 移动文件夹
     *
     * @param string $oldDir
     * @param string $aimDir
     * @param boolean $overWrite 该参数控制是否覆盖原文件
     * @return boolean
     */
    function moveDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            $this->mkdir($aimDir);
        }
        @$dirHandle = opendir($oldDir);
        if (!$dirHandle) {
            return false;
        }
        while(false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir.$file)) {
                $this->move($oldDir . $file,$aimDir.$file,true);

            } else {
                $this->moveDir($oldDir . $file,$aimDir.$file,true);
            }
        }
        closedir($dirHandle);
        //rmdir($oldDir);//删除目录
        return true;
    }
    /**
     * version 1.0.2
     * 检测utf-8格式文件有无bom,并删除bom头
     * @param String $filename
     * @return boolean
     * 2012-9-7
     */
    function checkBom($path, $result, $recursive = true){
        $path          = rtrim($path, "/") . "/";
        $folder_handle = @opendir($path);
        while(false !== ($filename = readdir($folder_handle))) {
            if  ($filename == '.' || $filename == '..')
            continue;
            if (@is_dir($path . $filename . "/"))
            {
                // Need to include full "path" or it's an infinite loop
                if ($recursive)
                $result = $this->checkBom($path . $filename . "/", $result, true);
            } else {
                if ($this->fopen_utf8($path . $filename))
                {
                    array_push($result, $path . $filename);
                }
            }
        }
        return $result;
    }
    /**
     * version 1.0.2
     * @param String $filename
     * @return boolean
     * 2012-9-10
     */
    function fopen_utf8 ($filename) {
        $size = filesize($filename);
        $file = @fopen($filename, "r+");
        $bom  = @fread($file, 3);
        if ($bom != "\xEF\xBB\xBF")
        {
            return false;
        }

        $buffer = @fread($file, $size - 3);
        @fseek($file, 0);
        @fwrite($file, $buffer);
        @ftruncate($file, $size - 3);
        @fclose($file);
        return true;
    }

    /**
     *
     * 判断一个文件夹是否为空
     * @param string $path
     *
     * @since 1.1.2
     */
    function isEmptyDir( $path )
    {
        $dh= opendir( $path );
        while(false !== ($f = readdir($dh)))
        {
            if($f != "." && $f != ".." )
            return true;
        }
        return false;
    }
}
?>
