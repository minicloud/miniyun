<?php
/**
 * 插件获取类
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MPluginObtain
{
    /**
     *
     * 获取插件安装目录下的插件
     * @since 1.0.7
     */
    public function getPlugin($plugin_name) {
        $plugins = $this->getPlugins();
        if (array_key_exists($plugin_name, $plugins)){
            return $plugins[$plugin_name];
        }
        return false;
    }
    /**
     *
     * 获取插件安装目录下的插件
     * @since 1.0.7
     */
    public function getPlugins() {
        $suffix  = 'Module.php';
        $path    = Yii::getPathOfAlias('application.modules') . DIRECTORY_SEPARATOR;
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
            $plugin_file = $path. $file . DIRECTORY_SEPARATOR . ucfirst($file) . $suffix;
            if (!file_exists($plugin_file) || is_file($plugin_file) == false){
                continue;
            }
            $plugin_data = $this->getPluginData($plugin_file);
            $plugin_data["logo"] = false; 
            //设置插件的logo.gif
            $logoPath = $path. $file . DIRECTORY_SEPARATOR . "logo.gif";
            if (file_exists($logoPath)){
            	$aimPath = $path."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."statics".DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR."plugins".DIRECTORY_SEPARATOR;
            	if (!file_exists($aimPath)){
            		mkdir($aimPath);
            	}
            	$pluginLogoPath = $aimPath.$file.".gif";
            	if (copy($logoPath, $pluginLogoPath)) {
            		$plugin_data["logo"] = true;
            	}
            }
            //
            // 屏蔽插件
            //
             if ( $plugin_data['hidden']) {
                 continue;
             }
            //获得插件时间，然后进行排序
            array_push($tmpPlugins,array("file"=>$file,"time"=>filemtime($plugin_file),"data"=>$plugin_data));
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
	    foreach ($tmpPlugins as $key => $row) {
		 	$plugins[$row['file']] = $row['data'];
		 }
        return $plugins;
    }

    /**
     *
     * 获取插件描述信息
     * @since 0.9.9
     * @param string $plugin_file  Path to the plugin file
     * @return array
     */
    public function getPluginData( $plugin_file ) {
        // We don't need to write to the file, so just open for reading.
        $fp = fopen( $plugin_file, 'r' );
        // Pull only the first 8kiB of the file in.
        $plugin_data = fread( $fp, 8192 );
        // PHP will close file handle, but we are good citizens.
        fclose( $fp );
        // Make sure we catch CR-only line endings.
        $plugin_data = str_replace( "\r", "\n", $plugin_data );
        if (preg_match( '|Plugin Name:(.*)$|mi', $plugin_data, $plugin_name ))
        {
            $plugin_name = $this->_transferInfo($plugin_file, trim($plugin_name[1]));
        }
        else
        {
            $plugin_name = '';
        }

        if (preg_match( '|Plugin URI:(.*)$|mi', $plugin_data, $plugin_uri ))
        $plugin_uri  = trim($plugin_uri[1]);
        else
        $plugin_uri = '';

        if (preg_match( '|Description:(.*)$|mi', $plugin_data, $description ))
        $description = $this->_transferInfo($plugin_file, trim($description[1]));
        else
        $description = '';

        if (preg_match( '|Author:(.*)$|mi', $plugin_data, $author_name ))
        $author_name = $this->_transferInfo($plugin_file, trim($author_name[1]));
        else
        $author_name = '';

        if (preg_match( '|Author URI:(.*)$|mi', $plugin_data, $author_uri ))
        $author_uri  = trim($author_uri[1]);
        else
        $author_uri = '';

        if ( preg_match( "|Version:(.*)|i", $plugin_data, $version ))
        $version = trim( $version[1] );
        else
        $version = '';
        if ( preg_match( "|Hidden:(.*)|mi", $plugin_data, $hidden ))
            $hidden = trim( $version[1] );
        else
            $hidden = '';
        return array('name' => $plugin_name, 'plugin_uri' => $plugin_uri, 'description' => $description, 'author' => $author_name, 'author_uri' => $author_uri , 'version' => $version, 'hidden' => $hidden);
    }

	/**
	 *
	 * 获取系统启用的插件
	 * @since 1.0.7
	 * @return mixed array('contacts'=>'application.modules.contacts.ContactsModule')
	 */
	public function getActivePlugins() {
		$value = MiniOption::getInstance()->getOptionValue('active_plugins');
		if ($value===NULL){
			return array();
		}
		return unserialize($value);
	}


    /**
     * 产生插件所需要的
     *
     * @param $plugin_file 插件名称
     * @param $info 需要转换的
     *
     * @since 1.0.7
     *
     * @return string 转换后的多国语言描述信息
     */
    private function _transferInfo($plugin_file, $info)
    {
        $include = 0;
        while(preg_match('/Yii::t\(\'(.*?)\',.*?\'(.*?)\'\)/', $info, $info_array))
        {
            //
            // 需要加载该文件才能够进行多国语言引用
            //
            if ($include == 0) {
                include_once $plugin_file;
            }
            $info = str_replace($info_array[0], Yii::t(trim($info_array[1]), trim($info_array[2])), $info);
            $include += 1;
            if ($include >= 50) { // 最多循环50次
                break;
            }
        }
        return $info;
    }
}