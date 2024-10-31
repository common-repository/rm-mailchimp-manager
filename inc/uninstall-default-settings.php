<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

global $MCManager_Settings;
$mcm_deactivation = '';
$mcm_deactivation = $MCManager_Settings->get_option('general', 'mcm_deactivation');

if($mcm_deactivation == 'on'){
  delete_option( 'mcmanager_lists' );
  delete_option( 'mcmanager_lists_details' );
  delete_option( 'mc_general_data' );
  delete_option( 'mc_campaign_data' );
  delete_option( 'mc_api_key_data' );
  mailchimp_manager_clear_all_generated_post_data();
}
function mailchimp_manager_clear_all_generated_post_data(){
	$args = array(
		'post_type'  => array('mcmanager_form', 'mcmanager_campaign', 'mcmanager_template'),
		'post_status' => 'any',
		'orderby' => 'post_date',
		'order' => 'ASC',
		'posts_per_page' => -1,
	);
	$mcm_query = new WP_Query( $args );
	if ( $mcm_query->have_posts() ) {
		while ( $mcm_query->have_posts() ) {
			$mcm_query->the_post();
			$mcm_post_id = get_the_ID();
      wp_delete_post( $mcm_post_id, true );
		}
	}
	wp_reset_postdata();
}
