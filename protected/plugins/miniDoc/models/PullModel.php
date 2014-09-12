<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class PullModel extends CFormModel {
    private $token;
    private $lastId;
    public function init(){
        $this->token = Yii::app()->request->getParam('token','');
        $this->lastId = Yii::app()->request->getParam('lastId','');
    }
    public function getDocs(){
        $service = MiniDocNode::getInstance()->getByToken($this->token);
    }
}