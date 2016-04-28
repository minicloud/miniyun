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
        //获得总空间 
        $total     = $this->getDiskTotalSpace();//总空间
        //获得已使用的空间
        $usedSpace = MiniVersion::getInstance()->getTotalSize();
        $percent = 0;
        if ($total > 0) {
            $percent=(float)($usedSpace)/$total;
        }
        $usedPercentage = round($percent,3)*100; 
        $data = array();
        $data['usedSpace'] = $this->_byteFormat($usedSpace);
        $data['totalSpace'] = $this->_byteFormat($total);
        $data['usedPercentage'] = $usedPercentage;
        $data['cacheSpace'] = 0;
        return $data;
    }
    private function getDiskTotalSpace() {
        $totalsize = 0;
        $nodes = PluginMiniStoreNode::getInstance()->getNodeList();
        foreach($nodes as $node){
            $totalsize+=$node['disk_size'];
        }
        return $totalsize;
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