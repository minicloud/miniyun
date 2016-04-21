<?php
/**
 *
 * Miniyun所有数据库层的Model都需继承的类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class CMiniyunModel extends CActiveRecord
{

    public function getDbConnection()
    {
        self::$db=Yii::app()->getComponent('db');
        return self::$db;
    }
    /**
     * 存储前的时间补全, 密码加密
     * @see CActiveRecord::beforeSave()
     */
    public function beforeSave()
    {
        $data = date("Y-m-d H:i:s",time());
        if ($this->isNewRecord)
        {
            //如果目标表有company_id字段，自动添加记录
            $abs = $this->attributes;
            if(array_key_exists('company_id', $abs)){
                $companyId = $_SESSION['company_id']; 
                if(isset($companyId)){ 
                    $companyId = intval($companyId);
                    $this->company_id = intval($companyId);
                }
            }
            $this->created_at = $data;
        }
        $this->updated_at = $data; 
        return true;

    }
    /**
     * 存储前的时间补全, 密码加密
     * @see CActiveRecord::beforeSave()
     */
    public function beforeFind()
    {
        $abs = $this->attributes; 
        if(array_key_exists('company_id', $abs)){
            if(empty($_SESSION)) return;
            $companyId = $_SESSION['company_id']; 
            if(isset($companyId)){ 
                //如果目标表有company_id字段，自动添加过滤条件
                $companyId = intval($companyId);
                $criteria= $this->getDbCriteria(); 
                $criteria->addCondition("company_id =".$companyId,"and"); 
            }
        } 
    }
    public function beforeCount()
    {
        $abs = $this->attributes;
        if(array_key_exists('company_id', $abs))
        {
            if(empty($_SESSION)) return;
            $companyId = $_SESSION['company_id'];
            if(isset($companyId)){ 
                //如果目标表有company_id字段，自动添加过滤条件
                $companyId = intval($companyId);
                $criteria= $this->getDbCriteria();
                $criteria->addCondition("company_id =".$companyId,"and");
            }            
        } 
    }
    public function beforeDelete(){
        $abs = $this->attributes; 
        if(array_key_exists('company_id', $abs)){
            if(empty($_SESSION)) return;
            $companyId = $_SESSION['company_id'];
            if(isset($companyId)){ 
                //如果目标表有company_id字段，自动添加过滤条件
                $companyId = intval($companyId);
                $criteria= $this->getDbCriteria(); 
                $criteria->addCondition("company_id =".$companyId,"and"); 
            }            
        }
    }
    /**
     *
     * 把列表數據根據指定的id組合成"1,2,3,4"格式的數據
     */
    public function getIds($data,$key="id"){
        $ids = "";
        foreach($data as $index=>$item){
            if(strlen($ids)>0){
                $ids = $ids.",";
            }
            $ids = $ids.$item[$key];
        }
        return $ids;
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