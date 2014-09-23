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
            $arr = MiniUserGroupRelation::getInstance()->findUserGroup($item["id"]);
            $friend["user_group"]= $arr;
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
            $friend["nick"] = $item["nick"];
            $friend["name"] = $item["user_name"];
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