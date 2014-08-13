<?php
/**
 * Miniyun api服务空间检查过滤器
 *
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MActionFilter extends MApplicationComponent implements MIController
{
    public $action = NULL;
    /**
     * 
     * 过滤器入口
     * @param string $uri
     */
    public function invoke ($uri = null)
    {
        // 转换动作
        switch ($this->action) {
            case "files":
                if (@$_SERVER["REQUEST_METHOD"] === "GET") {
                    return;
                } else {
                    $this->action = "files_post";
                }
                break;
            case "fileops":
                $this->action = $this->handleGetAction($uri);
                if ($this->action == "delete") {
                    return;
                }
                break;
            case "files_sec":
            case "restore":
            case "files_put":
                break;
            default: // 默认不进行空间检查
                return;
        }
        
        // 获取用户信息
        $user      = MUserManager::getInstance()->getCurrentUser();
        $user_id   = $user["user_id"];
        $space     = $user["space"];
        $usedSpace = $user["usedSpace"];
        // 预测空间是否超出限制,新接口中size作为文件大小参数判断
        switch ($this->action) {
            case "files_put":
            case "filse_post":
                $usedSpace += isset($_REQUEST['size']) ? $_REQUEST['size'] : 0;
                break;
            default:
                ;
                break;
        }
        // 空间检查
        if ($usedSpace >= $space) {
            throw new MFileopsException(Yii::t('api',"User is over storage quota."), 
            MConst::HTTP_CODE_507);
        }
    }
    /**
     * 
     * 解析url
     * @param string $uri
     * @throws MFileopsException
     */
    private function handleGetAction ($uri)
    {
        $fileops_controller = null;
        $parts = array_slice(explode('/', $uri), 2);
        if (count($parts) < 1) {
            throw new MFileopsException(
            Yii::t('api','{class} do not call an action', 
            array('{class}' => get_class($this))));
        }
        $parts = $parts[0];
        if ($pos = strpos($parts, '?')) {
            $parts = substr($parts, 0, $pos);
        }
        return $parts;
    }
}