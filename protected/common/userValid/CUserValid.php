<?php
/**
 * 应用程序模块
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class CUserValid extends CUserComponent
{
    public $validType;
    public $errorCode = UserIdentity::ERROR_UNKNOWN_IDENTITY;
    // 用户是否是冻结状态，默认false
    public static $userDisabled = false;

    /**
     * 构造函数初始化异常处理信息
     */
    public function  __construct()
    {

    }
    /**
     *
     * 验证用户信息
     * @param $userName
     * @param $password
     * @return bool
     */
    public function validUser($userName, $password){
        //迷你云第三方源验证
        //admin为系统保留账号，不能进行第三方用户源的验证
        if ($userName !== "admin"){
            //是否开启用户源插件
            $userSource = apply_filters('third_user_source', false);
            //用户源插件未开启时
            if ($userSource !== false) {
                $userInfo = array();
                $userInfo['userName'] = $userName;
                $userInfo['password'] = $password;
                $userData = $userSource->getUser($userInfo);
                //返回false的情况
                if (!$userData){
                    //
                    //不存在judgeSelf方法则直接返回错误码
                    //
                    if (!method_exists($userSource,'judgeSelf')){
                        //
                        //设置错误码
                        //
                        $this->errorCode = $userSource->errorCode;
                        return false;
                    }
                    //迷你云系统进行验证
                    if ($userSource->judgeSelf()){
                        $user = $this->validUserSelf($userName, $password);
                        return $user;
                    }
                    //
                    //设置错误码
                    //
                    $this->errorCode = $userSource->errorCode;
                    return false;
                }
                //存在该账号 则存储部分信息至迷你云数据库
                $userData["name"] = $userData["user_name"];
                $user = MiniUser::getInstance()->create($userData);
                if(!empty($userData['departmentData'])){
                    $model = new DepartmentBiz();
                    $model->import($userData['departmentData']);
                }
                if($user["user_status"]==0){
                    $this->errorCode = MConst::ERROR_USER_DISABLED;
                    return false;
                }
                return $user;
            }
        }
        //未开启则验证自有系统中是否存在此用户
        return $this->validUserSelf($userName, $password);
    }

    /**
     * 验证自有系统中是否存在此用户
     * @param string $userName
     * @param string $password
     * @return bool $use
     */
    public function validUserSelf($userName, $password){

        $user  =  MiniUser2::getInstance()->getUserByName2($userName);
        if ($user===NULL){
            //用户名不存在
           $this->errorCode = MConst::ERROR_USERNAME_INVALID;
           return false;
        }
        $signPassword = MSecret::passSign($password, $user["salt"]);
        if ($user["user_pass"] == $signPassword){
            //密码正确的情况下再验证用户是否被冻结
            if (!$user['user_status']){
                //返回用户被冻结错误码
                CUserValid::$userDisabled = true;
                $this->errorCode          = MConst::ERROR_USER_DISABLED;
                return false;
            }
            return $user;
        }

        //返回密码不正确 代码
        $this->errorCode = MConst::ERROR_PASSWORD_INVALID;
        return false;
    }
}