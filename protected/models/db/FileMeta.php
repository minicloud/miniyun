<?php
/**
 *
 * 文件元數據Model
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class FileMeta extends CMiniyunModel
{

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'file_metas';
    }

    public function beforeSave()
    {
        if(parent::beforeSave()){ 
            $key = $this->meta_key;
            if($key==="version"){
                $key = "versions";
                $this->meta_key = $key;
            } 
            $filePath = $this->file_path;
            $file = MiniFile::getInstance()->getByPath($filePath);
            $this->file_id=$file["id"];
        } 
        return true;
    }
    /**
     * 把用户的文件元数据删除
     */
    public function deleteFileMeta($userIds){
        if($userIds!='' && strlen($userIds)>0){
            $idsArray = explode(",",$userIds);
            foreach($idsArray as $index=>$userId){
                $this->deleteAll("file_path like '/".$userId."/%'");
            }
        }
    }



    /**
     *
     * 根据path 和 key查询记录
     */
    public function getFileMeta($filePath, $metaKey, $all = false) {
        if ($all) {
            return $this->findAll(array('condition' => 'file_path=:file_path and meta_key =:meta_key',
                                        'params'    => array(':file_path'=>$filePath, ':meta_key' => $metaKey)));
        } else {
            return $this->find(array('condition' => 'file_path=:file_path and meta_key =:meta_key',
                                     'params'    => array(':file_path'=>$filePath, ':meta_key' => $metaKey)));
        }
    }

    /**
     * 
     * 创建file_meta的值
     * @param string $file_path
     * @param string $meta_key
     * @param string $meta_value
     * 
     * @since 1.0.7
     */
    public function createFileMeta($file_path, $meta_key, $meta_value)
    {
        $fileMeta = $this->find("file_path=:file_path and meta_key=:meta_key", array(":file_path"=>$file_path, ":meta_key"=>$meta_key));
        if (empty($fileMeta)){
            $fileMeta = new FileMeta();
        }

        $fileMeta["file_path"] = $file_path;
        $fileMeta["meta_key"] = $meta_key;
        $fileMeta["meta_value"] = $meta_value;
        $fileMeta->save();
    }
}