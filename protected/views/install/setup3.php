<?php $name = 'en' == Yii::app()->getLanguage() ? NAME_EN: NAME_ZH;?>
<br>
<div class="main">
<p><?php echo Yii::t("front_common", "install_setup3_title_1", array("{app_name}"=>$name))?></p>
<p><?php echo Yii::t("front_common", "install_setup3_title_2")?></p>
<br>
<?php $form=$this->beginWidget('CActiveForm', array("id"=>"form")); ?>
<?php echo $form->errorSummary($model); ?>
<table class="form-table">
    <tr>
        <th scope="row"><?php echo $form->labelEx($model,'userName'); ?></th>
        <td><?php echo $form->textField($model,'userName',array('size'=>20,'maxlength'=>20,'class'=>'','disabled'=>true)); ?></td>
        <td><p><?php echo Yii::t("front_common", "install_setup3_name_prompt")?></p></td>
    </tr>
    <?php
    $this->widget('CMiniyunPasswdInput', array(
         "model"=>$model,
         "passDesc"=>false,//显示密码描述信息
         "form"=>$form
    )); ?>
    <tr>
        <th scope="row"><label for="admin_email"><?php echo $form->labelEx($model,'email'); ?></label></th>
        <td><?php echo $form->textField($model,'email',array('class'=>'')); ?></td>
        <td><p><?php echo Yii::t("front_common", "install_setup3_email_prompt")?></p></td>
    </tr>
</table>
<br>
<a style="width:150px;margin-left:250px;cursor:pointer;" href="#" onclick="javascript:document.getElementById('form').submit()" class="btn_white" ><span class="b1"><span class="b2"><?php echo Yii::t("front_common", "install_setup3_button")?></span></span></a>
<br>
<br>
<?php $this->endWidget(); ?>
</div>