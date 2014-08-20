<?php
/**
 * 下载配置文件类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniChooserForm
{ 
    /**
     * 根据Domain,app_key来获取结果
     */
    public function validKey($domain,$appKey){
        $chooser = MiniChooser::getInstance()->getByKey($appKey);
        if(empty($chooser)){
            return false;
        }
        $chooserId = $chooser['id'];
        $item = MiniChooserDomain::getInstance()->getByDomain($chooserId,$domain);
        if(empty($item)){
            return false;
        }
        return true;
    }
     
}
