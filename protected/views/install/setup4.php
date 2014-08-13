<?php $name = 'en' == Yii::app()->getLanguage() ? NAME_EN: NAME_ZH;?>
<br>
<div class="main">
<p><?php echo Yii::t("front_common", "install_setup4_title", array("{app_name}"=>$name))?></p>
    <a style="width:120px;margin-left:150px;cursor:pointer;" href="<?php echo Yii::app()->createUrl("site/login");?>" onclick="" class="btn_white" ><span class="b1"><span class="b2"><?php echo Yii::t("front_common", "install_setup4_button")?></span></span></a>
<br>
</div>