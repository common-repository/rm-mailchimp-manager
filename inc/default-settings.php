<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}
if(!empty(get_option('mc_api_key_data'))){
  return;
}
$general_data = array(
  'form_slug' => 'mc-form'
);
update_option( 'mc_general_data', $general_data);

$campaign_data = array(
  'campaign_slug' => 'mc-campaign',
  'campaign_adv_day' => 7
);
update_option( 'mc_campaign_data', $campaign_data);

update_option( 'mc_api_key_data', '');
