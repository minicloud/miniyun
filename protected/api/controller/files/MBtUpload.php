<?php
/**
 * Miniyun file upload服务主要入口地址,实现文件断点和多线程上传
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MBtUpload extends MApplicationComponent {
    
    private $description;
    /**
     * 
     * 大文件断点多线程上传入口，使用put/post方法,推荐使用put
     * 
     * header:
     *      If-Match         - request header，表示文件hash值
     *      ETag             - response header,表示文件hash值
     *      X-Block-Length   - 表示文件每个分片大小，第一次由客户端上传，若服务器端存在则与服务器端保持一致
     *      X-Description    - response header 文件描述信息：00000000000010   1表示这部分内容已经收到，不用再传
     *      X-Content-Length - 文件总大小
     * 
     */
    public function invoke() {
        //data源处理对象
        $dataObj = Yii::app()->data;
        
        parent::init();
        // 获取header值 
        $if_match   = @$_SERVER['HTTP_IF_MATCH'];
        $clength    = isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 0;
        $crange     = @$_SERVER['HTTP_CONTENT_RANGE'];
        $total_size = @$_SERVER['HTTP_X_CONTENT_LENGTH'];
        $block_size = @$_SERVER['HTTP_X_BLOCK_LENGTH'];
        //
        // 检查空间
        //
        $user     = MUserManager::getInstance()->getCurrentUser();
        $spaceInfo = MiniUser::getInstance()->getSpaceInfo($user);
        $space      = $spaceInfo["space"];
        $used_space = $spaceInfo["usedSpace"];
        
        $used_space += $total_size;
        if ($used_space > $space) {
            throw new MFilesException(Yii::t('api',"User is over storage quota."), 
            MConst::HTTP_CODE_507);
        }
        //
        // 文件临时存储路径
        //
        $this->cache = DOCUMENT_CACHE . MiniUtil::getPathBySplitStr($if_match);
        $des_path    = DOCUMENT_CACHE . MiniUtil::getPathBySplitStr($if_match) . ".des";
        $store_path  = MiniUtil::getPathBySplitStr($if_match);
        // 表示文件已经上传ok
        if ($dataObj->exists($store_path)) {
            $this->cache = $store_path;
            return true;
        }
        
        $this->description = $this->hanleDescription($des_path, $this->cache, $total_size, $block_size, $if_match);
        // 
        // 表示查询 
        //
        if ($clength == 0 && preg_match ( "/^bytes \*\/([0-9]+)/i", $crange, $match )) {
            if ($this->handleCheckCompleted() == true) {
                // 上传完毕检查hash值
                $this->handleCheck($this->cache, $if_match, $store_path,$des_path);
                $this->cache = $store_path;
                return true;
            }
            $this->handleAssigns();
        }
        
        //
        // 获取文件上传的位置
        //
        if (!preg_match ( "/^bytes ([0-9]+)-([0-9]+)/i", $crange, $match )) {
            throw new MFilesException (Yii::t('api',MConst::PARAMS_ERROR ), MConst::HTTP_CODE_400 );
        }
        
        $start = $match[1];
        $end   = $match[2];
        $index = $start / $block_size;
        //
        // 若对应的文件已经上传则返回
        //
        
        if ($this->description['blocks'][$index] != '1') {
            $this->handleWriteBytes($this->cache, $start, $clength);
            $this->description['blocks'][$index] = "1";
            file_put_contents($des_path, serialize($this->description));
        }
        
        //
        // 如果没有全部上传完成，则要求客户端继续上传
        //
        if ($this->handleCheckCompleted() == false) {
            $this->handleAssigns();
        }
        
        // 上传完毕检查hash值
        $this->handleCheck($this->cache, $if_match, $store_path, $des_path);
        $this->cache = $store_path;
    }
    
    /**
     * 检查文件是否上传完成
     * Enter description here ...
     */
    private function handleCheckCompleted() {
        $completed = true;
        //
        // 如果文件上传完全，则创建文件mata
        //
        for ($i = 0; $i < strlen($this->description['blocks']); $i++) {
            if ($this->description['blocks'][$i] == "0") {
                $completed = false;
                break;
            }
        }
        return $completed;
    }
    
    
    /**
     * 当文件全部上传完成后执行
     */
    private function handleCheck($cache, $hash, $store_path, $des_path) {
        $signature = MiniUtil::getFileHash($cache);
        if ($hash != $signature) {
            unlink($des_path);
            throw new MFilesException (Yii::t('api',MConst::PARAMS_ERROR ), MConst::HTTP_CODE_400 );
        }
        // 存储目录
        if (!MUtils::MkDirs(dirname($store_path))) {
            throw new MFilesException ( Yii::t('api',MConst::INTERNAL_SERVER_ERROR ), MConst::HTTP_CODE_500 );
        }
        //data源处理对象
        $dataObj = Yii::app()->data;
        // 移动文件到存储路径
        if ($dataObj->put($cache, $store_path) == FALSE) {
            throw new MFilesException ( Yii::t('api',MConst::PARAMS_ERROR ), MConst::HTTP_CODE_400 );
        }
        unlink($cache);
        unlink($des_path);
        return true;
    }
    
    /**
     * 
     * 写入文件,替换对应位置内容
     */
    private function handleWriteBytes($cache, $start, $length) {
        $handle = fopen("php://input", "rb");
        $data   = fread($handle, 8096);
        $file   = fopen($cache, "r+b");
        fseek($file, $start);
        $block_size = strlen($data);
        while ($data) {
            fwrite($file, $data);
            $data = fread($handle, 8096);
            $block_size += strlen($data);
        }
        fclose($file);
        fclose($handle);
        if ($length != $block_size) {
            throw new MFilesException (Yii::t('api',MConst::PARAMS_ERROR ), MConst::HTTP_CODE_400 );
        }
    }
    
    /**
     * 
     * 获取文件分块数量
     */
    private function handleGetBlockCount($total_size, $block_size) {
        $count = $total_size / $block_size;
        $int_count = (int)$count;
        if ($count > $int_count) {
            $count += 1;
        }
        return (int)$count;
    }
    
    /**
     * 
     * 返回描述信息，客户端上传文件
     */
    private function handleAssigns() {
        $etag       = $this->description['hash'];
        $des        = $this->description['blocks'];
        $block_size = $this->description['block_size'];
        
        header("HTTP/1.1 308 Resume Incomplete");
        header('ETag: ' . $etag);
        header('Content-Length: 0');
        header('X-Description: '. $des);
        header('X-Block-Length: '. $block_size);
        echo "0";
        exit(0);
    }

    private function hanleDescription($des_path, $cache, $total_size, $block_size, $hash) {
        // 
        // 如果描述文件或者临时文件不存在则创建
        //
        $blocks = "";
        $count = $this->handleGetBlockCount($total_size, $block_size);
        if (!file_exists($des_path)) {
            if (!MUtils::MkDirsLocal(dirname($des_path))) {
                throw new MFilesException ( Yii::t('api',MConst::INTERNAL_SERVER_ERROR ), MConst::HTTP_CODE_500 );
            }
            $description = array();
           
            for ($i = 0; $i < $count; $i++) {
                $blocks .= "0";
            }
            
            $description['hash']       = $hash;
            $description['total_size'] = $total_size;
            $description['block_size'] = $block_size;
            $description['blocks']     = $blocks;
            
            file_put_contents($des_path, serialize($description));
        } else {
            $str = file_get_contents($des_path);
            $description = unserialize($str);
        }
        
        // 临时文件
        if (!file_exists($cache) || filesize($cache) != $total_size) {
            $fp = fopen($cache, "wb");
            fseek($fp, $total_size - 1);
            fwrite($fp, "\0");
            fclose($fp);
            $blocks = "";
            for ($i = 0; $i < $count; $i++) {
                $blocks .= "0";
            }
            $description['blocks'] = $blocks;
        }
        //
        // 如果总大小不一致，则返回错误
        //
        if ($total_size != $description['total_size']) {
            throw new MFilesException ( Yii::t('api',MConst::PARAMS_ERROR ), MConst::HTTP_CODE_400 );
        }
        
        return  $description;
    }
}