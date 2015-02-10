<?php
class PluginBusinessThemeOption extends MiniVersion{
    /**
     * 设置默认参数
     */
    public static function getDefaultParams(){
        $host = MiniHttp::getMiniHost();
        $data = array(
            'companyName' => '成都迷你云科技有限公司',
            'companyEnglishName' => 'Chengdu Mini cloud Technology Co. Ltd.',
            'productName' => '迷你云',
            'logo'     => $host.'static/images/plugins/pluginTheme/logo.gif',
            'carouselImagesUrl' => array($host.'static/images/plugins/pluginTheme/default.png'),
            'helpName' => '帮助',
            'helpUrl'  => 'http://bbs.miniyun.cn'
        );
        return $data;
    }
}