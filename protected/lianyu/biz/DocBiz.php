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
        $items = array();
        foreach($versions as $version){
            $item['signature'] = $version['file_signature'];
            $item['mime_type'] = $version['mime_type'];
            $item['createTime'] = $version['createTime'];
            $item['updated_at'] = $version['updated_at'];
            $item['doc_convert_status'] = $version['doc_convert_status'];
            if($version['doc_convert_status']==2){
                $item['thumbnail'] = $version['file_signature'];
            }
            $items[] = $item;
        }

        return $items;
    }
    public function convert($fileHash){
        $version = MiniVersion::getInstance()->getBySignature($fileHash);
        if(empty($version)){
            throw new MFileopsException(
                Yii::t('api','File Not Found'),
                404);
        }
        if($version['doc_convert_status']==0||$version['doc_covert_status']==1||$version['doc_convert_status']==-1){
             return array('success'=>false,'doc_convert_status'=>$version['doc_convert_status']);
        }else{
             if($version['doc_covert_status']==2){
                $url = $_SERVER['HTTP_HOST']."/temp/".$fileHash.'/'.$fileHash.".pdf" ;
                if(file_exists($url)){
                    return $url;
                }
                 return NUll;
             }
        }
    }
}