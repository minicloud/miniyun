<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-7-2
 * Time: 上午9:11
 */
class EditorManageService extends MiniService{
    /**
     * 添加app
     */
    public function createEditor(){
        $extend           = MiniHttp::getParam('extend',"");
        $firstMethod   = MiniHttp::getParam('first_method',"");
        $secondMethod      = MiniHttp::getParam('second_method',"");
        $editorTip  = MiniHttp::getParam('editor_tip',"");
        $model = new EditorManageBiz();
        $data  = $model->createEditor($extend,$firstMethod,$secondMethod,$editorTip);
        return array('success'=>$data);
    }
    /**
     * @return mixed
     * 得到editorList
     */
    public function getList(){
        $type = MiniHttp::getParam('type','');
        $model = new EditorManageBiz();
        $data  = $model->getEditorList($type);
        return $data;
    }
    /**
     * disable
     */
    public function changeEditorStatus(){
        $extend = MiniHttp::getParam('extend','');
        $status = MiniHttp::getParam('status','');
        $model = new EditorManageBiz();
        $data  = $model->changeEditorStatus($extend,$status);
        return array('success'=>$data);
    }
    /**
     * extend information
     */
    public function getExtendInfo(){
        $extend = MiniHttp::getParam('extend','');
        $model = new EditorManageBiz();
        $data  = $model->getExtendInfo($extend);
        return $data;
    }
    /**
     * 修改editor
     */
    public function updateEditor(){
        $selectExtend   = MiniHttp::getParam('select_extend',"");
        $extend         = MiniHttp::getParam('extend',"");
        $firstMethod    = MiniHttp::getParam('first_method',"");
        $secondMethod   = MiniHttp::getParam('second_method',"");
        $editorTip      = MiniHttp::getParam('editor_tip',"");
        $model          = new EditorManageBiz();
        $data           = $model->updateEditor($selectExtend,$extend,$firstMethod, $secondMethod,$editorTip);
        return array('success'=>$data);
    }
}