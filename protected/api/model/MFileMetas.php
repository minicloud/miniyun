<?php
/**
 * 对应数据库$prefix$_file_metas
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFileMetas extends MModel {
    /**
     * 为文件创建meta信息
     * @param string $file_path
     * @param string $meta_key
     * @param string $meta_value
     */
    public static function createFileMeta($file_path, $meta_key, $meta_value) {
        $file_meta = new MFileMetas();
        $file_meta->file_path = $file_path;
        $file_meta->meta_key = $meta_key;
        $file_meta->meta_value = $meta_value;
        $meta_value = CUtils::real_escape_string($meta_value);
        $sql = "INSERT INTO " . DB_PREFIX . "_file_metas";
        $sql .= "(file_path,meta_key,meta_value,created_at,updated_at)";
        $sql .= " VALUES(";
        $sql .= "\"$file_path\",'$meta_key','$meta_value',now(),now()";
        $sql .= ")";
        Yii::trace("function: '{createFileMeta}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        if ($db_manager->insertDb($sql) === false){
            return false;
        }
        return $file_meta;
    }
    
    /**
     * 更新文件meta信息
     * @param string $file_path
     * @param string $meta_key
     * @param string $meta_value
     */
    public static function updateFileMeta($file_path,$meta_key, $meta_value) {
        $meta_value = CUtils::real_escape_string($meta_value);
        $sql  = "UPDATE " . DB_PREFIX . "_file_metas ";
        $sql .= "SET meta_value = '$meta_value', updated_at = now() ";
        $sql .= "WHERE file_path=\"$file_path\" AND meta_key=\"$meta_key\"";
        $db_manager = MDbManager::getInstance();
        return $db_manager->updateDb($sql);
    }
    
    /**
     * 查询文件meta信息
     * @param string $file_path
     * @param string $meta_key
     */
    public static function queryFileMeta($file_path,$meta_key) {
        $sql = "SELECT * FROM  " . DB_PREFIX . "_file_metas ";
        $sql .= "WHERE file_path=\"$file_path\" AND meta_key=\"$meta_key\"";
        Yii::trace("function: '{queryFileMeta}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        return $db_manager->selectDb($sql);
    }
    
    /**
     * 批量查询所有所需的数据
     * @param $meta_key     对应key
     * @param $flie_array   对应数组
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function batchQueryFileMeta($meta_key, $file_array)
    {
        if (empty($file_array))
        {
            return $file_array;
        }
        $sql = "SELECT * FROM  " . DB_PREFIX . "_file_metas ";
        $sql .= " where meta_key='$meta_key' AND file_path in (";
        foreach ($file_array as $file)
        {
            $sql .= "\"{$file->file_path}\",";
        }
        //
        // 去掉最后一个 ","
        //
        $sql = substr($sql, 0, -1);
        $sql .= ")";
        Yii::trace("function: '{queryFileMeta}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        return $db_manager->selectDb($sql);
    }
    
    /**
     * 批量处理添加数据
     * @param array $create_array 包含需要存储的数组
     * @param string $meta_key      查询关键字
     * @return mixed 创建成功返回true，否则返回false
     * @since 1.0.7
     */
    public static function batchCreateFileMetas($create_array, $meta_key)
    {
        $driver = Yii::app()->db->driverName;
        $sql  = "insert into ".DB_PREFIX."_file_metas(file_path,meta_key,";
        $sql .= "meta_value,created_at,updated_at) values ";
        if ($driver == 'sqlite') {
            $sql = "insert into  ".DB_PREFIX."_file_metas(file_path,meta_key,";
            $sql .= "meta_value,created_at,updated_at)  select ";
        }
        $sql_value  = '';
        $len        = strlen($sql);
        $result     = true;
        $db_manager = MDbManager::getInstance ();
        $index = 0;
        $count = count($create_array);
        foreach ($create_array as $file_meta) {
            if ($file_meta->is_add === false)
            {
                // 存在记录，不需要添加
                continue;
            }
            
            $sql_value .= $driver == 'sqlite' ? "" : "(";
            $sql_value .= "\"{$file_meta->file_path}\",";
            $sql_value .= "\"{$meta_key}\",";
            $sql_value .= "'{$file_meta->meta_value}',";
            $sql_value .= "now(),now()";
            $sql_value .= $driver == 'sqlite' ? "" : ")";
            
            //添加 union all select 或者 ','
            $index++;
            if ($index < $count){
                $sql_value .= $driver == 'sqlite' ? " union all select " : ",";
            }
            
            if (strlen($sql_value) + $len >= MConst::MAX_SQL_STRING_LENGTH 
            || strlen($sql_value) + $len >= MConst::MAX_SQL_STRING_LENGTH - 1000) {
                $sql_exe = $sql . $sql_value;
                Yii::trace ( "function: '{batchCreateFileMetas}',sql:'{$sql_exe}'" );
                $result = $db_manager->insertDb ( $sql_exe );
                if ($result == false) {
                    return $result;
                }
                $sql_value = '';
            }
        }
        
        if (!empty($sql_value)) {
            $sql_exe = $sql . $sql_value;
            $result = $db_manager->insertDb ( $sql_exe );
        }
        return $result;
    }
}

?>