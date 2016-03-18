<?php
/**
 * 文件上传相关接口
 * 新文件上传对接以阿里云OSS为参照
 * 迷你存储接口的实现也以阿里云OSS为标准
 */
class UploadService extends MiniService{   
    /**
    *文件上传成功后，OSS或迷你存储回调地址 
    */
    public function end(){
        return apply_filters("upload_end");        
    }
    /**
    *文件秒传接口 
    */
    public function sec(){
        return apply_filters("upload_sec");                       
    }
    /**
    *获得文件上传策略 
    */
    public function start(){ 
        return apply_filters("upload_start");        
    }    
}   