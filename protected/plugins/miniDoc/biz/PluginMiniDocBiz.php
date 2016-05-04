<?php
/**
 * 迷你文档业务层
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginMiniDocBiz extends MiniBiz{
    /**
     *根据文件的Hash值下载内容
     * @param string $signature 文件hash值
     * @throws 404错误
     */
    public function download($signature){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(!empty($version)){
            //根据文件内容输出文件内容
            MiniFile::getInstance()->getContentBySignature($signature,$signature,$version["mime_type"]);
        }else{
            throw new MFileopsException(
                Yii::t('api','File Not Found'),
                404);
        }

    } 
    private function getSharedDoc($page,$pageSize,$sharedList,$mimeType){
        $cursor = $pageSize;
        $data   = array();
        $num    = 0;
        $index  = 0;
        for($i=0;$i<count($sharedList);$i++){
            $item  = $sharedList[$i];
            $path  = $item[0];
            $count = $item[1];
            //当共享文件为共享者的时候进行过滤
            $userId = $this->user['id'];
            $arr = explode("/",$path);
            $slaveId = $arr[1];
            if($slaveId==$userId){
                continue;
            }
            if($cursor==0){
                return $data;
            }
            if($page<=$num+$count){
                if(($page-$num+$cursor)<=$count&&$page>0){
                    $doc = MiniFile::getInstance()->getSharedDocByPathType($path,$mimeType,$page-$num,$cursor);
                    array_splice($data,count($data),0,$doc);
                    break;
                }else{
                    if($index==0){
                        $doc = MiniFile::getInstance()->getSharedDocByPathType($path,$mimeType,$page-$num,$cursor);
                        array_splice($data,count($data),0,$doc);
                        $cursor = $cursor-($num+$count-$page);
                        if($cursor<=0){
                            $cursor = 0;
                        }
                    }else{
                        $doc = MiniFile::getInstance()->getSharedDocByPathType($path,$mimeType,0,$cursor);
                        array_splice($data,count($data),0,$doc);
                        $cursor = $cursor-$count;
                        if($cursor<=0){
                            $cursor = 0;
                        }
                    }
                    $index++;
                }
            }
            $num = $num+$count;
        }
        return $data;

    }
    /**
     * 获得账号里所有的指定文件类型列表
     * @param int $page 当前页码
     * @param int $pageSize 当前每页大小
     * @param string $mimeType 文件类型
     * @return array
     */
    public function getList($page,$pageSize,$mimeType){
        $isValid = false;
        $sharedList = array();
        $mimeTypeList = array("application/mspowerpoint","application/msword","application/msexcel","application/pdf");
        foreach ($mimeTypeList as $validMimeType) {
            if($validMimeType===$mimeType){
                $isValid = true;
            }
        }
        $data = array();
        if(!$isValid){
            $data['list'] = array();
            $data['totalPage'] = 0;
            return $data;
        }
        $userId = $this->user['id'];
        $fileTotal = MiniFile::getInstance()->getTotalByMimeType($userId,$mimeType);
        $pageSet=($page-1)*$pageSize;
        $albumBiz = new AlbumBiz();
        $shares = $albumBiz->getAllSharedPath($userId);
        $sharedTotal = 0;
        $files = array();
        if(count($shares)!=0){
            //获取当前文件夹下的子文件
            $sharedTotal = 0;
            foreach($shares as $filePath){
                $count = MiniFile::getInstance()->getSharedDocTotal($filePath,$mimeType);
                $sharedTotal = $count+$sharedTotal;
                if($count>0){
                    $item = array();
                    $item[] = $filePath;
                    $item[] = $count;
                    $sharedList[] = $item;
                }
            }
        }
        if($pageSet>=$sharedTotal){
            $pageSet = $pageSet-$sharedTotal;
            $files = MiniFile::getInstance()->getByMimeType($userId,$mimeType,$pageSet,$pageSize);
        }else{
            if($page*$pageSize<$sharedTotal){
                $files = $this->getSharedDoc($pageSet,$pageSize,$sharedList,$mimeType);
            }else{
                $fileArr = $this->getSharedDoc($pageSet,$sharedTotal,$sharedList,$mimeType);
                $fileList =  MiniFile::getInstance()->getByMimeType($userId,$mimeType,0,$pageSize*$page-$sharedTotal);
                if(count($fileList)!=0){
                    $files = array_merge($fileArr,$fileList);
                }else{
                    $files = $fileArr;
                }
            }
        }
        // $files = MiniFile::getInstance()->getByMimeType($userId,$mimeType,($page-1)*$pageSize,$pageSize);
        $items = array();
        foreach($files as $file){
            $version = PluginMiniDocVersion::getInstance()->getVersion($file['version_id']);
            $item['file_name'] = $file['file_name'];
            $item['path'] = $file['file_path'];
            $item['mime_type'] = $version['mime_type'];
            $item['createTime'] = $version['createTime'];
            $item['type'] = $file['file_type'];
            if($file['user_id']!=$userId){
                $item['type'] = 2;
            }
            $item['updated_at'] = $version['created_at'];
            $item['doc_convert_status'] = 2;
            $items[] = $item;
        }
        $data['list'] = $items;
        $data['totalPage'] = ceil(($fileTotal+$sharedTotal)/$pageSize);
        return $data;
    }
    /**
     * 在线浏览文件获得内容
     * @param string $path 文件当前路径
     * @param string $type 文件类型，可选择pdf/png
     * @throws
     * @return NULL
     */
    public function previewContent($path,$type){
        $file = MiniFile::getInstance()->getByPath($path);
        // 权限处理
        if(empty($file)){
            return array('success' =>false ,'msg'=>'file not existed');
        }
        $fileBiz = new FileBiz();
        $canRead = $fileBiz->privilege($path);
        if(!$canRead){
            throw new MFileopsException( Yii::t('api','no permission'),MConst::HTTP_CODE_409);
        }
        //获得文件当前版本对应的version
        $version   = PluginMiniDocVersion::getInstance()->getVersion($file["version_id"]);
        $signature = $version["file_signature"];
        $url = '';
        if($type==="png"){
            $url = PluginMiniStoreNode::getInstance()->getDocCoverPngUrl($version);
        }else if($type==="pdf"){
            $url = PluginMiniStoreNode::getInstance()->getDocPdfUrl($version);
        }           
        header('Location: '.$url);
    } 
    /**
     * 获得当前文档转换状态
     * 如状态为0，可能是老文件，以补偿形式开始转换
     * @param string $path 文件路径 
     */
    public function convertStatus($path){
        $file    = MiniFile::getInstance()->getByPath($path);
        $version = PluginMiniDocVersion::getInstance()->getVersion($file['version_id']);
        if($version["doc_convert_status"]==0){ 
            //状态为0说明是待转换状态，向minicloud发送请求
            $url = PluginMiniStoreNode::getInstance()->getConvertUrl($file,$version); 
        }
        return array('status'=>$version['doc_convert_status'],'url'=>$url);
    }
}
