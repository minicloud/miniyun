<?php
/**
 * 用户信息业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserBiz  extends MiniBiz{
    /**
     * @param $pageSize
     * @param $page
     * @return array
     */
    public function findSame($v1,$v2){
        if ($v1===$v2)
        {
            return 0;
        }
        if ($v1 > $v2) return 1;
        {
            return -1;
        }
        return 1;
    }
    public function getFriends($pageSize,$page){
        $userId = $this->user["id"];
        $userCount = MiniUser::getInstance()->getEnableCount()-1;
        $items = MiniUser::getInstance()->getPageList($userId,"id desc",$pageSize,($page-1)*$pageSize);
        $users = array();
        foreach($items as $item){
            $friend = array();
            $friend["id"]   = $item["id"];
            $friend["nick"] = $item["nick"];
            $friend["name"] = $item["user_name"];
            $friend["avatar"] = $item['avatar'];
            $arr = MiniUserGroupRelation::getInstance()->findUserGroup($item["id"]);//找到好友对应的群组(这里会关联到非当前用户新建的用户组，下面解决此问题)
            $ownerGroup = MiniGroup::getInstance()->getList($userId);
            $ownerGroupList = $ownerGroup['list'];//找到当前用户拥有的群组
            $result = array();
            foreach($arr as $brr){//遍历好友的群组，
                if(in_array($brr,$ownerGroupList)){//找出其群组与当前用户相同的则放入该用户的user_group,就是此处的$result
                    array_push($result,$brr);
                }
            }
            $friend["user_group"]= $result;
            array_push($users,$friend);
        }
        $data = array();
        $data["count"] = $userCount;
        $data["users"] = $users;
        return $data;
    }

    /**
     * 搜索用户
     */
    public function searchFriends($key){
        $userId = $this->user["id"];
        $items = MiniUser::getInstance()->searchByName($userId,$key);
        $users = array();
        foreach($items as $item){
            $friend = array();
            $friend["id"]   = $item["id"];
            $friend["nick"] = $item["nick"];
            $friend["name"] = $item["user_name"];
            $friend["avatar"] = $item['avatar'];
            $arr = MiniUserGroupRelation::getInstance()->findUserGroup($userId,$item["id"]);
            $friend["user_group"]= $arr;
            array_push($users,$friend);
        }
        return $users;
    }
    /**
     * 获取用户组列表
     */
    public function getGroupList(){

    }

    public function getCodeByUserId(){
        $data= MiniOption::getInstance()->getOptionValue("code");
        return $data;
    }
}