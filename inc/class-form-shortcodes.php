<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Form_Shortcodes' ) ) {
	class MCManager_Form_Shortcodes {

		/**
     * Action hook used by the AJAX class.
     *
     * @var string
     */
    const ACTION = 'send-mcmanager-subscribe-form-data';

    /**
     * Action argument used by the nonce validating the AJAX request.
     *
     * @var string
     */
    const NONCE = '_form_nonce';

		/**
		 * Class properties.
		 *
		 * @var string
		 */
		public $mailchimp_api_key;

    public function __construct(){
				global $MCManager_Settings;
				$this->mailchimp_api_key = $MCManager_Settings->get_option('api_key', 'api_key');

				add_shortcode( 'mcmanager_form', array( $this, 'mcmanager_form_shortcode_func' ) );
				add_action( 'wp_enqueue_scripts', array( $this, 'mcmanager_form_front_style' ) );
		}

		/**
		 * Register the AJAX handler class with all the appropriate WordPress hooks.
		 */
		public static function register(){

			$handler = new self();

			add_action('wp_ajax_' . self::ACTION, array($handler, 'mcmanager_form_data_handle'));
			add_action('wp_ajax_nopriv_' . self::ACTION, array($handler, 'mcmanager_form_data_handle'));
			add_action('wp_loaded', array($handler, 'register_script'));


    }

		/**
		 * Register front style
		 */
		function mcmanager_form_front_style() {
			global $post;
			wp_register_style(  'mcmanager-from-style', MCManager_PLUGIN_URL.'/css/mcmgt-form.css' );
			if(( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'mcmanager_form') ) || is_singular('mcmanager_form') ) {
					if ( ! wp_script_is( 'jquery', 'done' ) ) {
							wp_enqueue_script( 'jquery' );
					}
					wp_enqueue_style('mcmanager-from-style');
			}

		}


		public function mcmanager_form_data_handle(){

			if (!check_ajax_referer( self::NONCE, 'security' )) {
				wp_send_json_success();
			}else{
					$form_id = intval($_POST['form_id']);
					$list_id = sanitize_text_field($_POST['list_id']);
					$list_id = sanitize_text_field($_POST['list_id']);
					$first_name = sanitize_text_field($_POST['first_name']);
					$last_name = sanitize_text_field($_POST['last_name']);
					$double_opt_in = sanitize_text_field($_POST['double_opt_in']);
					$email_address = sanitize_email($_POST['email']);

					$success_redirect = sanitize_text_field($_POST['success_redirect']);
					if($success_redirect != 0){
						$success_redirect = esc_url($success_redirect);
					}

					$is_success = TRUE;
					$message = '';
					$message_class = '';

					if(is_email( $email_address )){
						//Process subscribe list
						$merge_vars = array();
						if(($first_name != '') || ($last_name != '')){
								$merge_vars = array('FNAME'=> $first_name, 'LNAME' => $last_name);
						}

						if($double_opt_in == 'no'){
								$subs_status = 'subscribed';
						}else{
							$subs_status = 'pending';
						}

						$args = array(
							'status' => $subs_status,
							'email_address' => $email_address,
							'merge_fields' => $merge_vars,
						);
						$already_on_list = false;

						try {
							$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);

							$existing_member_data = $mcAPI3->get_list_member( $list_id, $email_address );

							if( $existing_member_data->status === 'subscribed' ) {
								$already_on_list = true;
								$is_success = FALSE;
								$message = esc_html(get_post_meta( $form_id, '_msg_already_subscribed', true ));
								$message_class = 'error';
							}
						}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
							$is_success = FALSE;
							$message = esc_html__( 'Already exist error!', 'rm-mailchimp-manager' );
							$message_class = 'error';
						}catch( MCManager_API_Exception $e ) {
							$is_success = FALSE;
							$message = esc_html__( 'Already exist error!', 'rm-mailchimp-manager' );
							$message_class = 'error';
						}

						//If not already added
						if(! $already_on_list){
							try {
								$member_data = $mcAPI3->add_list_member($list_id, $args);
								$is_success = TRUE;
								$message = esc_html(get_post_meta( $form_id, '_msg_subscription_success', true ));
								$message_class = 'success';
							}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
								$is_success = FALSE;
								$message = esc_html__( 'Add new member to list error!', 'rm-mailchimp-manager' );
								$message_class = 'error';
							}catch( MCManager_API_Exception $e ) {
								$is_success = FALSE;
								$message = esc_html__( 'Add new member to list error!', 'rm-mailchimp-manager' );
								$message_class = 'error';
							}
						}

					}else{
						$is_success = FALSE;
						$message = esc_html(get_post_meta( $form_id, '_msg_invalid_email', true ));
						$message_class = 'error';
					}


			    $response= array(
			        'form_id' => $form_id,
							'success_redirect' => $success_redirect,
							'email' => $email_address,
							'is_success' => $is_success,
							'message' => $message,
							'message_class'   => $message_class,
			    );
			    wp_send_json_success($response);
			}

			die();

		}

		/**
     * Register our AJAX JavaScript.
     */
    public function register_script()
    {
        wp_register_script('mcm_form_ajax', MCManager_PLUGIN_URL.'/js/mcmgt-signup-form.js');
        wp_localize_script('mcm_form_ajax', 'mcm_subs_ajax_data', $this->get_ajax_data());
    }

		/**
		 * Get the AJAX data that WordPress needs to output.
		 *
		 * @return array
		 */
		private function get_ajax_data()
		{
				return array(
						'subs_ajaxurl' => admin_url( 'admin-ajax.php' ),
						'loadingimg' => MCManager_PLUGIN_URL.'/img/loading.svg',
						'action' => self::ACTION,
						'nonce' => wp_create_nonce(MCManager_Form_Shortcodes::NONCE)
				);
		}

		/**
		 * Get the post ID sent by the AJAX request.
		 *
		 * @return int
		 */
		private function get_post_id()
		{
				$post_id = 0;

				if (isset($_POST['post_id'])) {
						$post_id = absint(filter_var($_POST['post_id'], FILTER_SANITIZE_NUMBER_INT));
				}

				return $post_id;
		}

		/**
		 * Sends a JSON response with the details of the given error.
		 *
		 * @param WP_Error $error
		 */
		private function send_error(WP_Error $error)
		{
				wp_send_json(array(
						'code' => $error->get_error_code(),
						'message' => $error->get_error_message()
				));
		}


		public function mcmanager_form_shortcode_func($atts, $content = null) {
			extract(shortcode_atts(array(
				'id' => '',
			), $atts));
			ob_start();
			$form_id = intval($id);
			wp_enqueue_style('mcmanager-from-style');
			wp_enqueue_script('mcm_form_ajax');
			?>
			<form id="mcmanager-form-<?php echo $form_id; ?>" class="mcmanager-form mcmanager-form-<?php echo $form_id; ?>" method="post" data-form_id="<?php echo $form_id; ?>" data-form_name="<?php echo get_the_title($form_id); ?>">
				<div class="mcmanager-form-fields">
						<?php echo mcmanager_kses(get_post_meta( $form_id, '_mc_form_editor', true )); ?>
						<p class="error required-field-missing"><?php echo esc_html(get_post_meta( $form_id, '_msg_required_field_missing', true )); ?></p>
						<input type="hidden" name="mc_list_id" class="mc_list_id" value="<?php echo esc_html(get_post_meta( $form_id, '_mailchimp_list_id', true )); ?>" />
						<input type="hidden" name="mc_success_redirect" class="mc_success_redirect" value="<?php echo esc_html(get_post_meta( $form_id, '_redirect_url', true )); ?>" />
						<input type="hidden" name="mc_form_id" class="mc_form_id" value="<?php echo $form_id; ?>" />
						<input type="hidden" name="mc_double_opt_in" class="mc_double_opt_in" value="<?php echo esc_html(get_post_meta( $form_id, '_double_opt_in', true )); ?>" />
						<div class="mcmanager-form-response"></div>
						<!-- mcmanager-form-response -->
				</div><!-- mcmanager-form-fields -->
			</form>
			<?php
			$mc_subs_form = ob_get_contents();
			ob_end_clean();
			return $mc_subs_form;

		}

  }
}

MCManager_Form_Shortcodes::register();
