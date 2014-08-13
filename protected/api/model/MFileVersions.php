<?php
/**
 * 对应数据库$prefix$_file_versions
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFileVersions extends MModel {
    /**
     * 根据id查询文件版本信息
     * 
     * @param integer $id
     * @return file_version 文件版本信息
     */
    public static function queryFileVersionByID($id) {
        $db_manager = MDbManager::getInstance();
        $sql = "select * from " . DB_PREFIX . "_file_versions where id=$id";
        Yii::trace("function: '{queryFileVersionByID}',sql:'{$sql}'");
        
        return $db_manager->selectDb($sql);
    }
    
    /**
     * 根据文件hash值查找文件版本
     * 
     * @param string $signatrue
     * return file_version
     */
    public static function queryFileVersionBySignatrue($signatrue) {
        $db_manager = MDbManager::getInstance();
        $sql = "select * from " . DB_PREFIX . "_file_versions where file_signature=\"$signatrue\"";
        Yii::trace("function: '{queryFileVersionBySignatrue}',sql:'{$sql}'");
        
        return $db_manager->selectDb($sql);
    }
    
    /**
     * 创建文件版本
     * @param string $signature
     * @param int $size
     */
    public static function createFileVersion($signature, $size, $mime_type) {
        //
        // 组装sql
        //
        $sql  = "insert into " . DB_PREFIX . "_file_versions";
        $sql .= "(file_signature,file_size,block_ids,ref_count,mime_type,created_at,updated_at)";
        $sql .= " VALUES(";
        $sql .= "\"$signature\",$size,'0',0,\"$mime_type\",now(),now()";
        $sql .= ")";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        return $db_manager->insertDb($sql);
    }
    
    /**
     * 更新文件引用次数
     * @param int $id
     * @return 
     */
    public static function updateRefCountById($id, $isAdd = true) {
        $sql  = "UPDATE " . DB_PREFIX . "_file_versions ";
        if ($isAdd) {
            $sql .= "SET ref_count = ref_count + 1, updated_at = now() ";
        } else {
            $sql .= "SET ref_count = ref_count - 1, updated_at = now() ";
        }
        $sql .= "WHERE id=$id";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        return $db_manager->updateDb($sql);
    }
}
?>