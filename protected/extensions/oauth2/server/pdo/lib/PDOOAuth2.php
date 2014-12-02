<?php

/**
 * @file
 * Sample OAuth2 Library PDO DB Implementation.
 */

// Set these values to your database access info.
define("PDO_DSN", "mysql:dbname=".DB_NAME.";host=".DB_HOST.";port=".DB_PORT);
define("PDO_USER", DB_USER);
define("PDO_PASS", DB_PASSWORD);


/**
 * OAuth2 Library PDO DB Implementation.
 */
class PDOOAuth2 extends OAuth2 {

    private $db;

    /**
     * Overrides OAuth2::__construct().
     */
    public function __construct() {
        parent::__construct();
        $this->db = Yii::app()->db;
    }

    /**
     * Release DB connection during destruct.
     */
    function __destruct() {
        //$this->db = NULL; // Release db connection
    }

    /**
     * Handle PDO exceptional cases.
     */
    private function handleException($e) {
        echo "Database error: " . $e->getMessage();
        exit;
    }

    /**
     * Little helper function to add a new client to the database.
     *
     * Do NOT use this in production! This sample code stores the secret
     * in plaintext!
     *
     * @param $client_id
     *   Client identifier to be stored.
     * @param $client_secret
     *   Client secret to be stored.
     * @param $redirect_uri
     *   Redirect URI to be stored.
     */
    public function addClient($client_id, $client_secret, $redirect_uri) {
        try {
            $time = date("Y-m-d H:i:s", time());
            $sql = "INSERT INTO " .DB_PREFIX. "_clients (client_id, client_secret, redirect_uri, created_at, updated_at) VALUES (:client_id, :client_secret, :redirect_uri, :created_at, :updated_at)";
            $stmt = $this->db->createCommand($sql);
            $stmt->bindParam(":client_id", $client_id, PDO::PARAM_STR);
            $stmt->bindParam(":client_secret", $client_secret, PDO::PARAM_STR);
            $stmt->bindParam(":redirect_uri", $redirect_uri, PDO::PARAM_STR);
            $stmt->bindParam(":created_at", $time, PDO::PARAM_STR);
            $stmt->bindParam(":updated_at", $time, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Implements OAuth2::checkClientCredentials().
     *
     * Do NOT use this in production! This sample code stores the secret
     * in plaintext!
     */
    protected function checkClientCredentials($client_id, $client_secret = NULL) {
        $client = $this->getClient($client_id); 
        if ($client["client_secret"] == $client_secret){
            return $client;
         }
         return FALSE;
    }
    protected function getClient($client_id){
    	return MiniClient2::getInstance()->getClient2($client_id); 
    }

    /**
     * Implements OAuth2::getRedirectUri().
     */
    protected function getRedirectUri($client_id) {
        try {
            $sql = "SELECT redirect_uri FROM " .DB_PREFIX. "_clients WHERE client_id = :client_id";
            $stmt = $this->db->createCommand($sql);
            $stmt->bindParam(":client_id", $client_id, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->queryRow();

            if ($result === FALSE)
            return FALSE;

            return isset($result["redirect_uri"]) && $result["redirect_uri"] ? $result["redirect_uri"] : NULL;
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Implements OAuth2::getAccessToken().
     */
    protected function getAccessToken($oauth_token) {
        $accessInfo = MiniToken2::getInstance()->getAccessInfo2($oauth_token);
        return $accessInfo;
    }

    /**
     * Implements OAuth2::setAccessToken().
     */
    protected function setAccessToken($oauth_token, $client_id, $device_id, $expires, $scope = NULL) {
         MiniToken::getInstance()->create($oauth_token,$client_id,$device_id,$expires,$scope);
    }
    /**
     * Overrides OAuth2::getSupportedGrantTypes().
     */
    protected function getSupportedGrantTypes() {
        return array(
        OAUTH2_GRANT_TYPE_AUTH_CODE,OAUTH2_GRANT_TYPE_USER_CREDENTIALS,OAUTH2_GRANT_TYPE_REFRESH_TOKEN,OAUTH2_GRANT_TYPE_FREE_LOGIN
        );
    }

    /**
     * Overrides OAuth2::getAuthCode().
     */
    protected function getAuthCode($code) {
        try {
            $sql = "SELECT code, client_id, redirect_uri, expires, scope FROM " .DB_PREFIX. "_auth_codes WHERE code = :code";
            $stmt = $this->db->createCommand($sql);
            $stmt->bindParam(":code", $code, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->queryRow();

            return $result !== FALSE ? $result : NULL;
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Overrides OAuth2::setAuthCode().
     */
    protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = NULL) {
        try {
            $time = date("Y-m-d H:i:s", time());
            $sql = "INSERT INTO " .DB_PREFIX. "_auth_codes (code, client_id, redirect_uri, expires, scope, created_at, updated_at) VALUES (:code, :client_id, :redirect_uri, :expires, :scope, :created_at, :updated_at)";
            $stmt = $this->db->createCommand($sql);
            $stmt->bindParam(":code", $code, PDO::PARAM_STR);
            $stmt->bindParam(":client_id", $client_id, PDO::PARAM_STR);
            $stmt->bindParam(":redirect_uri", $redirect_uri, PDO::PARAM_STR);
            $stmt->bindParam(":expires", $expires, PDO::PARAM_INT);
            $stmt->bindParam(":scope", $scope, PDO::PARAM_STR);
            $stmt->bindParam(":created_at", $time, PDO::PARAM_STR);
            $stmt->bindParam(":updated_at", $time, PDO::PARAM_STR);

            $stmt->execute();
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Overrides OAuth2::checkUserCredentials()
     * 
     * @since 1.0.7
     */
    protected function checkUserCredentials($clientId, $userName, $password){
        $device = $this->judgeDevice();
        if ($device != false){
            $store = array(
              "device_id" => $device["id"],
              "scope" => "all"
              );
              return $store;
        }
        return false;
    }


    /**
     * Overrides OAuth2::getToken()
     */
    protected function getToken($clientId, $scope, $deviceId){
        //如是网页版，则把前一个Token删除，确保系统只有一个用户登录网页版
        //如果是在pc客户端解锁页面的时候，不用删除设备
        if(MiniHttp::clientIsBrowser()){
            $source  = $_REQUEST['source'];
            if(empty($source) || $source!="unlock"){
                MiniToken::getInstance()->deleteByDeviceId($deviceId);
            }
        }
        $tokenOauth = MiniToken::getInstance()->getToken4Login($clientId,$deviceId);
        if (!isset($tokenOauth)) {
            return false;
        } 
        $token = array(
          "access_token" => $tokenOauth["oauth_token"],
          "expires_in"   => $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME),
          "scope"        => $tokenOauth["scope"]
        );

        // Issue a refresh token also, if we support them
        if (in_array(OAUTH2_GRANT_TYPE_REFRESH_TOKEN, $this->getSupportedGrantTypes())) {
            $refreshToken           = MiniRefreshToken::getInstance()->getToken($tokenOauth["oauth_token"]);
            $token["refresh_token"] = $refreshToken["refresh_token"];
        }

        if ($tokenOauth["expires"] < time()){
            $this->setVariable('_old_oauth_token', $tokenOauth["oauth_token"]);
            if (isset($refreshToken)){
                $this->setVariable('_old_refresh_token', $refreshToken["refresh_token"]);
            }
            return false;
        }

        return $token;
    }

    /**
     * Overrides OAuth2::setRefreshToken()
     */
    protected function setRefreshToken($oauthToken, $refreshToken, $clientId, $expires, $scope = NULL){
        MiniRefreshToken::getInstance()->create($oauthToken,$refreshToken,$clientId,$expires,$scope);
    }

    /**
     * Overrides OAuth2::getRefreshToken()
     */
    protected function getRefreshToken($refresh_token) {
        try {
            $sql = "SELECT client_id, oauth_token as token, refresh_token, expires, created_at, updated_at FROM " .DB_PREFIX. "_refresh_token WHERE refresh_token = :refresh_token";
            $stmt = $this->db->createCommand($sql);
            $stmt->bindParam(":refresh_token", $refresh_token, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->queryRow();

            return $result !== FALSE ? $result : NULL;
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Overrides OAuth2::unsetOauthToken()
     */
    protected function unsetOauthToken($oauth_token) {
        try {
            $sql = "DELETE  FROM " .DB_PREFIX. "_tokens WHERE oauth_token = :oauth_token";
            $stmt = $this->db->createCommand($sql);
            $stmt->bindParam(":oauth_token", $oauth_token, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Overrides OAuth2::unsetRefreshToken()
     */
    protected function unsetRefreshToken($refresh_token) {
        try {
            $sql = "DELETE  FROM " .DB_PREFIX. "_refresh_token WHERE refresh_token = :refresh_token";
            $stmt = $this->db->createCommand($sql);
            $stmt->bindParam(":refresh_token", $refresh_token, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            $this->handleException($e);
        }
    }

    /**
     * 执行查询用户设备信息
     *
     * @return mixed $value 返回最终需要执行完的结果
     * @throws
     * @since 1.0.7
     */
    private function judgeDevice()
    {
        $deviceType  = $_REQUEST['device_type'];
        $deviceName  = urldecode($_REQUEST['device_name']);
        $deviceInfo  = $_REQUEST['device_info'];

        if (!empty($deviceType) && !empty($deviceName) && !empty($deviceInfo)){
        } else {
            # 当用户传递过来的设备信息都为空时，表示为三方开发者
            if (empty($deviceType) && empty($deviceName) && empty($deviceInfo)){
                $deviceType = 10;
                $deviceName = "第三方用户";
                $deviceInfo = "第三方设备";
            }else{
                throw new MAuthorizationException("param_is_null", MConst::HTTP_CODE_400);
            }
        }
        //如被锁定，且是网页端则不进行登录。
		$userName = $_REQUEST['username'];
		if(empty($userName)){
			$userName = $_POST['username'];
		}
        $name   = urldecode($userName);
        $isLock = MiniUser::getInstance()->isLock($name);
        $user   = false;
        if(!($isLock && MiniHttp::clientIsBrowser())){
            $user = self::searchUser();
        }
        if ($user === false){
            //如果用户在非锁定状态，则错误数+1
            if(!$isLock){
                MiniUser::getInstance()->setPasswordError($name);
            }
            return false;
        }else{
            //如果用户登录正确，则把密码错误次数清空
            MiniUser::getInstance()->cleanPasswordError($name);
        }
        if (!$user["user_status"]){
            throw new MAuthorizationException("User has disabled.", MConst::HTTP_CODE_407);
        }

        //对设备进行检测
        $device = DeviceManager::getDevice($user["id"], $deviceType, $deviceName, $deviceInfo);
        // 检测设备是否激活
        $device = apply_filters('valid_device', $device);
        return $device;
    }


    /**
     * 执行查询用户信息操作
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function searchUser()
    {
        $freeLogin = $_REQUEST['free_login'];
        if(isset($freeLogin) && $freeLogin=="yes"){
            //免登陆
            $params = array();
            $params["type"] = $_REQUEST['type'];
            $params["name"] = $_REQUEST['name'];
            $params["password"] = $_REQUEST['password'];
            $params["sign"] = $_REQUEST['sign'];
            $params["time"] = $_REQUEST['time'];
            $params["token"] = $_REQUEST['token'];
            $user = apply_filters('free_login', $params);
            if ($user){
                return $user;
            }
            return false;
        }else{
            //正常用户登陆模式
			$userName = $_REQUEST['username'];
			if(empty($userName)){
				$userName = $_POST['username'];
			}
            $name         = urldecode($userName);
            $password     = $_REQUEST['password'];
            $requestToken = $_REQUEST['client_id'];
            if (empty($name) || empty($password) || empty($requestToken))
            {
                Yii::trace(Yii::t('api',"用户名或者密码为空"),"miniyun.api");
                return false;
            }


            //签名密码
            $key          = substr($requestToken, 0, 8);
            //验证用户是否正确
            $user = $this->validUser($key, $name, $password);
            if ($user){
                return $user;
            }

            return false;
        }
    }

    /**
     * 验证密码是否正确
     */
    private function validUser($key, $name, $cipherText){
        //如果是浏览器客户端，采用明文传输密码
        if(MiniHttp::clientIsBrowser()){
            $password = $cipherText;
        }else{
            //进行des解码解析出明文密码
            $password = MSecret::decryptHex($key, $cipherText);
        }
        //进行多用户源的验证
        $uerObject    = new CUserValid();
        $user        = $uerObject->validUser($name, $password);
        if(!isset($user) || !$user){
            return false;
        }
        return $user;
    }
   
    /**
     * 返回输出的错误
     *
     */
    private function ret_error($message){
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: text/plain');
         
        echo $message;
    }
}
