
<?php
/**
 * 主要用于1.5升级1.6
 */
class m141226_161355_v170 extends EDbMigration{
    public function up()
    {
        $transaction = $this->getDbConnection()->beginTransaction();
        $this->addTable();
        $this->modifyData();
        $transaction->commit();
    }
    public function searchTable($tableName){
        $sql = 'show tables like \'%'.$tableName.'\'';
        $tb = Yii::app()->db->createCommand($sql)->queryAll();
        return count($tb);
    }
    public  function addTable(){
        $groupCount = $this->searchTable(DB_PREFIX.'_group');
        if($groupCount==1){
            $this->addColumn(DB_PREFIX.'_group', 'user_id', 'int(11) default  \'-1\'');
            $this->dropTable(DB_PREFIX.'_groups');
            $this->renameTable(DB_PREFIX.'_group',DB_PREFIX.'_groups');
        }

        /**
         *先修改file type为4，再存储公共目录的权限数据
         */
        $sql = "select * from ".DB_PREFIX.'_files where file_type=16';
        $files = Yii::app()->db->createCommand($sql)->query();
        foreach($files as $file){
            $sql = 'insert into '.DB_PREFIX.'_group_privileges (group_id,file_path,permission,created_at,updated_at) values (\'-1\',\''.$file['file_path'].'\',\'100000000\',\''.date('Y-m-d H:i:s',time()).'\',\''.date('Y-m-d H:i:s',time()).'\')';
            Yii::app()->db->createCommand($sql)->query();
            $sql = "update ".DB_PREFIX.'_files set file_type=4  where id='.$file['id'];
            Yii::app()->db->createCommand($sql)->query();
        }

        $groupRelationCount = $this->searchTable(DB_PREFIX.'_group_relation');
        if($groupRelationCount==1){
            $this->dropTable(DB_PREFIX.'_group_relations');
            $this->renameTable(DB_PREFIX.'_group_relation',DB_PREFIX.'_group_relations');
            $sql = "select * from ".DB_PREFIX.'_group_relations where parent_group_id=0';
            $groupRelations = Yii::app()->db->createCommand($sql)->queryAll();
            foreach($groupRelations as $groupRelation){
                $sql = "update ".DB_PREFIX.'_group_relations set parent_group_id=-1  where id='.$groupRelation['id'];
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        $userGroupRelationCount = $this->searchTable(DB_PREFIX.'_user_group_relation');
        if($userGroupRelationCount==1){
            $this->dropTable(DB_PREFIX.'_user_group_relations');
            $this->renameTable(DB_PREFIX.'_user_group_relation',DB_PREFIX.'_user_group_relations');
        }
        $userPrivilegeCount = $this->searchTable(DB_PREFIX.'_user_privilege');
        if( $userPrivilegeCount==1){
            $this->dropTable(DB_PREFIX.'_user_privileges');
            $this->renameTable(DB_PREFIX.'_user_privilege',DB_PREFIX.'_user_privileges');
            $sql = "select * from ".DB_PREFIX.'_user_privileges';
            $userPrivileges = Yii::app()->db->createCommand($sql)->queryAll();
            foreach($userPrivileges as $userPrivilege){
                $permission = unserialize($userPrivilege['permission']);
                $permissionStr = "1".$permission['folder.create'].$permission['folder.rename'].$permission['folder.delete'].$permission['file.create'].$permission['file.modify'].$permission['file.rename'].$permission['file.delete'].$permission['permission.grant'];
                $sql = "update ".DB_PREFIX.'_user_privileges set permission=\''.$permissionStr.'\' where id='.$userPrivilege['id'];
                Yii::app()->db->createCommand($sql)->query();
            }
        }
        $shareFileCount = $this->searchTable(DB_PREFIX.'_share_files');
        if($shareFileCount==1){
            $this->dropColumn(DB_PREFIX.'_share_files','expires');
        }
        /**
         * 迁移时删除file type为3的数据
         */
        $sql = "delete from ".DB_PREFIX.'_files where file_type=3';
        Yii::app()->db->createCommand($sql)->query();
    }
    public function modifyData(){
        $criteria = new CDbCriteria();
        $items = Group::model()->findAll($criteria);
        foreach($items as $item) {
             $groupRelation = MiniGroupRelation::getInstance()->getByGroupId($item->id);
             $group  = Group::model()->findByPk($item->id);
             $group->parent_group_id = $groupRelation['parent_group_id'];
             $group->save();
        }
    }
}