<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class ViewerController extends CController {

    
    public function actionDoc(){

        $model    = new DocModel();
        if($model->isNeedConvert()){
            $this->render('convert', array('model'=>$model));
        }else{
            $this->render('showDoc',array('model'=>$model));
        }

    }

    
    public function actionPpt(){
        $model    = new PptModel();
        if($model->isNeedConvert()){
            $this->render('convert', array('model'=>$model));
        }else{
            $this->render('showPpt',array('model'=>$model));
        }
    }
    
    public function actionXls(){

        $model    = new XlsModel();
        if($model->isNeedConvert()){
            $this->render('convert', array('model'=>$model));
        }else{
            $this->render('showXls',array('model'=>$model));
        }

    }
    
    public function actionRar() {

        $model = new RarModel();
        if($model->isNeedConvert()){
            $this->render('convert', array('model'=>$model));
        }else{
            $this->render('showZip', array('model'=>$model));
        }

    }
    
    public function actionZip() {
        $model = new ZipModel();
        if($model->isNeedConvert()){
            $this->render('convert', array('model'=>$model));
        }else{
            $this->render('showZip', array('model'=>$model));
        }
    }
    
    public function actionZipListTemplate() {

        $this->render('zipListTemplate');

    }
    public function actionNoServerVal() {

        $this->render('noServerVal');

    }

}