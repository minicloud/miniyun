<?php
/**
 * Miniyun api服务主要入口地址
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MAPIController extends MApplicationComponent implements MIController{
	
    private $commonUri = null;
    private $user      = null;
    private $device    = null;
    public  static $namespace = "api";

    /**
     * 控制器执行主逻辑函数
     * @param null $uri
     * @throws MException
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null)
    {
    	//IP安全检查
    	do_action('ip_check',false);
        // 解析控制器中对应操作名称
        $urlManager = new MUrlManager();
        $urlArray = $urlManager->parseActionFromUrl();
        if ($urlArray === false){
            throw new MException(Yii::t('api','{class} do not call an action',
            array('{class}'=>get_class($this))));
        }
        $action          = $urlArray["action"];
        $this->commonUri = $urlArray["uri"];
        self::$namespace = "api.{$action}";
        // 进行程序执行之前首先进行oauth用户身份信息验证
        // 排除指定動作可以匿名訪問
        $canAnonymous = false;
        if ($action == "info" || $action == "report"){
            $canAnonymous = true;
        }
        if($action=="link"){
            $parts = explode("/", $this->commonUri);
            $subAction = $parts[2];
            if($subAction=="selected"){
                $canAnonymous = true;
            }
        }
        if($canAnonymous){
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: POST');
        }
        if (!$canAnonymous) {
            $filter = new MUserFilter();
            $filter->invoke($this->commonUri);
            // 过滤器，检查空间剩余
            $spaceFilter = new MActionFilter();
            $spaceFilter->action = $action;
            $spaceFilter->invoke($this->commonUri);
            // 修改在线用户状态
            $this->user   = MUserManager::getInstance()->getCurrentUser();
            $this->device = MUserManager::getInstance()->getCurrentDevice();
            //更新设备在线状态
            MiniOnlineDevice::getInstance()->setOnlineDeviceValue($this->user["id"],$this->user["appId"],$this->device["id"]);
        }
        // 执行插件中自定义的api接口
        do_action('api_interface',$action);
        $this->$action();
    }

    /**
     * 
     * 获取系统用户状态，实现自动填写帐号和密码， admin/admin
     */
    private function info() {
        $response = CUtils::apiInfo();
        $response = apply_filters("api_info_add", $response);
        echo json_encode($response);
    }
    /**
     * 用户登录验证入口
     */
    private function oauth2()
    {
        $oauthController = new MOauth2Controller();
        $path = explode('?', $this->commonUri);
        $parts = array_slice(explode('/', $path[0]), 1);
        if (count($parts) <= 1)
        {
            throw new MFileopsException(Yii::t('api','{class} do not call an action',
                                    array('{class}'=>get_class($this))));
        }
        $part = $parts[1];
        if ($pos = strpos($part, '?'))
        {
            $part = substr($part, 0, $pos);
        }
        self::$namespace     .= ".{$part}";
        $oauthController -> invoke($this->commonUri);
    }
    
     /* 解除设备绑定
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function device()
    {
        self::$namespace     .= ".unmount";
        $unmountController = new MUnmountController();
        $unmountController -> invoke();
    }

    /**
     * 获取用户基本信息入口
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function account()
    {
        $accountController = null;
        $path               = explode('?', $this->commonUri);
        $parts = array_slice(explode('/', $path[0]), 2);
        if ($parts[0] === "info") {
            self::$namespace    .= ".info";
            $accountController = new MAccountInfoController();
        }
        $accountController->invoke();
    }


    /**
     * 文件/夹操作入口
     *
     */
    private function fileops()
    {
        $fileOperatorController = null;
        $parts = array_slice(explode('/', $this->commonUri), 2);
        if (count($parts) < 1)
        {
            throw new MFileopsException(Yii::t('api','{class} do not call an action',
                                    array('{class}'=>get_class($this))));
        }
        $parts = $parts[0];
        if ($pos = strpos($parts, '?'))
        {
            $parts = substr($parts, 0, $pos);
        }
        if ($parts === "copy") {
            $fileOperatorController = new MCopyController();
        }
        elseif ($parts === "create_folder") {
            $fileOperatorController = new MCreateFolderController();
        }
        elseif ($parts === "delete") {
            $fileOperatorController = new MDeleteController();
        }
        elseif ($parts === "move") {
            $fileOperatorController = new MMoveController();
        }elseif ($parts === "send") {
            $fileOperatorController = new MSendController();
        }
        else {
            throw new MFileopsException(Yii::t('api','{class} do not call an action',
                                    array('{class}'=>get_class($this))));
        }
        self::$namespace     .= ".{$parts}";
        $fileOperatorController->invoke();
    }

    /**
     * 获取上传的参数
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function paramsdata()
    {
        $paramController = new MParamsController();
        $paramController->invoke($this->commonUri);
    }

    /**
     * 创建文件的元数据
     * //上传成功，写入元数据到数据库
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function create_file()
    {
        $createFile = new MCreateFileController();
        $createFile->invoke($this->commonUri);
    }

    /**
     * 处理获取元数据
     *
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function metadata()
    {
        $metadataController = new MMetadataController();
        $metadataController -> invoke($this->commonUri);
    }

    /**
     * 文件在data中操作入口
     * 
     * @return mixed $value 返回最终需要执行完的结果
     */
    private  function  files() {
        $filesController = null;
        // 根据get或者post执行调用不同的类
        if (@$_SERVER["REQUEST_METHOD"] === "POST") {
            $filesController = new MFilePostController();
            self::$namespace  .= ".post";
        }
        elseif (@$_SERVER["REQUEST_METHOD"] === "GET") {
            self::$namespace  .= ".get";
            $filesController = new MFileGetController();
        }
        $filesController->invoke($this->commonUri);
    }
    
    /**
     * 文件在data中操作入口
     * 
     * @return mixed $value 返回最终需要执行完的结果
     */
    private  function  files_put() {
        $filesController = new MFilePutController();
        $filesController->invoke($this->commonUri);
    }
    /**
     * 处理多媒体文件
     */
    private function link() {

        $service = new LinkService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 处理文件
     */
    private function file() {

        $service = new FileService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 处理好友
     */
    private function user() {

        $service = new UserService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 处理好友权限
     */
    private function privilege() {
        $service = new PrivilegeService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 获得说有图片信息
     */
    private function album() {
        $service = new AlbumService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    private function recycle() {
        $service = new RecycleService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }

    /**
     * 处理event
     */
    private function event() {

        $service = new EventService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 处理个人空间应用
     */
    private function profile() {

        $service = new ProfileService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 处理历史版本
     */
    private function history() {
        $service = new HistoryService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 处理文件版本恢复
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function restore()
    {
        $restoreController = new MRestoreController();
        $restoreController->invoke($this->commonUri);
    }
    
    /**
     * 处理文件/夹搜索
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function search()
    {
        $searchController = new MSearchController();
        $searchController->invoke($this->commonUri);
    }
    
    /**
     * 缩略图处理
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function thumbnails() {

        $thumbnails = new MThumbnailsController();
        $thumbnails->invoke($this->commonUri);
    }
    
    /**
     * 版本meta信息
     * @return mixed $value 返回最终需要执行完的结果
     */
    private function revisions() {
        $revisions = new MRevisionsController();
        $revisions->invoke($this->commonUri);
    }
    
    /**
     * 返回事件列表
     */
    private function events() {
        $events = null;
        $path = explode('?', $this->commonUri);
        $parts = array_slice(explode('/', $path[0]), 2);
        if ($parts[0] === "list") {
            self::$namespace    .= ".list";
            $events = new MDownloadEventController();
        }
        $events->invoke();
    }
    
    
    /**
     * 文件秒传接口
     */
    private function files_sec() {
        $filesController = new MFileSecondsController();
        $filesController->invoke($this->commonUri);
    }
    /**
 *
 * 标签操作接口
 * @since 1.0.7
 * @return mixed $value 返回基本信息 - json格式
 */
    private function tags() {
        $handle = new MTagsController();
        $handle->invoke($this->commonUri);
    }
    /**
     * 
     * 转存接口
     * @since 1.1.1
     * @return mixed $value 返回基本信息 - json格式
     */
    private function dump() {
        $dump = new MDumpController();
        $dump->invoke($this->commonUri);
    }
    /**
     * 群组
     */
    private function group() {

        $service = new GroupService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 部门
     */
    private function department() {

        $service = new FrontDepartmentService();
        $result = $service->invoke($this->commonUri);
        echo(json_encode($result));
    }
    /**
     * 自定义异常处理
     * @see common/MApplicationComponent::handleException()
     */
    public function handleException($exception)
    {
        $code = $exception->getCode();
        if (empty($code)){
            $code = 403;
        }
        
        if ($exception instanceof MAuthorizationException)
        {
            header("HTTP/1.1 ".$code." ". $exception->getMessage());
            echo $exception->getMessage();
        }

        return $this->displayException($exception);
    }
}