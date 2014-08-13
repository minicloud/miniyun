<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/jquery.js'></script>
<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/password-strength-meter.js'></script>
<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/user-profile.js'></script>
<script
    type='text/javascript'
    src='<?php echo Yii::app()->request->baseUrl; ?>/statics/js/l10n.js'></script>
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

<div class="item">
  <?php echo $form->labelEx($model,'password', array('class' => 'label')) ?>
  <?php echo $form->passwordField($model,'password',array('id'=>'pass1'))?>
  <span id="pass-strength-result" class="password_strong"><?php echo Yii::t('front_common', 'password_strength_assessment')?></span> </div>
  <div class="tip">
    <p><?php if(!empty($passDesc)) echo $passDesc?></p>
</div>
<div class="item">
  <?php echo $form->labelEx($model,'passwordConfirm', array('class' => 'label')) ?>
  <?php echo $form->passwordField($model,'passwordConfirm',array('id'=>'pass2'))?>
</div>
<div class="tip">
    <p><?php echo Yii::t('front_common', 'password_description', array('{count}' =>'<span class="highlight_red">5</span>'))?></p>
</div>
