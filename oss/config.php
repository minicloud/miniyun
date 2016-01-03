<?php
    //CAS Server 主机名 
	define('CAS_SERVER_HOSTNAME', '192.168.50.100'); 
	//CAS Server 端口号 
	define('CAS_SERVER_PORT', 8080);
	//CAS Server应用名 
	define('CAS_SERVER_APP_NAME', '/sso');
	//退出登录后返回地址 '.CAS_SERVER_HOSTNAME.':'.CAS_SERVER_PORT.'/logout.action
	define('LOGOUT_ADDRESS', 'http://192.168.52.11');
?>