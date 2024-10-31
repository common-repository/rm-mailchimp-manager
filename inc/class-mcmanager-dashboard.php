<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Dashboard' ) ) {
	class MCManager_Dashboard {

		/**
		 * Class properties.
		 *
		 * @var string
		 */
		public $mailchimp_api_key;

    public function __construct(){
          add_action( 'admin_menu', array( $this, 'mcmanager_plugin_admin_menu' ) );
    }


    /**
     * get Mailchimp API Key
     */
    public function get_api_key(){
      global $MCManager_Settings;
      return $MCManager_Settings->get_option('api_key', 'api_key');
    }

    /**
     * Add admin settings menu
     */
    public function mcmanager_plugin_admin_menu() {

			$icon_url = MCManager_FOLDER_URL .'img/mc-icon.png';

      add_menu_page( esc_html__( 'MailChimp Manager', 'rm-mailchimp-manager' ), esc_html__( 'RM MailChimp', 'rm-mailchimp-manager' ), 'manage_options', 'mcmanager_dashboard', array(	$this,	'mcmanager_mailchimp_manager_plugin_main_page'), $icon_url);

    }

    public function mcmanager_mailchimp_manager_plugin_main_page(){
      //Plugin settings
      $mailchimp_api_key = $this->get_api_key();

      require_once( MCManager_BASE_FOLDER . '/inc/mcmanager-dashboard.php');
    }



  }
}

global $MCManager_Dashboard;
$MCManager_Dashboard = new MCManager_Dashboard();
