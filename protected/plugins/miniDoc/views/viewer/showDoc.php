<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="zh-CN">
<head>
    <?php
        $t = YII_DEBUG ? time() : Yii::app()->params['app']['version'];
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo CHtml::encode(Yii::app()->params["app"]["name"]); ?> - <?php echo CHtml::encode($model->name); ?></title>
    <link href="<?php echo $this->module->assetsUrl?>/css/doc-viewer.css?t=<?php echo($t)?>" media="screen" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" href="<?php echo $this->module->assetsUrl?>/css/convert-document.css?t=<?php echo($t)?>" type="text/css"/>
    <script type='text/javascript' src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/jquery.js?t=<?php echo($t)?>'></script>
    <script type="text/javascript" src="<?php echo $this->module->assetsUrl?>/js/doc-viewer.js?t=<?php echo($t)?>" charset="utf-8"></script>
    <script type="text/javascript">

        var data = {name: "<?php echo $model->name?>",
                    type: "<?php echo $model->type?>",
                    hash: "<?php echo $model->hash?>"};
    </script>
</head>
<body>
<input type='hidden' id='content-url' value="<?php echo $model->contentUrl;?>">
<div id="canvas" style="display:none"></div>
<div class="footbtn">
    <span class="foot-right-btn">
        <a id="breakobj"></a>
        <span id="numobj"></span>
        <a id="text-num" class="text-num"></a>
        <a id="reduce-btn" class="foot-right-btn-icon reduce-btn"></a>
        <div id="md-btn-bj">
            <a id="foot-right-btn-bg"></a>
            <a id="md-btn" class="foot-right-btn-icon md-btn"></a>
        </div>
        <a id="amplify-btn" class="foot-right-btn-icon amplify-btn"></a>
    </span>
</div>
<script type="text/javascript" src="<?php echo $this->module->assetsUrl?>/js/zoom.js?v=<?php echo $this->module->getVersion()?>" charset="utf-8"></script>
</body>
</html>
