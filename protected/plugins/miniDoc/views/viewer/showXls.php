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
    <link href="<?php echo $this->module->assetsUrl ?>/css/xls-viewer.css?t=<?php echo($t) ?>" media="screen"
          rel="stylesheet" type="text/css"/>
</head>
<body>
<input type='hidden' id='hash' value="<?php echo $model->hash; ?>">
<input type='hidden' id='content-url' value="<?php echo $model->contentUrl; ?>">
<input type="hidden" id="gif-url" value = "<?php echo $this->module->assetsUrl ?>/images/xls-loading.gif"/>
<div id="main-box" class="main-box">
<iframe id="content-box" class="content-box">
</iframe>
</div>
<div id="footer-bar" class="foot-bar">
    <div id="paging-bar" class="paging-bar">
        <a id="first-page" class="icon first-page"></a>
        <a id="previous-page" class="icon previous-page"></a>
        <a id="next-page" class="icon next-page"></a>
        <a id="last-page" class="icon last-page"></a>
    </div>
    <div id="title-bar" class="title-bar"></div>
    <div class="foot-right-btn">
        <div id="grid-line" class="grid-line">
            <input type="checkbox" class="checkbox-btn" id="checkbox-btn"/>
            <span class="text-content"><?php echo Yii::t('MiniDocModule.I18N', 'showGrid') ?></span>
        </div>
        <div id="zoom-bar" class="zoom-bar">
            <div id="split-line"></div>
            <div id="percentage-bar" class="text-content"></div>
            <a id="reduce-btn" class="foot-right-btn-icon reduce-btn"></a>

            <div id="md-btn-bj">
                <a id="foot-right-btn-bg"></a>
                <a id="md-btn" class="foot-right-btn-icon md-btn"></a>
            </div>
            <a id="amplify-btn" class="foot-right-btn-icon amplify-btn"></a>
        </div>
    </div>
</div>
<script type='text/javascript' src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/jquery.js?t=<?php echo($t)?>'></script>
<script type="text/javascript" src="<?php echo $this->module->assetsUrl ?>/js/xls-viewer.js?t=<?php echo($t) ?>"
        charset="utf-8"></script>
</body>