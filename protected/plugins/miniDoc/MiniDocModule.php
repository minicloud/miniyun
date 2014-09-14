<?php
/*
Plugin Name: 迷你文档转换服务
Plugin URI: http://www.miniyun.cn
Description: 提供doc/docx/ppt/pptx/xls/xlsx/rar/zip在线浏览功能 使用：<br>1) 点击左侧的“启用”链接。<br>2) 前往 <a href="Yii::t('MiniDocModule.I18N', 'url')" style="color:#21759B;">“Yii::t('MiniDocModule.I18N', 'settings')”</a>页面进行设置
Author: MiniYun
Version: 1.1.0
Author URI: http://www.miniyun.cn
*/
class MiniDocModule extends MiniPluginModule {

    /**
     * (non-PHPdoc)
     * @see CModule::init()
     */
    public function init() {
        $this->setImport ( array (
            'miniDoc.service.*',
            'miniDoc.models.*',
            'miniDoc.common.*',
            'miniDoc.cache.model.*',
            'miniDoc.utils.*',
        ));
        
        // 添加js引用
        add_action ( 'netdisk_header', array ($this, 'addNetdiskHeader' ) );
        add_filter('is_support_doc', array($this, "isSupportDoc"));
    }
    /**
     * 
     * 添加静态资源引用
     * @since 1.0.0
     */
    public function addNetdiskHeader() {
        $t = YII_DEBUG ? time() : Yii::app()->params['app']['version'];
        $args = '<script type="text/javascript" src="' . $this->getAssetsUrl().'/js/mini-doc.js?t=' . $t . '"></script>';
        $args .= '<script type="text/javascript" src="' . Yii::app()->createUrl($this->id . '/i18N').'?t=' . $t . '"></script>';
        echo $args;
    }
    /**
     * 是否支持文档浏览
     * @return bool
     */
    public function isSupportDoc(){
        return TRUE;
    }
    /**
     * 添加导航连接
     * @since 1.0.0
     */
    public function myAddPages() {
        add_options_page( Yii::t('MiniDocModule.I18N', 'title'), 'manage_options', "miniDoc/setting/index");    }
}