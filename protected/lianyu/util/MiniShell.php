<?php
/**
 * 迷你云执行shell指令类 
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
class MiniShell{
    /**
     * 把doc/ppt/xls/pdf文档提交到迷你文档服务器进行转换 
     */
    public static function docConvert(){
         $cmd = MINIYUN_PATH."/console docConvert &";
         MiniShell::shellExec($cmd);
    }
    /**
     * 从迷你文档服务器拉取文本内容到数据库 
     */
    public static function docPullxt($path){
         $cmd = MINIYUN_PATH."/console docPullTxt &";
         MiniShell::shellExec($cmd);
    }
    /**
     * 执行shell命令
     */
    private static function shellExec($cmd){
        shell_exec($cmd);
    }
}