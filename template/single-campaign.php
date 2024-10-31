<?php require_once('header.php'); ?>
<div class="mcmanager-campaign-wrapper">
<?php do_action('before-mcmanager-campaign'); ?>
<?php
  $mc_campaign_id = get_the_ID();
  echo do_shortcode('[mcmanager_campaign id="'.$mc_campaign_id.'"]');
?>
<?php do_action('after-mcmanager-campaign'); ?>
</div>
<?php require_once('footer.php'); ?>
