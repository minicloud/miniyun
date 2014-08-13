<?php
/**
 *
 * 图片exif信息model
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class FileExif extends CMiniyunModel
{

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'file_exifs';
    }
    /**
     * 创建文件exif信息
     */
    public function createFileExif($vertion_id, $lat, $lng, $exif){
        $fileExif = new FileExif();
        $fileExif->version_id = $vertion_id;
        $fileExif->latitude = $lat;
        $fileExif->longtitude = $lng;
        // $exifiis下存在无法插入数据的问题,屏蔽
        $fileExif->exif = null; 
        $fileExif->save();
        return $fileExif;
    }
   
      /**
       * 
       * 删除文件exif信息 
       * @param  $version_ids
       */
    function deleteFileExif($version_ids){
        if($version_ids!='' && strlen($version_ids)>0){
             $this->deleteAll("version_id in (".$version_ids.")");
        }
    }
}