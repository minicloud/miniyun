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
	private static  $CACHE_KEY = "cn.miniyun.MiniPlugin";
	/**
	 *  静态成品变量 保存全局实例
	 *  @access private
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
	public function load(){
        $configPath  = dirname(__FILE__).'/../../config/miniyun-config.php';
        //如果系统尚未安装，则不加载插件
        if (!file_exists($configPath)){
             return;
        }
        $value         = MiniOption::getInstance()->getOptionValue("active_plugins");
        if ($value !== NULL){
            $activePlugins = (array) unserialize($value);
            $data = array();
            //判断插件的入口文件是否存在
            foreach($activePlugins as $id=>$value){
                $path    = Yii::getPathOfAlias('application.plugins').DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.ucfirst($id)."Module.php";
                if(file_exists($path)){
                    $data[] = $id;
                }
            }
            //设置Yii环境变量
            Yii::app()->setModulePath(PLUGIN_DIR);
            Yii::app()->setModules($data);
            //引用模块，让其生效
            foreach ($data as $item) {
                try {
                    Yii::app()->getModule($item);
                } catch (Exception $e) {

                }
            }
        }
	}

    /**
     * 获得迷你云站点安装的插件列表
     * @return array
     */
    public function getAllPlugin(){
        $suffix  = 'Module.php';
        $path    = Yii::getPathOfAlias('application.plugins') . DIRECTORY_SEPARATOR;
        $handle  = opendir($path);
        $plugins = array();
        $tmpPlugins = array();
        while ($file = readdir($handle)) {
            if ($file == '..' || $file == '.'){
                continue;
            }
            if (is_file($path . $file) == true){
                continue;
            }
            $pluginFile = $path. $file . DIRECTORY_SEPARATOR . ucfirst($file) . $suffix;
            if (!file_exists($pluginFile) || is_file($pluginFile) == false){
                continue;
            }
            $pluginMeta = $this->getPluginMeta($pluginFile);
            $pluginMeta["logo"] = "";
            //设置插件的logo.gif
            $logoPath = $path. $file . DIRECTORY_SEPARATOR . "logo.gif";
            if (file_exists($logoPath)){
                $aimPath = $path."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."assets".DIRECTORY_SEPARATOR."plugin".DIRECTORY_SEPARATOR;
                if (!file_exists($aimPath)){
                    mkdir($aimPath);
                }
                $pluginLogoPath = $aimPath.$file.".gif";
                if (copy($logoPath, $pluginLogoPath)) {
                    $pluginMeta["logo"] = MiniHttp::getMiniHost()."assets/plugin/".$file.".gif";
                }
            }
            // 屏蔽插件
            if ($pluginMeta['hidden']) {
                continue;
            }
            //获得插件时间，然后进行排序
            array_push($tmpPlugins,array("file"=>$file,"time"=>filemtime($pluginFile),"data"=>$pluginMeta));
        }
        $timeList = array();
        $fileList = array();
        $dataList = array();
        foreach ($tmpPlugins as $key => $row) {
            $timeList[$key]  = $row['time'];
            $fileList[$key]  = $row['file'];
            $dataList[$key]  = $row['data'];
        }
        array_multisort($timeList, SORT_DESC, $fileList, SORT_ASC, $tmpPlugins);
        //判断插件是否启用
        $activePlugins = array();
        $value         = MiniOption::getInstance()->getOptionValue("active_plugins");
        if ($value !== NULL){
            $activePlugins = (array) unserialize($value);
        }
        foreach ($tmpPlugins as $row) {
            $meta = $row['data'];
            $enabled = false;
            foreach($activePlugins as $id=>$item){
                if($id===$row['file']){
                    $enabled = true;
                    break;
                }
            }
            $meta["enabled"] = $enabled;
            $plugins[] = $meta;
        }
        return $plugins;
    }
    /**
     * 获得启用的插件列表
     */
    public function searchPlugins($key){
        $plugins = array();
        $list = $this->getAllPlugin();
        foreach($list as $item){
            if(strpos($item["id"],$key)!== false || strpos($item["name"],$key)!== false||strpos($item["description"],$key)!== false){
                $plugins[] =  $item;
            }
        }
        return $plugins;
    }
    /**
     * 获得启用的插件列表
     */
    public function getEnabledPlugins(){
        $plugins        = array();
        $list = $this->getAllPlugin();
        foreach($list as $item){
            if($item["enabled"]){
                $plugins[] =  $item;
            }
        }
        return $plugins;
    }
    /**
     * 获得停用的插件列表
     */
    public function getDisabledPlugins(){
        $plugins = array();
        $list   = $this->getAllPlugin();
        foreach($list as $item){
            if(!$item["enabled"]){
                $plugins[] =  $item;
            }
        }
        return $plugins;
    }
    /**
     * 启用插件
     * @param $id
     * @return array
     */
    public function enablePlugin($id){
        $data  = array();
        $value = MiniOption::getInstance()->getOptionValue("active_plugins");
        $list  = (array)unserialize($value);
        //插件已启用，则不用后续的操作
        foreach($list as $key=>$item){
            if($key===$id){
                $data["success"] = true;
                return $data;
            }
        }
        //验证该插件是否合法
        //判断插件是否在约定的目录下
        $path    = Yii::getPathOfAlias('application.plugins').DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.ucfirst($id)."Module.php";
        if(!file_exists($path)){
            $data["success"] = false;
            return $data;
        }
        //把激活插件列表写入到DB中
        $item = array();
        $item[$id] = $id."/".ucfirst($id)."Module.php";
        $list = array_merge($item,$list);
        MiniOption::getInstance()->setOptionValue("active_plugins",serialize($list));
        //数据库增量更新插件相关数据结构
        $data["success"] = true;
        try{
            $migration = new MiniMigration();
            $migration->up($id);
        }catch(Exception $e){
            Yii::log($e->getMessage(),CLogger::LEVEL_ERROR);
            $data["success"] = false;
        }
        return $data;
    }
    /**
     * 停用插件
     * @param $id
     * @return array
     */
    public function disablePlugin($id){
        $data = array();
        $value          = MiniOption::getInstance()->getOptionValue("active_plugins");
        if ($value === NULL){
            $data["success"] = false;
            return $data;
        }
        $list = (array) unserialize($value);
        //卸载插件
        unset($list[$id]);
        //将剩下的插件写入到db中
        MiniOption::getInstance()->setOptionValue("active_plugins",serialize($list));
        $data["success"] = true;
        return $data;
    }
    /**
     * 删除插件
     * @param $id
     * @return array
     */
    public function deletePlugin($id){
        $data = array();
        if(empty($id)){
            $data["success"] = false;
            return $data;
        }
        //先停用插件
        $data = $this->disablePlugin($id);
        //后删除插件文件
        $path = Yii::getPathOfAlias('application.plugins').DIRECTORY_SEPARATOR.$id;
        self::deleteDir($path);
        $data["success"] = true;
        return $data;
    }
    /**
     * 递归删目录
     * @param $dirPath
     * @throws InvalidArgumentException
     */
    private static function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
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
     *
     * 获取插件元数据信息
     * @param  $pluginFile  插件入口文件
     * @return array
     */
    private function getPluginMeta( $pluginFile ) {
        // We don't need to write to the file, so just open for reading.
        $fp = fopen( $pluginFile, 'r' );
        // Pull only the first 8kiB of the file in.
        $pluginData = fread( $fp, 8192 );
        // PHP will close file handle, but we are good citizens.
        fclose( $fp );
        // Make sure we catch CR-only line endings.
        $pluginData = str_replace( "\r", "\n", $pluginData );
        if (preg_match( '|Plugin Name:(.*)$|mi', $pluginData, $pluginName ))
        {
            $pluginName = trim($pluginName[1]);
        }
        else
        {
            $pluginName = '';
        }

        if (preg_match( '|Plugin URI:(.*)$|mi', $pluginData, $pluginUri ))
            $pluginUri  = trim($pluginUri[1]);
        else
            $pluginUri = '';

        if (preg_match( '|Description:(.*)$|mi', $pluginData, $description ))
            $description = trim($description[1]);
        else
            $description = '';

        if (preg_match( '|Author:(.*)$|mi', $pluginData, $authorName ))
            $authorName = trim($authorName[1]);
        else
            $authorName = '';

        if ( preg_match( "|Version:(.*)|i", $pluginData, $version ))
            $version = trim( $version[1] );
        else
            $version = '';
        if ( preg_match( "|Hidden:(.*)|mi", $pluginData, $hidden ))
            $hidden = trim( $version[1] );
        else
            $hidden = '';
        $id = basename(dirname($pluginFile));
        return array('id'=>$id,'name' => $pluginName, 'url' => $pluginUri, 'description' => $description, 'author' => $authorName, 'version' => $version, 'hidden' => $hidden);
    }



}