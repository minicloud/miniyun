<?php
class PluginBusinessThemeOption extends MiniCache{
    /**
     * 设置默认参数
     */
    public static function getDefaultParams(){
        $host = MiniHttp::getMiniHost();
        $data = array(
            'companyName' => '让文件管理更简单',
            'companyEnglishName' => 'make document management easier',
            'productName' => '迷你云',
            'logo'     => $host.'static/images/logo.png',
            'carouselImagesUrl' => array($host.'static/images/plugins/pluginTheme/default.png'),
            'helpName' => '帮助',
            'helpUrl'  => 'http://bbs.miniyun.cn'
        );
        return $data;
    }
}