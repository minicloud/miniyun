<?php
/**
 * 外链选择业务处理
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class LinkSelectedBiz extends MiniBiz{
    /**
     * 根據文件Id获取文件数据
     */
    public function getLink($fileId,$linkType,$shareKey){
        $data            = array();
        $file            = MiniFile::getInstance()->getById($fileId);
        $data["name"]    = $file["file_name"];
        $data["bytes"]   = intval($file["file_size"]+"");
        $fileType        = $file["file_type"];
        if($fileType==0){
            $data["icon"] = MiniHttp::getIcon4File($file["file_name"]);
        }
        $ext  = MiniUtil::getFileExtension($file["file_name"]);
        $path = MiniUtil::getRelativePath($file["file_path"]);
        if($ext=="jpg" || $ext=="jpeg" || $ext=="png" || $ext=="gif"){
            $data["thumbnail_link"] = MiniHttp::createAnonymousUrl("linkAccess/thumbnail?key=".$shareKey."&size=256x256&path=".$path);
        }else{
            $data["thumbnail_link"] = "";
        }
        if($linkType==MiniLink::$PREVIEW_LINK){
            $data["link"] = MiniHttp::createUrl("link/access/key/".$shareKey);
        }else{
            $data["link"] = MiniHttp::createAnonymousUrl("linkAccess/download?key=".$shareKey."&path=".$path);
        }
        return $data;
    }
    /**
     * 形成最终第三方所需数据
     */
    public function generateFileData($appKey,$session,$linkType){
        //todo appKey检测
        if($linkType!=MiniLink::$DIRECT_LINK){
            $linkType = MiniLink::$PREVIEW_LINK;
        }
        $links = MiniChooserLink::getInstance()->getBySession($session);
        foreach($links as $link){
            $files       = MiniLink::getInstance()->getById($link['link_id']);
            $fileId      = $files['file_id'];
            $shareKey    = $files['share_key'];
            $fileInfo    = $this->getLink($fileId,$linkType,$shareKey);
            $filesInfo['list'][] = $fileInfo;
        }
        $filesInfo['success'] = true;
        if($filesInfo['list'] == null){
            $filesInfo['success'] = false;
        }
        return $filesInfo;
    }
}