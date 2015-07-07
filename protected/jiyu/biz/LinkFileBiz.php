<?php
/**
 * 文件外链
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class LinkFileBiz  extends MiniBiz{
    /**
     * 获取文件信息
     * @param $key
     * @throws MiniException
     * @return mixed
     */
    public function getInfo($key){
        $link     = MiniLink::getInstance()->getByKey($key);
        if($link!==NULL){
            $file     = MiniFile::getInstance()->getById($link["file_id"]);
            if($file!==NULL){
                $data = array();
                $info = $this->do2vo($file);
                if($link['password'] != "-1"){
                    $info["is_set_password"] = true;
                }else{
                    $info["is_set_password"] = false;
                }
                if(intval($link['expiry']) !== -1){
                    if(intval($link['expiry']) - intval(time()) >0){
                        $info["is_in_expiry"] = true;
                    }else{
                        $info["is_in_expiry"] = false;
                    }
                    $info['in_expiry'] = $link['expiry'];
                }else{
                    $info["is_in_expiry"] = true;
                }
                if(intval($link['user_id']) === intval($this->user['id'])){
                    $info["is_owner"] = true;
                }else{
                    $info["is_owner"] = false;
                }
                $user = MiniUser::getInstance()->getById($file["user_id"]);
                $info["user_name"] = $user["user_name"];
                $data["info"] = $info;
                return $data;
            }
        }
        throw new MiniException(1300);
    }
    /**
     * 把数据库对像转换为视图对象
     * @param $file
     * @return array
     */
    private function do2vo($file){
        $info = array();
        $info['file_type']      = $file['file_type'];
        $info['file_size']      = $file['file_size'];
        $info['file_path']      = MiniUtil::getRelativePath($file['file_path']);
        $info['updated_at']     = $file['updated_at'];
        return $info;
    }
    /**
     * 获取子文件和文件夹
     * @param $key
     * @param $path
     * @return array
     */
    public function getFiles($key,$path){
        $data = array();
        $data["success"] = false;
        $link     = MiniLink::getInstance()->getByKey($key);
        if($link!==NULL){
            $file       = MiniFile::getInstance()->getById($link["file_id"]);
            $parentPath = $file["file_path"];
            if($file!==NULL){
                $userId = $file["user_id"];
                $path = MiniUtil::getAbsolutePath($userId,$path);
                //必须限定子目录在外链目录下
                if(strpos($path,$parentPath)==0){
                    $parentFile = MiniFile::getInstance()->getByPath($path);
                    $files = MiniFile::getInstance()->getChildrenByParentId($parentFile['user_id'],$parentFile['id']);
                    $filesInfo = array();
                    foreach($files as $item){
                        array_push($filesInfo,$this->do2vo($item));
                    }
                    $data["success"] = true;
                    $data['files'] = $filesInfo;
                }
            }
        }
        return $data;
    }
    /**
     * 获得缩略图
     * @param $key
     * @param $path
     * @param $size
     * @return string
     */
    public function thumbnail($key,$path,$size){
        $link     = MiniLink::getInstance()->getByKey($key);
        if($link!==NULL){
            $file       = MiniFile::getInstance()->getById($link["file_id"]);
            $parentPath = $file["file_path"];
            if($file!==NULL){
                $userId = $file["user_id"];
                $absolutePath = MiniUtil::getAbsolutePath($userId,$path);
                //必须限定子目录在外链目录下
                if(strpos($absolutePath,$parentPath)==0){
                    $thumbnail          = new MThumbnailBase();
                    $thumbnail->user_id = $file["user_id"];
                    $thumbnail->path    = $path;
                    $thumbnail->size    = $size;
                    $thumbnail->format  = "jpg";
                    $thumbnail->create();
                    $thumbnail->render();
                }
            }
        }
    }
    /**
     * 下载文件
     * @param $key
     * @param $path
     * @return string
     */
    public function download($key,$path){
        $link     = MiniLink::getInstance()->getByKey($key);
        if($link!==NULL){
            $file       = MiniFile::getInstance()->getById($link["file_id"]);
            $parentPath = $file["file_path"];
            if($file!==NULL){
                $userId = $file["user_id"];
                //便于二维码地址更加短
                if(empty($path)){
                    $absolutePath = $file["file_path"];
                }else{
                    $absolutePath = MiniUtil::getAbsolutePath($userId,$path);
                }
                //必须限定子目录在外链目录下
                if(strpos($absolutePath,$parentPath)==0){
                    MiniFile::getInstance()->download($absolutePath);
                }
            }
        }
        $data = array();
        $data["msg"] = "no auth";
        return $data;

    }
    /**
     * 获取文本内容
     * @param $key
     * @param $path
     * @return mixed
     */
    public function content($key,$path){
        $link     = MiniLink::getInstance()->getByKey($key);
        if($link!==NULL){
            $file       = MiniFile::getInstance()->getById($link["file_id"]);
            $parentPath = $file["file_path"];
            if($file!==NULL){
                $userId = $file["user_id"];
                $absolutePath = MiniUtil::getAbsolutePath($userId,$path);
                //必须限定子目录在外链目录下
                if(strpos($absolutePath,$parentPath)==0){
                    MiniFile::getInstance()->getContent($absolutePath);
                }
            }
        }

    }
    /**
     * 获取文本内容
     * @param $key
     * @param $path
     * @return mixed
     */
    public function txtContent($key,$path){
        $link     = MiniLink::getInstance()->getByKey($key);
        if($link!==NULL){
            $file       = MiniFile::getInstance()->getById($link["file_id"]);
            $parentPath = $file["file_path"];
            if($file!==NULL){
                $userId = $file["user_id"];
                $absolutePath = MiniUtil::getAbsolutePath($userId,$path);
                //必须限定子目录在外链目录下
                if(strpos($absolutePath,$parentPath)==0){
                    return MiniFile::getInstance()->getTxtContent($absolutePath);
                }
            }
        }

    }
}

