<?php

class LockService extends MiniService{
    /**
     * 查找文件是否被锁定
     * @return array
     */
    public function status(){
       $filePath = MiniHttp::getParam('file_path','');
       $result = LockBiz::getInstance()->status($filePath);
       return $result;
   }
   public function create(){
       $filePath = MiniHttp::getParam('file_path','');
       LockBiz::getInstance()->create($filePath);
       return array('success'=>true);
   }
    public function delete(){
        $filePath = MiniHttp::getParam('file_path','');
        LockBiz::getInstance()->delete($filePath);
        return array('success'=>true);
    }
}