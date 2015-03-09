<?php
/**
 * 用户权限控制
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.7
 */
class UserPermissionBiz extends MiniBiz{
    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    static private $_instance = null;
    /**
     * 静态方法, 单例统一访问入口
     * @return object  返回对象的唯一实例
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function getPermission($path,$userId){
        $file = MiniFile::getInstance()->getByPath($path);
        if(empty($file)){
            throw new MFilesException(Yii::t('api',MConst::PARAMS_ERROR), MConst::HTTP_CODE_400);
        }
        //查询公共目录
        $pathArr = explode('/',$path);
        $masterId = $pathArr[1];
        $master = MiniUser::getInstance()->getUser($masterId,false);
        $shareUserNick = $master['nick'];
        $privilegeLength = 9;
        $file = MiniFile::getInstance()->getByPath($path);
        $fileType = $file['file_type'];
        if($fileType==2){//如果刚好是共享目录
            if((int)$masterId!=$userId){//该共享目录非当前用户目录时才会涉及权限
                $userPrivilege = MiniUserPrivilege::getInstance()->getSpecifyPrivilege($userId,$path);
                if(empty($userPrivilege)){//如果不存在user_privilege，则向上查找group_privilege和department_privilege
                    $groupPermission = GroupPermissionBiz::getInstance()->getPermission($path,$userId);
                    $departmentPrivilege = new DepartmentPermissionBiz();
                    $departmentPermission = $departmentPrivilege->getPermission($userId,$path);
                    if(empty($groupPermission)){
                        $permission = $departmentPermission;
                    }
                    if(empty($departmentPermission)){
                        $permission = $groupPermission;
                    }
                    if(!empty($groupPermission)&&!empty($departmentPermission)){
                        $permission = '';
                        $total = $groupPermission+$departmentPermission;
                        for($i=0;$i<$privilegeLength;$i++){
                            $value = substr($total,$i,1);
                            if($value == '1'||$value == '2'){
                                $permission .='1';
                            }else{
                                $permission .='0';
                            }
                        }
                    }
                    if(empty($groupPermission)&&empty($departmentPermission)){
                        $permission = null;
                    }
                }else{
                    $permission = $userPrivilege['permission'];
                }
                if($permission==null){
                    return array('permission'=>$permission);
                }
                return array("permission"=>$permission,"share_root_path"=>$path,"share_user_nick"=>$shareUserNick,"is_share_folder"=>true,'can_set_share'=>0);
            }
            return array("permission"=>MConst::SUPREME_PERMISSION,"share_root_path"=>$path,"share_user_nick"=>$shareUserNick,'can_set_share'=>1);
        }
        if($fileType==1||$fileType==0){//普通目录情况
            $model = new GeneralFolderPermissionBiz($path);
//            if($model->permission == null){
                if($model->isChildrenShared($path)){
                    $permission = MConst::SUPREME_PERMISSION;
                    return array("permission"=>$permission,"share_user_nick"=>$shareUserNick,'children_shared'=>true,'can_set_share'=>0);
                }
//            }
            if($model->isShared){//如果该普通目录向上或者向下有共享
                if($model->isParentShared($path)){//如果是父目录被共享
                    if((int)$masterId!=$userId){//非共享者本人操作此文件
                        $permission = $model->permission;
                        return array("permission"=>$permission,"share_root_path"=>$model->shareRootPath,"share_user_nick"=>$shareUserNick,"is_share_folder"=>true,'can_set_share'=>0);
                    }else{//本人操作文件
                        $permission = MConst::SUPREME_PERMISSION;
                        return array("permission"=>$permission,"share_root_path"=>$model->shareRootPath,"share_user_nick"=>$shareUserNick,"is_share_folder"=>true,'can_set_share'=>0);
                    }
                }
            }else{//向上向下均没有共享
                return null;
            }
        }
        if($fileType==4){//公共目录情况
            $model = new PublicFolderPermissionBiz();
            $permission = $model->getPublicPermission($path);
            if($permission == null){
                return null;
            }
            if((int)$masterId!=$userId){//非共享者本人操作此文件
                return array("permission"=>$permission,"share_user_nick"=>$shareUserNick,"is_public_folder"=>true,'can_set_share'=>0);
            }else{
                $permission = MConst::SUPREME_PERMISSION;
                return array("permission"=>$permission,"share_user_nick"=>$shareUserNick,"is_public_folder"=>true,'can_set_share'=>0);
            }
        }
    }
}