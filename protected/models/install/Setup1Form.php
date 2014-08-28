<?php
/**
 *  用户安装第二步
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.6
 */
class Setup1Form extends CFormModel {
    //用戶所使用的数据库类型
    public $db;

    public $dirItems;
    public $extensionItems;
    public $envItems;
    public $funcItems;
    private $itemsUrl = array(
        'mysql'              => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'php'                => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'exif'               => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'gdversion'          => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'dirfile'            => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'gettext'            => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'ldap'               => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'pdo_mysql'          => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'mbstring'           => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'zip'                => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'zlib'               => "http://open.miniyun.cn/index.php?title=PHP依赖",
        'attachmentupload'   => "http://help.miniyun.cn/index.php?title=Php.ini",
    );
    function myErrorHandler($errno, $errstr, $errfile, $errline) {
        if(!$this->hasErrors()) {
            $this->addError("msg", "");
        }
        return true;
    }

    public function init() {
        set_error_handler(array(&$this, ''));
    }

    public function check() {
        $this->envCheck();
        $this->readOrWrite();
        $this->funExist();
    }


    private function getUrl($key) {
        return array_key_exists($key, $this->itemsUrl) == true ? $this->itemsUrl[$key] : "http://bbs.miniyun.cn";
    }

    public function toString($key) {
        $obj = $this->attributeLabels();
        return array_key_exists($key, $obj) == true ? $obj[$key] : $key;
    }

