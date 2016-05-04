<?php

/**
 * 把要加载的插件进行缓存，借以提升系统的性能
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class MiniPlugin extends MiniCache
{
    private static $CACHE_KEY = "cn.miniyun.MiniPlugin";
    /**
     *  静态成品变量 保存全局实例
     * @access private
     */
    static private $_instance = null;

    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        parent::MiniCache();
    }
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
    /**
     * 加载插件
     */
    public function load()
    {
        $activePlugins = ['miniStore','miniDoc'];
        $pluginNames = array();
        //判断插件的入口文件是否存在
        foreach ($activePlugins as $index => $pluginName) {
            $path = Yii::getPathOfAlias('application.plugins') . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . ucfirst($pluginName) . "Module.php";
            if (file_exists($path)) {
                $pluginNames[] = $pluginName;
            }
        }
        //设置Yii环境变量
        Yii::app()->setModulePath(PLUGIN_DIR);
        Yii::app()->setModules($pluginNames);
        //引用模块，让其生效
        foreach ($pluginNames as $pluginName) {
            try {
                Yii::app()->getModule($pluginName);
            } catch (Exception $e) {

            }
        }
    }

    /**
     * 上传并更新插件
     */
    public function uploadPlugin($name){
        $data = array();
        if ($_FILES["file"]["error"] > 0){
            $data["success"] = false;
            $data["msg"] = "not file";
            return $data;
        }
        $fileType = $_FILES["file"]["type"];
        if($fileType!=="application/zip"){
            $data["success"] = false;
            $data["msg"] = "file invalid";
            return $data;
        }
        //把安装包放到upload/temp目录下
        $aimTempPath = BASE."temp/";
        if(!file_exists($aimTempPath)){
            mkdir($aimTempPath);
        }
        $zipFilePath = $aimTempPath.$_FILES["file"]["name"];
        move_uploaded_file($_FILES["file"]["tmp_name"],$zipFilePath);
        $fileName = basename($_FILES["file"]["name"],".zip");
        $unZipFilePath = $aimTempPath.$fileName;
        //解压目录
        $zip = new ZipArchive;
        $res = $zip->open($zipFilePath);
        if ($res === TRUE) {
            $zip->extractTo($unZipFilePath);
            $zip->close();
        }
        //迷你云系统升级
        $isPlugin = true;
        //移动目录到plugins
        $aimPath = PLUGIN_DIR."/".$fileName;
        if($name==="miniyun"){
            $isPlugin = false;
            $aimPath = BASE."../";
        }
        //拷贝文件与目录
        self::copyDir($unZipFilePath,$aimPath);
        //删除缓存数据
        self::deleteDir($unZipFilePath);
        unlink($zipFilePath);
        if($isPlugin){
            //如该插件已启用，则需升级数据库
            //如插件未启用，则在启用时候升级数据库
            $value = MiniOption::getInstance()->getOptionValue("active_plugins");
            if ($value !== NULL) {
                $activePlugins = (array)unserialize($value);
                foreach($activePlugins as $id=>$item){
                    if($id===$fileName){
                        try {
                            $migration = new MiniMigration();
                            $migration->up($fileName);
                        } catch (Exception $e) {

                        }
                        break;
                    }
                }
            }
        }else{
            //如升级迷你云核心系统，则需升级数据库
            try {
                $migration = new MiniMigration();
                $migration->up("core");
            } catch (Exception $e) {
            }
        }
        $data["success"] = true;
        return $data;
    }

    /**
     * 获得迷你云站点安装的插件列表
     * @return array
     */
    public function getAllPlugin()
    {
        $suffix = 'Module.php';
        $path = Yii::getPathOfAlias('application.plugins') . DIRECTORY_SEPARATOR;
        $handle = opendir($path);
        $plugins = array();
        $tmpPlugins = array();
        while ($file = readdir($handle)) {
            if ($file == '..' || $file == '.') {
                continue;
            }
            if (is_file($path . $file) == true) {
                continue;
            }
            $pluginFile = $path . $file . DIRECTORY_SEPARATOR . ucfirst($file) . $suffix;
            if (!file_exists($pluginFile) || is_file($pluginFile) == false) {
                continue;
            }
            $pluginMeta = $this->getPluginMeta($pluginFile);
            $pluginMeta["logo"] = "";
            //设置插件的logo.png
            $logoPath = $path . $file . DIRECTORY_SEPARATOR . "logo.png"; 
            if (file_exists($logoPath)) {
                $aimPath = $path . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "plugin" . DIRECTORY_SEPARATOR;
                if (!file_exists($aimPath)) {
                    mkdir($aimPath);
                }
                $pluginLogoPath = $aimPath . $file . ".png"; 
                if (copy($logoPath, $pluginLogoPath)) {
                    $pluginMeta["logo"] = MiniHttp::getMiniHost() . "assets/plugin/" . $file . ".png";
                }
            }
            // 屏蔽插件
            if ($pluginMeta['hidden']) {
                continue;
            }
            //获得插件时间，然后进行排序
            array_push($tmpPlugins, array("file" => $file, "time" => filemtime($pluginFile), "data" => $pluginMeta));
        }
        $timeList = array();
        $fileList = array();
        $dataList = array();
        foreach ($tmpPlugins as $key => $row) {
            $timeList[$key] = $row['time'];
            $fileList[$key] = $row['file'];
            $dataList[$key] = $row['data'];
        }
        array_multisort($timeList, SORT_DESC, $fileList, SORT_ASC, $tmpPlugins);
        //判断插件是否启用
        $activePlugins = array();
        $value = MiniOption::getInstance()->getOptionValue("active_plugins");
        if ($value !== NULL) {
            $activePlugins = (array)unserialize($value);
        }
        foreach ($tmpPlugins as $row) {
            $meta = $row['data'];
            $enabled = false;
            foreach ($activePlugins as $id => $item) {
                if ($id === $row['file']) {
                    $enabled = true;
                    break;
                }
            }
            $meta["enabled"] = $enabled;
            $plugins[] = $meta;
        }
        //把迷你云主系统加入到列表中
        // $plugins[] = array(
        //     "id"=>"miniyun",
        //     "type"=>"miniyun",
        //     "name"=>NAME_ZH,
        //     "url"=>"http://www.miniyun.cn",
        //     "description"=>Yii::t("common", "plugin_miniyun_description", array("{app_name}"=>NAME_ZH)),
        //     "version"=>APP_VERSION,
        //     "logo"=>"http://static.miniyun.cn/static/mini-box/images/logo.png",
        //     "enabled"=>true,
        // ); 
        return $plugins;
    }

    /**
     * 获得启用的插件列表
     */
    public function searchPlugins($key)
    {
        $plugins = array();
        $list = $this->getAllPlugin();
        foreach ($list as $item) {
            if (strpos($item["id"], $key) !== false || strpos($item["name"], $key) !== false || strpos($item["description"], $key) !== false) {
                $plugins[] = $item;
            }
        }
        return $plugins;
    }

    /**
     * 获得启用的插件列表
     */
    public function getEnabledPlugins()
    {
        $plugins = array();
        $list = $this->getAllPlugin();
        foreach ($list as $item) {
            if ($item["enabled"]) {
                $plugins[] = $item;
            }
        }
        return $plugins;
    }

    /**
     * 获得停用的插件列表
     */
    public function getDisabledPlugins()
    {
        $plugins = array();
        $list = $this->getAllPlugin();
        foreach ($list as $item) {
            if (!$item["enabled"]) {
                $plugins[] = $item;
            }
        }
        return $plugins;
    }

    /**
     * 启用插件
     * @param $id
     * @return array
     */
    public function enablePlugin($id)
    {
        $data = array();
        //判断插件是否在约定的目录下
        $path = Yii::getPathOfAlias('application.plugins') . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . ucfirst($id) . "Module.php";
        if (!file_exists($path)) {
            $data["success"] = false;
            return $data;
        }
        //获得插件元数据
        $pluginMeta = $this->getPluginMeta($path);
        $value = MiniOption::getInstance()->getOptionValue("active_plugins");
        $list = (array)unserialize($value);
        foreach ($list as $key => $item) {
            //判断插件是否已经激活
            if ($key === $id) {
                $data["success"] = true;
                return $data;
            }
            //判断同类型的插件是否已激活，同类型的插件只能启动一个
            if($item["type"]===$pluginMeta["type"]){
                $data["success"] = false;
                $data["msg"] = Yii::t("common", "plugin_has_install", array("{plugin_name}"=>$item["name"]));
                return $data;
            }
        }
        //把激活插件列表写入到DB中
        $item = array();
        $item[$id] = array("type"=>$pluginMeta["type"],"name"=>$pluginMeta["name"]);
        $list = array_merge($item, $list);
        MiniOption::getInstance()->setOptionValue("active_plugins", serialize($list));
        //数据库增量更新插件相关数据结构
        $data["success"] = true;
        try {
            $migration = new MiniMigration();
            $migration->up($id);
        } catch (Exception $e) {
//            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
//            $data["success"] = false;
        }
        return $data;
    }

    /**
     * 停用插件
     * @param $id
     * @return array
     */
    public function disablePlugin($id)
    {
        $data = array();
        $value = MiniOption::getInstance()->getOptionValue("active_plugins");
        if ($value === NULL) {
            $data["success"] = false;
            return $data;
        }
        $list = (array)unserialize($value);
        //卸载插件
        unset($list[$id]);
        //将剩下的插件写入到db中
        MiniOption::getInstance()->setOptionValue("active_plugins", serialize($list));
        $data["success"] = true;
        return $data;
    }

    /**
     * 删除插件
     * @param $id
     * @return array
     */
    public function deletePlugin($id)
    {
        $data = array();
        if (empty($id)) {
            $data["success"] = false;
            return $data;
        }
        //先停用插件
        $data = $this->disablePlugin($id);
        //后删除插件文件
        $path = Yii::getPathOfAlias('application.plugins') . DIRECTORY_SEPARATOR . $id;
        self::deleteDir($path);
        $data["success"] = true;
        return $data;
    }

    /**
     * 递归删目录
     * @param $dirPath
     * @throws InvalidArgumentException
     */
    private static function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            throw new InvalidArgumentException("$dirPath must be a directory");
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    /**
     * 拷贝目录
     * @param $src
     * @param $dst
     */
    private static function copyDir($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    self::copyDir($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
    /**
     *
     * 获取插件元数据信息
     * @param  $pluginFile
     * @return array
     */
    private function getPluginMeta($pluginFile)
    {
        $metaFile = dirname($pluginFile).DIRECTORY_SEPARATOR."meta";
        // We don't need to write to the file, so just open for reading.
        $fp = fopen($metaFile, 'r');
        // Pull only the first 8kiB of the file in.
        $pluginData = fread($fp, 8192);
        // PHP will close file handle, but we are good citizens.
        fclose($fp);
        // Make sure we catch CR-only line endings.
        $pluginData = str_replace("\r", "\n", $pluginData);
        if (preg_match('|Plugin Name:(.*)$|mi', $pluginData, $pluginName)) {
            $pluginName = trim($pluginName[1]);
        } else {
            $pluginName = '';
        }
        if (preg_match('|Plugin Type:(.*)$|mi', $pluginData, $pluginType)) {
            $pluginType = trim($pluginType[1]);
        } else {
            $pluginType = '';
        }
        if (preg_match('|Plugin URI:(.*)$|mi', $pluginData, $pluginUri)) {
            $pluginUri = trim($pluginUri[1]);
        } else {
            $pluginUri = '';
        }
        if (preg_match('|Description:(.*)$|mi', $pluginData, $description)) {
            $description = trim($description[1]);
        } else {
            $description = '';
        }

        if (preg_match('|Author:(.*)$|mi', $pluginData, $authorName)) {
            $authorName = trim($authorName[1]);
        } else {
            $authorName = '';
        }
        if (preg_match("|Version:(.*)|i", $pluginData, $version)) {
            $version = trim($version[1]);
        } else {
            $version = '';
        }

        if (preg_match("|Hidden:(.*)|mi", $pluginData, $hidden)) {
            $hidden = trim($version[1]);
        } else {
            $hidden = '';
        }
        $id = basename(dirname($pluginFile));
        return array('id' => $id, 'type' => $pluginType, 'name' => $pluginName, 'url' => $pluginUri, 'description' => $description, 'author' => $authorName, 'version' => $version, 'hidden' => $hidden);
    }
}