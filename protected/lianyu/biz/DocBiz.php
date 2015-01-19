<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 15-1-13
 * Time: 上午10:25
 */
class DocBiz extends MiniBiz
{
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
                    $files = array_merge($fileList,$fileArr);
                }else{
                    $files = $fileArr;
                }
            }
        }
        // $files = MiniFile::getInstance()->getByMimeType($userId,$mimeTypeList[$mimeType],($page-1)*$pageSize,$pageSize);
        $items = array();
        foreach($files as $file){
            $version = MiniVersion::getInstance()->getVersion($file['version_id']);
            $item['file_name'] = $file['file_name'];
            $item['path'] = $file['file_path'];
            $item['signature'] = $version['file_signature'];
            $item['mime_type'] = $version['mime_type'];
            $item['createTime'] = $version['createTime'];
            $item['updated_at'] = $version['updated_at'];
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
    public function cache($fileHash,$type){
        $url = "http://minidoc.miniyun.cn/".$fileHash."/".$fileHash.".".$type;
        $savePath = MINIYUN_PATH. DS .'temp';
        $confContent    =  file_get_contents($url);
        if(!file_exists($savePath.DS .$fileHash)){
            mkdir($savePath.DS .$fileHash);
        }
        file_put_contents($savePath.DS .$fileHash.DS .$fileHash.".".$type, $confContent);
    }
    public function convert($fileHash){
        $version = MiniVersion::getInstance()->getBySignature($fileHash);
        if(empty($version)){
            throw new MFileopsException(
                Yii::t('api','File Not Found'),
                404);
        }
        if($version['doc_convert_status']==0||$version['doc_convert_status']==1||$version['doc_convert_status']==-1){
            return array('success'=>false,'doc_convert_status'=>$version['doc_convert_status']);
        }else{
             if($version['doc_convert_status']==2){
                 $url = "http://".$_SERVER['HTTP_HOST']."/temp/".$version['file_signature'].'/'.$version['file_signature'].".pdf" ;
                 if(!file_exists($url)){
                     $this->cache($fileHash,'pdf');
                 }
                 return array('success'=>true,'doc_convert_status'=>$version['doc_convert_status']);
             }
        }

    }
}