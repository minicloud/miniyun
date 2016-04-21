<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-9-10
 * Time: 上午10:07
 */
class FrontDepartmentService extends MiniService{
    /**l
     * 群组列表
     */
    public function getList(){
    	$departmentId = MiniHttp::getParam('department_id','-1');
        $biz = new FrontDepartmentBiz();
        return $biz->getList($departmentId);
    } 
}