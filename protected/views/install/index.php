<?php $name = 'en' == Yii::app()->getLanguage() ? NAME_EN: NAME_ZH;?>
<div class='main'>
<p><?php echo Yii::t("front_common", "install_index_title", array("{app_name}"=>$name))?></p>
<p><?php echo Yii::t("front_common", "install_index_prompt_1")?></p>
<p><?php echo Yii::t("front_common", "install_index_prompt_2")?></p>
<br>
<a style="width:150px;margin-left:150px;cursor:pointer;" href="<?php echo Yii::app()->request->baseUrl; ?>/index.php/install/setup1" class="btn_white" ><span class="b1"><span class="b2"><?php echo Yii::t("front_common", "install_index_button")?></span></span></a>
<br>
</div>