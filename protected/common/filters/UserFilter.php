<?php
/**
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class UserFilter extends CAPI2Component {
    /**
     * (non-PHPdoc)
     *
     * @see CAPI2Component::invoke()
     */
    public function invoke() {
        //
        // web端请求
        //
        
        if (isset($_POST['SESSIONID'])) {
            if (! isset(Yii::app()->request->cookies['PHPSESSID']) ||
                    $_POST['SESSIONID'] != Yii::app()->request->cookies['PHPSESSID']->value) {
                $user = Yii::app()->session["user"];
                if (empty($user)) {
                    session_destroy();
                    session_id($_POST['SESSIONID']); //注意这个时候session_id()这个函数是带有参数的
                    session_start();
                    if (isset(Yii::app()->request->cookies['PHPSESSID'])) {
                        $_COOKIE['PHPSESSID'] = $_POST['SESSIONID'];
                    }
                }
            }
        }
        
        if (isset(Yii::app()->session["user"]))
        {
            MUserManager::getInstance()->setIsWeb(true);
            MUserManager::getInstance()->getUserFromSession();
        } else {
            $this->oauth2();
        }
        return true;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @see CAPI2Component::buildResponse()
     */
    public function buildResponse() {
        
    }
    
    /**
     * oauth2.0的验证
     */
    private function oauth2() {
        $oauth = new PDOOAuth2();
        $token = $oauth->verifyAccessToken();
        if ($token) {
            $user = MUserManager::getInstance()->getUserOauth2( $token ["device_id"] ); // 获取用户的信息
            if($user === NULL){
              $oauth->errorWWWAuthenticateResponseHeader( OAUTH2_HTTP_FORBIDDEN, NULL, SYSTEM_ERROR_USER_DISABLED, 'This user has been disabled.', NULL, NULL );
              return false;
            }
            $user["appId"] = $token["appId"];
            MUserManager::getInstance()->setCurrentUser($user);
            if (!$user["user_status"]) {
                $oauth->errorWWWAuthenticateResponseHeader( OAUTH2_HTTP_FORBIDDEN, NULL, SYSTEM_ERROR_USER_DISABLED, 'This user has been disabled.', NULL, NULL );
            }
        } else {
            throw new CException( "Unauthorized", 401);
        }
        return true;
    }
}