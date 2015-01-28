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
}