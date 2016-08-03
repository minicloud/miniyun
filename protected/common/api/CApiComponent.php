<?php
/** 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
abstract class CApiComponent extends CComponent {
    private $_initialized = false;
    public $result = array();
    public $_userId; // 用户id
    public $_deviceId; // 设备id
    public $_deviceName; // 设备id
    public $_parentId; // 父目录id
    
    public $totalSpace;
    public $usedSpace;
    /**
     * 构造函数初始化异常处理信息
     */
    public function __construct() {
        set_exception_handler ( array ($this, 'handleException' ) );
    }
    
    /**
     * 初始化应用程序模块
     * 
     */
    public function init() {
        //
        // 注册自定义的错误和异常处理函数
        //
        set_exception_handler ( array ($this, 'handleException' ) );
        set_error_handler ( array ($this, 'handleError' ) );
        
        $this->_initialized = true;
    }
    
    /**
     * 处理错误逻辑的主入口地址
     * 默认显示错误信息，基类可以重载此函数来自定义处理错误逻辑
     * @return boolean 返回true表明对应模块已经完成初始化，否则表明出现异常
     */
    public function handleError($code, $message, $file, $line) {
        return $this->displayError ( $code, $message, $file, $line );
    }
    
    /**
     * 自定义异常处理
     * 可以重写此函数
     * 
     */
    public function handleException($exception) {
        $code = $exception->getCode ();
        if (empty ( $code )) {
            $code = 403;
        }
        header ( "HTTP/1.1 {$code} " . $exception->getMessage () );
        return $this->displayException ( $exception );
    }
    
    /**
     * 显示捕获的错误信息
     * 
     * @param integer $code 错误代码
     * @param string $message 消息
     * @param string $file 文件名
     * @param string $line 错误行号
     */
    public function displayError($code, $message, $file, $line)
    {
        $content = "";
        if (YII_DEBUG)
        {
            $content = "<h1>PHP Error [$code]</h1>\n";
            $content .= "<p>$message ($file:$line)</p>\n";
            $content .= '<pre>';

            //
            // 获取trace信息，并忽略前面三个
            //
            $trace = debug_backtrace();
            if (count($trace) > 3)
                $trace = array_slice($trace,3);

            foreach ($trace as $i=>$t)
            {
                if (!isset($t['file']))
                    $t['file']='unknown';
                if (!isset($t['line']))
                    $t['line']=0;
                if (!isset($t['function']))
                    $t['function']='unknown';
                $content .= "#$i {$t['file']}({$t['line']}): ";

                if (isset($t['object']) && is_object($t['object']))
                    $content .= get_class($t['object']).'->';
                $content .= "{$t['function']}()\n";
            }

            $content .= '</pre>';
        }
        else
        {
            $content = "<h1>PHP Error [$code]</h1>\n";
            $content .= "<p>$message</p>\n";
        }
        Yii::log($content, CLogger::LEVEL_ERROR);
    }
    
    /**
     * 显示捕获的exception
     * 
     * @param Exception $exception 需要显示的exception
     */
    public function displayException($exception)
    {
        $content = "";
        if (YII_DEBUG)
        {
            $content = '<h1>'.get_class($exception)."</h1>\n";
            $content .= '<p>'.$exception->getMessage();
            $content .= ' ('.$exception->getFile().':'.$exception->getLine().')</p>';
            $content .= '<pre>'.$exception->getTraceAsString().'</pre>';
        }
        else
        {
            $content = '<h1>'.get_class($exception)."</h1>\n";
            $content .= '<p>'.$exception->getMessage().'</p>';
        }
        Yii::log($content, CLogger::LEVEL_ERROR);
    }
    
    /**
     * 组装返回值，状态标志
     * 
     * @param bool $status
     * @param int $code
     * @param string $message
     */
    public function handleResult($status, $code, $message) {
        $this->result["state"]   = $status;
        $this->result["code"]    = $code;
        $this->result["msg"]     = $message;
    }
    
    /**
     * 
     * 输出结果，退出进程
     */
    public function handleEnd() {
        echo CJSON::encode ( $this->result );
        Yii::app ()->end ();
    }
    
    /**
     * 
     * 获取用户空间信息
     */
    public function handleSpace() {
    	$user             = MiniUser2::getInstance()->getUser2($this->_userId);
        $spaceInfo = MiniUser::getInstance()->getSpaceInfo($user);
        $this->totalSpace = $spaceInfo["space"];
        $this->usedSpace  = $spaceInfo["usedSpace"];
        
        if ($this->totalSpace <= $this->usedSpace) {
            $this->result["msg"]     = "空间不足";
            $this->result["message"] = "空间不足";
            throw new ApiException("File name is NULL.");
        }
    }
}
?>