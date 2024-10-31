<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Campaign' ) ) {
	class MCManager_Campaign {

		/**
		 * Class properties.
		 *
		 * @var string
		 */
		public $mailchimp_api_key;

    public function __construct(){
					global $MCManager_Settings;
					$this->mailchimp_api_key = $MCManager_Settings->get_option('api_key', 'api_key');

					add_action( 'init', array( $this, 'mcmanager_register_form_cpt' ) );
          add_action( 'admin_menu', array( $this, 'mcmanager_register_form_menu' ) );
					add_filter( 'parent_file', array( $this, 'campaign_menu_highlight' ) );
					add_action( 'admin_init', array( $this, 'mcmanager_campaign_sync_init' ) );
					add_action( 'media_buttons', array( $this, 'add_mcm_campaign_media_buttons' ) );

					add_action( 'add_meta_boxes_mcmanager_campaign', array( $this, 'mcmanager_campaign_meta_boxes' ) );
					add_action( 'save_post_mcmanager_campaign', array( $this, 'mcmanager_campaign_save_meta_box_data' ) );

					add_filter( 'manage_edit-mcmanager_campaign_columns', array( $this, 'mcmanager_campaign_columns' ) );
					add_action( 'manage_mcmanager_campaign_posts_custom_column', array( $this, 'mcmanager_campaign_custom_columns_content' ), 10, 2 );

					add_action( 'admin_notices', array( $this, 'mcm_campaign_validation_admin_notice' ) );

					add_action( 'single_template', array( $this, 'mcmanager_campaign_page_template' ) );
    }

		/**
		 * Register a Form post type.
		 *
		 * @link http://codex.wordpress.org/Function_Reference/register_post_type
		 */
		function mcmanager_register_form_cpt() {
			global $MCManager_Settings;
			$labels = array(
				'name'               => _x( 'Campaign', 'post type general name', 'rm-mailchimp-manager' ),
				'singular_name'      => _x( 'Campaign', 'post type singular name', 'rm-mailchimp-manager' ),
				'menu_name'          => _x( 'Campaigns', 'admin menu', 'rm-mailchimp-manager' ),
				'name_admin_bar'     => _x( 'Campaign', 'add new on admin bar', 'rm-mailchimp-manager' ),
				'add_new'            => _x( 'Add New', 'Campaign', 'rm-mailchimp-manager' ),
				'add_new_item'       => esc_html__( 'Add New Campaign', 'rm-mailchimp-manager' ),
				'new_item'           => esc_html__( 'New Campaign', 'rm-mailchimp-manager' ),
				'edit_item'          => esc_html__( 'Edit Campaign', 'rm-mailchimp-manager' ),
				'view_item'          => esc_html__( 'View Campaign', 'rm-mailchimp-manager' ),
				'all_items'          => esc_html__( 'All Campaigns', 'rm-mailchimp-manager' ),
				'search_items'       => esc_html__( 'Search Campaigns', 'rm-mailchimp-manager' ),
				'parent_item_colon'  => esc_html__( 'Parent Campaigns:', 'rm-mailchimp-manager' ),
				'not_found'          => esc_html__( 'No Campaigns found.', 'rm-mailchimp-manager' ),
				'not_found_in_trash' => esc_html__( 'No Campaigns found in Trash.', 'rm-mailchimp-manager' )
			);

			$campaign_slug = '';
			$campaign_slug = $MCManager_Settings->get_option('campaign', 'campaign_slug');
			if($campaign_slug == ''){
					$campaign_slug = 'mcmanager-campaign';
			}

			$args = array(
				'labels'             => $labels,
		    'description'        => esc_html__( 'Campaign Description.', 'rm-mailchimp-manager' ),
				'public'             => apply_filters( 'mcmanager_campaign_public', true ),
				'publicly_queryable' => apply_filters( 'mcmanager_campaign_public', true ),
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => $campaign_slug ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 100,
				'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes' )
			);

			register_post_type( 'mcmanager_campaign', $args );
		}



		public function mcmanager_campaign_columns( $columns ) {
			$columns = array(
				'cb' => '<input type="checkbox" />',
				'title' => __( 'Campaign Title' ),
				'mc_list' => __( 'Campaign List' ),
				'template' => __( 'Template' ),
				'mc_status' => __( 'MC Status' ),
				'date' => __( 'Date' )
			);

			return $columns;
		}

		public function mcmanager_campaign_custom_columns_content( $column, $post_id ) {
				$mcmanager_lists  = get_option( 'mcmanager_lists' );

				switch ( $column ) {

				case 'mc_list' :
					if ( (get_post_status ( $post_id ) == 'publish') || (get_post_status ( $post_id ) == 'future') ) {
						$list_id = get_post_meta($post_id, '_mailchimp_list_id', true);
						if(($list_id != '') && isset($mcmanager_lists[$list_id])){
							echo $mcmanager_lists[$list_id];
						}
					}
					break;
				case 'template' :
					if ( (get_post_status ( $post_id ) == 'publish') || (get_post_status ( $post_id ) == 'future') ) {
						$template_id = get_post_meta($post_id, '_template_id', true);
						if($template_id != ''){
							echo get_the_title($template_id);
						}
					}
					break;
				case 'mc_status' :
						if ( (get_post_status ( $post_id ) == 'publish') || (get_post_status ( $post_id ) == 'future') ) {
							$campaign_id = get_post_meta($post_id, '_mc_campaign_id', true);
							if($campaign_id != ''){
								$mc_status = $this->check_mc_campaign_status($campaign_id);
								update_post_meta($post_id, '_mc_campaign_status', $mc_status);
								echo $mc_status;
							}
						}
					break;

		    }
		}


		public function campaign_menu_highlight( $parent_file ){
			/* Get current screen */
			global $current_screen, $self;

	    if ( in_array( $current_screen->base, array( 'post', 'edit' ) ) && 'mcmanager_campaign' == $current_screen->post_type ) {
	        $parent_file = 'mcmanager_dashboard';
	    }

	    return $parent_file;

		}



		public function add_mcm_campaign_media_buttons(){
			global $post;
			if ( 'mcmanager_campaign' == get_post_type( $post ) ){
				echo '<a href="#mcmanager_campaign_preview_meta_box" id="go-to-campaign-preview" class="button button-primary btn-campaign-preview mcm-btn-beside-media">'.esc_html__( 'Campaign Preview', 'rm-mailchimp-manager' ).'</a>';
			}
		}


    /**
     * Register Admin Menu
     */
    public function mcmanager_register_form_menu() {
      add_submenu_page( 'mcmanager_dashboard', esc_html__( 'Campaign Manager', 'rm-mailchimp-manager' ), esc_html__( 'Campaigns', 'rm-mailchimp-manager' ), 'manage_options', 'edit.php?post_type=mcmanager_campaign', NULL);
    }


		public function mcmanager_campaign_sync_init(){
			if ( current_user_can( 'delete_posts' ) ){
				add_action( 'wp_trash_post', array( $this, 'delete_mc_campaign' ), 10 );
			}

			if ( current_user_can('edit_posts') ) {
				add_action( 'save_post', array( $this, 'sync_mc_campaign_server' ), 10 );
			}
		}


		/**
		 * Delete mailchimp campaign
		 *
		 * @param post_id $post The post object
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#create-post_campaigns
		 */
		public function delete_mc_campaign($post_id){
			// If this is just a revision, don't send the email.
			if ( wp_is_post_revision( $post_id ) ){
					return;
			}

			$post_type = get_post_type($post_id);
			if ( "mcmanager_campaign" != $post_type ){
				 return;
			}

			$campaign_id = get_post_meta($post_id, '_mc_campaign_id', true);
			if($campaign_id){
				if( $this->check_mc_campaign_exist( $campaign_id ) ) {
					try {
						$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);
						$mcAPI3->delete_campaign($campaign_id);
					}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
						 return FALSE;
					}catch( MCManager_API_Exception $e ) {
						 return FALSE;
					}

				}
			}



		}


		/**
		 * Update or create campaign
		 *
		 * @param post $post The post object
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#create-post_campaigns
		 */
		public function sync_mc_campaign_server($post_id){
				// If this is just a revision, don't send the email.
				if ( wp_is_post_revision( $post_id ) ){
						return;
				}

				$post_type = get_post_type($post_id);
				if ( "mcmanager_campaign" != $post_type ){
					 return;
				}

				//date_default_timezone_set(get_option('timezone_string'));
				// do some stuff
				//date_default_timezone_set('UTC');

				$campaign_id = get_post_meta($post_id, '_mc_campaign_id', true);

				if ( (get_post_status ( $post_id ) == 'publish') || (get_post_status ( $post_id ) == 'future') ) {

					$campaign_content = $this->get_campaign_content($post_id);

					if($campaign_id) {
						if( $this->check_mc_campaign_exist( $campaign_id ) ) {
							$this->update_mc_campaign_data( $post_id, $campaign_id, $campaign_content );
						} else {
							// create a new campaign
							$this->create_new_mc_campaign( $post_id, $campaign_content );
						}
					}else{
						// create a new campaign
						$this->create_new_mc_campaign( $post_id, $campaign_content );
					}
				}
		}

		/**
		 * Create campaign to mailchimp server
		 *
		 * @param post id and schedule date
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#create-post_campaigns
		 */
		function create_new_mc_campaign($post_id, $campaign_content, $schedule_date = '') {
			global $MCManager_Settings;
			$mc_list_id = $MCManager_Settings->get_option('campaign', 'campaign_list');

			//Reset default list using campaign list
			if(get_post_meta($post_id, '_mailchimp_list_id', true) != ''){
					$mc_list_id = get_post_meta($post_id, '_mailchimp_list_id', true);
			}

			$campaign_title = get_the_title($post_id);
			$mc_from_name = $MCManager_Settings->get_option('campaign', 'campaign_from_name');
			$mc_reply_to_email = $MCManager_Settings->get_option('campaign', 'campaign_reply_to');
			$mc_to_name = $MCManager_Settings->get_option('campaign', 'campaign_to_name');
			$campaign_time = $MCManager_Settings->get_option('campaign', 'campaign_time');

			$camp_options = array(
					'type' => 'regular',
					'recipients' => array('list_id' => $mc_list_id),
					'settings' => array(
						'subject_line' => $campaign_title,
						'title' => $campaign_title,
						'from_name' => $mc_from_name,
						'reply_to' => $mc_reply_to_email,
						'to_name' => $mc_to_name
					),
			);

			$camp_contents = array('html' => $campaign_content);

			try {
				$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);
				$campaign_new = $mcAPI3->add_campaign($camp_options);
				if(isset($campaign_new->id)){
					$campaign_id = $campaign_new->id;
					update_post_meta($post_id, '_mc_campaign_id', $campaign_id);

					//update campaign list meta data
					update_post_meta($post_id, '_mailchimp_list_id', $mc_list_id);

					$mcAPI3->update_campaign_content( $campaign_id, $camp_contents );

					//schedule campaign
					if($schedule_date != ''){
						$campaign_schedule_date = date('Y-m-d', strtotime($schedule_date));
						$campaign_schedule_date = $this->format_schedule_datetime($campaign_schedule_date, $campaign_time);
					}else{
						$campaign_schedule_date = get_the_date('Y-m-d', $post_id);
						$campaign_schedule_date = $this->format_schedule_datetime($campaign_schedule_date, $campaign_time);
					}
					wp_update_post( array ( 'ID' => $post_id, 'post_date' => $campaign_schedule_date, 'post_date_gmt' => get_gmt_from_date( $campaign_schedule_date ) ) );
					$schedule_data = array(
						'schedule_time' => $campaign_schedule_date,
					);
					$mcAPI3->campaign_action( $campaign_id, 'schedule', $schedule_data );

				}


			}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
				 return FALSE;
			}catch( MCManager_API_Exception $e ) {
				 return FALSE;
			}

		}


		/**
		 * Update campaign to mailchimp server
		 *
		 * @param post id and schedule date
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#create-post_campaigns
		 */
		function update_mc_campaign_data($post_id, $campaign_id, $campaign_content) {
			global $MCManager_Settings;
			$mc_list_id = $MCManager_Settings->get_option('campaign', 'campaign_list');

			//Reset default list using campaign list
			if(get_post_meta($post_id, '_mailchimp_list_id', true) != ''){
					$mc_list_id = get_post_meta($post_id, '_mailchimp_list_id', true);
			}

			$campaign_title = get_the_title($post_id);
			$mc_from_name = $MCManager_Settings->get_option('campaign', 'campaign_from_name');
			$mc_reply_to_email = $MCManager_Settings->get_option('campaign', 'campaign_reply_to');
			$mc_to_name = $MCManager_Settings->get_option('campaign', 'campaign_to_name');
			$campaign_time = $MCManager_Settings->get_option('campaign', 'campaign_time');

			$camp_options = array(
					'recipients' => array('list_id' => $mc_list_id),
					'settings' => array(
						'subject_line' => $campaign_title,
						'title' => $campaign_title,
						'from_name' => $mc_from_name,
						'reply_to' => $mc_reply_to_email,
						'to_name' => $mc_to_name
					),
			);

			$camp_contents = array('html' => $campaign_content);

			try {
				$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);

				if($this->check_mc_campaign_status($campaign_id) == 'schedule'){
						$mcAPI3->campaign_action( $campaign_id, 'unschedule' );
				}

				$campaign_schedule_date = get_the_date('Y-m-d '.$campaign_time, $post_id);

				//update campaign list meta data
				update_post_meta($post_id, '_mailchimp_list_id', $mc_list_id);

				$schedule_data = array(
					'schedule_time' => $campaign_schedule_date,
				);

				$mcAPI3->update_campaign( $campaign_id, $camp_options );

				$mcAPI3->update_campaign_content( $campaign_id, $camp_contents );

				$mcAPI3->campaign_action( $campaign_id, 'schedule', $schedule_data );


			}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
				 return FALSE;
			}catch( MCManager_API_Exception $e ) {
				 return FALSE;
			}

		}

		/**
		* @date should be Y-m-d format
		* @time 00:30 type
		* return formate date for campaign schedule
		**/
		function format_schedule_datetime($date, $time){
					global $MCManager_Settings;
					$campaign_adv_day = $MCManager_Settings->get_option('campaign', 'campaign_adv_day');

					$request_date = date('Y-m-d '.$time, strtotime($date));
					$today_date = date("Y-m-d");
					$current_date = date('Y-m-d '.$time, strtotime($today_date));

					$request_date_time = strtotime($request_date);
					$current_date_time = strtotime($current_date);

					if($request_date_time <= $current_date_time){
						$campaign_new_schedule_date = strtotime("+{$campaign_adv_day} day", strtotime($date));
						$campaign_new_schedule_date = date('Y-m-d '.$time, $campaign_new_schedule_date);
					}else{
						$campaign_new_schedule_date = date('Y-m-d '.$time, strtotime($date));
					}

					return $campaign_new_schedule_date;

		}

		function get_campaign_content($post_id){

			global $MCManager_Settings;
			$mc_template_id = $MCManager_Settings->get_option('campaign', 'campaign_template');

			$campaign_html = '';
			$search = array();
			$replace = array();

			$campaign_content = get_post($post_id);
			$campaign_content_html = $campaign_content->post_content;
			$campaign_content_html = apply_filters('the_content', $campaign_content_html);

			$search[] = '{mcm_campaign_content}';
			$replace[] = $campaign_content_html;

			//get template from campaign itself
			if(intval(get_post_meta($post_id, '_template_id', true)) > 0){
				$template_post_id = get_post_meta($post_id, '_template_id', true);
				$template_content = get_post($template_post_id);
				$template_content_html = $template_content->post_content;
				//$template_content_html = apply_filters('the_content', $template_content_html);
				$campaign_html = str_replace($search, $replace, $template_content_html);
			}elseif($mc_template_id > 0){
				//Default template from campaign settings
				$template_content = get_post($mc_template_id);
				$template_content_html = $template_content->post_content;

				//Set default template to this campaign
				update_post_meta($post_id, '_template_id', $mc_template_id);

				//$template_content_html = apply_filters('the_content', $template_content_html);
				$campaign_html = str_replace($search, $replace, $template_content_html);
			}else{
				$campaign_html = $campaign_content_html;
			}

			return $campaign_html;
		}


		/**
		 * Check if mailchimp campaign exist
		 *
		 * @param campaign_id id and schedule date
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/campaigns/#create-post_campaigns
		 */
		function check_mc_campaign_exist($campaign_id) {

			$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);

			$campaigns_options = array('fields' => array('id', 'create_time'));

			try {
			  $campaign_details = $mcAPI3->get_campaign($campaign_id, $campaigns_options);
				return true;
			}catch( MC4WP_API_Resource_Not_Found_Exception $e ) {
			   return FALSE;
			}catch( MC4WP_API_Exception $e ) {
			   return FALSE;
			}

		}


		function check_mc_campaign_status($campaign_id) {

			$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);

			$campaigns_options = array('fields' => array('id', 'create_time', 'status'));

			try {
			  $campaign_details = $mcAPI3->get_campaign($campaign_id, $campaigns_options);
				if(isset($campaign_details->status)){
						return $campaign_details->status;
				}
			}catch( MC4WP_API_Resource_Not_Found_Exception $e ) {
			   return FALSE;
			}catch( MC4WP_API_Exception $e ) {
			   return FALSE;
			}

		}


		/**
		 * Add meta box
		 *
		 * @param post $post The post object
		 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/add_meta_boxes
		 */
		function mcmanager_campaign_meta_boxes( $post ){
			add_meta_box( 'mcmanager_campaign_meta_box', esc_html__( 'Settings', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_campaign_build_meta_box'), 'mcmanager_campaign', 'normal', 'high' );
			add_meta_box( 'mcmanager_campaign_preview_meta_box', esc_html__( 'Campaign Preview', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_campaign_preview_build_meta_box'), 'mcmanager_campaign', 'normal', 'default' );
		}




		/**
		 * Build custom field meta box
		 *
		 * @param post $post The post object
		 */
		function mcmanager_campaign_preview_build_meta_box( $post ){
			// make sure the form request comes from WordPress
			wp_nonce_field( basename( __FILE__ ), 'campaign_preview_meta_box_nonce' );
			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){
					$campaign_id = $_GET['post'];
					$campaign_preview_html = $this->get_campaign_content($campaign_id);
			}else{
					$campaign_preview_html = 'N/A';
			}
			?>
			<div class='inside'>
					<?php
						echo $campaign_preview_html;
					?>
			</div>
			<?php
		}



		/**
		 * Build custom field meta box
		 *
		 * @param post $post The post object
		 */
		function mcmanager_campaign_build_meta_box( $post ){
			// make sure the form request comes from WordPress
			wp_nonce_field( basename( __FILE__ ), 'campaign_meta_box_nonce' );
			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){

				$mc_template_id = esc_html(get_post_meta( $post->ID, '_template_id', true ));
				$mailchimp_list_id = esc_html(get_post_meta( $post->ID, '_mailchimp_list_id', true ));

			}else{
					global $MCManager_Settings;
					$mailchimp_list_id = $MCManager_Settings->get_option('campaign', 'campaign_list');
					$mc_template_id = $MCManager_Settings->get_option('campaign', 'campaign_template');
			}

			$disabled = ' disabled="disabled"';
			if(mcmanager_is_premium()){
				$disabled = '';
			}

			?>
			<div class='inside'>
				<table class="form-table">
					<tr>
					<th><label for="mailchimp_list_id"><?php echo esc_html__( 'Campaign To Lists', 'rm-mailchimp-manager' ); ?></label></th>
					<td>
						<?php
						$mcmanager_lists  = get_option( 'mcmanager_lists' );
							?>
							<p><select id="mailchimp_list_id" name="mailchimp_list_id" class="regular-text">
								<?php
								if(mcmanager_is_premium()){
									echo mcmanager_lists_drowpdown_options($mailchimp_list_id);
								}else{
									echo mcmanager_lists_drowpdown_options($mailchimp_list_id, true);
								}
								?>
							</select>
						</p>
					<p class="description" id="mailchimp_list_id-description"><?php echo esc_html__( 'To which MailChimp lists should this form subscribe?', 'rm-mailchimp-manager' ); ?></p></td>
					</tr>
					<tr>
					<th><?php echo esc_html__( 'Template', 'rm-mailchimp-manager' ); ?></th>
					<td>
						<?php
						$mcmanager_templates  = get_mcmanager_templates();
							?>
							<p><select id="template_id" name="template_id" class="regular-text">
								<?php
								if(isset($mcmanager_templates)){
										echo '<option value="">'. esc_html__( 'Select Template', 'rm-mailchimp-manager' ) .'</option>';
										foreach($mcmanager_templates as $template_id => $template_name){
											//print_r($list);
											echo '<option ';
											echo $disabled;
											if($template_id == $mc_template_id) echo ' selected="selected" ';
											echo 'value="'.esc_attr($template_id).'">';
											echo esc_html($template_name);
											echo '</option>';
										}
								}
								?>
							</select>
							<p>
					<p class="description" id="mailchimp_list_id-description"><?php echo esc_html__( 'Selected template will use for campaign. Template must need to include {mcm_campaign_content} to replace campaign content.', 'rm-mailchimp-manager' ); ?></p></td>
					</tr>
					<?php if(!mcmanager_is_premium()){ ?>
						<tr><td colspan="2"><p><em><?php echo esc_html__( 'All inactive features are required to premium license.', 'rm-mailchimp-manager' ); ?></em></p></td></tr>
					<?php } ?>
				</table>
			</div>
			<?php
		}




		/**
		 * Store custom field meta box data
		 *
		 * @param int $post_id The post ID.
		 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
		 */
		function mcmanager_campaign_save_meta_box_data( $post_id ){
			// verify meta box nonce
			if ( !isset( $_POST['campaign_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['campaign_meta_box_nonce'], basename( __FILE__ ) ) ){
				return;
			}
			// return if autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
				return;
			}
		  // Check the user's permissions.
			if ( ! current_user_can( 'edit_post', $post_id ) ){
				return;
			}
			// store custom fields values
			if ( isset( $_REQUEST['template_id'] ) ) {
				update_post_meta( $post_id, '_template_id', sanitize_text_field( $_POST['template_id'] ) );
			}
			if ( isset( $_REQUEST['mailchimp_list_id'] ) ) {
				update_post_meta( $post_id, '_mailchimp_list_id', sanitize_text_field( $_POST['mailchimp_list_id'] ) );
			}


			//check if not premium vesrion
			if(!mcmanager_is_premium() && ($this->get_total_campaigns() > 10)){
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
				add_filter( 'redirect_post_location', array( $this, 'mcm_premium_restriction_notice_query_var' ), 99 );
			}


		}

		function mcm_premium_restriction_notice_query_var( $location ) {
			return add_query_arg( array( 'mcm_campaign_limit' => 'yes' ), $location );
		}

		function mcm_campaign_validation_admin_notice(){
			if(isset($_GET['mcm_campaign_limit']) && ($_GET['mcm_campaign_limit'] =='yes')){
				?>
				<div class="notice-error notice is-dismissible">
					<p><?php _e( 'You have the Lite version of Mailchimp Manager, which limits you to 10 campaigns. Please <a href="https://mailchimpmanager.com/" target="_blank">upgrade to</a> the premium license if you need more.', 'rm-mailchimp-manager' ); ?></p>
				</div>
				<?php
			}

			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){
				$post_id = intval($_GET['post']);
				$post_type = get_post_type($post_id);
				$mc_status = get_post_meta($post_id, '_mc_campaign_status', true);

				if ( ("mcmanager_campaign" == $post_type) && ($mc_status == 'sent')){
					?>
					<div class="notice-info notice is-dismissible">
						<p><?php echo esc_html__( 'This campaign have already been sent. Current update will not marged to mailchimp server.', 'rm-mailchimp-manager' ); ?></p>
					</div>
					<?php
				}


			}

		}

		function get_total_campaigns(){
			$count_campaigns = wp_count_posts('mcmanager_campaign');
			$published_campaigns = $count_campaigns->publish;

			return $published_campaigns;
		}



		/**
		 * Form details page template
		 */
		public function mcmanager_campaign_page_template($single_template) {
				global $post;
				if ($post->post_type == 'mcmanager_campaign' ) {
					$single_template = MCManager_BASE_FOLDER . '/template/single-campaign.php';
				}
				wp_reset_postdata();
				return $single_template;
		}

  }
}
global $MCManager_Campaign;
$MCManager_Campaign = new MCManager_Campaign();
