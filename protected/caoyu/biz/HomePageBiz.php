<?php
/**
 * 管理员首页
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class HomePageBiz extends MiniBiz{
    /**
     * 获取系统已用空间
     */
    public function getUsedSpace(){
        $remain    = $this->getDiskFreeSpace();//空闲空间 字节
        $total     = $this->getDiskTotalSpace();//总空间 字节
//        $usedSpace = $this->_byteFormat($total - $remain);//磁盘已用的空间单位G(包括miniyun以外文件)
        $usedSpace = $this->_byteFormat(MiniVersion::getInstance()->getTotalSize());
        $totalSpace = $this->_byteFormat($total);
        $usedPercentage = $this->getUsedPercent();//已用空间占的百分比
        $tempDirectory = $this->getDirectorySize(BASE.'temp');
        $tempSize = $tempDirectory['size'];
        $cacheSize = $this->countCache();
        $cacheSpace = MiniUtil::formatSize($tempSize+$cacheSize);
        $data = array();
        $data['usedSpace'] = $usedSpace;
        $data['totalSpace'] = $totalSpace;
        $data['usedPercentage'] = $usedPercentage;
        $data['cacheSpace'] = $cacheSpace;

        return $data;
    }

    public function getDiskTotalSpace() {
        if (file_exists(BASE) == false) {
            CUtils::MkDirs(BASE);
        }
        return @disk_total_space(BASE);
    }

    public function getDiskFreeSpace() {
        if (file_exists(BASE) == false) {
            CUtils::MkDirs(BASE);
        }
        return @disk_free_space(BASE);
    }

    public function getDiskUsedInMiniyun() {
        return $this->_directorySize();
    }
    /**
     * 获取剩余空间 数值 以G为单位
     * @return number
     */
    public function getRemainDisk() {
        $remain_disk=$this->getDiskFreeSpace();
        return $this->_byteFormat($remain_disk);
    }
    /**
     * 获取使用空间百分比
     * @return number
     */
    public function getUsedPercent() {
        $remain    = $this->getDiskFreeSpace();//空闲空间
        $total     = $this->getDiskTotalSpace();//总空间
        $usedSpace = MiniVersion::getInstance()->getTotalSize();
        $percent = 0;
        if ($total > 0) {
            $percent=(float)($usedSpace)/$total;
        }
        $retval = round($percent,3)*100;
        return $retval;
    }
    /**
     * 把目录大小统一为G单位
     * @return int
     */
    private function _byteFormat($size)
    {
        $size = $size/(1024*1024*1024);
        $size = round($size,2);
        if($size==0){
            $size =0.001;
        }
        return $size;
    }
    /**
     *
     * 软件占用空间大小
     *
     * @since 1.1.2
     */
    private function _directorySize() {
        $fileVersion = new FileVersion();
        $totalSize = $fileVersion->fileSum();
        if (!$totalSize) $totalSize = 0;
        return $totalSize;
    }
    // 获取文件夹大小
    public function getDirectorySize($path)
    {
        $totalsize = 0;
        $totalcount = 0;
        $dircount = 0;
        if ($handle = opendir ($path))
        {
            while (false !== ($file = readdir($handle)))
            {
                $nextpath = $path . '/' . $file;
                if ($file != '.' && $file != '..' && !is_link ($nextpath))
                {
                    if (is_dir ($nextpath))
                    {
                        $dircount++;
                        $result = $this->getDirectorySize($nextpath);
                        $totalsize += $result['size'];
                        $totalcount += $result['count'];
                        $dircount += $result['dircount'];
                    }
                    elseif (is_file ($nextpath))
                    {
                        $totalsize += filesize ($nextpath);
                        $totalcount++;
                    }
                }
            }
        }
        closedir ($handle);
        $total['size'] = $totalsize;
        $total['count'] = $totalcount;
        $total['dircount'] = $dircount;
        return $total;
    }
    /**
     * 统计文件的缓存,不统计清除
     */
    public function countCache() {
        $sql_str = 'SELECT file_size FROM ' . Yii::app()->params['tablePrefix'] . 'file_versions WHERE ref_count<=0';
        $sql = Yii::app()->db->createCommand($sql_str);
        $versions = $sql->queryAll();
        //计算总大小
        $sum_size = 0;
        foreach ($versions as $version){
            $sum_size += $version["file_size"];
        }
        return $sum_size;
    }
    // 获取文件夹大小
    public  function getDirSize($dir)
    {
        $handle = opendir($dir);
        while (false!==($FolderOrFile = readdir($handle)))
        {
            if($FolderOrFile != "." && $FolderOrFile != "..")
            {
                if(is_dir("$dir/$FolderOrFile"))
                {
                    $sizeResult += $this->getDirSize("$dir/$FolderOrFile");
                }
                else
                {
                    $sizeResult += filesize("$dir/$FolderOrFile");
                }
            }
        }
        closedir($handle);
        return $sizeResult;
    }
}