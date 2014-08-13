<?php
/**
 *  用户安装第三步
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class Setup3Form extends CFormModel
{
    public  $userName;//用户名
    public  $password;//密码
    public  $passwordConfirm;//确认密码
    public  $email;//电子邮件
    public function init(){
        $this->userName="admin";
    }
    public function rules()
    {
        $valid = array(
        array('userName, password, passwordConfirm, email', 'required'),
        array('password', 'length', 'min'=>5, 'max'=>20),
        array('password', 'checkPassword'),
        array('passwordConfirm', 'length', 'min'=>5, 'max'=>20),
        array('passwordConfirm', 'compare', 'compareAttribute' => 'password'),
        array('email', 'email'),
        );
        return $valid;
    }
    public function attributeLabels()
    {
        return array(
            'userName'=>Yii::t("front_common", "install_setup3_name"),
            'password'=>Yii::t("front_common", "install_setup3_password"),
            'passwordConfirm'=>Yii::t("front_common", "install_setup3_confirm"),
            'email'=>Yii::t("front_common", "install_setup3_email"),
        );
    }

    /**
     *
     * 检查密码是否正确
     */
    public function checkPassword(){
        if(!$this->hasErrors())
        {
            $strArr = explode(" ", $this->password);
            if(count($strArr)!=1){
                $this->addError('password',Yii::t("front_common", "install_setup3_password_have_space"));
                return;
            }
        }
    }

    public function save(){
        if($this->validate()){
            $userData = array(
                "name"=>$this->userName,
                "password"=>$this->password,
                "is_admin"=>1,
                "email"=>$this->email
            );
            MiniUser::getInstance()->create($userData);
            return true;
        }
        return false;
    }
}