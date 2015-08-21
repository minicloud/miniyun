<?php
/**
 * 更新miniyun_file_meta表记录
 *
* @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 2.2
 */
?>
<?php
class m150821_112955_v220  extends EDbMigration{
    
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        try { 
            $this->addColumn(DB_PREFIX.'_file_metas', 'file_id', 'int(11)');
            $criteria = new CDbCriteria();   
            $metas = FileMeta::model()->findAll($criteria);
            foreach ($metas as $key => $meta) {
                $key = $meta->meta_key;
                if($key==="version"){ 
                    $this->getFileId($meta);
                    if(isset($meta->file_id)){
                        $this->getNewRevs($meta); 
                        $meta->meta_key = "versions"; 
                        $meta->save(); 
                    }                                                         
                }                
            }
            $transaction->commit();
        } catch (Exception $e) {
            var_dump($e);exit;
            $transaction->commit();
        }
    }
    /**
    * 获得file_id
    */
    private function getFileId($meta){
        $filePath = $meta->file_path;
        $criteria = new CDbCriteria();
        $criteria->condition = "file_path =:file_path";
        $criteria->params    = array(
            "file_path"=>$filePath
        );
        $file = UserFile::model()->find($criteria); 
        if(isset($file)){ 
            $meta->file_id=$file->id;
        }
    }
    /**
    * 获得设备ID
    */
    private function getDeviceId($rev){
        $userId = $rev['user_id'];
        $deviceName = $rev['device_name'];
        //优先deviceName+userId
        $criteria = new CDbCriteria();
        $criteria->condition = "user_id =:user_id and user_device_name=:user_device_name";
        $criteria->params    = array(
            'user_id' => $userId,
            'user_device_name'=>$deviceName
            );
        $device  = UserDevice::model()->find($criteria);
        if(isset($device)){
            return $device->id;
        }
        //其次userId第一个设备
        $criteria->condition = "user_id =:user_id";
        $criteria->params    = array(
            'user_id' => $userId
            );
        $device  = UserDevice::model()->find($criteria);
        if(isset($device)){
            return $device->id;
        }
        return -1;
    }
    /**
    * 获得新版本
    */
    private function getNewRevs($meta){ 
        $value = $meta->meta_value;
        $revs = unserialize($value);
        $newRevs = array();
        foreach ($revs as $key => $rev) { 
            $versionId = $rev['version_id'];
            $deviceId = $this->getDeviceId($rev);
            //根据$versionId获得hash值
            $version =  FileVersion::model()->find("id=:id",array("id"=>$versionId));
            $hash = "";
            if($version){
                $hash = $version->file_signature;
            }
            $newRev = array();
            $newRev["hash"] = $hash;
            $newRev["device_id"] = $deviceId;
            $newRev["time"] = strtotime($rev['datetime']);
            $newRevs[] = $newRev;
        }
        $meta->meta_value = json_encode(array_reverse($newRevs));
    } 

}