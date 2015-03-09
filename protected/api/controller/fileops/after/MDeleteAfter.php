<?php
/**
 * Miniyun delete服务结束后的后续动作
 * 如：权限删除
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MDeleteAfter{
    //数据库中查询的数据
    public $query_db_file;
    //判断共享的对象
    public $share_filter;
    //文件的详细信息
    public $file_detail;

    /**
     * 控制器执行主逻辑函数, 处理删除文件或者文件夹
     *
     * @since 1.0.7
     */
    public function execute()
    {
        $this->deletePermission();
    }

    /**
     *
     * 删除权限
     *
     * @since 1.0.7
     */
    public function deletePermission(){
        if ($this->share_filter->is_shared) {
            if ($this->query_db_file[0]['file_type'] != CConst::OBJECT_TYPE_FILE){
                Yii::app()->privilege->deleteAllPrivilege($this->query_db_file[0]['file_path']);
            }
        }
    }
}