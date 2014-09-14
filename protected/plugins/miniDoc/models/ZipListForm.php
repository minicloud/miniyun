<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class ZipListForm extends CFormModel {

    public  $path;
    public  $currentPath;
    private $contentPath;
    private $hash;

    public function  ZipListForm($hash){
        $this->hash        = $hash;
        $this->contentPath = $this->getContentPath();
        $this->currentPath = $this->getCurrentPath();
    }

    
    public static function iconDetail($type) {
        $types = array(
            0 => Yii::app()->params['app']['host'] . "/statics/images/main/type/undefind.gif",
            1 => Yii::app()->params['app']['host'] . "/statics/images/main/type/folder.gif"
        );
        return CHtml::image($types[$type]);
    }

    
    public static function sizeDetail($size, $type) {
        if ($type == 1) {
            return '';
        }
        return CUtils::getFormatSize($size);
    }

    
    private static function getName($path){

        $isDir = false;
        $lastChar = $path[strlen($path)-1];
        if($lastChar=="/"){
            $isDir = true;
        }
        $parts = explode('/', $path);
        if($isDir){
            return $parts[count($parts)-2];
        }
        return $parts[count($parts)-1];

    }
    
    public static function pathDetail($id, $path, $type, $hash, $crc) {
                if ($id == -1) {
            $name  = '..';
            $parts = explode('/', $path);
            array_pop($parts);
            array_pop($parts);
            $uri   = urlencode(join('/', $parts));
        } else {
            $name = self::getName($path);
            $name = ZipListForm::formatStringStart($name);
            $uri  = urlencode($path);
        }
        if ($type == 0) {
                        $href = Yii::app()->createUrl('miniDoc/viewer/zipFileContent',
                array(
                    'hash' => $hash,
                    'crc'  => $crc,
                    'name' => $name,
                )
            );
            return CHtml::link($name, $href);
        }else{
                        $href = Yii::app()->createUrl('miniDoc/viewer/zip',
                array(
                    'hash' => $hash,
                    'path' => $uri,
                )
            );
            return CHtml::link($name, $href);
        }

    }

    
    private static function formatStringStart($str) {
        $len = strlen($str);
        if ($len > 45) {
            return  '...' . substr($str, $len - 45);
        }
        return $str;
    }
    
    public function formatStringEnd($str) {
        $len = strlen($str);
        if ($len > 45) {
            return substr($str, 0, 45) . '...';
        }
        return $str;
    }
    
    private function getContentPath() {
        $hash = $this->hash;
        $parts = array(MINIYUN_PATH,'statics', 'views', MiniUtil::getPathBySplitStr($hash), $hash, $hash. '.meta'
        );
        return join('/', $parts);
    }

    
    public function getDataProvider() {
        $data = $this->getData();
        return new CArrayDataProvider($data, array(
            'id'         => 'grid_id',
            'sort'       => array(),
            'pagination' => array('pageSize'=>800)
        ));
    }
    
    private function getData() {
        $context = '';
        $handle  = fopen($this->contentPath, 'rb');
        while(!feof($handle)){
            $context .= fgets($handle, 4096);
        }
        fclose($handle);
        $items = json_decode($context);
        $data  = array();
        foreach ($items as $id=>$item) {
            $path = $this->convertStandardPath($item->path);
            if ($path == $this->currentPath) {

                foreach ($item as $k=>$v) {
                    $arr[$k] = $v;
                }
                $arr['id']   = -1;
                $arr['type'] = 1;
                $arr['hash'] = $this->hash;
                array_unshift($data, $arr);
                continue;
            }
                        if ($this->currentPath != "/" && strpos($path,$this->currentPath) !== 0){
                continue;
            }
            $rootC = substr_count($this->currentPath,"/" );
            if ($this->currentPath != "/") {
                                $rootC += 1;
            }
            $count = substr_count( $path , "/" );
            if ($count <= $rootC) {
                foreach ($item as $k=>$v) {
                    $arr[$k] = $v;
                }
                $arr['id']=$id;
                if (substr($item->path, -1)  == "/") {
                    $arr['type']=1;
                } else {
                    $arr['type']=0;
                }
                $arr['hash']=$this->hash;
                array_push($data, $arr);
                continue;
            }
        }
        return $data;
    }

    
    private function getCurrentPath() {
        $path = Yii::app()->getRequest()->getParam('path', null);
        if (empty($path)) {
            $path = '/';
        }
        $path = urldecode($path);
        return  $this->convertStandardPath($path);
    }
    
    private function convertStandardPath($path)
    {
        if ($path == "")
        {
            return false;
        }
                $path = str_replace("\\", "/", $path);
        while (!(strpos($path, "//") === false)) {
            $path = str_replace("//", "/", $path);
        }

                if ($path[0] != "/")
        {
            $path = "/".$path;
        }

                $len = strlen($path);
        if ($len > 1 && "/" == $path[$len - 1]) {
            $path = substr($path, 0, $len - 1);
        }
        return $path;
    }
}