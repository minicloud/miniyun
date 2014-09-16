<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<!DOCTYPE html>
<html>
<head>
    <?php
        $t = YII_DEBUG ? time() : Yii::app()->params['app']['version'];
    ?>
    <title><?php echo $model->name ?></title>
    <meta charset="utf-8"/>
    <link href="<?php echo $this->module->assetsUrl?>/css/ppt-viewer.css?t=<?php echo($t)?>" media="screen" rel="stylesheet" type="text/css"/>
    <script type='text/javascript' src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/jquery.js?t=<?php echo($t)?>'></script>
</head>
<body>
<input type='hidden' id='hash' value="<?php echo $model->hash; ?>">
<input type='hidden' id='content-url' value="<?php echo $model->contentUrl;?>">
<div id="left-image-box" class="left-image-box">
</div>
<div id="right-image-box" class="right-image-box">
</div>
<script type="text/javascript" src="<?php echo $this->module->assetsUrl?>/js/ppt-viewer.js?t=<?php echo($t)?>" charset="utf-8"></script>
</body>

