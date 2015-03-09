<?php
/**
 * Miniyun move服务结束后的后续动作
 * 如：权限删除
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MMoveAfter{
    //数据库中查询的数据
    public $action;
    //判断源路径共享的对象
    public $from_share_filter;
    //判断目标路径的对象
    public $to_share_filter;
    //文件源路径
    public $from_path;
    //文件目标路径
    public $to_path;
    //文件对象
    public $file_detail;

    /**
     * 控制器执行主逻辑函数, 处理删除文件或者文件夹
     *
     * @since 1.0.7
     */
    public function execute()
    {
        $this->deletePermission();
        //移动之后执行的hook
        do_action("after_move", $this);
    }

    /**
     *
     * 删除权限
     *
     * @since 1.0.7
     */
    public function deletePermission(){
        $from_share_filter = $this->from_share_filter;
        $to_share_filter   = $this->to_share_filter;
        $from_path         = $this->from_path;
        $to_path           = $this->to_path;
        $file_detail       = $this->file_detail;
        $action            = $this->action;

        //权限判断
        $isRename = false;
        //当属于共享目录时才进行权限删除(源路径)
        if ($from_share_filter->is_shared){
            //判断文件重命名是否有权限操作
            if ($action == MConst::RENAME){           //如果是重命名则不进行删除
                //如果重命名文件后权限保持不变
                if ($file_detail->file_type != MConst::OBJECT_TYPE_FILE){
                    Yii::app()->privilege->updatedAllFilePath($from_path, $to_path);
                }
                $isRename = true;
            }
        }
    }
}