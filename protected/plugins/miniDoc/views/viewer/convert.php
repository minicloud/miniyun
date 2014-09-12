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
<title><?php echo CHtml::encode(Yii::app()->params["app"]["name"]); ?> - <?php echo CHtml::encode($this->pageTitle); ?></title>
<?php echo Yii::app()->params['app']['bootstrap'] ?>
<link href="<?php echo $this->module->assetsUrl?>/css/doc-viewer.css?t=<?php echo($t)?>" media="screen" rel="stylesheet" type="text/css"/>
<?php echo Yii::app()->params['app']['bootstrapJs'] ?>
<script type='text/javascript' src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/jquery.js?t=<?php echo($t)?>'></script>
<script type="text/javascript" src="<?php echo $this->module->assetsUrl?>/js/doc-convert.js?t=<?php echo($t)?>" charset="utf-8"></script>
<script type="text/javascript" src="<?php echo $this->createUrl('i18N/index')?>?t=<?php echo($t)?>" charset="utf-8"></script>
<script type="text/javascript">
var data = {
    callback: "<?php echo  $this->createAbsoluteUrl('/miniDoc/push')?>",
    name: "<?php echo $model->name?>",
    type: "<?php echo $model->type?>",
    hash: "<?php echo $model->hash?>"};
</script>
</head>
<body>
<?php
if(property_exists($model,"viewUrl")){
?>
<input id="view-url" type="hidden" value="<?php echo $model->viewUrl?>" />
<?php
}
?>
<input id="mini-doc-server-id" type="hidden" value=""/>
<input id="convert-type" type="hidden" value="<?php echo $model->type?>"/>
<input id="sub-url" type="hidden" value="" />
<input id="no-server-url" type="hidden" value="<?php echo $this->createUrl('noServerVal'); ?>" />
<!--//canvas 用于zip/rar文件的转换-->
<div id="canvas" style="display:none">
</div>
<div id="mask" style="display:none;">
     <div class="desc-box">
         <div class="desc" id="loading-msg"></div>
         <div class="progress progress-striped active">
             <div id="progress" class="progress-bar"  role="progressbar" aria-valuenow="1" aria-valuemin="0" aria-valuemax="100" style="width: 1%;"> </div>
         </div>
     </div>

</div>

</body>
</html>
