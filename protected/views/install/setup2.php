<?php $name = 'en' == Yii::app()->getLanguage() ? NAME_EN: NAME_ZH;?>
<?php $form=$this->beginWidget('CActiveForm', array("id"=>"form")); ?>
<?php echo $form->errorSummary($model); ?>
<?php if (disk_free_space($model->base) < 1*1024*1024*1024){?>
<div class="warn"><?php echo Yii::t("front_common", "install_setup2_disk_tip")?></div>
<?php } ?>
<div class="main">
<p><font color="#2ED2EB"><?php echo Yii::t("front_common", "install_setup2_db", array("{db}"=>"Mysql"))?></font>:  <?php echo Yii::t("front_common", "install_setup2_title")?></p>
<table class="form-table">
    <tr>
        <th scope="row"><?php echo $form->labelEx($model,'dbName'); ?></th>
        <td><?php echo $form->textField($model,'dbName',array('size'=>25,'maxlength'=>45,'class'=>'')); ?></td>
        <td><?php echo Yii::t("front_common", "install_setup2_db_name_prompt", array("{app_name}"=>$name))?></td>
    </tr>
    <tr>
        <th scope="row"><label><?php echo $form->labelEx($model,'userName'); ?></label></th>
        <td><?php echo $form->textField($model,'userName',array('size'=>25,'maxlength'=>45,'class'=>'')); ?></td>
        <td><?php echo Yii::t("front_common", "install_setup2_db_user_prompt")?></td>
    </tr>
    <tr>
        <th scope="row"><label><?php echo $form->labelEx($model,'password'); ?></label></th>
        <td><?php echo $form->textField($model,'password',array('size'=>25,'maxlength'=>45,'class'=>'')); ?></td>
        <td><?php echo Yii::t("front_common", "install_setup2_db_password_prompt")?></td>
    </tr>
    <tr>
        <th scope="row"><label><?php echo $form->labelEx($model,'dbHost'); ?></label></th>
        <td><?php echo $form->textField($model,'dbHost',array('size'=>12,'maxlength'=>45,'class'=>'')); ?>
            <label><?php echo $form->labelEx($model,'dbPort'); ?></label>
            <?php echo $form->textField($model,'dbPort',array('size'=>3,'maxlength'=>45,'class'=>'')); ?>
        </td>
        <td><?php echo Yii::t("front_common", "install_setup2_db_host_prompt")?></td>
    </tr>
    <tr>
        <th scope="row"><label><?php echo $form->labelEx($model,'tablePrefix'); ?></label></th>
        <td><?php echo $form->textField($model,'tablePrefix',array('size'=>25,'maxlength'=>45,'class'=>'')); ?></td>
        <td><?php echo Yii::t("front_common", "install_setup2_table_prefix_prompt", array("{app_name}"=>$name))?></td>
    </tr>
    <tr>
        <th scope="row"><label><?php echo $form->labelEx($model,'base'); ?></label></th>
        <td><?php echo $form->textField($model,'base',array('size'=>25,'maxlength'=>255,'class'=>'')); ?></td>
        <td><?php echo Yii::t("front_common", "install_setup2_upload_path_prompt")?></td>
    </tr>
</table>
<br>
<a style="width:150px;margin-left:250px;cursor:pointer;" href="#" onclick="javascript:document.getElementById('form').submit()" class="btn_white" ><span class="b1"><span class="b2"><?php echo Yii::t("front_common", "install_setup2_button")?></span></span></a>
<br>
<br>
<?php $this->endWidget(); ?>
</div>
