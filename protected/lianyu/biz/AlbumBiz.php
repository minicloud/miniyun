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
    private function getGroupIds($groupId,$ids){
        $group = MiniGroupRelation::getInstance()->getByGroupId($groupId);
        if(isset($group)){
            if($group['parent_group_id']!=-1){
                array_push($ids,$group['parent_group_id']);
                return $this->getGroupIds($group['parent_group_id'],$ids);
            }else{
                return $ids;
            }
        }
    }
    public function getAllSharedPath($userId){
        $userPrivileges = MiniUserPrivilege::getInstance()->getByUserId($userId);
        $filePaths = array();
        foreach($userPrivileges as $userPrivilege){
            array_push($filePaths,$userPrivilege['file_path']);
        }
        $groupPrivileges = MiniGroupPrivilege::getInstance()->getAllGroups();
        $publicPrivileges = MiniGroupPrivilege::getInstance()->getPublic();
        foreach($publicPrivileges as $publicPrivilege){
            array_push($filePaths,$publicPrivilege['file_path']);
        }
        $groupIds = array();
        foreach($groupPrivileges as $groupPrivilege){
            array_push($groupIds,$groupPrivilege['group_id']);
        }
        $groupIdsArr = array();
        $userGroupRelations =MiniUserGroupRelation::getInstance()->findUserGroup($userId);
        if(isset($userGroupRelations)){
            foreach($userGroupRelations as $userRelation){
                $groupId = $userRelation['id'];
                $arr = array();
                array_push($arr,$groupId);
                $result = MiniGroup::getInstance()->findById($groupId);
                if($result['user_id']>0){
                    array_push($groupIdsArr,$groupId);
                }else{
                    $ids = $this->getGroupIds($groupId,$arr);
                }
            }
            array_splice($groupIdsArr,0,0,$ids);
            $commonGroupIds = array_intersect($groupIdsArr,$groupIds);
            foreach($commonGroupIds as $commonGroupId){
                $groupInfos = MiniGroupPrivilege::getInstance()->getByGroupId($commonGroupId);
                foreach($groupInfos as $groupInfo){
                    $paths[] = $groupInfo['file_path'];
                }
            }
            if($paths){
                array_splice($filePaths,0,0,$paths);
            }
        }
        $paths = array();
        $filePaths = array_unique($filePaths);
        foreach($filePaths as $filePath){
            $result = MiniFile::getInstance()->getByPath($filePath);
            if(count($result)==0){
                continue;
            }
            $fileBiz = new FileBiz();
            $canRead = $fileBiz->privilege($filePath);
            if(!$canRead){
                continue;
            }
            $paths[] = $filePath;
        }
        return $paths;
    }
    public function getPage($page,$pageSize){
        $list=array();
        $pageSet=($page-1)*$pageSize;
        $user      = $this->user;
        $filePaths = $this->getAllSharedPath($user['id']);
        $albumList = MiniFile::getInstance()->getFileByUserType($user["id"],"image");
        //获取当前文件夹下的子文件
        foreach($filePaths as $filePath){
            $images = MiniFile::getInstance()->searchFileByPathType($filePath);
            foreach($images as $image){
                $imageArr[] = $image;
            }
        }
        $sharedImgTotal = count($imageArr);
        $fileList["total"]=count($albumList)+$sharedImgTotal;
        if( $pageSet>=$sharedImgTotal){
            $pageSet = $pageSet-$sharedImgTotal;
            $albums = MiniFile::getInstance()->getFileListPage( $pageSet,$pageSize,$user["id"],"image");
        }else{
            if($page*$pageSize<$sharedImgTotal){
                 for($index=$pageSet;$index<=$page*$pageSize-1;$index++){
                     $albums[] = $imageArr[$index];
                 }
            }else{
                for($index=$pageSet;$index<$sharedImgTotal;$index++){
                    $albumShared[] = $imageArr[$index];
                }
                $albumList =  MiniFile::getInstance()->getFileListPage(0,$pageSize*$page-$sharedImgTotal,$user["id"],"image");
                if(count($albumList)!=0){
                    $albums = array_merge($albumList,$albumShared);
                }else{
                    $albums = $albumShared;
                }
            }
        }
        foreach($albums as $value){
            $permission = UserPermissionBiz::getInstance()->getPermission($value['file_path'],$this->user['user_id']);
            if(!empty($permission)){
                $filePermission = new MiniPermission($permission['permission']);
                $data['canDelete'] = $filePermission->canDeleteFile();
            }else{
                $data['canDelete'] = true;
            }
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

