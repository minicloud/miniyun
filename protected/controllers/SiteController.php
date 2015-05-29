<?php
/**
 * error错误页面
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class SiteController extends CController {
    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest||MiniHttp::isPCClient()){
				echo $error['message'];
			}                
            else{
                $this->render('error', $error);
			}
        }
    } 
}