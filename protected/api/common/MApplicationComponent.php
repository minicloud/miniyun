<?php
/**
 * 应用程序模块
 *   从MIApplicationComponent继承，用于判断是否完成初始化逻辑
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

abstract class MApplicationComponent extends MComponent implements MIApplicationComponent{
	
    private $_initialized = false;
    public static $scene  = NULL;
    /**
     * 构造函数初始化异常处理信息
     */
    public function  __construct()
    {
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));
    }
    
    
    /**
     * 初始化应用程序模块
     * 
     */
    public function init()
    {
        // 注册自定义的错误和异常处理函数
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));
        $this->_initialized = true;
    }

    /**
     * 判断程序模块是否已经完成了初始化逻辑
     * 
     * @return boolean 返回true表明对应模块已经完成初始化，否则表明出现异常
     */
    public function isInitialized()
    {
        return $this->_initialized;
    }
    
    /**
     * 
     * Enter description here ...
     * @param integer $action
     */
    public function setAction($action) {
        self::$scene = $action;
    }
    /**
     * 处理错误逻辑的主入口地址
     * 默认显示错误信息，基类可以重载此函数来自定义处理错误逻辑
     * @return boolean 返回true表明对应模块已经完成初始化，否则表明出现异常
     */
    public function handleError($code, $message, $file, $line)
    {
        if ($code & error_reporting()) {
            return $this->displayError($code, $message, $file, $line);
        }
    }
    
    /**
     * 判断程序模块是否已经完成了初始化逻辑
     * 
     * @return boolean 返回true表明对应模块已经完成初始化，否则表明出现异常
     */
    public function handleException($exception)
    {
        $code = $exception->getCode();
        if (empty($code)){
            $code = 403;
        }
        header("HTTP/1.1 {$code} ". $exception->getMessage());
        return $this->displayException($exception);
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
        Yii::log($content, CLogger::LEVEL_ERROR,"miniyun.api");
        
        // 处理不同端，不同返回值
        if ( MUserManager::getInstance()->isWeb() === true)
        {
            $message = CUtils::transtalte(self::$scene, $exception->getMessage(), $exception->getCode());
            header("HTTP/1.1 200 OK");
            $result = array();
            $result["state"]      = false;
            $result["code"]       = 0;
            $result["message"]    = Yii::t('api_message', $message);
            $result["msg"]        = Yii::t('api_message', $message);
            $result["msg_code"]   = "0";
            $result["data"]       = array("d" => false);
            echo json_encode($result);
        }
    }
    
    /**
     * 组装错误日志到数据库的信息
     */
    private function assembleMemo()
    {
        $memo = array();
        foreach ($_REQUEST as $key=>$value)
        {
            $memo[$key] = $value;
        }
        return $memo;
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
        Yii::log($content, CLogger::LEVEL_ERROR,"miniyun.api");
        
        if (MUserManager::getInstance()->isWeb() === true) {
            $message = CUtils::transtalte(self::$scene, '', 500);
            header("HTTP/1.1 200 OK");
            $result = array();
            $result["state"]      = false;
            $result["code"]       = 0;
            $result["message"]    = Yii::t('api_message', $message);
            $result["msg"]        = Yii::t('api_message', $message);
            $result["msg_code"]   = "0";
            $result["data"]       = array("d" => false);
        } else {
            MiniUtil::sendResponse(500,$content);
        }
        
    }
    


function handleShutdown() {
        $error = error_get_last();
        if($error !== NULL){
            $info = "[SHUTDOWN] file:".$error['file']." | ln:".$error['line']." | msg:".$error['message'] .PHP_EOL;
            echo ($info);
        }
        else{
            echo ("SHUTDOWN");
        }
    }
}