<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class PushController extends CController {

    
    public function actionServer(){
        $retValue = array();
        $server = MiniDocNode::getInstance()->getBestServer();
        if ($server===NULL) {
            $retValue["success"] = false;
        }else{
            $retValue["success"] = true;
            $host = $server["ip"].":".$server["port"];
            $retValue["host"] = $host;
            $retValue["id"] = $server["id"];
        }
        echo(json_encode($retValue));
    }
    
    public function actionDownload() {
        $model = new ConvertActionModel();
        if (!$model->getContent()) {
            $errors = $model->getError('hash');
            throw new CHttpException(404, $errors, 404);
        }
    }
    
    public function actionSuccess() {
        $model = new ConvertActionModel();
        if (!$model->success()) {
            $errors = $model->getError('hash');
            throw new CHttpException(404, $errors, 404);
        }
    }
    
    public function actionLimitSize(){
        $size      = MiniOption::getInstance()->getOptionValue(MiniDocNode::$OPTION_KEY);
        if($size===NULL){
            $limitSize = array('excelSize'=>'1','pptSize'=>'1','wordSize'=>'1','zip_rarSize'=>'1');
            $limitSize = serialize($limitSize);
            MiniOption::getInstance()->setOptionValue(MiniDocNode::$OPTION_KEY,$limitSize);
            $size1      = MiniOption::getInstance()->getOptionValue(MiniDocNode::$OPTION_KEY);
            $limitSize = unserialize($size1);
        }else{
            $limitSize = unserialize($size);
        }
        echo json_encode($limitSize);
    }

}