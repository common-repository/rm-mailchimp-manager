<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mcmanager_debug($data, $exit = false){
	echo "<pre>";
	print_r($data);
	echo "<pre>";
	if($exit){
		die();
	}
}

function mcmanager_kses($data, $allowed_protocols = ''){

	$allowed_tags = array(
	    'a' => array(
	        'href' => array(),
					'title' => array(),
	        'target' => array()
	    ),
			'label' => array(
	        'for' => array()
	    ),
	    'br' => array(),
	    'em' => array(),
	    'strong' => array(),
			'p' => array(
				'class' => array(),
				'id' => array()
			),
			'div' => array(
				'class' => array(),
				'id' => array()
			),
			'input' => array(
				'class' => array(),
				'name' => array(),
				'placeholder' => array(),
				'required' => array(),
				'type' => array(),
				'id' => array(),
				'value' => array()
			),
	);

	$data = wp_kses($data, $allowed_tags, $allowed_protocols);

	return $data;

}

//Get list name form local database
function mcmanager_get_mc_list_name($list_id){

	$mcmanager_lists  = get_option( 'mcmanager_lists' );

	if(isset($mcmanager_lists[$list_id])){
		return esc_html($mcmanager_lists[$list_id]);
	}

	return '';

}

function get_mcmanager_templates(){
	$templates = array();
	$args = array(
		'post_type'  => array('mcmanager_template'),
		'post_status' => 'publish',
		'orderby' => 'post_date',
		'order' => 'ASC',
		'posts_per_page' => -1,
	);
	$tempalte_query = new WP_Query( $args );
	if ( $tempalte_query->have_posts() ) {
		while ( $tempalte_query->have_posts() ) {
			$tempalte_query->the_post();
			$template_post_id = get_the_ID();
			$template_post_title = get_the_title();
			$templates[$template_post_id] = $template_post_title;
		}
	}
	wp_reset_postdata();

	return $templates;
}

function mcmanager_lists_drowpdown_options($selected_list, $all_disabled = FALSE){
		$output = '';
		$mcmanager_lists  = get_option( 'mcmanager_lists_details' );
		if(isset($mcmanager_lists)){
				$output .= '<option value="">'. esc_html__( 'Select List', 'rm-mailchimp-manager' ) .'</option>';
				foreach($mcmanager_lists as $list_id => $list_details){
					$disabled = '';
					if(($list_details['member_count'] == 0) || ($all_disabled)){
						$disabled = ' disabled="disabled" ';
					}
					$output .= '<option '.$disabled;
					$output .= selected( $list_id, $selected_list, FALSE );
					$output .= 'value="'.esc_attr($list_id).'">';
					$output .= esc_html($list_details['name']);
					$output .= ' ('.$list_details['member_count']. ')';
					$output .= '</option>';
				}
		}

		return $output;
}


function mcmanager_is_premium(){
	if ( rmmcm_fs()->is_plan('premium', true) ) {
		return true;
	}
	return false;
}

//Send test email
function mcmanager_test_email($user_email, $subject, $message){
	//process email template
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));
	@wp_mail( $user_email, $subject, $message, $headers );
}


add_action( 'admin_notices', 'mailchimp_manager_global_validation_admin_notice' );
function mailchimp_manager_global_validation_admin_notice(){
	if(isset($_GET['global_notice']) && ($_GET['global_notice'] =='yes')){
		//notice-error, notice-warning, notice-success, or notice-info.
		?>
		<div class="error notice-error">
		  <p><?php echo esc_html( 'You need premium version for more features.', 'rm-mailchimp-manager' ); ?></p>
		</div>
		<?php
	}

}
