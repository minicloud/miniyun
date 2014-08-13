<?php
/**
 * 对应数据库$prefix$_files
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MFiles extends MModel {
    public $mime_type = NULL;
    /**
     * 组装file_details表数据到$file_detail，将数据库相关属性封装
     * @param string $file_path        其路径
     * @param string $file_name        文件名
     * @return mixed $value 执行成功返回该对象，否则返回false
     */
    public static function buildFileDetail($file_path, $file_name)
    {
        $file_detail = new MFiles();
        $file_detail->file_name               = $file_name;
        $file_detail->file_path               = $file_path;
        return $file_detail;
    }
    
    /**
     * 通过path查询文件信息
     * @param string path
     * @return file  数据库对应的文件信息，结构查看数据库
     */
    public static function queryFilesByPath($path, $is_deleted = false) {
        $db_manager = MDbManager::getInstance ();
        //
        // 由于传入参数True,False转换整数，不正确需要这样转换(如：python的get metadata请求)
        //
        if ($is_deleted) {
            $is_deleted = intval ( true );
        } else {
            $is_deleted = intval ( false );
        }
        
        $sql = "select * from " . DB_PREFIX . "_files where file_path=\"{$path}\" ";
        $sql .= "AND is_deleted={$is_deleted}";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 创建元数据对象
     */
    public static function CreateFileDetail($file_detail, $user_id) {
        $sql = "insert into " . DB_PREFIX . "_files(user_id,file_type,parent_file_id,";
        $sql .= "file_create_time,file_update_time,file_name,version_id,";
        $sql .= "file_size,file_path,event_uuid,mime_type,created_at,updated_at) values ";
        //
        // 新建对象，需要生成对应的长度信息
        //
        $file_detail->event_uuid = MiniUtil::getEventRandomString( MConst::LEN_EVENT_UUID );
        if (isset ( $file_detail->version_id ) === false)
            $file_detail->version_id = 0;
        
        $sql .= "({$user_id},$file_detail->file_type,";
        $sql .= "{$file_detail->parent_file_id},";
        $sql .= "{$file_detail->file_create_time},";
        $sql .= "{$file_detail->file_update_time},";
        $sql .= "\"{$file_detail->file_name}\",";
        $sql .= "{$file_detail->version_id},";
        $sql .= "{$file_detail->file_size},";
        $sql .= "\"{$file_detail->file_path}\",";
        $sql .= "\"{$file_detail->event_uuid}\",";
        if ($file_detail->mime_type === NULL) {
            $sql .= "NULL,";
        } else {
            $sql .= "\"{$file_detail->mime_type}\",";
        }
        $sql .= "now(),now())";
        $db_manager = MDbManager::getInstance ();
        $result = $db_manager->insertDb ( $sql );
        if ($result === false) {
            return false;
        }
        
        //
        // 修复sort值为id值，确保唯一
        //
        $sql = "update  ".DB_PREFIX."_files set sort=id where sort=0";
        $db_manager->insertDb($sql);
        return $file_detail;
    }
    
    /**
     * 处理第一次删除数据请求，相当于放入回收站
     * @param object $file_detail        文件对象
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function updateRemoveFileDetail($file_detail) {
        $db_manager = MDbManager::getInstance();
        $sql = "UPDATE " . DB_PREFIX . "_files SET updated_at=now(), is_deleted = 1 ";
        $sql = apply_filters('modify_file_type', $sql, $file_detail->file_type);
        $sql .= "WHERE file_path = \"{$file_detail->file_path}\" ";
        Yii::trace ( "handleRemoveObject: " . $sql );
        return $db_manager->updateDb ( $sql );
    }
    
    /**
     * 更新元数据对象
     * @param int $id
     * @param array $keys         - 需要更新的字段
     */
    public static function updateFileDetailById($id, $updates = array()) {
        $sql = "UPDATE " . DB_PREFIX . "_files SET ";
        foreach ( $updates as $k => $v ) {
            if (is_string ( $v )) {
                $sql .= "$k=\"$v\"";
            } else {
                $sql .= "$k=$v";
            }
            $sql .= ",";
        }
        $sql .= "updated_at=now() WHERE id=$id";
        Yii::trace ( "function: '{updateFileDetailById}',sql:'{$sql}'" );
        $db_manager = MDbManager::getInstance ();
        return $db_manager->updateDb ( $sql );
    }
    
    /**
     * 根据父亲目录id获取子对象
     * @param int $id
     * @param bool $is_deleted
     */
    public static function queryChildrenByParentId($userId,$parent_file_id, $is_deleted = false) {
        $sql = "SELECT * FROM " . DB_PREFIX . "_files WHERE parent_file_id=$parent_file_id";
        $sql .= " AND user_id=$userId AND is_deleted=";
        $sql .= ( int ) $is_deleted;
        Yii::trace ( "function: '{__FUNCTION__}',sql:'{$sql}'" );
        $result = mysql_query ( $sql );
        
        $ret_array = array ();
        if ($result === false) {
            $message = 'Invalid query: ' . mysql_error () . "\n";
            $message .= 'Whole query: ' . $sql;
            Yii::log ( $message, CLogger::LEVEL_WARNING );
            return false;
        }
        
        while ( $row = mysql_fetch_assoc ( $result ) ) {
            array_push ( $ret_array, $row );
        }
        
        //
        // 释放资源
        // 
        mysql_free_result ( $result );
        
        return $ret_array;
    }
    
    /**
     * 更新删除子文件的所有删除状态
     * @param $path
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function updateRemoveChildrenFile($path) {
        $db_manager = MDbManager::getInstance ();
        $path .= "/";
        $sql = "UPDATE ".DB_PREFIX."_files SET updated_at=now(),is_deleted = 1 ";
        $sql .= "WHERE file_path like \"{$path}%\" ";
        Yii::trace ( "handleRemoveObject: " . $sql );
        return $db_manager->updateDb ( $sql );
    }
    
    /**
     * 处理移动或重命名文件夹或文件子文件的情况
     * @param int $user_id      用户id
     * @param object $file_detail  文件对应信息
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function updateMoveChildrenFileDetail($user_id, $file_detail) {
        $relative_path = $file_detail->file_path;
        $relative_path .= "/";
        $from_path = $file_detail->from_path;
        $from_path .= "/";
        $db_manager = MDbManager::getInstance ();
        $sql = "UPDATE ".DB_PREFIX."_files SET updated_at=now(), file_path = ";
        //
        // sqlite处理
        //
        if (Yii::app()->db->driverName == 'sqlite') {
            $sql .= "(\"$relative_path\" || substr(file_path, LENGTH(\"$from_path\") + 1))";
        } else {
            $sql .= "CONCAT(\"$relative_path\", SUBSTRING(file_path,CHAR_LENGTH(\"$from_path\") + 1))";
        }
        $sql .= ",user_id={$file_detail->user_id} ";
        $sql .= " WHERE file_path like \"$from_path%\" and user_id = {$user_id}";
        Yii::trace ( "handleRemoveObject: " . $sql );
        return $db_manager->updateDb ( $sql );
    }
    
    /**
     * 处理移动或重命名文件夹或文件的情况
     * @param object $file_detail
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function updateMoveFileDetail($file_detail)
    {
        $db_manager = MDbManager::getInstance();
        $sql = "UPDATE ".DB_PREFIX."_files SET updated_at=now(),file_path = \"{$file_detail->file_path}\", ";
        $sql .= " parent_file_id = {$file_detail->parent_file_id}, ";
        $sql .= "file_name =\"{$file_detail->file_name}\", ";
        $sql .= "is_deleted =0,";
        $sql .= "event_uuid = \"{$file_detail->event_uuid}\", ";
        if ($file_detail->mime_type === NULL) {
            $sql .= "mime_type=NULL ";
        } else {
            $sql .= "mime_type=\"{$file_detail->mime_type}\" ";
        }
        $sql .= "WHERE id = {$file_detail->id}";
        Yii::trace ( "handleRemoveObject: " . $sql );
        return $db_manager->updateDb ( $sql );
    }
    
    public static function queryFileById($id) {
        $sql = "SELECT * FROM " . DB_PREFIX . "_files WHERE id={$id}";
        Yii::trace ( "function: '{queryFileById}',sql:'{$sql}'" );
        $db_manager = MDbManager::getInstance ();
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 处理查询该路径下所有子文件
     * @param string $path 文件对象
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function queryChildrenFilesByPath($path, $is_sort=false, $is_deleted=-1) {
        $db_manager = MDbManager::getInstance();
        $path .= "/";
        $sql = "select * from ".DB_PREFIX."_files where file_path like \"{$path}%\" ";
        if ($is_deleted != -1) {
            $is_deleted = intval($is_deleted);
            $sql .= "AND is_deleted={$is_deleted}";
        }
        if ($is_sort === true) {
            $sql .= " order by parent_file_id desc";
        }
        Yii::trace ( "function: '{__FUNCTION__}',sql:'{$sql}'" );
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 批量处理文件
     * @param $file_details
     * @return mixed 创建成功返回true，否则返回false
     * @since 1.0.7
     */
    public static function batchCreateFileDetails($user_id, $file_details)
    {
        $driver = Yii::app()->db->driverName;
        $sql  = "insert into ".DB_PREFIX."_files(user_id,file_type,parent_file_id,";
        $sql .= "file_create_time,file_update_time,file_name,version_id,";
        $sql .= "file_size, file_path, event_uuid,mime_type,created_at,updated_at) values ";
        if ($driver == 'sqlite') {
            $sql = "insert into  ".DB_PREFIX."_files(user_id,file_type,parent_file_id,";
            $sql .= "file_create_time,file_update_time,file_name,version_id,";
            $sql .= "file_size, file_path, event_uuid,mime_type,created_at,updated_at) select ";
        }
        $db_manager = MDbManager::getInstance ();
        $sql_value  = '';
        $len        = strlen($sql);
        $result     = true;
        $index = 0;
        $count = count($file_details);
        foreach ( $file_details as $file_detail ) {
            $sql_value .= $driver == 'sqlite' ? "" : "(";
            $sql_value .= "{$user_id},$file_detail->file_type,";
            $sql_value .= "{$file_detail->parent_file_id},";
            $sql_value .= "{$file_detail->file_create_time},";
            $sql_value .= "{$file_detail->file_update_time},";
            $sql_value .= "\"{$file_detail->file_name}\",";
            $sql_value .= "{$file_detail->version_id},";
            $sql_value .= "{$file_detail->file_size},";
            $sql_value .= "\"{$file_detail->file_path}\",";
            $sql_value .= "\"{$file_detail->event_uuid}\",";
            if ($file_detail->mime_type === NULL) {
                $sql_value .= "NULL, ";
            } else {
                $sql_value .= "\"{$file_detail->mime_type}\", ";
            }
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
                Yii::trace ( "function: '{batchCreateFileDetails}',sql:'{$sql_exe}'" );
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
        
        //
        // 修复sort值为id值，确保唯一
        //
        $sql = "update  ".DB_PREFIX."_files set sort=id where sort=0";
        $db_manager->insertDb($sql);
        
        return $result;
    }
    
    /**
     * 根据父目录id查找当前目录下这层的所有文件/夹
     * @param int $parent_file_id 目录id
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function queryChildrenFilesByParentFileID($parent_file_id, $include_deleted = false, $user_id=null) {
        $db_manager = MDbManager::getInstance ();
        $sql = "select * from ".DB_PREFIX."_files where ";
        $sql .= " parent_file_id = {$parent_file_id} ";
        //
        // 处理是否包含已删除的, 请求过来的参数，是字符串
        //
        if ($include_deleted == false) {
            $is_deleted = intval(false);
            $sql .= " AND is_deleted={$is_deleted}";
        }
        if ($user_id)
        {
            
            $var = array('condition'=>" user_id={$user_id}", 'params'=>array(':parent_file_id'=>$parent_file_id));
            //
            // 增加查询条件
            //
            $var = apply_filters('file_list_filter', $var);
            $sql .= ' AND ' .$var['condition'];
        }
        $sql .= ' ORDER BY file_type desc,id DESC ';
        Yii::trace ( "function: '{__FUNCTION__}',sql:'{$sql}'" );
        
        return $db_manager->selectDb ( $sql );
    }
    /**
     * 根据路径查询文件
     * @param int $user_id
     * @param string $path
     */
    public static function queryAllFilesByPath($path) {
        $db_manager = MDbManager::getInstance ();
        $sql = "select * from " . DB_PREFIX . "_files where file_path=\"{$path}\" ";
        Yii::trace ( "function: " . __FUNCTION__ . ",sql:'{$sql}'" );
        
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 将查询结果转换为对象
     * @param array $file_detail
     */
    public static function exchange2Object($file_detail,$signal = false) {
        $retval = new MFiles ();
        $temp = $file_detail;
        if ($signal == false) {
            $temp = $file_detail[0];
        }
        foreach ( $temp as $k => $v ) {
            $retval->$k = $v;
        }
        return $retval;
    }

    
    /**
     * 根据路径更新相应的属性值
     * @param $path
     * @param $updates
     * @return mixed 创建成功返回true，否则返回false
     */
    public static function updateFileDetailByPath($path, $updates) {
        if (empty($updates))
        {
            return false;
        }
        $sql  = "UPDATE " .DB_PREFIX . "_files SET ";
        foreach ($updates as $k => $v) {
            if (is_string($v)) {
                $sql .= "$k=\"$v\"";
            } else {
                $sql .= "$k=$v";
            }
            $sql .= ",";
        }
        $sql .= "updated_at=now() WHERE file_path=\"{$path}\"";
        Yii::trace("function: '{__FUNCTION__}',sql:'{$sql}'");
        $db_manager = MDbManager::getInstance();
        return $db_manager->updateDb($sql);
    }
    
    /**
     * 根据条件查询需要的信息
     * @param $path
     * @param $query
     * @param $include_deleted
     */
    public static function searchFilesByPath($path, $query, $userID, $include_deleted=false) {
        $query =  str_replace("%","\\%",$query);
        $db_manager = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_files where ";
        $sql .= " file_name like \"%{$query}%\" ";
        $sql .= " and user_id = {$userID}";
        if ($path !== "/{$userID}/")
        {
            $sql .= " and file_path like \"{$path}%\" ";
        }
        //
        // 处理是否包含已删除的
        //
        if ($include_deleted == false) {
            $is_deleted = intval ( $include_deleted );
            $sql .= " AND is_deleted={$is_deleted}";
        }

        Yii::trace ( "function: '{__FUNCTION__}',sql:'{$sql}'" );
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 
     * 根据条件查询
     * @param string $condition
     */
    public static function findAll($condition = 1) {
        $db_manager = MDbManager::getInstance();
        $sql = "select * from ".DB_PREFIX."_files where ";
        $sql .= $condition;
        Yii::trace ( "function: '{findAll}',sql:'{$sql}'" );
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 
     * 根据条件删除对应记录
     * @param int $id
     */
    public static function deleteById($id) {
        $db_manager = MDbManager::getInstance();
        $sql = "delete from ".DB_PREFIX."_files where ";
        $sql .= "id = $id";
        Yii::trace ( "function: '{deleteById}',sql:'{$sql}'" );
        if (empty($id)) return false;
        return $db_manager->selectDb ( $sql );
    }
    
    /**
     * 
     * 根据id更新parentid
     * @param int $fromId
     * @param int $toId
     */
    public static function updateParentId($fromId,$toId) {
        $sql = "UPDATE " .DB_PREFIX . "_files SET ";
        $sql .= "parent_file_id=$toId";
        $sql .= " WHERE parent_file_id = $fromId";
        Yii::trace ( "function: '{updateParentId}',sql:'{$sql}'" );
        $db_manager = MDbManager::getInstance();
        $db_manager->updateDb($sql);
    }
}
?>