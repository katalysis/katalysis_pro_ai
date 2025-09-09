<?php
defined('C5_EXECUTE') or die("Access Denied.");

$form = \Core::make('helper/form');
$color = \Core::make('helper/form/color');
?>

<div class="form-group">
    <?php echo $form->label('primaryColor', t('Primary Color')); ?>
    <?php echo $color->output('primaryColor', $primaryColor ?? '#7749F8'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Main color for buttons, links, and primary elements'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('primaryDarkColor', t('Primary Dark Color')); ?>
    <?php echo $color->output('primaryDarkColor', $primaryDarkColor ?? '#4D2DA5'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Darker shade for hover effects and secondary elements'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('secondaryColor', t('Secondary Color')); ?>
    <?php echo $color->output('secondaryColor', $secondaryColor ?? '#6c757d'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Color for secondary text and less prominent elements'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('successColor', t('Success Color')); ?>
    <?php echo $color->output('successColor', $successColor ?? '#28a745'); ?>
    <small class="form-text text-muted  "><?php echo t('Color for success states and positive feedback'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('lightColor', t('Light Color')); ?>
    <?php echo $color->output('lightColor', $lightColor ?? '#ffffff'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Light color for backgrounds and contrast'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('darkColor', t('Dark Color')); ?>
    <?php echo $color->output('darkColor', $darkColor ?? '#333333'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Dark color for text and dark elements'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('borderColor', t('Border Color')); ?>
    <?php echo $color->output('borderColor', $borderColor ?? '#e9ecef'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Color for borders and dividers'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('shadowColor', t('Shadow Color')); ?>
    <?php echo $color->output('shadowColor', $shadowColor ?? 'rgba(0,0,0,0.1)'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Color for shadows and depth effects'); ?></small>
</div>

<div class="form-group">
    <?php echo $form->label('hoverBgColor', t('Hover Background Color')); ?>
    <?php echo $color->output('hoverBgColor', $hoverBgColor ?? 'rgba(255,255,255,0.2)'); ?>
    <small class="form-text text-muted d-block"><?php echo t('Background color for hover effects'); ?></small>
</div>