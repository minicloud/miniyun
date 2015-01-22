<?php
/**
 * 文件业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class FileBiz  extends MiniBiz{

    /**
     * download current user file
     * @param $path
     * @return mixed
     */
    public function download($path){
        $share = new MiniShare();
        $minFileMeta = $share->getMinFileMetaByPath($path);
        MiniFile::getInstance()->download($minFileMeta['ori_path']);
    }

    /**
     * 目录打包下载
     */
    public function downloadToPackage($paths,$filePath){
        $arr = explode('/',$filePath);
        $isRoot = false;
        $isMine = false;
        if(count($arr)==3){
            $isRoot = true;
        }
        $fileOwnerId = $arr[1];
        $currentUser = $this->user;
        $currentUserId = $currentUser['user_id'];
        if($fileOwnerId==$currentUserId ){
            $isMine = true;
        }
        if($isRoot&&!$isMine){//如果是在根目录下且不是自己的目录 则后台控制不准取消共享
            throw new MFileopsException(
                Yii::t('api','Internal Server Error'),
                MConst::HTTP_CODE_409);
        }
        //打包下载限制
        header("Content-type: text/html; charset=utf-8");
        $limit = new DownloadPackageLimit();
        $limitCount = $limit->getLimitCount();
        $limitSize  = $limit->getLimitSize();
        $code = '';
        $fileNames = array();
        $user = $this->user;
        $userId = $user['user_id'];
        $paths = explode(',',$paths);
        foreach($paths as $path){
            $file = MiniFile::getInstance()->getByPath($path);
            if (empty($file)){
                echo  Yii::t('i18n','error_path');
                Yii::app()->end();
            }
            $code = $code.','.$file['id'] ;
            array_push($fileNames,$file['file_name']);
        }

        if(count($fileNames)>1){
            $packageName = 'miniyun';
        }else{
            $packageName = $fileNames[0];
        }

        //创建临时文件夹
        $fileSystem = new CFileSystem();
        MUtils::MkDirsLocal(DOCUMENT_TEMP.$userId);
        $storePath = DOCUMENT_TEMP.$userId."/".$packageName;
        $array = array();
        $ids = explode(",", $code);
        foreach ($ids as $id){
            $file = MiniFile::getInstance()->getById($id);
            if (empty($file)){
                continue;
            }
            if ($file["file_type"] == MConst::OBJECT_TYPE_FILE){    //属于自己的文件
                $array[] = $file;
            } else { //不属于自己的文件
                //查询共有多少个子目录
                $array[] = $file;
                $files = MiniFile::getInstance()->getChildrenByPath($file["file_path"]);
                $array = array_merge($array, $files);
            }
        }
        if (count($array) > $limitCount){
            echo  Yii::t('i18n','out_of_count');
            Yii::app()->end();
        }

        $size = $this->calculateSize($array);
        if ($size > $limitSize*1024*1024){
            echo  Yii::t('i18n','out_of_size');
            Yii::app()->end();
        }

        $path         = CUtils::removeUserFromPath($array[0]["file_path"]);
        $removeParent = pathinfo($path, PATHINFO_DIRNAME);
        if (strlen($removeParent) == 1){
            $removeParent = "";
        }
        //zip压缩
        $zip = new ZipArchive;
        $zipFile = $storePath.".zip";
        //删除上次存在的压缩文件
        $fileSystem->delete($zipFile);
        try {
            $zipFile        = mb_convert_encoding($zipFile, "gb2312", "UTF-8");
        } catch (Exception $e) {
            $zipFile        = $zipFile;
        }
        if ($zip->open($zipFile,ZIPARCHIVE::OVERWRITE) === TRUE) {
            //执行拷贝操作
            foreach ($array as $file){
                $fileType = $file["file_type"];
                $filePath = $file["file_path"];
                //获取存储文件的绝对路径
                if (!empty($removeParent)){
                    $relativePath = CUtils::str_replace_once($removeParent,"",CUtils::removeUserFromPath($filePath));
                } else {
                    $relativePath = CUtils::removeUserFromPath($filePath);
                }
                //打包加上nick
                $relativePath = $packageName. $relativePath;
                //转换文件编码为中文编码
                try {
                    $store        = mb_convert_encoding($relativePath, "gb2312", "UTF-8");
                } catch (Exception $e) {
                    $store        = $relativePath;
                }
                $hasRead = true;
                if ($userId == $file["user_id"] && $fileType == MConst::OBJECT_TYPE_FILE){    //属于自己的文件
                    $this->addToFile($zip, $file, $store, $fileSystem);
                } elseif ($userId != $file["user_id"] && $fileType == MConst::OBJECT_TYPE_FILE){ //不属于自己的文件
                    if ($hasRead){
                        $this->addToFile($zip, $file, $store, $fileSystem);
                    }
                } elseif ($userId == $file["user_id"] && $fileType == MConst::OBJECT_TYPE_DIRECTORY){ //属于自己的文件夹
                    $this->addToFolder($zip, $store);
                } else { //不属于自己的文件夹
                    if ($hasRead){
                        $this->addToFolder($zip, $store);
                    }
                }
            }
            $zip->close(); //关闭
        }
        if (!file_exists($zipFile)){
            echo  Yii::t('i18n','no_privilege');
            Yii::app()->end();
        };
        //进行下载
        CUtils::output($zipFile, "application/octet-stream", $packageName.".zip");
    }

    /**
     *
     * 将文件拷贝到临时目录
     *
     * @since 1.0.0
     */
    private function calculateSize($files){
        $size = 0;
        foreach ($files as $file){
            if ($file["file_type"] == MConst::OBJECT_TYPE_FILE){
                $size += $file["file_size"];
            }
        }
        return $size;
    }

    /**
     *
     * 将文件夹添加到临时目录
     *
     * @since 1.0.0
     */
    private function addToFolder($zip, $storePath){
        $zip->addEmptyDir($storePath);
    }

    /**
     *
     * 将文件拷贝到临时目录
     *
     * @since 1.0.0
     */
    private function addToFile($zip, $file, $storePath){
        $fileVersion =  MiniVersion::getInstance()->getVersion($file["version_id"]);
        $basePath  = MiniUtil::getPathBySplitStr ($fileVersion["file_signature"]);

        $dataObj = Yii::app()->data;
        $contents = $dataObj->get_contents($basePath);
        $zip->addFromString($storePath, $contents);
    }
    /**
     * 通过signature下载文件
     * @param $signature .文件signature
     * @param $filePath .文件路径
     */
    public function downloadBySignature($filePath,$signature){
        $item = explode("/",$filePath);
        $permissionModel = new UserPermissionBiz($filePath,$this->user['id']);
        $permissionArr = $permissionModel->getPermission($filePath,$this->user['id']);
        if($item[1]!==$this->user['id']&&count($permissionArr)==0){
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }
        $this->content($filePath,$signature,true);
    }

    /**
     * download current user file
     * 通过signature查询文件
     * @param $filePath
     * @param $signature
     * @param $forceDownload
     * @return mixed
     */
    public function content($filePath,$signature,$forceDownload=false){
        $share = new MiniShare();
        $miniFileMeta = $share->getMinFileMetaByPath($filePath);
        if($miniFileMeta!==NULL){
            MiniFile::getInstance()->getContent($miniFileMeta['ori_path'],$signature,null,$forceDownload);
        }
    }

    /**
     * 獲取文本文件信息！
     * @param $path
     * @param $signature
     * @return mixed
     */
    public function txtContent($path,$signature){
        $share = new MiniShare();
        $minFileMeta = $share->getMinFileMetaByPath($path);
        $file = array();
        $content = MiniFile::getInstance()->getTxtContent($minFileMeta['ori_path'],$signature);
        $file['content'] = $content;
        $file['type']    = $minFileMeta['mime_type'];
        return $file;
    }

    /**
     * 獲取office文件預覽地址
     * @param $path
     * @param $signature
     * @return string
     */
    public function doc($path,$signature){
        $share = new MiniShare();
        $minFileMeta = $share->getMinFileMetaByPath($path);
        $type     = explode('/',$minFileMeta['mime_type']);
        $fileType = '';
        if($type[1] == 'msexcel'){
            $fileType = 'xls';
        }else if($type[1] == 'msword'){
            $fileType = 'doc';
        }else if($type[1] == 'mspowerpoint'){
            $fileType = 'ppt';
        }else if($type[1] == 'zip'){
            $fileType = 'zip';
        }else if($type[1] == 'x-rar-compressed'){
            $fileType = 'rar';
        }
        $isSupport = apply_filters("is_support_doc");
        if($isSupport){
            $url = Yii::app()->getBaseUrl().'miniDoc/viewer/'. $fileType.'?path='.$path;
        }else{
            $url = "";
        }
        return $url;
    }
    /**
     * 上传文件
     */
    public function upload($path){
        //下面的方式将取得共享目录下的原始路径，如在自己目录下，会返回当前用户目录
//        $share = new MiniShare();
//        $minFileMeta = $share->getMinFileMetaByPath($path);
        //表示没有权限
//        if($minFileMeta===NULL){
//            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
//            return;
//        }
//        $filePath = $minFileMeta["ori_path"];
        $fileHandler = new MFilePostController();
        $uri  = '/files/miniyun' . $path;
        $fileHandler->invoke($uri);
    }

    /**
     * 获取已分享的文件
     * @param $path
     * @return bool
     */
    public  function shared($path){
        $userId = $this->user['id'];
        $absolutePath = MiniUtil::getAbsolutePath($userId,$path);
        $file = MiniFile::getInstance()->getByPath($absolutePath);
        $link = MiniLink::getInstance()->getByFileId($file['id']);
        if(empty($link['share_key'])){
            return false;
        }else{
            return true;
        }
    }
    /**
     * 清空回收站
     */
    public function cleanRecycle(){
        $user = $this->user;
        $files = MiniFile::getInstance()->getUserRecycleFile($user['user_id']);
        foreach($files as $file){
            MiniFile::getInstance()->deleteFile($file['id']);
        }
        if(MiniFile::getInstance()->trashCount($user['user_id']) == 0){
            return array('success'=>true);
        }else{
            return array('success'=>false);
        }
    }
    /**
     * extend
     */
    public function getExtendTactics(){
        $editors = MiniOption::getInstance()-> getOptionValue('online_editor');
        $editors = unserialize($editors);
        return $editors;
    }
    public function privilege($path){
        $isSharedPath = false;
        $pathArr = explode('/',$path);
        $masterId = $pathArr[1];
        if($masterId!=$this->user['id']){
            $isSharedPath = true;
        }else{
            $model = new GeneralFolderPermissionBiz($path);
            if($model->isParentShared($path)){//如果是父目录被共享
                $isSharedPath = true;
            }
        }
        if($isSharedPath){
            $permissionModel = new UserPermissionBiz($path,$this->user['id']);
            $permissionArr = $permissionModel->getPermission($path,$this->user['id']);
            if(!isset($permissionArr)){
                $permission = MConst::SUPREME_PERMISSION;
            }else{
                $permission = $permissionArr['permission'];
            }
        }else{
            $permission = MConst::SUPREME_PERMISSION;
        }
        $miniPermission = new MiniPermission($permission);
        $canRead = $miniPermission->canRead();
        return $canRead;
    }
    /**
     * 全文检索
     */
    public function contentSearch($key){
        $cl = new SphinxClient();
        //SPHINX_HOST来源{protected/config/miniyun-backup.php}
        $url = SPHINX_HOST;
        $urlInfo = parse_url($url);
        $port = 80;
        if(array_key_exists("port", $urlInfo)){
            $port = $urlInfo['port'];
        }
        $cl->SetServer($urlInfo['host'], $port); //注意这里的主机

        #$cl->SetMatchMode(SPH_MATCH_EXTENDED); //使用多字段模式
        //dump($cl);
        $index='main1';
        $res = $cl->Query($key, $index);
        if((int)$res['total']===0){
            return array();
        }
        $err = $cl->GetLastError();
        $ids = array_keys($res['matches']);
        $ids = join(',',$ids);
        $items = MiniSearchFile::getInstance()->search($ids);//将所有符合条件的做了索引的文件都取出来
        $opts = array(//摘要选项
            "before_match"          => "<span style='background-color: #ffff00'><b>",
            "after_match"           => "</b></span>",
            "chunk_separator"       => " ... ",
            "limit"                         => 100,
            "around"                        => 20,
        );
        $files = array();
        foreach($items as $item){//遍历，查询文件signature，根据signature判断当前用户有无浏览该文件权限
            $docs = array();
            $file['signature']=$item->file_signature;//相同的signature可能对应多个文件
            $fileVersionId = MiniVersion::getInstance()->getVersionIdBySignature($file['signature']);
            $list = MiniFile::getInstance()->getAllByVersionId($fileVersionId);
            foreach($list as $unit){//对具有相同signature的文件进行过滤
                $filePath = $unit['file_path'];
                $userId = (int)$this->user['id'];
                $permissionModel = new UserPermissionBiz($filePath,$userId);
                $permission = $permissionModel->getPermission($filePath,$userId);
                if($permission['permission'] == '000000000'||$permission['permission']=='011111111'){//没有读权限则不显示出来
                    continue;
                }
                if(empty($permission)){//如果上面读权限为空，则说明没有共享，这时当前用户只能看见自己的文件
                    $pathArr = explode('/',$filePath);
                    $masterId = $pathArr[1];
                    if($masterId!=$userId){
                        continue;
                    }
                }
                $file['file_name'] = $unit['file_name'];
                $file['file_path'] = $filePath;
                $file['type'] =
                $file['content']=$item->content;
                array_push($docs,$item->content);
                foreach ( array(0) as $exact ){//获取$entry即文件内容摘要
                    $opts["exact_phrase"] = $exact;
                    $res = $cl->BuildExcerpts ( $docs, $index, $key, $opts );
                    if ( !$res ){
                        die ( "ERROR: " . $cl->GetLastError() . ".\n" );
                    }else{
                        foreach ( $res as $entry )
                        {
                            $file['content']=$entry;
                        }
                    }
                }
                array_push($files,$file);
            }
        }
//        var_dump($files);exit;
        return $files;
    }
    /**
     * 在线浏览文件获得内容
     * @param $path 文件当前路径
     * @param $type 文件类型，可选择pdf/png
     * @return NULL
     */
    public function previewContent($path,$type){
        $file = MiniFile::getInstance()->getByPath($path);
        // 权限处理
         if(empty($file)){
            return array('success' =>false ,'msg'=>'file not existed');
        }
        $canRead = $this->privilege($path);
        if(!$canRead){
            throw new MFileopsException( Yii::t('api','no permission'),MConst::HTTP_CODE_409);
        }
        //获得文件当前版本对应的version
        $version = MiniVersion::getInstance()->getVersion($file["version_id"]); 
        $signature = $version["file_signature"];
        //$signature = "48a6fe3e2dd674ff5fa72009c0bca6c7f686e47f";
        $localPath = MINIDOC_CACHE_PATH.$signature."/".$signature.".".$type;  
        if(!file_exists($localPath)){  
            if($version["doc_convert_status"]===0){
                //TODO 执行文档转换脚本
            }
            if($version["doc_convert_status"]===-1){
                throw new MFileopsException( Yii::t('api','convert error'),MConst::HTTP_CODE_412);
            }
            //根据情况判断是否需要向迷你文档拉取内容
            $needPull = false;
            //ppt/excel/word的pdf/png需要向迷你文档拉取
            $mimeTypes = array("application/mspowerpoint","application/msword","application/msexcel");
            foreach ($mimeTypes as $mimeType) {
                if($mimeType===$version["mime_type"]){
                    $needPull = true;
                }
            }
            //pdf的png需要向迷你文档拉取
            if("application/pdf"===$version["mime_type"] && $type==="png"){
                $needPull = true;
            } 
            //TODO 如这里接管了文本文件在线浏览，这里还要处理其他mime_type,当前暂时未处理
            if($needPull){
                    $parentPath = dirname($localPath); 
                    //如果缓存目录不存在，则需要创建
                    if(!file_exists($parentPath)){
                        MUtils::MkDirsLocal($parentPath);
                    }
                    //文件不存在，则需要从迷你文档拉取文件内容
                    $url = MINIDOC_HOST."/".$signature."/".$signature.".".$type; 
                    $http = new HttpClient();
                    $http->get($url);
                    $status = $http->get_status();
                    if($status=="200"){
                        $content = $http->get_body();
                        //把文件内容存储到本地硬盘
                        file_put_contents($localPath, $content);
                        Yii::log($signature." get ".$type." success",CLogger::LEVEL_INFO,"doc.convert");
                    }else{
                        Yii::log($signature." get ".$type." error",CLogger::LEVEL_ERROR,"doc.convert");
                    }
            }else{
                //pdf and type=pdf, download file content 
                if("application/pdf"===$version["mime_type"] && $type==="pdf"){
                    $filePath = MiniUtil::getPathBySplitStr ($signature);
                    //data源处理对象
                    $dataObj = Yii::app()->data;
                    if ($dataObj->exists( $filePath ) === false) {
                        throw new MFilesException ( Yii::t('api',MConst::NOT_FOUND ), MConst::HTTP_CODE_404 );
                    }
                    $content = $dataObj->get_contents($filePath);
                    //把文件内容存储到本地硬盘
                    file_put_contents($localPath, $content);
                }
            }
        }

        if(file_exists($localPath)){
            if($type==="png"){
                $contentType = "image/png";
            }
            if($type==="pdf"){
                $contentType = "Content-type: application/pdf";
            }
            Header ( "Content-type: ".$contentType);
            echo(file_get_contents($localPath));
        }
    }
}

