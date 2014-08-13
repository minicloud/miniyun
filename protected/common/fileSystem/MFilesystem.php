<?php
/**
 * Miniyun Filesystem Class for direct PHP file and folder manipulation.
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFilesystem extends CApplicationComponent{
    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    //当前系统所使用的存储系统
    public $fileSystem;

    //是否需要本地备份文件
    public $localBackup = false;

    /**
     * 构造函数初始化
     * 初始化文件对象处理函数
     */
    public function  __construct()
    {
        //获取数据库连接对象
        $fileSystem = apply_filters("data_source_object");
        if (!isset($fileSystem) || empty($fileSystem)){
            $this->fileSystem = new MFilesystemDirect();
        } else {
            $this->fileSystem = $fileSystem;
        }

        $localBackup = apply_filters("is_local_backup", "");
        if (!isset($localBackup) || empty($localBackup)){
            $this->localBackup = false;
        } else {
            $this->localBackup = $localBackup;
        }

        $this->createDefault();
    }

    /**
     *
     * 获取文件操作对象
     *
     * @since 1.1.2
     */
    public function getFileSystemObject(){
        $fileSystem = $this->fileSystem;
        $fileSystem = apply_filters("reset_data_source_object", $fileSystem);
        return $fileSystem;
    }

    /**
     *
     *  设置文件操作对象
     *
     * @since 1.1.2
     */
    public function setFileSystemObject($fileSystem){
        if (!empty($fileSystem)){
            $this->fileSystem = $fileSystem;
        }
    }

    /**
     *
     * 创建默认存储目录
     */
    public function createDefault(){
        $obj = new MFilesystemDirect();
        if (!file_exists(DOCUMENT_TEMP)){
            MUtils::MkDirsOject($obj, DOCUMENT_TEMP);
        }
        if (!file_exists(DOCUMENT_ROOT_BLOCK)){
            MUtils::MkDirsOject($obj, DOCUMENT_ROOT_BLOCK);
        }
        if (!file_exists(THUMBNAIL_TEMP)) {
            MUtils::MkDirsOject($obj, THUMBNAIL_TEMP);
        }
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
     * 是否存在本地备份
     */
    function isExistLocal(){
        if ($this->getFileSystemObject() instanceof MFilesystemDirect){
            return true;
        }
        return $this->localBackup;
    }

    /**
     *
     * 是否需要本地备份
     */
    function isNeedBackUpLocal(){
        if ($this->getFileSystemObject() instanceof MFilesystemDirect){
            return false;
        }
        return $this->localBackup;
    }

    /**
     * 
     * 获取存储路径
     * 
     * @since 1.1.2
     */
    function documentStorePath($path){
        return $this->getFileSystemObject()->documentRootBlock($path);
    }

    /**
     * Reads entire file into a string
     *
     * @param string $file Name of the file to read.
     * @return string|bool The function returns the read data or false on failure.
     */
    function get_contents($file, $type = '', $resumepos = 0) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            return $direct->get_contents($direct->localPath($file), $type, $resumepos);
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->get_contents($file, $type, $resumepos);
    }

    function render_contents($file, $type = '', $resumepos = 0) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            return $direct->render_contents($direct->localPath($file), $type, $resumepos);
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->render_contents($file, $type, $resumepos);
    }


    function get($from, $to, $resumepos = 0) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            return $direct->get($direct->localPath($from), $to, $resumepos);
        }
        $from = $this->documentStorePath($from).$from;
        return $this->getFileSystemObject()->get($from, $to, $resumepos);
    }

    /**
     *
     * 获取一个本地的文件地址，如果是远程文件则先进行下载
     * @param unknown_type $path
     */
    function get_local_path($path) {
        $direct = new MFilesystemDirect();
        if ($this->isExistLocal()){
            return $direct->localPath($path);
        }
        $from = $this->documentStorePath($path).$path;

        $pathArray = CUtils::pathinfo_utf($path);
        $to = DOCUMENT_TEMP.$pathArray["filename"];
        $direct->touch($to);
        $this->getFileSystemObject()->get($from, $to, $resumepos = 0);
        return $to;
    }

    /**
     * Reads entire file into an array
     *
     * @param string $file Path to the file.
     * @return array|bool the file contents in an array or false on failure.
     */
    function get_contents_array($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->get_contents_array($file);
    }

    /**
     * 本地上传到远程
     * @param unknown_type $source 本地文件路径
     * @param unknown_type $destination 远程文件路径
     * @param unknown_type $mode
     */
    function put($source, $destination, $mode = false ) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->put($source, $direct->localPath($destination), $mode);
        }
        $destination = $this->documentStorePath($destination).$destination;
        return $this->getFileSystemObject()->put($source, $destination, $mode);
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
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->put_contents($file, $contents, $mode);
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->put_contents($file, $contents, $mode);
    }
    /**
     * Gets the current working directory
     *
     * @return string|bool the current working directory on success, or false on failure.
     */
    function cwd() {
        return $this->getFileSystemObject()->cwd();
    }
    /**
     * Change directory
     *
     * @param string $dir The new current directory.
     * @return bool Returns true on success or false on failure.
     */
    function chdir($dir) {
        $dir = $this->documentStorePath($dir).$dir;
        return $this->getFileSystemObject()->chdir($dir);
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
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->chgrp($direct->localPath($file), $group, $recursive);
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->chgrp($file, $group, $recursive);
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
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->chmod($direct->localPath($file), $mode, $recursive);
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->chmod($file, $mode, $recursive);
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
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->chown($direct->localPath($file), $owner, $recursive);
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->chown($file, $owner, $recursive);
    }
    /**
     * Gets file owner
     *
     * @param string $file Path to the file.
     * @return string Username of the user.
     */
    function owner($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->owner($file);
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
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->getchmod($file);
    }
    function group($file) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->group($direct->localPath($file));
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->group($file);
    }

    /**
     *
     * 只能为同源拷贝
     * @param unknown_type $source
     * @param unknown_type $destination
     * @param unknown_type $overwrite
     * @param unknown_type $mode
     */
    function copy($source, $destination, $overwrite = false, $mode = false) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->copy($direct->localPath($source), $direct->localPath($destination), $overwrite, $mode);
        }
        $source = $this->documentStorePath($source).$source;
        $destination = $this->documentStorePath($destination).$destination;
        return $this->getFileSystemObject()->copy($source, $destination, $overwrite, $mode);
    }

    function move($source, $destination, $overwrite = false) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->move($direct->localPath($source), $direct->localPath($destination), $overwrite);
        }
        $source = $this->documentStorePath($source).$source;
        $destination = $this->documentStorePath($destination).$destination;
        return $this->getFileSystemObject()->move($source, $destination, $overwrite);
    }

    function delete($file, $recursive = false, $type = false) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->delete($direct->localPath($file), $recursive, $type);
        }
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->delete($file, $recursive, $type);
    }

    function exists($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->exists($file);
    }

    function is_file($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->is_file($file);
    }

    function is_dir($path) {
        $path = $this->documentStorePath($path).$path;
        return $this->getFileSystemObject()->is_dir($file);
    }

    function is_readable($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->is_readable($file);
    }

    function is_writable($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->is_writable($file);
    }

    function atime($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->atime($file);
    }

    function mtime($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->mtime($file);
    }
    function size($file) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->size($file);
    }

    function touch($file, $time = 0, $atime = 0) {
        $file = $this->documentStorePath($file).$file;
        return $this->getFileSystemObject()->touch($file, $time, $atime);
    }

    function mkdir($path, $chmod = false, $chown = false, $chgrp = false) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->mkdir($direct->localPath($path), $chmod, $chown, $chgrp);
        }
        $path = $this->documentStorePath($path).$path;
        return $this->getFileSystemObject()->mkdir($path, $chmod, $chown, $chgrp);
    }

    function rmdir($path, $recursive = false) {
        if ($this->isNeedBackUpLocal()){
            $direct = new MFilesystemDirect();
            $direct->rmdir($direct->localPath($path), $recursive);
        }
        $path = $this->documentStorePath($path).$path;
        return $this->getFileSystemObject()->rmdir($path, $recursive);
    }

    function dirlist($path, $include_hidden = true, $recursive = false) {
        $path = $this->documentStorePath($path).$path;
        return $this->getFileSystemObject()->dirlist($path, $include_hidden, $recursive);
    }

    /**
     * 返回存储本地的全路径
     *
     * @param $path 需要保存的文件路径
     *
     * @since 1.0.3
     *
     */
    private function getFullPath($path) {
        $base_path = $this->documentStorePath($path);
        if ($base_path == false) {
            return $path;
        }

        if (substr($base_path, -1) == '/' && substr($path, 0, 1) == '/') {
            $path = $base_path . substr($path, 1);
        }
        else {
            $path = $base_path . $path;
        }
        return $path;
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
        $path = $this->getFullPath($path);
        return $this->getFileSystemObject()->AppendFile($handle, $path, $offset);
    }
}
?>
