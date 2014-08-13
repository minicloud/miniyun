<?php $is_error = false;?>
<?php $form=$this->beginWidget('CActiveForm', array("id"=>"form")); ?>
<div class="main">
<?php echo $form->errorSummary($model); ?>
<!-- 环境检查信息　 -->
<h2 class="title" style="text-align: left"><label><?php echo Yii::t("front_common", "install_setup1_check_environment")?></label></h2>
<table class="tb" style="margin:20px 0 20px 25px;width:99%;">
    <tr>
	    <th><label><?php echo Yii::t("front_common", "install_setup1_item")?></label></th>
    	<th class="padleft"><label><?php echo Yii::t("front_common", "install_setup1_require_config", array("{app_name}"=>NAME_ZH))?></label></th>
    	<th class="padleft"><label><?php echo Yii::t("front_common", "install_setup1_optimal", array("{app_name}"=>NAME_ZH))?></label></th>
    	<th class="padleft" style="text-align: center;"><label><?php echo Yii::t("front_common", "install_setup1_current_server")?></label></th>
    </tr>
    <?php foreach($model->envItems as $key=>$value) {?>
        <tr>
        <td style="text-align: left"><?php echo $model->toString($key); ?></td>
        <td class="padleft"><label><?php echo $model->toString($value['r']); ?></label></td>
        <td class="padleft"><?php echo $model->toString($value['b']); ?></td>
        <?php if ($value['status'] == 0) {?>
            <?php $is_error = true;?>
            <td class="nw pdleft1"><font color="red"><?php echo $model->toString($value['current'])?></font></td>
        <?php } elseif ($value['status'] == -1) {?>
            <td class="ww pdleft1"><?php echo $model->toString($value['current'])?></td>
        <?php } else {?>
            <td class="w pdleft1"><?php echo $model->toString($value['current'])?></td>
        <?php } ?>
        </tr>
    <?php }?>
</table>
<!-- 目录文件夹权限 -->
<h2 class="title" style="text-align: left"><label><?php echo Yii::t("front_common", "install_setup1_file_check")?></label></h2>
<table class="tb" style="margin:20px 0 20px 25px;width:99%;">
    <tr>
	    <th><label><?php echo Yii::t("front_common", "install_setup1_file")?></label></th>
    	<th class="padleft"><label><?php echo Yii::t("front_common", "install_setup1_require_state", array("{app_name}"=>NAME_ZH))?></label></th>
    	<th class="padleft"><label><?php echo Yii::t("front_common", "install_setup1_current_state", array("{app_name}"=>NAME_ZH))?></label></th>
    </tr>
    <?php foreach($model->dirItems as $key=>$value) {?>
        <tr>
        <td style="text-align: left"><?php echo $key; ?></td>
        <td class="w pdleft1"><?php echo $model->toString('+r+w'); ?></td>
        <?php if ($value['status'] == 0) {?>
            <?php $is_error = true;?>
            <td class="nw pdleft1"><font color="red"><?php echo $model->toString($value['current'])?></font></td>
        <?php } else {?>
            <td class="w pdleft1"><?php echo $model->toString($value['current'])?></td>
        <?php } ?>
        </tr>
    <?php }?>
</table>
<!-- 目录文件夹权限 -->
<h2 class="title" style="text-align: left"><label><?php echo Yii::t("front_common", "install_setup1_PHP_extension_protocol")?></label></h2>
<table class="tb" style="margin:20px 0 20px 25px;width:99%;">
    <tr>
	    <th><label><?php echo Yii::t("front_common", "install_setup1_extension_protocol_name")?></label></th>
    	<th class="padleft"><label><?php echo Yii::t("front_common", "install_setup1_check_result")?></label></th>
    	<?php $extra_class = 'style="text-align: left;padding-left:100px;"';?>
    	<th class="padleft"  <?php echo $extra_class;?>><label><?php echo Yii::t("front_common", "install_setup1_propose")?></label></th>
    </tr>
    <?php foreach($model->funcItems as $key=>$value) {?>
        <tr>
        <td style="text-align: left"><?php echo 'php_' . $key; ?></td>
        <?php if ($value['status'] == -1) {?>
            <td class="ww pdleft1"><font color="orange"><?php echo $model->toString($value['support'])?></font></td>
            <td class="pdleft1" <?php echo $extra_class;?>><font color="orange"><?php echo $model->toString($value['current'])?></font></td>
        <?php } else if ($value['status'] == 0){?>
            <?php $is_error = true;?>
            <td class="nw pdleft1"><font color="red"><?php echo $model->toString($value['support'])?></font></td>
            <td class="pdleft1" <?php echo $extra_class;?>><font color="red"><?php echo $model->toString($value['current'])?></font></td>
        <?php } else { ?>
            <td class="w pdleft1"><?php echo $model->toString($value['support']); ?></td>
            <td class="pdleft1" <?php echo $extra_class;?>><?php echo $model->toString($value['current'])?></td>
        <?php } ?>
        </tr>
    <?php }?>
</table>
<hr>
<?php if($is_error == false): ?>
    <a style="width:150px;margin-left:250px;cursor:pointer;" href="#" onclick="javascript:document.getElementById('form').submit()" class="btn_white" ><span class="b1"><span class="b2"><?php echo Yii::t("front_common", "install_setup1_button")?></span></span></a>
    <?php $this->endWidget(); ?>
<?php else: ?>
    <a style="width:150px;margin-left:250px;color: red;font-weight: bolder;cursor:pointer;" href="#" onclick="javascript:document.getElementById('form').submit()" class="btn_white" ><span class="b1"><span class="b2"><?php echo Yii::t("front_common", "install_setup1_button_error")?></span></span></a>
    <?php $this->endWidget(); ?>
<?php endif;?>
<br>
</div>

