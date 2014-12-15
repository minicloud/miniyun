<?php
/**
 * 创建外链业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class LinkCreateBiz extends MiniBiz{
    /**
     * 文件对象
     */
    private $file;
    /**
     * 生成外链的Host
     * @var
     */
    private $originDomain;
    /**
     * 生成外链的APP_KEY，如为空就是迷你云自己
     * @var
     */
    private $appKey;
    /**
     * 网页版文件选择器使用到的单次选中的会话信息
     * @var
     */
    private $session;
    /**
     *
     * @param $path
     * @param $originDomain口
     * @param $chooserAppKey
     * @param $session
     */
    public function LinkCreateBiz($path,$originDomain,$chooserAppKey,$session) {
        parent::MiniBiz();
        $this->originDomain = $originDomain;
        $this->appKey  = $chooserAppKey;
        $this->session = $session;
        $path          = MiniUtil::joinPath($path);
        $this->file    = MiniFile::getInstance()->getByPath($path);
    }

    /**
     * 合法性检查
     */
    private function valid(){
        $file = $this->file;
        $data = array();
        $data["success"] = true;
        //判断文件是否正确
        if(empty($file)){
            throw new MiniException(1100);
        }
        //判断用户是否有访问该文件的权限
//        if($this->user["id"]!=$file["user_id"]){
            //当前判断的是用户是否文件拥有者进行判断
//            throw new MiniException(1101);
//        }
        //本域自身使用无限制
        $currentHost = MiniHttp::getMiniHost();
        if(strpos($currentHost,$this->originDomain)===false){
            $model = new MiniChooserForm();
            if($model->validKey($this->originDomain,$this->appKey) !== true){
                throw new MiniException(1102);
            }
        }
        return $data;
    }
    /**
     * 创建外链
     */
    public function createLink($linkType,$password,$expiryTime){
        $result = $this->valid();
        if($result["success"]===false){
            return $result;
        }
        $file = $this->file;
        //创建外链
        $link = MiniLink::getInstance()->create($this->user["id"],$file["id"],$password,$expiryTime);
        //创建外链属性
        MiniChooserLink::getInstance()->create($link["id"],$this->appKey,$this->session);
        //返回直链或预览链接
        $data = array();
        $data["success"] = true;
        $data["name"]    = $file["file_name"];
        $data["bytes"]   = intval($file["file_size"]+"");
        //获得文件的icon
        $fileType        = $file["file_type"];
        //根据文件后缀，如果为jpg/jpeg/gif/png就直接显示缩略图
        $ext = MiniUtil::getFileExtension($file["file_name"]);
        if($ext=="jpg" || $ext=="jpeg" || $ext=="png" || $ext=="gif"){
            $data["thumbnail_link"] = MiniHttp::createUrl("link/thumbnail/key/".$link["share_key"]);
        }else{
            $data["thumbnail_link"] = "";

        }
        if($linkType==MiniLink::$PREVIEW_LINK){
            $data["link"] = MiniHttp::createUrl("link/access/key/".$link["share_key"]);
        }else{
            $data["link"] = MiniHttp::createUrl("link/direct/key/".$link["share_key"]);
        }
        return $data;
    }

    /**
     * 修改分享信息
     * @param $key
     * @param $shareKey
     * @param $password
     * @param $time
     */
    public function setAccessPolicy($key,$shareKey,$password,$time){
        return MiniLink::getInstance()->setAccessPolicy($key,$shareKey,$password,$time);
    }

}