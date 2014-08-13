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

    public function getPage($page,$pageSize){
        $list=array();
        $pageSet=($page-1)*$pageSize;
        $user      = $this->user;
        $albumList = MiniFile::getInstance()->getFileListPage( $pageSet,$pageSize,$user["id"],"image");
        foreach($albumList as $value){
            $data["filename"]=$value['file_name'];
            $data["fileSize"]=$value['file_size'];
            $data["path"]= MiniUtil::getRelativePath($value['file_path']);
            $data["createTime"]=$value['file_create_time'];
            $list[]=$data;
        }
        $albumList = MiniFile::getInstance()->getFileByUserType($user["id"],"image");
        $fileList['list']=$list;
        $fileList["total"]=count($albumList);
        return $fileList;
    }

}

