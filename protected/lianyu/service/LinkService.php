<?php
/**
 * 外链服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class LinkService extends MiniService{
    /**
     * 创建外链
     * @return array
     */
    public function create() {
        $originDomain  = MiniHttp::getParam("origin_domain","");
        $chooserAppKey = MiniHttp::getParam("chooser_app_key","");
        $session       = MiniHttp::getParam("session","");
        $password      = MiniHttp::getParam("password","-1");
        $linkType      = MiniHttp::getParam("link_type",MiniLink::$PREVIEW_LINK);
        $expiryTime    = MiniHttp::getParam("expiry_time","-1");
        $filePath      = MiniHttp::getParam("path","");
        if($linkType!=MiniLink::$DIRECT_LINK){
            $linkType = MiniLink::$PREVIEW_LINK;
        }
        $path = $this->path;
        if(!empty($filePath)){
            $path = $filePath;
        }
        $biz = new LinkCreateBiz($path,$originDomain,$chooserAppKey,$session);
        $link = $biz->createLink($linkType,$password,$expiryTime);
        $links[] = $link;
        $data['linkShare'] = $links;
        return $data;
    }

    /**
     * 获取外链列表
     */
    public function getList(){
        $page     = MiniHttp::getParam("page",1);
        $pageSize = MiniHttp::getParam("page_size",10);
        $pageSet  = ($page-1)*$pageSize;
        $link     = new LinkListBiz();
        $linkList = $link->getPage($pageSet,$pageSize);
        return $linkList;
    }


    /**
     * 获得搜索数据
     */
    public function search(){
        $key  = MiniHttp::getParam("key","");
        $share     = new LinkListBiz();
        $linkList =$share->getFileSearch($key);
        return $linkList;
    }
    /**
     * 按照id删除对应数据
     */
    public function delete(){
        $path     = MiniHttp::getParam("filePath","");
        $share  = new LinkListBiz();
        $result = $share->delete($path);
        return $result ;
    }
    /**
     * link transfer
     */
    public function transfer(){
        $key = MiniHttp::getParam("key","");
        $transfer = new LinkTransferBiz($key);
        $transfer->transfer();
        $data = array();
        $data["success"] = true;
        return $data;
    }

    /**
     * link transfer
     */
    public function sendToTransfer(){
        $key = MiniHttp::getParam("key","");
        $userNames = MiniHttp::getParam("user_names","");
        $transfer = new LinkTransferBiz($key);
        $result = $transfer->sendToTransfer($userNames);
        $data = array();
        $data["success"] = $result;
        return $data;
    }
    /**
     * 设置分享链接
     * @return mixed
     */
    public function setLinkAccessPolicy(){
        $originDomain  = MiniHttp::getParam("origin_domain","");
        $chooserAppKey = MiniHttp::getParam("chooser_app_key","");
        $session       = MiniHttp::getParam("session","");
        $key           = MiniHttp::getParam("key","");
        $shareKey      = MiniHttp::getParam("shareKey","");
        $password      = MiniHttp::getParam("password","");
        $time          = MiniHttp::getParam("time","");
        $link          = new LinkCreateBiz($this->path,$originDomain,$chooserAppKey,$session);
        $result = $link->setAccessPolicy($key,$shareKey,$password,$time);
        return $result;

    }
}