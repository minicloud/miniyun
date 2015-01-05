<?php

class LockService extends MiniService{
    /**
     * 查找文件是否被锁定
     * @return array
     */
    public function status(){
       $filePath = MiniHttp::getParam('file_path','');
       $lock = new LockBiz();
       $result = $lock->status($filePath);
       return $result;
   }
   public function create(){
       $filePath = MiniHttp::getParam('file_path','');
       $lock = new LockBiz();
       $lock->create($filePath);
       return array('success'=>true);
   }
    public function delete(){
        $filePath = MiniHttp::getParam('file_path','');
        $lock = new LockBiz();
        $lock->delete($filePath);
        return array('success'=>true);
    }
}