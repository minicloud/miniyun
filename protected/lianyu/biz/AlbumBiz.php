<?php
/**
 * 相册业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class AlbumBiz  extends MiniBiz{


    public function getTimeLine(){
        $timeLine=array();
        $list=array();
        $data=array();
        $user      = $this->user;
        $albumList= MiniFile::getInstance()->getfileListByType($user["id"],"image");

        foreach($albumList as $index=>$value){
            if($index==0){
                $data['time']=$value['file_create_time'];
                $data['number']=0;
                $data['path']=MiniUtil::getRelativePath($value['file_path']);
                $list[]=$data;
            }else{
                if(date("y-m",$albumList[$index]['file_create_time'])!=date("y-m",$albumList[$index-1]['file_create_time'])){
                    $data['time']=$value['file_create_time'];
                    $data['number']=$index;
                    $data['path']=MiniUtil::getRelativePath($value['file_path']);
                    $list[]=$data;
                }
            }

        }
        $timeLine['list']=$list;
        $timeLine['total']=count($albumList);
        return $timeLine;
    }
    public function getAllSharedPath($userId){
        $publicFiles = MiniFile::getInstance()->getPublics();
        $groupShareFiles  = MiniGroupPrivilege::getInstance()->getAllGroups();
        $userShareFiles   = MiniUserPrivilege::getInstance()->getAllUserPrivilege();
        $filePaths  = array();
        $shareFiles = array_merge($publicFiles,$groupShareFiles,$userShareFiles);
        foreach($shareFiles as $file){
            $file = MiniFile::getInstance()->getByPath($file['file_path']);
            if(!empty($file)){
                if((($file['parent_file_id'] == 0) && $file['is_deleted'] == 0) || (($file['file_type'] == 2)&&($file['user_id'] != $userId))){
                    $filePaths[] = $file['file_path'];
                }
            }
        }
        $filePaths = array_unique($filePaths);
        return $filePaths;
    }
    public function getPage($page,$pageSize){
        $list=array();
        $pageSet=($page-1)*$pageSize;
        $user      = $this->user;
        $filePaths = $this->getAllSharedPath($user['id']);
        $albumList = MiniFile::getInstance()->getFileByUserType($user["id"],"image");
        $fileList["total"]=count($albumList);
        //获取当前文件夹下的子文件
        foreach($filePaths as $filePath){
            $images = MiniFile::getInstance()->searchFileByPathType($filePath);
            foreach($images as $image){
                $imageArr[] = $image;
            }
        }
        $sharedImgTotal = count($imageArr);
        if( $pageSet>$sharedImgTotal){
            $pageSet = $pageSet-$sharedImgTotal;
            $albums = MiniFile::getInstance()->getFileListPage( $pageSet,$pageSize,$user["id"],"image");
        }else{
            if($page*$pageSize<=$sharedImgTotal){
                 for($index=$pageSet;$index<$page*$pageSize-1;$index++){
                     $albums[] = $imageArr[$index];
                 }
            }else{
                for($index=$pageSet;$index<$sharedImgTotal;$index++){
                    $albumShared[] = $imageArr[$index];
                }
                $albumList =   $albums = MiniFile::getInstance()->getFileListPage(0,$pageSize*$page-$sharedImgTotal,$user["id"],"image");
                $albums = array_merge($albumList,$albumShared);
            }
        }

        foreach($albums as $value){
            $data["filename"]=$value['file_name'];
            $data["fileSize"]=$value['file_size'];
            $data["path"]= MiniUtil::getRelativePath($value['file_path']);
            $data["createTime"]=$value['file_create_time'];
            $list[]=$data;
        }
        $fileList['list']=$list;

        return $fileList;
    }

}

