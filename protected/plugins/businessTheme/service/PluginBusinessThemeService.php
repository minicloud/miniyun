<?php
/**
 * 商业版主题插件业务处理
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginBusinessThemeService extends MiniService{
    protected function anonymousActionList(){
        return array(
        );
    }
    protected function adminActionList(){
        return array(
            "setParams"
        );
    }
    /**
     * 商业版主题参数设置接口
     */
    public function  setParams(){
        $companyName = MiniHttp::getParam('company_name',"");
        $productName = MiniHttp::getParam('product_name',"");
        $companyEnglishName = MiniHttp::getParam('company_english_name',"");
        $helpUrl  = MiniHttp::getParam('help_url',"");
        $helpName = MiniHttp::getParam('help_name',"");
        $biz = new PluginBusinessThemeBiz();
        $biz->setParams($companyName,$productName,$companyEnglishName,$helpUrl,$helpName);
    }
    /**
     * 商业版主题获得设置参数
     */
    public function  getParams(){
        $biz = new PluginBusinessThemeBiz();
        return $biz->getParams();
    }
}