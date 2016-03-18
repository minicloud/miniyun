<?php
/**
 * 外链访问服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class LinkAccessService extends MiniService{

    /**
     * 获取文件信息
     * @return mixed
     */
    public function info(){
        $link = new LinkFileBiz();
        $key  = MiniHttp::getParam("key","");
        $file = $link->getInfo($key);
        return $file;
    }
    /**
     * 获取文件夹子文件和文件夹
     * @return array
     */
    public function folder(){
        $key  = MiniHttp::getParam("key","");
        $path = MiniHttp::getParam("path","");
        $link = new LinkFileBiz();
        $files = $link->getFiles($key,$path);
        return $files;
    }
    /**
     * 获取图片
     * @return string
     */
    public function thumbnail(){
        $key = MiniHttp::getParam("key","");
        $path = MiniHttp::getParam("path","");
        $size = MiniHttp::getParam("size","");
        $link = new LinkFileBiz();
        $link->thumbnail($key,$path,$size);
    }
    /**
     * 文件下载
     * @return string
     */
    public function download(){
        $key = MiniHttp::getParam("key","");
        $path = MiniHttp::getParam("path","");
        $link = new LinkFileBiz();
        $link->download($key,$path);
    }
    /**
     * 获取PDF文件信息
     * @return string
     */
    public function content(){
        $path = MiniHttp::getParam("path","");
        $key = MiniHttp::getParam("key","");
        $link = new LinkFileBiz();
        $src  = $link->content($key,$path);
        return $src;
    }
    /**
     * 获取文本文件内容
     * @return string
     */
    public function txtContent(){
        $path = MiniHttp::getParam("path","");
        $key = MiniHttp::getParam("key","");
        $link = new LinkFileBiz();
        $content  = $link->txtContent($key,$path);
        return $content;
    }
    /**
     * 根據session獲取文件外鏈集合
     */
    public function selected(){
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        $appKey       = MiniHttp::getParam("chooser_app_key","");
        $session      = MiniHttp::getParam("session","");
        $linkType     = MiniHttp::getParam("link_type",MiniLink::$PREVIEW_LINK);
        $model        = new LinkSelectedBiz();
        $filesInfo    = $model->generateFileData($appKey,$session,$linkType);
        return $filesInfo;
    }

    /**
     * 验证chooser是否有效
     */
    public function chooserValid(){
        $valid = false;
        $url = MiniHttp::getParam("key","");
        $params = explode('/',$url);
        if(count($params)>9){
            $originDomain = $params[3];
            $appKey       = $params[9];
            //本域自身使用无限制
            $currentHost  = MiniHttp::getMiniHost();
            if(strpos($currentHost,$originDomain)!==false){
                $valid = true;
            }else{
                $model        = new MiniChooserForm();
                if($model->validKey($originDomain,$appKey) === true){
                    $valid = true;
                }
            }
        }
        if($valid === false){
            throw new MiniException(1301);
        }
        return array('success'=>true);
    }

    /**
     * 检查密码
     * @return mixed
     */
    public function checkPassword(){
        $key = MiniHttp::getParam("key","");
        $password = MiniHttp::getParam("password","");
        $result = MiniLink::getInstance()->checkPassword($key,$password);
        return $result;
    }

}