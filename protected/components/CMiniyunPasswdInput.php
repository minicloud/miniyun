<?php
/**
 * 密码输入框并检查密码强度
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class CMiniyunPasswdInput extends CWidget{
    public $model;
    public $form;
    public $passDesc;
    public $view = 'miniyunPasswdInput';
    public function init()
    {

    }

    public function run()
    {
        $this->render($this->view, array(
         "form"=>$this->form,
         "model"=>$this->model,
         "passDesc"=>$this->passDesc
        ));
    }
}