    public function attributeLabels() { // 提供显示的标签
        return array('os'              => Yii::t("front_common", "install_setup1_operat_system"),
                    'php'              => Yii::t("front_common", "install_setup1_PHP_version"),
                    'attachmentupload' => Yii::t("front_common", "install_setup1_upload_size"),
                    'gdversion'        => Yii::t("front_common", "install_setup1_GD_library"),
                    'diskspace'        => Yii::t("front_common", "install_setup1_disk_space"),
                    'notset'           => Yii::t("front_common", "install_setup1_without_limit"),
                    '+r'               => Yii::t("front_common", "install_setup1_read_only"),
                    '+r+w'             => Yii::t("front_common", "install_setup1_writable"),
                    'nodir'            => Yii::t("front_common", "install_setup1_no_dir"),
                    'nofile'           => Yii::t("front_common", "install_setup1_no_file"),
                    'yes'              => Yii::t("front_common", "install_setup1_support"),
                    'no'               => Yii::t("front_common", "install_setup1_not_support"),
        );
    }
    /**
     * 检测依赖
     */
    private function readOrWrite() {
        $this->dirItems = array(
            './assets/'            => array('path' => '../assets'),
            './protected/config/'  => array('path' => 'config'),
            './protected/runtime/' => array('path' => 'runtime'),
            './static/thumbnails/' => array('path' => '../static/thumbnails/'),
        );
        foreach($this->dirItems as $key => $item) {
            $item_path = $item['path'];
            if (file_exists(ROOT_PATH . $item_path) == false) {
                $this->dirItems[$key]['status'] = 0;
                $this->dirItems[$key]['current'] = 'nodir';
                $this->addError('msg',Yii::t("front_common", "install_setup1_dir_not_found", array("{path}"=>$key)).'<a target="_blank" href="' . $this->getUrl('dirfile') . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>');
                continue;
            }
            if(is_writable(ROOT_PATH . $item_path) == false) {
                $this->dirItems[$key]['status'] = 0;
                $this->dirItems[$key]['current'] = '+r';
                $this->addError('msg',Yii::t("front_common", "install_setup1_dir_not_writable", array("{path}"=>$key)).'<a target="_blank" href="' . $this->getUrl('dirfile') . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>');
                continue;
            }
            $this->dirItems[$key]['status'] = 1;
            $this->dirItems[$key]['current'] = '+r+w';
        }
    }

    /**
     * 检测系统环境
     */
    private function envCheck() {
        $this->envItems = array(
            'os'               => array('c' => 'PHP_OS', 'r' => 'notset', 'b' => 'unix'), 
            'php'              => array('c' => 'PHP_VERSION', 'r' => '5.2.0', 'b' => '5.3.0'), 
            'attachmentupload' => array('r' => 'notset', 'b' => '1024M'), 
            'gdversion'        => array('r' => '1.0', 'b' => '2.0'),
            'diskspace'        => array('r' => 'notset', 'b' => 'notset'),
        );

        foreach($this->envItems as $key => $item) {
            // 检测php版本
            if($key == 'php') {
                $this->envItems[$key]['current'] = PHP_VERSION;
            }

            // 检测上传文件大小
            elseif($key == 'attachmentupload') {
                $this->envItems[$key]['current'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow';
                // 8x1024x1024
                if ($this->envItems[$key]['current'] == 'unknow' || CUtils::return_bytes($this->envItems[$key]['current']) < 8388608) {
                    $prompt = '<div style="color:orange;width:60px;float:left;text-align:right">'.$this->envItems[$key]['current'].'</div><a target="_blank" href="' . $this->getUrl($key) . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>';
                    $this->envItems[$key]['current']= $prompt;
                    $this->envItems[$key]['status'] = -1;
                    continue;
                }
            }

            // 检测gd库版本
            elseif($key == 'gdversion') {
                if (function_exists('gd_info')) {
                    $this->envItems[$key]['status'] = 1;
                }
                else {
                    $this->envItems[$key]['status'] = -1;
                }
                $tmp = function_exists('gd_info') ? gd_info() : array();
                $prompt = '<font color="orange">'.Yii::t("front_common", "install_setup1_GD_library_notfound").'</font>&nbsp;<a target="_blank" href="' . $this->getUrl($key) . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>';
                $prompt2 = '<br><font color="orange">'.Yii::t("front_common", "install_setup1_not_found_freetype").'</font>&nbsp;<a target="_blank" href="' . $this->getUrl($key) . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>';
                if(!empty($tmp)){
                    $free_type=$tmp['FreeType Support'] ? '' : $prompt2;
                }
                $this->envItems[$key]['current'] = empty($tmp['GD Version']) ? $prompt : $tmp['GD Version'].$free_type;
                unset($tmp);
                continue;
            }
            // 检测磁盘空间大小
            elseif($key == 'diskspace') {
                if(function_exists('disk_free_space')) {
                    $this->envItems[$key]['current'] = floor(disk_free_space(ROOT_PATH) / (1024 * 1024)) . 'M';
                }else {
                    $this->envItems[$key]['current'] = 'unknow';
                }
            }
            elseif(isset($item['c'])) {
                $this->envItems[$key]['current'] = constant($item['c']);
            }
            $this->envItems[$key]['status'] = 1;
            if($item['r'] != 'notset' && strcmp($this->envItems[$key]['current'], $item['r']) < 0) {
                $this->envItems[$key]['status'] = 0;
                if ($key == 'php'){
                    $this->addError('msg', Yii::t("front_common", "install_setup1_less_mini_config", array("{key}"=>$this->toString($key))) .'<a target="_blank" href="' . $this->getUrl($key) . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>');
                }
            }
        }
    }

    /**
     * 检测必要函数是否存在
     *
     * @since 1.0.4
     */
    private function funExist() {
        // 必要函数
        // 其中mast为是否必须开启标志1，为必须开启的扩展，0为不需要开启的扩展
        $this->funcItems = array(
        // 必须开启
            'zlib'      => array('mast'=>1, 'current'  => Yii::t("front_common", "install_setup1_need_open")),
            'zip'       => array('mast'=>1, 'current'  => Yii::t("front_common", "install_setup1_need_open")), 
            'mbstring'  => array('mast'=>1, 'current' => Yii::t("front_common", "install_setup1_need_open")),

        // 建议开启
            'gd'        => array('mast'=>0, 'current' => Yii::t("front_common", "install_setup1_propose_open").'<a target="_blank" href="' . $this->getUrl('gdversion') . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>'),
            'exif'      => array('mast'=>0, 'current' => Yii::t("front_common", "install_setup1_propose_open").'<a target="_blank" href="' . $this->getUrl('exif') . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>'),
            'gettext'   => array('mast'=>0, 'current' => Yii::t("front_common", "install_setup1_propose_open").'<a target="_blank" href="' . $this->getUrl('gettext') . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>'),
            'ldap'      => array('mast'=>0, 'current' => Yii::t("front_common", "install_setup1_propose_open").'<a target="_blank" href="' . $this->getUrl('ldap') . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>'),
        );
        //根据不用的数据库类型,显示不同的pdo的数据库依赖
        if (empty($this->db)){
            $this->db = "mysql";
        }
        if ($this->db == "mysql"){
            $this->funcItems['mysql']       = array('mast'=>1, 'current' => Yii::t("front_common", "install_setup1_need_open"));
            $this->funcItems['pdo_mysql']   = array('mast'=>1, 'current' => Yii::t("front_common", "install_setup1_need_open"));
        }
        // 组装返回的数据
        // status为前端显示状态-1：警告状态，0： 错误状态， 1：通过状态
        // support为服务器是否支持状态 yes：支持， no： 不支持
        foreach($this->funcItems as $key => $item) {
            if(function_exists($key)) {
                $this->funcItems[$key]['status']    = 1;
                $this->funcItems[$key]['support']   = 'yes';
                $this->funcItems[$key]['current']   = Yii::t("front_common", "install_setup1_nothing");
                continue;
            } elseif (extension_loaded($key)){
                $this->funcItems[$key]['current']   = Yii::t("front_common", "install_setup1_nothing");
                $this->funcItems[$key]['status']    = 1;
                $this->funcItems[$key]['support']   = 'yes';
                continue;
            }

            $this->funcItems[$key]['status'] = -1;
            $this->funcItems[$key]['support'] = 'no';
            if ($this->funcItems[$key]['mast'] == 1){
                $this->funcItems[$key]['status'] = 0;
                $this->addError('msg', Yii::t("front_common", "install_setup1_load_failed", array("{key}"=>$key)).'<a target="_blank" href="' . $this->getUrl($key) . '">'.Yii::t("front_common", "install_setup1_get_help").'</a>');
            }
        }
    }
}