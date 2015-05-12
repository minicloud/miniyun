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
    private function getSharedImgPage($page,$pageSize,$sharedList){
        $cursor = $pageSize;
        $data   = array();
        $num    = 0;
        $index  = 0;
        for($i=0;$i<count($sharedList);$i++){
            $item  = $sharedList[$i];
            $path  = $item[0];
            $count = $item[1];
            if($cursor==0){
                return $data;
            }
            if($page<=$num+$count){
                if($page-$num+$cursor<=$count&&$page!=0){
                    $img = MiniFile::getInstance()->searchFileByPathType($path,$page-$num,$cursor);
                    array_splice($data,count($data),0,$img);
                    break;
                }else{
                    if($index==0){
                        $img = MiniFile::getInstance()->searchFileByPathType($path,$page-$num,$cursor);
                        array_splice($data,count($data),0,$img);
                        $cursor = $cursor-($num+$count-$page);
                        if($cursor<=0){
                            $cursor = 0;
                        }
                    }else{
                        $img = MiniFile::getInstance()->searchFileByPathType($path,0,$cursor);
                        array_splice($data,count($data),0,$img);
                        $cursor = $cursor-$count;
                        if($cursor<=0){
                            $cursor = 0;
                        }
                    }
                    $index++;
                }
            }
            $num = $num+$count;
        }
        return $data;

    }

    /**
     * 图片列表分页
     * @param $page
     * @param $pageSize
     * @return mixed
     */
    public function getPage($page,$pageSize){
        $fileList = array();
        $list   = array();
        $sharedList = array();
        $limit  = ($page-1)*$pageSize;
        $user   = $this->user;
        $shares = $this->getAllSharedPath($user['id']);
        $albumTotal = MiniFile::getInstance()->getImageTotal($user["id"],"image");
        //获得当前用户被共享目录里面的每个共享目录的子图片总数
        $sharedImgTotal = 0;
        foreach($shares as $filePath){
            $count = MiniFile::getInstance()->getImageTotalByPath($filePath);
            $sharedImgTotal = $count+$sharedImgTotal;
            if($count>0){
                $item = array();
                $item[] = $filePath;
                $item[] = $count;
                $sharedList[] = $item;
            }
        }
        $fileList["total"]=$albumTotal+$sharedImgTotal;
        //共享目录图片取完后，取自己的图片
        if( $limit>=$sharedImgTotal){
            $limit = $limit-$sharedImgTotal;
            $albums = MiniFile::getInstance()->getFileListPage( $limit,$pageSize,$user["id"],"image");
        }else{
            //共享目录图片下的图片
            if($page*$pageSize<$sharedImgTotal){
                $albums = $this->getSharedImgPage($limit,$pageSize,$sharedList);
            }else{
                //部分共享目录，部分自己的图片
                $albumShared = $this->getSharedImgPage($limit,$sharedImgTotal,$sharedList);
                $albumList   =  MiniFile::getInstance()->getFileListPage(0,$pageSize*$page-$sharedImgTotal,$user["id"],"image");
                if(count($albumList)>0){
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

