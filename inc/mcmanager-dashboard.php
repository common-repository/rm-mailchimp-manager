<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap">
<h1><?php echo esc_html__( 'MailChimp Manager Dashboard', 'rm-mailchimp-manager' ); ?></h1>

  <ul class="mcm-dashboard-menu">
      <li class="mcm-item-settings"><a class="button button-primary button-large" href="admin.php?page=mcmanager_general_settings"><?php echo esc_html__( 'Settings', 'rm-mailchimp-manager' ); ?></a></li>
      <li class="mcm-item-form"><a class="button button-primary button-large" href="edit.php?post_type=mcmanager_form"><?php echo esc_html__( 'Form Manager', 'rm-mailchimp-manager' ); ?></a></li>
      <li class="mcm-item-campaign"><a class="button button-primary button-large" href="edit.php?post_type=mcmanager_campaign"><?php echo esc_html__( 'Campaign Manager', 'rm-mailchimp-manager' ); ?></a></li>
      <li class="mcm-item-template"><a class="button button-primary button-large" href="edit.php?post_type=mcmanager_template"><?php echo esc_html__( 'Template Manager', 'rm-mailchimp-manager' ); ?></a></li>
  </ul>

<br /><br />
<h3><?php echo esc_html__( 'Latest campaigns', 'rm-mailchimp-manager' ); ?></h3>
<?php
$args = array(
  'post_type'  => array('mcmanager_campaign'),
  'post_status' => array( 'publish', 'future' ),
  'posts_per_page' => 10,
);
$campaign_query = new WP_Query( $args );
if ( $campaign_query->have_posts() ) {
  $mcmanager_lists  = get_option( 'mcmanager_lists' );
  echo '<table class="wp-list-table widefat">';
  echo '<thead><tr><th>Campaign Title</th><th>List</th><th>MailChimp Status</th><th>Date</th></tr></thead>';
  while ( $campaign_query->have_posts() ) {
    $campaign_query->the_post();
    $campaign_post_id = get_the_ID();
    $campaign_post_title = get_the_title();
    $campaign_post_date = get_the_date('Y-m-d', $campaign_post_id);

    $mcmanager_list_name = '';
    $list_id = get_post_meta($campaign_post_id, '_mailchimp_list_id', true);
    if(($list_id != '') && isset($mcmanager_lists[$list_id])){
      $mcmanager_list_name = $mcmanager_lists[$list_id];
    }

    $campaign_status = '';
    $mc_campaign_status = get_post_meta($campaign_post_id, '_mc_campaign_status', true);
    if(($mc_campaign_status != '')){
      $campaign_status = $mc_campaign_status;
    }

    echo '<tr>';
    echo '<td>';
    echo $campaign_post_title;
    echo '</td>';
    echo '<td>';
    echo $mcmanager_list_name;
    echo '</td>';
    echo '<td>';
    echo $campaign_status;
    echo '</td>';
    echo '<td>';
    echo $campaign_post_date;
    echo '</td>';
    echo '</tr>';
  }
  echo '</table>';
}

?>


</div>
