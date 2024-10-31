<?php

/*
Plugin Name: MailChimp Manager
Plugin URI: https://mailchimpmanager.com/
Description: MailChimp Manager plugin manage subscriber, list, campaigns, campaign templates from wordpress admin. Add your site multiple subscription form in various way to get more and more subscribers.
Author: MCManager Team
Version: 1.0.2
Author URI: https://rmweblab.com/
Copyright: © 2018 - 2018 RM Web Lab.
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: rm-mailchimp-manager
Domain Path: /languages
*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !function_exists( 'rmmcm_fs' ) ) {
    function rmmcm_fs()
    {
        global  $rmmcm_fs ;
        
        if ( !isset( $rmmcm_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $rmmcm_fs = fs_dynamic_init( array(
                'id'              => '2692',
                'slug'            => 'rm-mailchimp-manager',
                'type'            => 'plugin',
                'public_key'      => 'pk_dec2f96df9521d82775a83c0a8f5a',
                'is_premium'      => false,
                'is_premium_only' => false,
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'menu'            => array(
                'slug' => 'mcmanager_dashboard',
            ),
                'is_live'         => true,
            ) );
        }
        
        return $rmmcm_fs;
    }

}
if ( !class_exists( 'RMMCManager_MailchimpManager' ) ) {
    /**
     * Main RMMCManager_MailchimpManager clas set up for us
     */
    class RMMCManager_MailchimpManager
    {
        /**
         * Constructor
         */
        public function __construct()
        {
            define( 'MCManager_VERSION', '1.0.1' );
            define( 'MCManager_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
            define( 'MCManager_MAIN_FILE', __FILE__ );
            define( 'MCManager_BASE_FOLDER', dirname( __FILE__ ) );
            define( 'MCManager_FRONT_URL', home_url( '/' ) );
            define( 'MCManager_FOLDER_URL', plugins_url( '/', __FILE__ ) );
            // Actions
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
            add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
        }
        
        /**
         * Init localisations and hook
         */
        public function init()
        {
            // Init Freemius.
            rmmcm_fs();
            // Signal that SDK was initiated.
            do_action( 'rmmcm_fs_loaded' );
            require_once MCManager_BASE_FOLDER . '/inc/attachments.php';
            // Localisation
            load_plugin_textdomain( 'rm-mailchimp-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }
        
        /**
         * Add relevant links to plugins page
         * @param  array $links
         * @return array
         */
        public function plugin_action_links( $links )
        {
            $plugin_links = array( '<a href="' . admin_url( 'admin.php?page=mcmanager_general_settings' ) . '">' . esc_html__( 'Settings', 'rm-mailchimp-manager' ) . '</a>' );
            return array_merge( $plugin_links, $links );
        }
        
        /**
         * Install default data
         */
        static function mailchimp_manager_plugin_install()
        {
            require_once 'inc/default-settings.php';
        }
        
        /**
         * Uninstall default data
         */
        static function mailchimp_manager_plugin_uninstall()
        {
            require_once 'inc/uninstall-default-settings.php';
        }
    
    }
}
new RMMCManager_MailchimpManager();
register_activation_hook( __FILE__, array( 'RMMCManager_MailchimpManager', 'mailchimp_manager_plugin_install' ) );
register_deactivation_hook( __FILE__, array( 'RMMCManager_MailchimpManager', 'mailchimp_manager_plugin_uninstall' ) );