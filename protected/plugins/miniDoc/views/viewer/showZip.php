<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<!doctype html>
<html lang="en" ng-app="zipViewApp">
<head>
    <?php
        $t = YII_DEBUG ? time() : Yii::app()->params['app']['version'];
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <?php echo(Yii::app()->params['app']['bootstrap']) ?>
    <link rel="stylesheet" href="<?php echo $this->module->assetsUrl?>/css/zip-viewer.css?t=<?php echo($t)?>"/>
    <script type='text/javascript' src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/jquery.js?t=<?php echo($t)?>'></script>
    <script type="text/javascript" src="<?php echo $this->module->assetsUrl?>/js/zip-viewer.js?t=<?php echo($t)?>" charset="utf-8"></script>
</head>
<body>
<input type="hidden" id="domain" value="<?php echo Yii::app()->request->baseUrl; ?>">
<input type="hidden" id="type" value="<?php echo($model->type) ?>">
<input type="hidden" id="host" value="<?php echo($model->host) ?>">
<input type="hidden" id="hash" value="<?php echo($model->hash) ?>">
<input type="hidden" id="parent-path" value="">
<input type="hidden" id="current-path" value="">

<div class="container-fluid">
    <div class="row-fluid">
        <div class="span2">
            <form class="form-inline" role="form" >
                <ul class="nav nav-pills" id="parent-nav">

                </ul>
            </form>
        </div>
        <div class="bs-example">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="tfiles-icon"></th>
                    <th class="tfiles-name"><?php echo(Yii::t('MiniDocModule.I18N', 'zipName'))?></th>
                    <th class="tfiles-size"><?php echo(Yii::t('MiniDocModule.I18N', 'zipSize'))?></th>
                    <th class="tfiles-date"><?php echo(Yii::t('MiniDocModule.I18N', 'zipTime'))?></th>
                    <th class="tfiles-handle"><?php echo(Yii::t('MiniDocModule.I18N', 'zipAction'))?></th>
                </tr>
                </thead>
                <tbody id="file-list">

                </tbody>
            </table>
        </div>
        <div class="form-group">
            <button class="btn btn-default" id="page-previous">
                <?php echo(Yii::t('MiniDocModule.I18N', 'zipPrevious'))?>
            </button>
            <span id="page-info"></span>
            <button class="btn btn-default" id="page-after">
                <?php echo(Yii::t('MiniDocModule.I18N', 'zipAfter'))?>
            </button>
        </div>
    </div>
</div>
</body>
</html>