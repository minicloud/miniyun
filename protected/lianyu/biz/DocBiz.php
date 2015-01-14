<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 15-1-13
 * Time: 上午10:25
 */
class DocBiz extends MiniBiz
{

    public function word($page,$pageSize){
        $versions = MiniVersion::getInstance()->getConvertListByType('application/msword');
        return $versions;
    }
    public function convert($fileHash){
        $version = MiniVersion::getInstance()->getBySignature($fileHash);
        $type = null;
        if($version['type']=='application/pdf'){
            $type = 'pdf';
        }
//        if(empty($version)){
//            throw new MFileopsException(
//                Yii::t('api','File Not Found'),
//                404);
//        }
//        if($version['doc_convert_status']==0||$version['doc_covert_status']==1||$version['doc_convert_status']==-1){
//             return array('success'=>false,'doc_convert_status'=>$version['doc_convert_status']);
//        }else{
//             if($version['doc_covert_status']==2){
                $biz = new DocConvertBiz();
                $url = $biz->cache($fileHash,$type);
                if(file_exists($url)){
                    return $_SERVER['HTTP_HOST']."/temp/".$fileHash.'/'.$fileHash.".pdf" ;
                }
                 return NUll;
//             }
//        }
    }
}