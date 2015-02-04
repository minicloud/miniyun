<?php
class PluginBusinessThemeOption extends MiniVersion{
    /**
     * 设置默认参数
     */
    public static function getDefaultParams(){
        $host = MiniHttp::getMiniHost();
        $data = array(
            'companyName' => '成都迷你云科技有限公司',
            'companyEnglishName' => 'CHENGDU MINI ALOUD TECHNOLOGY CO.LTD.',
            'productName' => '迷你云',
            'logo'     => 'http://static.miniyun.cn/static/mini-box/images/logo.gif',
            'carouselImagesUrl' => array($host.'upload/plugins/pluginTheme/default.png'),
            'helpName' => '帮助',
            'helpUrl'  => 'http://bbs.miniyun.cn'
        );
        return $data;
    }
}