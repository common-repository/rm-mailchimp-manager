<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( MCManager_BASE_FOLDER . '/inc/mailchimp/class-exception.php');
require_once( MCManager_BASE_FOLDER . '/inc/mailchimp/class-connection-exception.php');
require_once( MCManager_BASE_FOLDER . '/inc/mailchimp/class-resource-not-found-exception.php');
require_once( MCManager_BASE_FOLDER . '/inc/mailchimp/class-api-v3-client.php');
require_once( MCManager_BASE_FOLDER . '/inc/mailchimp/class-api-v3.php');

require_once( MCManager_BASE_FOLDER . '/inc/mcmanager-functions.php');
require_once( MCManager_BASE_FOLDER . '/inc/class-mcadmin.php');
require_once( MCManager_BASE_FOLDER . '/inc/class-mcmanager-dashboard.php');
require_once( MCManager_BASE_FOLDER . '/inc/class-mcmanager-options.php');
require_once( MCManager_BASE_FOLDER . '/inc/class-form-manager.php');
require_once( MCManager_BASE_FOLDER . '/inc/class-campaign-manager.php');
require_once( MCManager_BASE_FOLDER . '/inc/class-template-manager.php');

require_once( MCManager_BASE_FOLDER . '/inc/class-form-shortcodes.php');
require_once( MCManager_BASE_FOLDER . '/inc/class-campaign-shortcodes.php');
