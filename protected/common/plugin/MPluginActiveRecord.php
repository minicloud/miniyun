<?php
/** 
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MPluginActiveRecord extends CActiveRecord {
    /**
     * 存储前的时间补全, 密码加密
     *
     * @see CActiveRecord::beforeSave()
     */
    public function beforeSave()
    {
        $data = date("Y-m-d H:i:s",time());
        if ($this->isNewRecord) {
            $this->created_at = $data;
        }
        $this->updated_at = $data;
        return true;
    }

    /**
     * 重置主键
     * @see CActiveRecord::primaryKey()
     */
    public function primaryKey(){
        if (parent::primaryKey() == "") {
            return "id";
        }
        return parent::primaryKey();
    }
}