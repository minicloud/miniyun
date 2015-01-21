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
                    $files = array_merge($fileArr,$fileList);
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
            $item['mime_type'] = $version['mime_type'];
            $item['createTime'] = $version['createTime'];
            $item['type'] = $file['file_type'];
            if($file['user_id']!=$userId){
                $item['type'] = 2;
            }
            $item['updated_at'] = $version['created_at'];
            $item['doc_convert_status'] = $version['doc_convert_status'];
            $items[] = $item;
        }
        $data['list'] = $items;
        $data['totalPage'] = ceil(($fileTotal+$sharedTotal)/$pageSize);
        return $data;
    } 
}