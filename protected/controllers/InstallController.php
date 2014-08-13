<?php
define('ROOT_PATH', dirname(__FILE__) . '/../');
/**
 * 安装向导
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class InstallController extends CController {
    //支持的数据库列表
    var $supportDb = array("mysql"=>"Mysql");
    /**
     * 如果系统已初始化数据库，则不能进行第一步或第二步骤,如果系统没有初始化用户，则跳到管理员设置页面
     */
    private function notInstalled() {
        // 如果系统已安装该文件将不能被执行
        if(Yii::app()->params['app']['initialized']) {
            if(User::model()->count() == 0) {
                $this->redirect(Yii::app()->createUrl("install/setup3"));
                return false;
            }else {
                $this->redirect(Yii::app()->createUrl("site/index"));
            }
            return false;
        }

        return true;
    }
    /**
     * 如果系统已经存在用户，则不能出现用户设置向导
     */
    private function checkInitDbConfigFile() {
        if(Yii::app()->params['app']['initialized'] == false) {
            // 如果系统没有初始化则跳回到初始化向导首页
            $this->redirect(Yii::app()->createUrl("install/index"));
            return false;
        }
        if(User::model()->count() > 1) {
            $this->redirect(Yii::app()->createUrl("install/index"));
            return false;
        }
        return true;

    }

    public function actionIndex() {
        if($this->notInstalled()) {
            $this->layout = '//layouts/install';
            $this->render('index', array());
        }
    }

    /**
     * 基本依赖检测
     */
    public function actionSetup1() {
        if($this->notInstalled()) {
            $this->layout = '//layouts/install';
            $model = new Setup1Form();
            $model->db = "mysql";
            $model->check();
            if(Yii::app()->request->isPostRequest) {
                if(count($model->getErrors()) == 0) {
                    $this->redirect(Yii::app()->createUrl("install/setup2"));
                }
            }
            $this->render('setup1', array('model' => $model, "supportDb"=>$this->supportDb, "db"=>"mysql"));
        }
    }

    /**
     * 初始化数据库
     *
     * @since 1.0.4
     */
    public function actionSetup2() {
        if($this->notInstalled()) {
            $this->layout = '//layouts/install';
            $model = new Setup2Form();
            $form = "Setup2Form";
            if(Yii::app()->request->isPostRequest) {
                $model->attributes = $_POST[$form];
                if($model->save()) {
                    $this->redirect(Yii::app()->createUrl("install/setup3"));
                }
            }
            $this->render('setup2', array('model' => $model));
        }
    }
    /**
     * 初始化管理员
     */
    public function actionSetup3() {
        if($this->checkInitDbConfigFile()) {
            $this->layout = '//layouts/install';
            $model = new Setup3Form();
            if(Yii::app()->request->isPostRequest) {
                $model->attributes = $_POST['Setup3Form'];
                if($model->save()) {
                    $this->redirect(Yii::app()->createUrl("install/setup4"));
                }
            }
            $this->render('setup3', array('model' => $model));
        }
    }
    /**
     * 成功安装页面
     */
    public function actionSetup4() {
        $this->layout = '//layouts/install';
        $this->render('setup4', array());
    }

}