<?php    
    // 引入配置文件
    include './config.php';
    // 引入CAS Client项目
    include './includes/CAS.php'; 

    // 初始化CAS客户端参数
    phpCAS::client(CAS_VERSION_2_0,CAS_SERVER_HOSTNAME,CAS_SERVER_PORT,CAS_SERVER_APP_NAME,true); 
    // 不使用SSL服务校验 
    phpCAS::setNoCasServerValidation();   
    // 这里会检测服务器端的退出的通知，就能实现php和其他语言平台间同步登出了
    phpCAS::handleLogoutRequests();
    // 判断是否已经访问CAS验证，true-获取用户信息，false-访问CAS验证
    if(phpCAS::checkAuthentication()){  
        /**
        *获取用户的唯一标识信息
        *由UIA的配置不同可分为两种：
        *(1)学生：学号；教工：身份证号
        *(2)学生：学号；教工：教工号
        **/
        $userid=phpCAS::getUser();

        // 获取登录用户的扩展信息  
        // 用户姓名
        $name = phpCAS::getAttribute("comsys_name");
        // 电话号码
        $phone = phpCAS::getAttribute("comsys_phone");
        // 民族
        $national = phpCAS::getAttribute("comsys_national");
        // 性别
        $genders = phpCAS::getAttribute("comsys_genders");
        // 邮件
        $email = phpCAS::getAttribute("comsys_email");
        // 其它职位
        $other_post = phpCAS::getAttribute("comsys_other_post");
        // 教育度程
        $educationals = phpCAS::getAttribute("comsys_educational");
        // 教工号
        $teaching_number = phpCAS::getAttribute("comsys_teaching_number");
        // 学生号
        $studentNumber = phpCAS::getAttribute("comsys_student_number");
        // 获取用户类型   1-学生  2-教工
        $type = phpCAS::getAttribute("comsys_usertype");
        /**
        *  角色数组
        *  key:ROLECNNAME;value:角色中文名称
        *  key:ROLEIDENTIFY;value:角色代码
        **/
        $role = toArray(phpCAS::getAttribute("comsys_role"));
        /**
        *  部门数组
        *  key:DEPARTMENTNAME;value:部门中文名称
        *  key:DEPARTMENTIDENTIFY;value:部门代码
        **/
        $department = toArray(phpCAS::getAttribute("comsys_department"));
        /**
        *  岗位数组
        *  key:POSTNAME;value:岗位中文名称
        *key:POSTIDENTIFY;value:岗位代码
        **/
        $post = toArray(phpCAS::getAttribute("comsys_post"));
        // 学生院系名称
        $faculetName = phpCAS::getAttribute("comsys_faculetyname");
        // 学生院系代码
        $faculetCode = phpCAS::getAttribute("comsys_faculetycode");
        // 学生年级名称
        $gradName = phpCAS::getAttribute("comsys_gradename");
        // 学生年级代码
        $gradCode = phpCAS::getAttribute("comsys_gradecode");
        // 学生专业名称
        $disciplinName = phpCAS::getAttribute("comsys_disciplinename");
        // 学生专业代码
        $disciplinCode = phpCAS::getAttribute("comsys_disciplinecode");
        // 学生班级名称
        $className = phpCAS::getAttribute("comsys_classname");
        // 学生班级代码
        $classCode = phpCAS::getAttribute("comsys_classcode");

        // 获取所有扩展参数信息
        $attribute = phpCAS::getAttributes();
        //初始化YII
        date_default_timezone_set("PRC");
        defined('YII_DEBUG') or define('YII_DEBUG', false);
        @ini_set('display_errors', '1');
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        // change the following paths if necessary
        $config=dirname(__FILE__).'/../protected/config/main.php';
        $yii=dirname(__FILE__).'/../yii/framework/yii.php';

        // specify how many levels of call stack should be shown in each log message
        defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',8);
        // 客户端请求
        defined('CLIENT_REQUEST_API') or define('CLIENT_REQUEST_API', TRUE);
        require_once($yii);
        Yii::createWebApplication($config);

        header('Access-Control-Allow-Origin: http://static.miniyun.cn');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Methods: GET');
        MiniAppParam::getInstance()->load();//初始化APP静态数据 
        MiniPlugin::getInstance()->load();//加载插件
        function genAccessToken() {
            return md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
        }
        function setAccessToken($user) {
             $oauth_token = genAccessToken();
             $client_id = "JsQCsjF3yr7KACyT";
             $device = DeviceManager::getDevice($user["id"], MConst::DEVICE_WEB, "web", $_SERVER['HTTP_USER_AGENT']);
             MiniToken::getInstance()->create($oauth_token,$client_id,$device['id'],time() + OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME,NULL);
             setcookie("accessToken", $oauth_token,0,'/');
             //Yii::app()->session["accessToken"] = $oauth_token;
             //echo(Yii::app()->session["accessToken"]);exit;
        }
        //添加用户信息到数据库中
        $userData = array(); 
        $userData["name"] = $userid;
        $metas = array();
        $metas['nick'] = $name;
        $metas['email'] = $email;
        $userData["extend"] = $metas;
        $user = MiniUser::getInstance()->create($userData);
        setAccessToken($user);
        $userIdentity = new UserIdentity(NULL, NULL);
        $userIdentity->loadUser($user);
        header('Location: /index.php/box/index#/');
    }else{  
        // 访问CAS的验证
        phpCAS::forceAuthentication();  
    }  

    // 退出登录
    if(isset($_REQUEST['a'])){
        if($_REQUEST['a'] == "logout"){
            $param=array("service"=>LOGOUT_ADDRESS);  
            phpCAS::logout($param);
        }
    }

    /**
    * 根据传入的字符串，返回用户扩展信息数组。
    * $result 返回数据格式（array(1) { [0]=> array(2) { ["ROLECNNAME"]=> string(6) "学生" ["ROLEIDENTIFY"]=> string(12) "ROLE_STUDENT" } }）
    * $data 待处理数据格式（ROLECNNAME:学生,ROLEIDENTIFY:ROLE_STUDENT）
    ***/
    function toArray($data){
      $result = array();
      if(isset($data)){
        $arrays = explode("-",$data);
        foreach ($arrays as $temp) {
              $_array = explode(",",$temp);
              $arrayName = array();
              if(count($_array)>0){
                foreach ($_array as $_temp) {
                    $_array1 = explode(":",$_temp);  
                    if(count($_array1)>0&&isset($_array1[0])&&isset($_array1[1])){
                      $arrayName[$_array1[0]]  = $_array1[1];
                    }
                }
                if(count($arrayName)>0){
                  array_push($result,  $arrayName);
                }
              } 
          }
      }
      return $result;
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>测试单点登录</title>
    <style type="text/css">
        table td{
          padding-left: 10px;
          height:30px;
        }
        body{
          font-family: "微软雅黑";
        }
    </style>
</head>
<body>
<table border="1" style="width:80%;margin-left:auto;margin-right:auto;margin-top:40px;margin-bottom:40px;">
    <tr>
        <th style="width:20%;">参数名称：</th>
        <th style="widht:80%;">参数值</th>
    </tr>
    <tr>
        <td>登陆唯一ID：</td><td><?php echo $userid;?></td>
    </tr>
    <tr>
         <td>用户名称：</td><td><?php echo $name;?></td>
    </tr>
    <tr>
        <td>教工号：</td><td><?php echo $teaching_number;?></td>
    </tr>
    <tr>
        <td>学生号：</td><td><?php echo $studentNumber;?></td>
    </tr>
    <tr>
        <td>用户类型：</td><td><?php echo $type == 1 ? "学生":"教工";?></td>
    </tr>
    <tr>
        <td>用户电话：</td><td><?php echo $phone;?></td>
    </tr>
    <tr>
        <td>用户民族：</td><td><?php echo $national;?></td>
    </tr>
    <tr>
        <td>用户性别：</td><td><?php echo $genders;?></td>
    </tr>

    <tr>
        <td>用户邮箱：</td><td><?php echo $email;?></td>
    </tr>
    <tr>
        <td>其他职位：</td><td><?php echo $other_post;?></td>
    </tr>
    <tr>
        <td>教育程度：</td><td><?php echo $educationals;?></td>
    </tr>
    

    <tr>
        <td>用户角色：</td><td><?php var_dump($role);?></td>
    </tr>
    <tr>
        <td>用户部门：</td><td><?php var_dump($department);?></td>
    </tr>
    <tr>
        <td>用户岗位：</td><td><?php var_dump($post);?></td>
    </tr>
    <tr>
        <td>院系信息：</td><td><?php echo $faculetName;?></td>
    </tr>
    <tr>
        <td>年级信息：</td><td><?php echo $gradName;?></td>
    </tr>
     <tr>
        <td>专业信息：</td><td><?php echo $disciplinName;?></td>
    </tr>
    <tr>
        <td>班级信息：</td><td><?php echo $className;?></td>
    </tr>
    <tr>
        <td>获取所有参数信息：</td><td><?php var_dump($attribute);?></td>
    </tr>
    <tr>
        <td colspan="2"><a href="http://192.168.4.116/?a=logout">退出登录</a></td>
    </tr>
</table>

</body>
</html>