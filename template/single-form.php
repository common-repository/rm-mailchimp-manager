<?php require_once('header.php'); ?>
<div class="mcmanager-form-wrapper">
<?php do_action('before-mcmanager-form'); ?>
<?php
  $mc_form_id = get_the_ID();
  echo do_shortcode('[mcmanager_form id="'.$mc_form_id.'"]');
?>
<?php do_action('after-mcmanager-form'); ?>
</div>
<?php require_once('footer.php'); ?>
