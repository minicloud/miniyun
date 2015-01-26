<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 15-1-13
 * Time: 上午10:25
 */
class PluginMiniDocBiz extends MiniBiz{
    /**
     *根据文件的Hash值下载内容
     * @param $fileHash 文件hash值
     * @throws 404错误
     */
    public function download($fileHash){
        $version = MiniVersion::getInstance()->getBySignature($fileHash);
        if(!empty($version)){
            //根据文件内容输出文件内容
            MiniFile::getInstance()->getContentBySignature($fileHash,$fileHash,$version["mime_type"]);
        }else{
            throw new MFileopsException(
                Yii::t('api','File Not Found'),
                404);
        }

    }
    /**
     *给迷你云报告文件转换过程
     * @param $fileHash 文件hash值
     * @param $status 文件状态
     * @return array
     */
    public function report($fileHash,$status){
        $version = MiniVersion::getInstance()->getBySignature($fileHash);
        if(!empty($version)){
            //文件转换成功
            if($status==="1"){
                PluginMiniDocVersion::getInstance()->updateDocConvertStatus($fileHash,2);
                //通过回调方式让迷你搜索把文件文本内容编制索引到数据库中
                do_action("pull_text_search",$fileHash);
            }
            //文件转换失败
            if($status==="0"){
                PluginMiniDocVersion::getInstance()->updateDocConvertStatus($fileHash,-1);
            }
        }
        return array("success"=>true);
    }
    /**
     * 获得账号里所有的指定文件类型列表
     * @param $page 当前页码
     * @param $pageSize 当前每页大小
     * @param $mimeType 文件类型
     * @return array
     */
    public function getList($page,$pageSize,$mimeType){
        $mimeTypeList = array("ppt"=>"application/mspowerpoint","word"=>"application/msword","excel"=>"application/msexcel","pdf"=>"application/pdf");
        $userId = $this->user['id'];
        $fileTotal = MiniFile::getInstance()->getTotalByMimeType($userId,$mimeTypeList[$mimeType]);
        $pageSet=($page-1)*$pageSize;
        $albumBiz = new AlbumBiz();
        $filePaths = $albumBiz->getAllSharedPath($userId);
        $sharedTotal = 0;
        $files = array();
        if(count($filePaths)!=0){
             //获取当前文件夹下的子文件
            foreach($filePaths as $filePath){
                $sharedFiles = MiniFile::getInstance()->getSharedDocByPathType($filePath,$mimeTypeList[$mimeType]);
                if(count($sharedFiles)==0){
                    continue;
                }
                foreach($sharedFiles as $sharedFile){
                    $sharedDocs[] = $sharedFile;
                }
            }
            $sharedTotal = count($sharedDocs);
        }
         if($pageSet>=$sharedTotal){
            $pageSet = $pageSet-$sharedTotal;
            $files = MiniFile::getInstance()->getByMimeType($userId,$mimeTypeList[$mimeType],$pageSet,$pageSize);
        }else{
            if($page*$pageSize<$sharedTotal){
                 for($index=$pageSet;$index<=$page*$pageSize-1;$index++){
                     $files[] = $sharedDocs[$index];
                 }
            }else{
                for($index=$pageSet;$index<$sharedTotal;$index++){
                   $fileArr[] = $sharedDocs[$index];
                }
                $fileList =  MiniFile::getInstance()->getByMimeType($userId,$mimeTypeList[$mimeType],0,$pageSize*$page-$sharedTotal);
                if(count($fileList)!=0){
                    $files = array_merge($fileArr,$fileList);
                }else{
                    $files = $fileArr;
                }
            }
        }
        // $files = MiniFile::getInstance()->getByMimeType($userId,$mimeTypeList[$mimeType],($page-1)*$pageSize,$pageSize);
        $items = array();
        foreach($files as $file){
            $version = PluginMiniDocVersion::getInstance()->getVersion($file['version_id']);
            $item['file_name'] = $file['file_name'];
            $item['path'] = $file['file_path'];
            $item['signature'] = $version['file_signature'];
            $item['mime_type'] = $version['mime_type'];
            $item['createTime'] = $version['createTime'];
            $item['type'] = $file['file_type'];
            if($file['user_id']!=$userId){
                $item['type'] = 2;
            }
            $item['updated_at'] = $version['created_at'];
            $item['doc_convert_status'] = $version['doc_convert_status'];
            if($version['doc_convert_status']==2){
                $url = "http://".$_SERVER['HTTP_HOST']."/temp/".$version['file_signature'].'/'.$version['file_signature'].".png" ;
                if(!file_exists($url)){
                    $this->cache($version['file_signature'],'png');
                }
                $item['url'] = $url;
            }
            $items[] = $item;
        }
        $data['list'] = $items;
        $data['totalPage'] = ceil(($fileTotal+$sharedTotal)/$pageSize);
        return $data;
    }
    /**
     * 判断权限是否匹配
     * @param $path
     * @return bool
     */
    private function privilege($path){
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
     * 在线浏览文件获得内容
     * @param $path 文件当前路径
     * @param $type 文件类型，可选择pdf/png
     * @throws
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