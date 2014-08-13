<?php
/**
 * 选择器域名服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class DomainService extends MiniService{
    /**
     * 获取域名
     */
    public function getList()
    {
        $chooserId = MiniHttp::getParam('chooser_id','');
        $domain = MiniChooserDomain::getInstance()->getByChooserId($chooserId);
        $item = array();
        $item['domains'] = $domain;
        return $item;
    }
    /**
     * 创建域名
     */
    public function create()
    {
        $chooserId = MiniHttp::getParam('chooser_id','');
        $domain = MiniHttp::getParam('domain','');
        MiniChooserDomain::getInstance()->create($chooserId, $domain);
        $value = array();
        $value['success'] = true;
        return $value;
    }
    /**
     * 删除域名
     */
    public function delete()
    {
        $id = MiniHttp::getParam('id','');//域名所对应的id
        $item = MiniChooserDomain::getInstance()->getById($id);
        MiniChooserDomain::getInstance()->deleteDomain($item['id']);
        $value = array();
        $value['success'] = true;
        return $value;
    }
    /**
     * 下载配置文件
     */
    public function downloadConfigFile()
    {
        $model = new MiniChooserForm();
        $id = MiniHttp::getParam('id','');
        $model->downloadConfig($id);
    }
}