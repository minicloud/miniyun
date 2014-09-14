<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-9-3
 * Time: 下午3:34
 */
class MiniDocBiz extends MiniBiz{
    const  OPTION_KEY="mini_doc_limit_file_size";
    /**
     * 获得迷你文档文档大小控制策略
     */
    public function limitPolicy(){
        $size      = MiniOption::getInstance()->getOptionValue(MiniDocBiz::OPTION_KEY);
        if($size===NULL){
            $limitSize = array('excelSize'=>'1','pptSize'=>'1','wordSize'=>'1','zip_rarSize'=>'1');
            $limitSize = serialize($limitSize);
            MiniOption::getInstance()->setOptionValue(MiniDocBiz::OPTION_KEY,$limitSize);
            $size1      = MiniOption::getInstance()->getOptionValue(MiniDocBiz::OPTION_KEY);
            $limitSize = unserialize($size1);
        }else{
            $limitSize = unserialize($size);
        }
        $result = array('success'=>true,'msg'=>'200','xls_size'=>$limitSize['excelSize'],'ppt_size'=>$limitSize['pptSize'],'doc_size'=>$limitSize['wordSize'],'zip_size'=>$limitSize['zip_rarSize']);
        return $result;
    }
    /**
     * 修改迷你文档文档大小控制策略
     */
    public function saveLimitPolicy($word,$ppt,$excel,$zip_rar){
        $arr = array('excelSize'=>$excel,'pptSize'=>$ppt,'wordSize'=>$word,'zip_rarSize'=>$zip_rar);
        $limitSize = serialize($arr);
        MiniOption::getInstance()->setOptionValue(MiniDocBiz::OPTION_KEY,$limitSize);
        return array('success'=>true,'msg'=>'modify success');
    }
    /**
     * 新建迷你文档节点
     */
    public function createNode($ip,$port){
        if($ip==''||$port==''){
            return array('success'=>false,'msg'=>'parameter empty');
        }
        $node=MiniDocNode::getInstance()->getByIP($ip);
        if($node!=null){
            return array('success'=>false,'msg'=>'ip exited');
        }
         MiniDocNode::getInstance()->create($ip,$port);
        return array('success'=>true,'msg'=>'create success');

    }
    /**
     * 获得所有的文档节点信息
     */
    public function listNode(){
        $nodes=MiniDocNode::getInstance()->getLists();
        $docModel= DocNode::model();
        $total=$docModel->count();
        if($total==0){
            $data = array('success'=>false,'msg'=>'no data','list'=>$nodes,'total'=>$total);
        }else{
            $data = array('success'=>true,'msg'=>'node list','list'=>$nodes,'total'=>$total);
        }
        return $data;
    }
    /**
     * 修改迷你文档节点
     */
    public function modifyNode($id,$ip,$port){
        $result=MiniDocNode::getInstance()->modify($id,$ip,$port);
        if(!$result){
            $data = array('success'=>false,'msg'=>'ip exited');
        }else{
            $data = array('success'=>true,'msg'=>'modify success');
        }
        return $data;
    }
    /**
     * 更改迷你文档节点状态
     */
    public function changeNodeStatus($id,$runStatus){
        $result = MiniDocNode::getInstance()->modifyServerRunStatus($id,$runStatus);
        if($result){
            return array('success'=>true,'msg'=>'change success');
        }else{
            return array('success'=>false,'msg'=>'parameter empty');
        }

    }
}