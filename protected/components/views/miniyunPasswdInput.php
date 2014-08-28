<?php
//miniyunPasswdInput用来检测用户输入账号与密码强度
?>
<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/static/js/jquery.js'></script>
<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/static/js/password-strength-meter.js'></script>
<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/static/js/user-profile.js'></script>
<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/static/js/l10n.js'></script>
<tr class="form-field form-required">
    <th scope="row"><?php echo $form->labelEx($model,'password'); ?></th>
    <td><?php echo $form->passwordField($model,'password',array("id"=>"pass1","class"=>"input")); ?></td>
    <?php
    if(isset($passDesc)) {
        ?>
    <td><span class="description"><?php echo $passDesc;?></span></td>
    <?php }?>
</tr>
<tr class="form-field">
    <th scope="row"><?php echo $form->labelEx($model,'passwordConfirm'); ?></th>
    <td><?php echo $form->passwordField($model,'passwordConfirm',array("id"=>"pass2","class"=>"input")); ?></td>
    <td></td>
</tr>
<tr>
    <td></td>
    <td>
    <div id="pass-strength-result" style="display: block;"><?php echo Yii::t('front_common', 'password_strength_assessment');?></div>
    <td><?php echo Yii::t('front_common', 'password_description',array('{count}'=>5));?>    </td>
</tr>
<script type='text/javascript'>
/* <![CDATA[ */
var commonL10n = {
    warnDelete: '<?php echo Yii::t('front_common', 'warn_delete')?>',
};
try{convertEntities(commonL10n);}catch(e){};
var wpAjax = {
    noPerm: <?php echo Yii::t('front_common', 'no_permission')?>,
    broken: <?php echo Yii::t('front_common', 'broken')?>
};
try{convertEntities(wpAjax);}catch(e){};
var pwsL10n = {
    empty: "<?php echo Yii::t('front_common', 'password_strength_assessment')?>",
    short: "<?php echo Yii::t('front_common', 'password_strength_weak')?>",
    bad: "<?php echo Yii::t('front_common', 'password_strength_bad')?>",
    good: "<?php echo Yii::t('front_common', 'password_strength_good')?>",
    strong: "<?php echo Yii::t('front_common', 'password_strength_strong')?>",
    mismatch: "<?php echo Yii::t('front_common', 'password_strength_mismatch')?>"
};
try{convertEntities(pwsL10n);}catch(e){};
/* ]]> */
</script>
