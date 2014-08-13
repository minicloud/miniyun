<?php
/**
 * Miniyun request_token服务主要入口地址
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MOauth2Controller extends MApplicationComponent implements MIController
{
    /**
     * 控制器执行主逻辑函数
     */
    public function invoke($uri=null)
    {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();

        $path = explode('?', $uri);
        $parts = array_slice(explode('/', $path[0]), 2);

        if ($parts[0] === "authorize") {
            $oauth = new PDOOAuth2();
            
            if ($_POST) {
              $oauth->finishClientAuthorization($_POST["accept"] == "Yep", $_POST);
            }
            
            $auth_params = $oauth->getAuthorizeParams();
            
            $inputs = "";
            foreach ($auth_params as $k => $v) { 
              $inputs = $inputs.'<input type="hidden" name="'.$k.'" value="'.$v. '" />';
            }
            
            $content =
            '<html>'.
              '<head>Authorize</head>'.
              '<body>'.
                '<form method="post" action="http://web.miniyun.cn/miniyun_oauth2/api.php/1/oauth2/authorize">'.
                  $inputs.
                  'Do you authorize the app to do its thing?'.
                  '<p>'.
                   ' <input type="submit" name="accept" value="Yep" />'.
                    '<input type="submit" name="accept" value="Nope" />'.
                 ' </p>'.
                '</form>'.
              '</body>'.
           ' </html>';
          echo $content;
        }
        elseif ($parts[0] === "token") {

            $oauth = new PDOOAuth2();
            $token = $oauth->grantAccessToken();
            #添加登陆日志
            $deviceId = $oauth->getVariable("device_id");
            MiniLog::getInstance()->createLogin($deviceId);
            #返回site_id，便于与cloud.miniyun.cn通信
            $token["site_id"] = MiniSiteUtils::getSiteID();
            echo(json_encode($token));
        }
    }

}