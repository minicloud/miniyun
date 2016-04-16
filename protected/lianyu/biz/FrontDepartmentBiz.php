<?php
/**
 * 部门权限
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
class FrontDepartmentBiz extends MiniBiz{
    /**
     * 群组列表
     */
    public function getList($departmentId){
        $data = MiniGroup::getInstance()->getChildren($departmentId);
        return $data;
    } 
}