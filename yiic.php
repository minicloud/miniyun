<?php
date_default_timezone_set("PRC");
$yiic=dirname(__FILE__).'/yii/framework/yiic.php';
$config=dirname(__FILE__).'/protected/config/main.php';
require_once($yiic);
Yii::createConsoleApplication($config);