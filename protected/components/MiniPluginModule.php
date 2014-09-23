<?php
/**
 * 迷你云应用的父类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class MiniPluginModule extends CWebModule {

    /**
     *
     * 资源文件根路径
     * @var string
     */
    private $_assetsUrl;



    /**
     *
     * 静态资源路径
     */
    public function getAssetsUrl() {
        if ($this->_assetsUrl === null)
            $this->_assetsUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.plugins.'.$this->getName().'.assets'),false, -1,true);
        return $this->_assetsUrl;
    }

    /**
     *
     * 设置静态资源路径
     * @param string $value
     */
    public function setAssetsUrl($value) {
        $this->_assetsUrl = $value;
    }
}