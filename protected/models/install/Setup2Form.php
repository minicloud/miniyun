<?php
/**
 * mysql数据库安装
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class Setup2Form extends CFormModel
{

    public  $dbName;//数据库名称
    public  $userName;//用户名
    public  $password;//用户密码
    public  $dbHost;//主机
    public  $dbPort;//端口
    public  $tablePrefix;//表前缀
    public  $base;//文件存储路径
    private $_db;//数据库连接

    // 数据库不存在  1049错误码
    const DATABASE_NOT_EXISTS = 1049;

    // 当前用户没有访问数据库的权限  1044错误码
    const DENIED_FOR_USER = 1044;

    function myErrorHandler($errno, $errorMsg, $errfile, $errline) {
        if(!$this->hasErrors()){
            $this->addError("dbName", $errorMsg);
        }
        return true;
    }

    public function init(){
        set_error_handler( array( &$this, 'myErrorHandler' ) );
        $this->dbName="miniyun";
        $this->userName = "miniyun";
        $this->password = "miniyun";
        $this->dbHost = "127.0.0.1";
        $this->dbPort = "3306";
        $this->tablePrefix = "miniyun";
        $this->base = Yii::app()->basePath."/../upload/";//默认存储路径;
    }

    public function rules()
    {
        $valid = array(
            array('dbName,userName,password,dbHost,dbPort,tablePrefix,base', 'required'),
            array('dbPort', 'numerical', 'integerOnly'=>true),
        );
        return $valid;
    }

    public function attributeLabels()
    { //提供显示的标签
        return  array(
            'dbName'=>Yii::t("front_common", "install_setup2_db_name"),
            'userName'=>Yii::t("front_common", "install_setup2_db_user"),
            'password'=>Yii::t("front_common", "install_setup2_db_password"),
            'dbHost'=>Yii::t("front_common", "install_setup2_db_host"),
            'dbPort'=>Yii::t("front_common", "install_setup2_db_port"),
            'tablePrefix'=>Yii::t("front_common", "install_setup2_table_prefix"),
            'base'=>Yii::t("front_common", "install_setup2_upload_path"),
        );
    }

    /**
     * 检查路径是否正确且该目录可写
     */
    private  function checkPath(){
        // 判断父目录是否存在
        if (file_exists(dirname($this->base)) == false){
            $this->addError('base', Yii::t("front_common", "install_setup2_dir_not_found"));
            return false;
        }
        // 文件不存在
        if (file_exists($this->base) == false){
            mkdir($this->base);
            chmod($this->base, 755);
        }
        // 文件存在
        if (is_writable($this->base) == false){
            $this->addError('base', Yii::t("front_common", "install_setup2_dir_not_writable"));
            return false;
        }

        return true;
    }
    /**
     * PHP 5.5导入数据库
     */
    private function php55Import(){
        $success = true;
        $this->_db = mysqli_connect(trim($this->dbHost), $this->userName, $this->password,"",trim($this->dbPort));
        if (!$this->_db) {
            // 连接失败，打印错误日志到application.log
            Yii::log(mysqli_error(), CLogger::LEVEL_ERROR, 'mysql');
            $success = false;
        }
        if($this->hasErrors()==false){
            if(!mysqli_select_db($this->_db,$this->dbName)){
                if (mysqli_errno($this->_db) == Setup2Form::DATABASE_NOT_EXISTS) {
                    // 创建对应数据库
                    $sql = "create database IF NOT EXISTS ".$this->dbName." default charset utf8 COLLATE utf8_general_ci";
                    mysqli_query($this->_db,$sql);
                    // 创建数据库失败 --无权限错误
                    if (mysqli_errno($this->_db) == Setup2Form::DENIED_FOR_USER) {
                        $this->addError("dbName", Yii::t("front_common", "install_setup2_create_denied"));
                        $success = false;
                    }

                    // 创建数据库失败 --其他未知错误
                    elseif(mysqli_errno($this->_db) != 0) {
                        $this->addError("dbName", Yii::t("front_common", "install_setup2_create_error", array("{dbname}"=>$this->dbName)));
                        $success = false;
                    }
                }

                // 当前用户没有访问数据库的权限
                elseif (mysqli_errno($this->_db) == Setup2Form::DENIED_FOR_USER) {
                    $this->addError("dbName", Yii::t("front_common", "install_setup2_access_denied", array("{dbname}"=>$this->dbName)));
                    $success = false;
                } else {
                    $this->addError("dbName", Yii::t("front_common", "install_setup2_access_error", array("{dbname}"=>$this->dbName)));
                    $success = false;
                }
            }
        }
        if($this->hasErrors()==false){
            mysqli_select_db($this->_db,$this->dbName);
            $sql = "show tables";
            $result = mysqli_query($this->_db,$sql);
            while ($row = mysqli_fetch_row($result)) {
                $tableName = $row[0];
                $pos = strpos($tableName,$this->tablePrefix);
                if(!is_bool($pos) && $pos==0){
                    $this->addError("dbName", Yii::t("front_common", "install_setup2_table_is_exist"));
                    $success = false;
                    break;
                }
            }
        }
        return $success;
    }
    /**
     * PHP 5.3导入数据库
     */
    private function php53Import(){
        $success = true;
        $this->_db = mysql_connect(trim($this->dbHost).':'.trim($this->dbPort), $this->userName, $this->password,true);
        if (!$this->_db) {
            // 连接失败，打印错误日志到application.log
            Yii::log(mysql_error(), CLogger::LEVEL_ERROR, 'mysql');
            $success = false;
        }
        if($this->hasErrors()==false){
            if(!mysql_select_db($this->dbName,$this->_db)){
                if (mysql_errno() == Setup2Form::DATABASE_NOT_EXISTS) {
                    // 创建对应数据库
                    $sql = "create database IF NOT EXISTS ".$this->dbName." default charset utf8 COLLATE utf8_general_ci";
                    mysql_query($sql);
                    // 创建数据库失败 --无权限错误
                    if (mysql_errno() == Setup2Form::DENIED_FOR_USER) {
                        $this->addError("dbName", Yii::t("front_common", "install_setup2_create_denied"));
                        $success = false;
                    }

                    // 创建数据库失败 --其他未知错误
                    elseif(mysql_errno() != 0) {
                        $this->addError("dbName", Yii::t("front_common", "install_setup2_create_error", array("{dbname}"=>$this->dbName)));
                        $success = false;
                    }
                }

                // 当前用户没有访问数据库的权限
                elseif (mysql_errno() == Setup2Form::DENIED_FOR_USER) {
                    $this->addError("dbName", Yii::t("front_common", "install_setup2_access_denied", array("{dbname}"=>$this->dbName)));
                    $success = false;
                } else {
                    $this->addError("dbName", Yii::t("front_common", "install_setup2_access_error", array("{dbname}"=>$this->dbName)));
                    $success = false;
                }
            }
        }
        if($this->hasErrors()==false){
            mysql_select_db($this->dbName);
            $sql = "show tables";
            $result = mysql_query($sql,$this->_db);
            while ($row = mysql_fetch_row($result)) {
                $tableName = $row[0];
                $pos = strpos($tableName,$this->tablePrefix);
                if(!is_bool($pos) && $pos==0){
                    $this->addError("dbName", Yii::t("front_common", "install_setup2_table_is_exist"));
                    $success = false;
                    break;
                }
            }
        }
        return $success;
    }
    /**
     * 数据库信息检测
     */
    private function baseValidate(){
        if($this->checkPath()==false){
            return false;
        }
        if(function_exists('mysqli_connect')){
            return $this->php55Import();
        }else{
            return $this->php53Import();
        }
    }
    /**
     * 初始化DB配置文件
     */
    private function initDbConfigFile(){
        $filePath   = dirname(__FILE__).'/../../config/miniyun-config-simple.php';

        $content = "";
        $fileHandle = fopen($filePath, "rb");
        while (!feof($fileHandle) ) {
            $content = $content.fgets($fileHandle);
        }

        $this->base = str_replace("\\", "/", $this->base);
        fclose($fileHandle);
        $content = str_replace("#dBType#", "mysql", $content);
        $content = str_replace("#dBName#",$this->dbName,$content);
        $content = str_replace("#dBUser#",$this->userName,$content);
        $content = str_replace("#dBPasswd#",$this->password,$content);
        $content = str_replace("#tablePrefix#",$this->tablePrefix,$content);
        $content = str_replace("#key#",MiniUtil::genRandomString(),$content);
        $content = str_replace("#base#",$this->base,$content);
        $content = str_replace("#dBHost#",$this->dbHost,$content);
        $content = str_replace("#dBPort#",$this->dbPort,$content);
        $filePath = dirname(__FILE__).'/../../config/miniyun-config.php';
        $fh = fopen($filePath, 'w');
        fwrite($fh, $content);
        fclose($fh);

    }
    public function save(){
        $success = false;
        if($this->validate()){
            if($this->baseValidate()){
                if(!$this->hasErrors()){//如果校驗没有错误

                    $dbComponent = Yii::createComponent(array(
                        'class'=>'CDbConnection',
                        'connectionString'=>'mysql:host='.trim($this->dbHost).';port='.trim($this->dbPort).';dbname='.$this->dbName,
                        'username'=>$this->userName,
                        'password'=>$this->password,
                        'emulatePrepare'=>true,
                        'charset' => "utf8",
                    ));
                    Yii::app()->setComponent("dbInstall", $dbComponent);

                    define('DB_PREFIX',$this->tablePrefix);
                    define('DB_TYPE',"mysql");

                    $migration = new MiniMigration();
                    $migration->connectionID = "dbInstall";
                    $migration->up();
                    $this->initDbConfigFile();
                    $success =  true;
                }
            }
        }
        if(isset($this->_db)){
            if(function_exists('mysqli_close')) {
                mysqli_close($this->_db);
            }else{
                mysql_close($this->_db);
            }
        }
        return $success;
    }
}