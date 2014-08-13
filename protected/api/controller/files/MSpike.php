<?php
/**
 * 重构秒传接口，
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MSpike extends MFileSecondsController {
    /* 标注秒是否成功 */
    public $status = true;
    
    /**
     * (non-PHPdoc)
     * @see MFilesecController::handleAssign()
     */
    protected function handleAssign() {
        $this->status = FALSE;
        return FALSE;
    }
}