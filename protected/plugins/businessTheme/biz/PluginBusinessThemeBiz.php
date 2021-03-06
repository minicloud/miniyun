<?php
/**
 * 商业版主题插件业务处理
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginBusinessThemeBiz extends MiniBiz{
    /**
     *商业般主题插件参数设置
     * @param $companyName 公司名称
     * @param $productName 产品名称
     * @param $companyEnglishName 公司英文名称
     * @param $helpUrl 帮助链接地址
     * @param $helpName 帮助名称
     */
    public function setParams($companyName,$productName,$companyEnglishName,$helpUrl,$helpName){
        $host = MiniHttp::getMiniHost();
        $key = "plugin_"."businessTheme";
        $value = array();
        if(empty($companyName)){
            echo '公司名不能为空';exit;
        }
        if(empty($productName)){
            echo '产品名不能为空';exit;
        }
        $businessThemeData = MiniOption::getInstance()->getOptionValue($key);
        if(!empty($businessThemeData)){
            $value = unserialize($businessThemeData);
        }
        $value['companyName'] = $companyName;
        $value['productName'] = $productName;
        $value['companyEnglishName'] = $companyEnglishName;
        $value['helpUrl'] = $helpUrl;
        $value['helpName'] = $helpName;
        $iconFile = $_FILES['icon'];
        $carouselPictures = $_FILES['carousel_pictures'];
        if(!empty($iconFile['name'])){
            $iconFilePath = BASE.'../static/images/plugins/pluginTheme/icon.png';
            if($iconFile['error'] == 0){
                list($width, $height, $type, $attr) = getimagesize($iconFile['tmp_name']);
                if($width!=256 || $height!=256){
                    echo 'Logo文件大小不合法！';
                    echo "<script type='text/javascript'> setTimeout(function(){window.parent.location = '".$host."index.php/consolePlugin/businessTheme'},1000);</script>";
                    exit;
                }
                move_uploaded_file($iconFile['tmp_name'],$iconFilePath);
            }
            $value['logo'] = $host.'static/images/plugins/pluginTheme/icon.png';
        }else{
            $value['logo'] = '';
        }
        if(!empty($carouselPictures['name'])){
            $total = count($carouselPictures['name']) - 1;
            $carouselImagesUrl = array();
            for($i = 0;$i<= $total;$i++){
                if($carouselPictures['error'][$i] == 0){
                    list($width, $height, $type, $attr) = getimagesize($carouselPictures['tmp_name'][$i]);
                    if($width!=1055 || $height!=575){
                        echo '轮播图片文件大小不合法！';
                        echo "<script type='text/javascript'> setTimeout(function(){window.parent.location = '".$host."index.php/consolePlugin/businessTheme'},1000);</script>";
                        exit;
                    }
                    $saveFilePath = BASE.'../static/images/plugins/pluginTheme/p'.$i.'.png';
                    move_uploaded_file($carouselPictures['tmp_name'][$i],$saveFilePath);
                    array_push($carouselImagesUrl,$host.'static/images/plugins/pluginTheme/p'.$i.'.png');
                }
            }
            $value['carouselImagesUrl'] = $carouselImagesUrl;
        }else{
            $value['carouselImagesUrl'] = '';
        }
        MiniOption::getInstance()->setOptionValue($key,serialize($value));
        echo '提交成功！';
        echo "<script type='text/javascript'> setTimeout(function(){window.parent.location = '".$host."index.php/consolePlugin/businessTheme'},1000);</script>";
        exit;
    }
    /**
     *商业般主题插件获得参数
     *
     */
    public function getParams(){
        $key = "plugin_"."businessTheme";
        $value = MiniOption::getInstance()->getOptionValue($key);
        $defaultParams =  PluginBusinessThemeOption::getDefaultParams();
        if(!empty($value)){
            $pluginData =  unserialize($value);
        }else{
            $pluginData = $defaultParams;
        }
        if(empty($pluginData['logo'])){
            $pluginData['logo'] = $defaultParams['logo'];
        }
        if(empty($pluginData['carouselImagesUrl'])){
            $pluginData['carouselImagesUrl'] = $defaultParams['carouselImagesUrl'];
        }
        if(empty($pluginData['companyEnglishName'])){
            $pluginData['companyEnglishName'] = $defaultParams['companyEnglishName'];
        }
        if(empty($pluginData['helpName'])){
            $pluginData['helpName'] = $defaultParams['helpName'];
        }
        if(empty($pluginData['helpUrl'])){
            $pluginData['helpUrl'] = $defaultParams['helpUrl'];
        }
        return $pluginData;
    }

}