<?php
/**
 * 过滤器模块
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MUserFilter extends MApplicationComponent implements MIController{

    /**
     * 控制器执行主逻辑函数
     *
     * @param null $uri
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null)
    {
        $path = explode('?', $uri);
        $parts = array_slice(explode('/', $path[0]), 1);
        if (isset($parts[0]) && ($parts[0] == "oauth" || $parts[0] == "oauth2")) {
            return;
        }

        $this->oauth2Judge();
    }

    /**
     *
     * oauth2.0的验证
     */
    public function oauth2Judge(){
        $oauth = new PDOOAuth2();
        $token = $oauth->verifyAccessToken();
        if ($token) {
            $user          = MUserManager::getInstance()->getUserOauth2($token["device_id"]);//获取用户的信息
            if($user === NULL){
              $oauth->errorWWWAuthenticateResponseHeader( OAUTH2_HTTP_DISABLED, NULL, OAUTH2_HTTP_DISABLED, 'This user has been disabled.', NULL, NULL );
              return false;
            }
            $user["appId"] = $token["appId"];//修改了User的appId值
            MUserManager::getInstance()->setCurrentUser($user);
            if(!$user["user_status"]){
                $oauth->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_DISABLED, NULL, SYSTEM_ERROR_USER_DISABLED, 'This user has been disabled.', NULL, NULL);
            }
        }
    }

}