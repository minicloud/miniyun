<?php
/**
 * 打包下载获取限制
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class DownloadPackageLimit
{
    /**
     *
     * 获取下载限制的文件个数
     * @since 1.0.0
     */
    public function getLimitCount(){
        $limit_count     = DownloadPackageConst::DEFAULT_LIMIT_COUNT;
        $count           = MiniOption::getInstance()->getOptionValue("download_package_limit_count");
        if (isset($count)) {
            $limit_count = $count;
        }
        return $limit_count;
    }

    /**
     *
     * 获取下载限制的文件个数
     * @since 1.0.0
     */
    public function getLimitSize(){
        $limit_size  = DownloadPackageConst::DEFAULT_LIMIT_SIZE;
        $size        = MiniOption::getInstance()->getOptionValue("download_package_limit_size");
        if (isset($size)) {
            $limit_size = $size;
        }
        return $limit_size;
    }
}
