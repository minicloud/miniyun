<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo NAME_ZH; ?> &rsaquo; <?php echo Yii::t("front_common", "install_title")?></title>
<link rel="shortcut icon" href="<?php echo(Yii::app()->params['app']['host'].Yii::app()->params['app']['logoSmall']);?>" />
<link rel="stylesheet" href="<?php echo MiniHttp::getMiniHost()?>static/css/install.css?v=<?php echo(APP_VERSION)?>" type="text/css" />
<style>
.errorSummary {
	border: 2px solid #C00;
	padding: 7px 7px 12px 7px;
	margin: 0 0 20px 0;
	background: #FEE;
	font-size: 0.9em;
}
.errorMessage {color: red;font-size: 0.9em;}
.errorSummary p {margin: 0;padding: 5px;}
.errorSummary ul {margin: 0;padding: 0 0 0 20px;}
p{text-align:left;line-height:30px;margin-left:150px;}
ul li{text-align:left;line-height:20px;margin-left:10px;}
</style>
</head>
<body>
   <h1 id="logo"><img alt="Miniyun" src="<?php echo Yii::app()->params['app']['host'].Yii::app()->params['app']['logo'];?>" /></h1>
<?php echo $content; ?>
</body>
</html>

