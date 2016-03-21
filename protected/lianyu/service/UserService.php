<?php
/**
 * 用户信息服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
class UserService extends MiniService{
    protected function anonymousActionList(){
        return array(
            "oauth2",
        );
    }
    /**
     * 用户登录验证入口
     */
    public function oauth2()
    {
        $biz = new UserBiz();
        return $biz->oauth2();
    }
    /**
     * 获取好友列表
     * @return array
     */
    public function getList() {
        $pageSize  = MiniHttp::getParam("page_size",10);
        $page      = MiniHttp::getParam("page",1);
        $biz    = new UserBiz();
        return $biz->getFriends($pageSize,$page);
    }
    /**
     * 搜索好友
     */
    public function search(){
        $key  = MiniHttp::getParam("key",'');
        $biz    = new UserBiz();
        return $biz->searchFriends($key);
    }

    /**
     * 获取用户组
     * @return array
     */
    public function getGroupList() {

    }

    public function getCode(){
        $model = new UserBiz();
        $data  = $model->getCodeByUserId();
        return $data;
    }
    public function saveSortType(){
        $type      = MiniHttp::getParam("type","");
        $sortOrder      = MiniHttp::getParam("sortOrder","");
        $biz    = new UserBiz();
        return $biz->saveSortType($type,$sortOrder);
    }
    /**
     * 新建隐藏空间密码 
     */
    public function newHideSpacePassword(){ 
        $biz    = new UserBiz();
        $passwd      = MiniHttp::getParam("passwd",""); 
        return $biz->newHideSpacePassword($passwd);
    }
    /**
     * 验证隐藏空间密码 
     */
    public function validHideSpacePassword(){
        $biz    = new UserBiz();
        $passwd      = MiniHttp::getParam("passwd",""); 
        return $biz->validHideSpacePassword($passwd);
    }
    /**
     * 重置隐藏空间密码
     */
    public function resetHideSpacePassword(){
        $biz    = new UserBiz();
        $oldPasswd      = MiniHttp::getParam("old_passwd",""); 
        $passwd      = MiniHttp::getParam("passwd",""); 
        return $biz->resetHideSpacePassword($oldPasswd,$passwd);
    }
    /**
     * 管理员重置隐藏空间密码
     */
    public function adminResetHideSpacePassword(){
        $biz    = new UserBiz(); 
        $userId      = MiniHttp::getParam("user_id",""); 
        $passwd      = MiniHttp::getParam("passwd",""); 
        return $biz->adminResetHideSpacePassword($userId,$passwd);
    }
}