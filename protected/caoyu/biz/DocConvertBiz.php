<?php
/** 
 * 文件转换Biz
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class DocConvertBiz extends MiniBiz{ 
   /**
    *根据文件的Hash值下载内容
    * @param $fileHash 文件hash值
    * @throws 404错误
    */
   public function download($fileHash){ 
        $version = MiniVersion::getInstance()->getBySignature($fileHash);
        if(!empty($version)){
            //根据文件内容输出文件内容
            MiniFile::getInstance()->getContentBySignature($fileHash,$fileHash,$version["mime_type"]);
        }else{
            throw new MFileopsException(
                Yii::t('api','File Not Found'),
                404);
        }
        
   }
    public function cache($fileHash,$type=null){
        $typeList = array("txt","png");
        if(!empty($type)){
            $typeList[] = $type;
        }
        foreach($typeList as $type){
            $url = "http://minidoc.miniyun.cn/".$fileHash."/".$fileHash.".".$type;
            $confContent    =  file_get_contents($url);
            $savePath = MINIYUN_PATH. DS .'temp';
            if(!file_exists($savePath.DS .$fileHash)){
                mkdir($savePath.DS .$fileHash);
            }
            file_put_contents($savePath.DS .$fileHash.DS .$fileHash.".".$type, $confContent);
        }
        return $savePath.DS .$fileHash.DS .$fileHash.".txt";
    }
   /**
    *给迷你云报告文件转换过程
    * @param $fileHash 文件hash值
    * @param $status 文件状态
    * @return array
    */
   public function report($fileHash,$status){
        $version = MiniVersion::getInstance()->getBySignature($fileHash);
        if(!empty($version)){
            //文件转换成功
            if($status==="1"){
                MiniVersion::getInstance()->updateDocConvertStatus($fileHash,2);
                $type = null;
                if($version['type']!='application/pdf'){
                    $type = 'pdf';
                }
                $this->cache($fileHash,$type);
            }
            //文件转换失败
            if($status==="0"){
                MiniVersion::getInstance()->updateDocConvertStatus($fileHash,-1);
            } 
        }
        return array("success"=>true);
   }

}