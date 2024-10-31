<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Admin' ) ) {
	class MCManager_Admin {

		/**
		 * Class properties.
		 *
		 * @var string
		 */
		public $mailchimp_api_key;

    public function __construct(){
					add_action( 'admin_init', array( $this, 'initialize' ) );
					add_action( 'admin_notices', array( $this, 'show_api_key_notice' ) );
					add_action( 'mcmanager_admin_dismiss_api_key_notice', array( $this, 'dismiss_api_key_notice' ) );

					add_action( 'admin_enqueue_scripts', array( $this, 'mc_manager_admin_scripts' ) );

    }

		/**
		* Initializes various stuff used in WP Admin
		*
		* - Registers settings
		*/
		public function initialize() {

			// listen for custom actions
			$this->listen_for_actions();
		}


		/**
		* Listen for `_mcmanager_action` requests
		*/
		public function listen_for_actions() {

			// listen for any action (if user is authorised)
			if( ! isset( $_REQUEST['_mcmanager_action'] ) ) {
				return false;
			}

			$action = (string) $_REQUEST['_mcmanager_action'];

			/**
			* Allows you to hook into requests containing `_mcmanager_action` => action name.
			*
			* The dynamic portion of the hook name, `$action`, refers to the action name.
			*
			* By the time this hook is fired, the user is already authorized. After processing all the registered hooks,
			* the request is redirected back to the referring URL.
			*
			* @since 3.0
			*/
			do_action( 'mcmanager_admin_' . $action );

			// redirect back to where we came from
			$redirect_url = ! empty( $_POST['_redirect_to'] ) ? $_POST['_redirect_to'] : remove_query_arg( '_mcmanager_action' );
			wp_redirect( $redirect_url );
			exit;
		}


		/**
		* Shows a notice when API key is not set.
		*/
		public function show_api_key_notice() {

			// don't show if dismissed
			if( get_transient( 'mcmanager_api_key_notice_dismissed' ) ) {
				return;
			}

			global $MCManager_Settings;
			$mailchimp_api_key = $MCManager_Settings->get_option('api_key', 'api_key');
			// don't show if api key is set already
			if( empty( $mailchimp_api_key ) ) {
				echo '<div class="mcmanager-notice notice notice-warning mcmanager-is-dismissible">';
				echo '<p>' . sprintf( __( 'Please <a href="%s">enter your MailChimp API key</a> on the settings page  to connect mailchimp server.', 'rm-mailchimp-manager' ), admin_url( 'admin.php?page=mcmanager_general_settings&tab=mcmanager_api_key_settings' ) ) . '</p>';
				echo '<form method="post"><input type="hidden" name="_mcmanager_action" value="dismiss_api_key_notice" /><button type="submit" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></form>';
				echo '</div>';
				return;
			}

			$mcmanager_lists  = get_option( 'mcmanager_lists_details' );
			if(is_array($mcmanager_lists) && (count($mcmanager_lists) > 0) && ($mailchimp_api_key != '')){
				return;
			}else{
				echo '<div class="mcmanager-notice notice notice-warning mcmanager-is-dismissible">';
				echo '<p>' . sprintf( __( 'Please <a href="%s">re-save your MailChimp API key</a> on the settings page  to sync mailchimp list.', 'rm-mailchimp-manager' ), admin_url( 'admin.php?page=mcmanager_general_settings&tab=mcmanager_api_key_settings' ) ) . '</p>';
				echo '<form method="post"><input type="hidden" name="_mcmanager_action" value="dismiss_api_key_notice" /><button type="submit" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></form>';
				echo '</div>';
				return;
			}

		}

		/**
		* Dismisses the API key notice for 1 week
		*/
		public function dismiss_api_key_notice() {
			set_transient( 'mcmanager_api_key_notice_dismissed', 1, 3600 * 24 * 7 );
		}




		/**
		* Backend style and script enqueue here
		*/
		public function mc_manager_admin_scripts($hook){
			global $pagenow, $post;

			wp_register_style( 'mcmanager-admin-style', MCManager_PLUGIN_URL. '/css/mcmgt-admin.css' );
			wp_register_script(  'mcmanager-admin-js', MCManager_PLUGIN_URL.'/js/mcmgt-backend.js' );

			if(
				((isset($post)) && ($post->post_type == 'mcmanager_form') && ($pagenow == 'post.php')) ||
				((isset($post)) && ($post->post_type == 'mcmanager_campaign') && ($pagenow == 'post.php')) ||
				((isset($post)) && ($post->post_type == 'mcmanager_template') && ($pagenow == 'post.php')) ||
				($hook == 'toplevel_page_mcmanager_dashboard')
			) {
				wp_enqueue_style( 'mcmanager-admin-style' );
				wp_enqueue_script('mcmanager-admin-js');


				/******Inline style manager*******/
				$dynamic_generated_css = '';

				//$dynamic_generated_css .= 'body{ color: #dadada; }';


				if($dynamic_generated_css != ''){
					wp_add_inline_style( 'mcmanager-admin-style', $dynamic_generated_css );
				}


			 }

		}



  }
}

global $MCManager_Admin;
$MCManager_Admin = new MCManager_Admin();
