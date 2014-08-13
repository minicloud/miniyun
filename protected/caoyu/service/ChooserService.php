<?php
/**
 * 选择器服务
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class ChooserService extends MiniService{

    public function create()
    {
        // check type
        $chooserName = MiniHttp::getParam('chooser_name','');
        $type = MiniHttp::getParam('type',1);
        $chooser = MiniChooser::getInstance()->getByName($chooserName);
        $value = array();
        if (!empty($chooser)) {
            throw new MiniException(1201);
        }else{
            if(($type=="1"||$type=="2")&&$chooserName!=""){
                MiniChooser::getInstance()->create($chooserName,$type);
                $value['success'] = true;
            }
        }
        return $value;
    }
    /**
     * 选择器名字的列表
     */
    public function getList()
    {
        $pageSize    = MiniHttp::getParam('page_size',10);
        $currentPage = MiniHttp::getParam('current_page',1);
        $type  = MiniHttp::getParam('type','web');
        $items = MiniChooser::getInstance()->getPageList($pageSize, $currentPage,$type);
        $total = MiniChooser::getInstance()->getTotal($type);
        $value['items'] = $items;
        $value['total'] = $total;
        return $value;
    }
}