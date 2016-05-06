<?php
/**
 * 文件转换相关接口 
 */
class ConvertService extends MiniService{  
    /**
    *文档转换开始
    */
    public function docStart(){
        return apply_filters("doc_convert_start");        
    } 
    /**
    *文档转换结束
    */
    public function docEnd(){
        return apply_filters("doc_convert_end");        
    }
    /**
    *视频转换开始
    */
    public function videoStart(){
        return apply_filters("video_convert_start");        
    } 
    /**
    *视频转换结束 
    */
    public function videoEnd(){
        return apply_filters("video_convert_end");        
    } 
}   