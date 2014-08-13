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
	private $pluginArray       = NULL;
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
		if (!defined("PLUGIN_DIR")){
			return;
		}
		if($this->pluginArray===NULL){
			$this->pluginArray = $this->getPlugins();
		}else{
			Yii::trace(MiniPlugin::$CACHE_KEY." get cache","miniyun.cache2");
		}
		Yii::app()->setModules($this->pluginArray);
		//引用模块
		foreach ($this->pluginArray as $pluginModule) {
			try {
				Yii::app()->getModule($pluginModule);
			} catch (Exception $e) {

			}
		}
	}
	/**
	 * 重新加载插件
	 */
	public function reload(){
		//清理一级缓存
		if($this->hasCache===true){
			Yii::trace(MiniPlugin::$CACHE_KEY." clean cache ","miniyun.cache1");
			$this->deleteCache(MiniPlugin::$CACHE_KEY);
		}
		//清理二级缓存
		$this->pluginArray = NULL;
		//重新加载
		$this->load();
	}
	/**
	 * 获得有效的插件，如果开启了Cache就要进行缓存，这里可节省30毫秒
	 */
	private function getPlugins(){
		if($this->hasCache===true){
			$datastr = $this->get(MiniPlugin::$CACHE_KEY);
			if($datastr===false){
				Yii::trace(MiniPlugin::$CACHE_KEY." set cache ","miniyun.cache1");
				$object = $this->getEnablePlugs();
				$this->set(MiniPlugin::$CACHE_KEY, serialize($object));
			}else{
				Yii::trace(MiniPlugin::$CACHE_KEY." get cache ","miniyun.cache1");
				$object = unserialize($datastr);
			}
			return $object;
		}
		return $this->getEnablePlugs();

	}
	/**
	 * 获得Enabled的插件列表
	 */
	private function getEnablePlugs(){
		$plugins                = $this->m_get_active_and_valid_plugins();
		//设置模块
		$pluginArray            = array();
		foreach ($plugins as $plugin) {
			$pluginName         = $this->extractModule($plugin);
			if ($pluginName){
				$pluginArray[]  = $pluginName;
			}
		}
		//插件操作
		$pluginArray = $this->pluginOperate($pluginArray);
		return $pluginArray;
	}
	private function pluginOperate($pluginArray){
		if (!isset($_SERVER['REQUEST_URI'])){
			return $pluginArray;
		}
		$path = $_SERVER['REQUEST_URI'];
		if (empty($path)){
			return $pluginArray;
		}
		$index = strrpos($path, "/");
		if ($index <= 0){
			return $pluginArray;
		}
		//前面部分
		$suffix = substr($path, 0, $index);
		$prefix = substr($path, $index+1);
		//启用插件
		if (strstr($suffix, "/adminPlugin/index/action/toEnabled/id")){
			if (!$this->hasPlugin($pluginArray, $prefix)){
				$pluginArray[] = $prefix;
			}
		}
		//禁用插件
		if (strstr($suffix, "/adminPlugin/index/action/toDisabled/id")){
			$pluginArray = $this->removePlugin($pluginArray, $prefix);
		}
		if (isset($_REQUEST['PluginsListForm']) && isset($_REQUEST['selectItems'])) {
			$action = @$_REQUEST['PluginsListForm']['action'];
			$items  =  $_REQUEST['selectItems'];
			foreach ($items as $item) {
				if ($action == 'toEnabled') {
					if (!$this->hasPlugin($pluginArray, $item)){
						$pluginArray[] = $item;
					}
				} elseif ($action == 'toDisabled') {
					$pluginArray = $this->removePlugin($pluginArray, $item);
				}
			}
		}
		return $pluginArray;
	}

	private function hasPlugin($pluginArray, $plugin){
		foreach ($pluginArray as $pluginModule) {
			if ($pluginModule == $plugin){
				return true;
			}
		}
		return false;
	}

	private function removePlugin($pluginArray, $plugin){
		$index = 0;
		foreach ($pluginArray as $pluginModule) {
			if ($pluginModule == $plugin){
				break;
			}
			$index = $index + 1;
		}
		unset($pluginArray[$index]);
		return $pluginArray;
	}

	/**
	 *
	 * 提取插件名称
	 */
	private function extractModule($path){
		$pathArray = explode('/', $path);

		if (sizeof($pathArray) < 2){
			return false;
		}

		$classArray = explode(".", $pathArray[1]);

		if (sizeof($classArray) < 2){
			return false;
		}
		//如果不是php文件
		if ($classArray[1] != "php"){
			return false;
		}

		//class的前缀如果不是Module
		$classSuffixName = substr($classArray[0], strlen($classArray[0])-6);

		if ($classSuffixName != "Module"){
			return false;
		}

		//判断文件夹名称与module的名称是否一致
		$classPrefixName = substr($classArray[0], 0, strlen($classArray[0]) - 6);
		if (strtolower($pathArray[0]) != strtolower($classPrefixName)){
			return false;
		}
		return $pathArray[0];
	}

	/**
	 * Returns array of plugin files to be included in global scope.
	 *
	 * The default directory is wp-content/plugins. To change the default directory
	 * manually, define <code>WP_PLUGIN_DIR</code> and <code>WP_PLUGIN_URL</code>
	 * in wp-config.php.
	 *
	 * @access private
	 * @since 3.0.0
	 * @return array Files to include
	 */
	private function m_get_active_and_valid_plugins() {
		$plugins        = array();
		$value          = MiniOption::getInstance()->getOptionValue("active_plugins");
		if ($value === NULL){
			return $plugins;
		}
		$active_plugins = (array) unserialize($value);
		foreach ( $active_plugins as $plugin ) {
			if ( ! CUtils::validate_file( $plugin ) // $plugin must validate as file
			&& '.php' == substr( $plugin, -4 ) // $plugin must end with '.php'
			&& file_exists( PLUGIN_DIR . '/' . $plugin ) // $plugin must exist
			){
				$plugins[] = $plugin;
			}
		}
		return $plugins;
	}
}