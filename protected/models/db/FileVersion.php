<?php
/**
 * 文件版本Model
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class FileVersion extends CMiniyunModel
{
    public $usedSize;

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'file_versions';
    }

    /**
     * 把文件版本的引用数减1
     * @param $ids
     */
    public function deleteFileVersion($ids){
        if($ids!='' && strlen($ids)>0){
            $idsArray = explode(",",$ids);
            foreach($idsArray as $index=>$versionId){
                //这里不能使用批操作，因为同一个文件，同一个用户可能引用多次。采用in方式只能减1
                $this->updateAll(array("ref_count"=>"ref_count-1"), "id=".$versionId);
            }
        }
    }
    /**
     * 整个系统消耗的文件空间大小
     */
    public function fileSum(){
        $dbCommand = Yii::app()->db->createCommand("SELECT sum(file_size) as usedSpace  FROM `".Yii::app()->params['tablePrefix']."file_versions`");
        $data =  $dbCommand->queryAll();
        foreach($data as $index=>$item){
            return $item["usedSpace"];
        }
    }





    /**
     * 根据文件hash值查找文件版本
     */
    public function queryFileVersionBySignatrue($signatrue) {
        $fileVersion = $this->find(array('condition'=>'file_signature=:file_signature','params'=>array(':file_signature'=>$signatrue)));
        return $fileVersion;
    }
    /**
     * 文档数量
     */
    public function officeIds()
    {
        // 循环组装condition
        $condition='';
        $params=array();
        foreach (Yii::app()->params["officeType"] as $key=>$item)
        {
            $condition.='mime_type=:'.$key.' or ';
            $params[$key] = $item;
        }
        // 截取字符串
        $condition = substr($condition, 0, count($condition) - 4);

        $model = $this->findAll($condition, $params);

        return $model;
    }
    /**
     * 图片数量
     */
    public function imageIds()
    {
        return $this->findAll('mime_type like :type', array('type'=>'image%'));
    }
    /**
     * 视频数量
     */
    public function videoIds()
    {
        return $this->findAll('mime_type like :type', array('type'=>'video%'));
    }
    /**
     * 声音数量
     */
    public function audioIds()
    {
        return $this->findAll('mime_type like :type', array('type'=>'audio%'));
    }

    /**
     *
     * 更新文件
     */
    public function updateRefCountByIds($ids, $add = false) {
        $sql_str = 'UPDATE ' . FileVersion::model()->tableName() . ' SET ref_count=ref_count - 1 WHERE id = ?';
        if ($add) {
            $sql_str = 'UPDATE ' . FileVersion::model()->tableName() . ' SET ref_count=ref_count + 1 WHERE id = ?';
        }
        foreach ($ids as $id) {
            // 文件版本引用次数减 1
            $sql = Yii::app()->db->createCommand($sql_str);
            $sql->execute(array($id));
        }
    }


}