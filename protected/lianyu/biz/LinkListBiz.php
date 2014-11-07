<?php
/**
 * 外链列表业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class LinkListBiz extends MiniBiz {


    /**获得file  share 数据 ，组装分页数组
     * @param $pageSet
     * @param $pageSize
     * @return mixed
     */
    public function getPage($pageSet,$pageSize){
        $sessionUser = $this->user;
        $userId      = $sessionUser["id"];
        $shareList   = MiniLink::getInstance()->getPageSize($pageSet,$pageSize,$userId);
        $total       = MiniLink::getInstance()->getCount($userId);
        $fileList=null;
        $key=0;
        $listShare =$this->do2vo($fileList,$shareList,$key);
        if(!empty($listShare)){
            $list['listShare'] = $listShare;
        }else{
            $list['listShare'] = array();
        }

        $list['total']     = count($listShare);
        return $list;
    }

    /**获得搜索数据
     * @param $fileName
     * @return mixed
     */
    public function getFileSearch($fileName){
        $sessionUser = $this->user;
        $userId      = $sessionUser["id"];
        $files       = MiniFile::getInstance()->getFileByName($userId,$fileName);
        $shareList   = null;
        $key=1;
        $listShare =$this->do2vo($files,$shareList,$key);
        $num=count($listShare);
        for($i=0;$i<$num;$i++){
            if(!$listShare[$i]['share_key']){
                unset($listShare[$i]);
            }
        }
        $list['listShare'] = $listShare;
        $list['total']     = count($listShare);
        return $list;
    }
    /**
     * 判断文件类型生成url
     * @param $shareKey
     * @return string
     */
    private function link($shareKey){
        $link= Yii::app()->getBaseUrl()."/index.php/link/access/key/".$shareKey;
        return $link;
    }

    /**删除文件外链
     * @param $path
     * @return int
     */
    public function delete($path){
        $userId      = $this->user["id"];
        $absolutePath = MiniUtil::joinPath($userId ,$path);
        $file=MiniFile::getInstance()->getByPath($absolutePath);
        $id=$file['id'];
        $link  = MiniLink::getInstance()->getByFileId($id);
        $linkId=$link["id"];
        $share   = MiniLink::getInstance()->deleteById($userId,$id);
        MiniChooserLink::getInstance()->deleteByLinkId($linkId);
        if($share){
            return true;
        }else{
            return false;
        }
    }

    /** 组装数组
     * @param $fileList
     * @param $shareList
     * @param $key
     * @return array
     */
    private function do2vo($fileList,$shareList,$key){
        if($key==1){
            $list=$fileList;
        }else{
            $list=$shareList;
        }
        $listShare   = array();
        foreach($list as $value){
            if($key==1){
                $file=$value;
                $shareList = MiniLink::getInstance()->getByFileId($file["id"]);
                $share=$shareList;
                $shareData['file_name'] = null;
            }else{
                $share= $value;
                $fileList= MiniFile::getInstance()->getUndeleteFile($share['file_id']);
                $file=$fileList;
            }
            if(empty($file)){
                break;
            }
            $shareData['file_name']   = $file['file_name'];
            $shareData['path']        = MiniUtil::getRelativePath($file['file_path']);
            $shareData['size']        = $file['file_size'];
            $shareData['updated_at']  = $share['updated_at'];
            $shareData['share_key']   = $share['share_key'];
            $shareData['file_type']   = $file['file_type'];
            $shareData['link']        = $this->link($share['share_key']);
            $listShare[]=$shareData;
        }
        return  $listShare;
    }
}