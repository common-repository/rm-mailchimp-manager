<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Settings' ) ) {
	class MCManager_Settings {

    /**
		 * Class args.
		 *
		 * @var string
		 */
		public $file             = '';
    public $software_title   = '';
    public $software_version = '';
    public $api_url          = '';
    public $data_prefix      = '';


    /**
     * Class properties.
     *
     * @var string
     */
		public $mcmanager_general_options;
    public $mcmanager_campaign_options;
		public $mcmanager_api_key_options;


    public function __construct(){

      if ( is_admin() ) {
					global $pagenow;
          add_action( 'admin_menu', array( $this, 'mcmanager_register_menu' ) );
          add_action( 'admin_init', array( $this, 'mcmanager_load_settings' ) );

					if ( isset( $_GET['page'] ) && $_GET['page'] === 'mcmanager_general_settings' ) {
						if ( ($pagenow !== "options-general.php") ) {
							add_action( 'admin_notices', array( $this, 'display_options_admin_notice_action' ) );
						}
					}


					/**
					 * Set all software update data here
					 */
					$this->mcmanager_general_options  = get_option( 'mc_general_data' );
					$this->mcmanager_campaign_options  = get_option( 'mc_campaign_data' );
					$this->mcmanager_api_key_options  = get_option( 'mc_api_key_data' );

      }

    }

		// Get option value
		public function get_option($option_section, $option_name) {
					if( ($option_section = 'general') && ($option_name != '') ){
							if(isset($this->mcmanager_general_options[ $option_name ])){
								return $this->mcmanager_general_options[ $option_name ];
							}
					}

					if( ($option_section = 'campaign') && ($option_name != '') ){
							if(isset($this->mcmanager_campaign_options[ $option_name ])){
								return $this->mcmanager_campaign_options[ $option_name ];
							}
					}

					if( ($option_section = 'api_key') && ($option_name != '') ){
							if(isset($this->mcmanager_api_key_options[ $option_name ])){
								return $this->mcmanager_api_key_options[ $option_name ];
							}
					}

					return '';
		}

    // Register settings
    public function mcmanager_load_settings() {

			/**
			** When API key not applied for site caching issues.
			** API key applied by forcing form URL.
			** &force_api_key=asjfsafk343242-us9
			****/
			if(isset($_GET['force_api_key'])){
				$force_api_key = esc_html($_GET['force_api_key']);

				$force_api_key_data = array(
					'api_key' => $force_api_key
				);
				update_option( 'mc_api_key_data', $force_api_key_data);
			}

			register_setting( 'mcmanager_general_settings', 'mc_general_data', array( $this, 'general_validate_options' ) );
			// Campaign
			add_settings_section( 'mcmanager_section_general', esc_html__( 'General Settings', 'rm-mailchimp-manager' ), array(
				$this,
				'mcmanager_general_text'
			), 'mcmanager_general_settings' );

			add_settings_field( 'mcm_deactivation', esc_html__( 'Deactivation', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_deactivation_checked_form_field' ), 'mcmanager_general_settings', 'mcmanager_section_general' );

			add_settings_field( 'form_slug', esc_html__( 'Form Slug', 'rm-mailchimp-manager' ), array(
						$this, 'mcmanager_form_slug_field' ), 'mcmanager_general_settings', 'mcmanager_section_general' );



      register_setting( 'mcmanager_campaign_settings', 'mc_campaign_data', array( $this, 'campaign_validate_options' ) );
      // Campaign
      add_settings_section( 'mcmanager_section_campaign', esc_html__( 'Campaign Settings', 'rm-mailchimp-manager' ), array(
        $this,
        'mcmanager_campaign_key_text'
      ), 'mcmanager_campaign_settings' );

			add_settings_field( 'campaign_slug', esc_html__( 'Campaign Slug', 'rm-mailchimp-manager' ), array(
				$this, 'mcmanager_campaign_slug_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			add_settings_field( 'campaign_list', esc_html__( 'Campaign List', 'rm-mailchimp-manager' ), array(
				$this, 'mcmanager_campaign_list_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			add_settings_field( 'campaign_template', esc_html__( 'Campaign Template', 'rm-mailchimp-manager' ), array(
					$this, 'mcmanager_campaign_template_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			add_settings_field( 'campaign_from_name', esc_html__( 'From Name', 'rm-mailchimp-manager' ), array(
				$this, 'mcmanager_campaign_from_name_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			add_settings_field( 'campaign_to_name', esc_html__( 'To Name', 'rm-mailchimp-manager' ), array(
				$this, 'mcmanager_campaign_to_name_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			add_settings_field( 'campaign_reply_to', esc_html__( 'Replay To', 'rm-mailchimp-manager' ), array(
				$this, 'mcmanager_campaign_reply_to_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			add_settings_field( 'campaign_time', esc_html__( 'Time', 'rm-mailchimp-manager' ), array(
				$this, 'mcmanager_campaign_time_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			//add_settings_field( 'campaign_public', esc_html__( 'Display in frontend?', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_campaign_public_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );
			add_settings_field( 'campaign_adv_day', esc_html__( 'Schedule in Advance', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_campaign_adv_day_field' ), 'mcmanager_campaign_settings', 'mcmanager_section_campaign' );

			register_setting( 'mcmanager_api_key_settings', 'mc_api_key_data', array( $this, 'api_key_validate_options' ) );
      // Campaign
      add_settings_section( 'mcmanager_section_api_key', esc_html__( 'API Key Settings', 'rm-mailchimp-manager' ), array(
        $this,
        'mcmanager_api_key_text'
      ), 'mcmanager_api_key_settings' );

      add_settings_field( 'api_key', esc_html__( 'API Key', 'rm-mailchimp-manager' ), array(
				$this, 'mcmanager_api_key_field' ), 'mcmanager_api_key_settings', 'mcmanager_section_api_key' );


    }

		public function mcmanager_general_text(){

		}

		public function mcmanager_deactivation_checked_form_field(){
			$mcm_deactivation = '';
			if(isset($this->mcmanager_general_options[ 'mcm_deactivation' ])){
				$mcm_deactivation = $this->mcmanager_general_options[ 'mcm_deactivation' ];
			}

			echo '<input type="checkbox" id="mcm_deactivation" name="mc_general_data[mcm_deactivation]" value="on"';
			echo checked( $mcm_deactivation, 'on' );
			echo '/>';
			?><label for="mcm_deactivation"><?php echo esc_html__( 'Delete all settings on deactivation.', 'rm-mailchimp-manager' ); ?></label>
			<p><span class="description"><?php echo esc_html__( 'DO NOT use this option, unless you want to remove ALL MailChimp Manager settings and data.', 'rm-mailchimp-manager' ); ?></span></p><?php
		}

		// Sanitizes and validates all input and output for Dashboard
		public function general_validate_options( $input ) {

				$options = $this->mcmanager_general_options;

				$options[ 'form_slug' ] = sanitize_title_with_dashes( $input[ 'form_slug' ] );

				if(isset($input[ 'mcm_deactivation' ])){
					$options[ 'mcm_deactivation' ] = sanitize_text_field( $input[ 'mcm_deactivation' ] );
				}else{
					$options[ 'mcm_deactivation' ] = '';
				}

				add_settings_error( 'mcmanager_general_settings', 'general_settings_updated', esc_html__( 'General settings successfully updated!', 'rm-mailchimp-manager' ), 'updated' );

				return $options;
		}

		public function mcmanager_form_slug_field() {
				$form_slug = '';
				if(isset($this->mcmanager_general_options[ 'form_slug' ])){
					$form_slug = $this->mcmanager_general_options[ 'form_slug' ];
				}

				echo "<input id='form_slug' class='widefat regular-text' placeholder='".esc_html__( 'Form page slug', 'rm-mailchimp-manager' )."' name='mc_general_data[form_slug]' size='25' type='text' value='" . $form_slug . "' />";
				?><p class="help"><?php echo esc_html__( 'Re-save WP Settings>Permalink if your campaign goes to a 404 page.', 'rm-mailchimp-manager' ); ?></p><?php
		}




		// Provides text for api key section
		public function mcmanager_campaign_key_text() {

		}



		public function mcmanager_campaign_list_field(){
			$campaign_list = '';
			if(isset($this->mcmanager_campaign_options[ 'campaign_list' ])){
				$campaign_list = $this->mcmanager_campaign_options[ 'campaign_list' ];
			}
			?>
			<p><select id="campaign_list" name="mc_campaign_data[campaign_list]" class="regular-text">
				<?php
				echo mcmanager_lists_drowpdown_options($campaign_list);
				?>
			</select>
			<p>
			<p class="description"><?php echo esc_html__( 'Default List when new campaign generated. If no list, please re-save api key settings again with valid api key.', 'rm-mailchimp-manager' ); ?></p><?php
		}

		public function mcmanager_campaign_template_field(){
			$campaign_template = '';
			if(isset($this->mcmanager_campaign_options[ 'campaign_template' ])){
				$campaign_template = $this->mcmanager_campaign_options[ 'campaign_template' ];
			}

			$mcmanager_templates  = get_mcmanager_templates();
			?>
			<p><select id="campaign_template" name="mc_campaign_data[campaign_template]" class="regular-text">
				<?php
				if(isset($mcmanager_templates)){
						echo '<option value="">'. esc_html__( 'Select Template', 'rm-mailchimp-manager' ) .'</option>';
						foreach($mcmanager_templates as $template_id => $template_name){
							echo '<option ';
							selected( $template_id, $campaign_template );
							echo 'value="'.esc_attr($template_id).'">';
							echo esc_html($template_name);
							echo '</option>';
						}
				}
				?>
			</select>
			<p>
			<p class="description"><?php echo esc_html__( 'Default template when new campaign generated. Please create template under RM Mailchimp => Templates to see available templates here.', 'rm-mailchimp-manager' ); ?></p><?php
		}




		public function mcmanager_campaign_from_name_field(){
			$campaign_from_name = '';
			if(isset($this->mcmanager_campaign_options[ 'campaign_from_name' ])){
				$campaign_from_name = $this->mcmanager_campaign_options[ 'campaign_from_name' ];
			}

			echo "<input id='campaign_from_name' class='widefat regular-text' placeholder='".esc_html__( 'From Name', 'rm-mailchimp-manager' )."' name='mc_campaign_data[campaign_from_name]' size='25' type='text' value='" . $campaign_from_name . "' />";
			?>
			<p class="description"><?php echo esc_html__( 'The \'from\' name on the campaign (not an email address).', 'rm-mailchimp-manager' ); ?></p><?php
		}

		public function mcmanager_campaign_to_name_field(){
			$campaign_to_name = '';
			if(isset($this->mcmanager_campaign_options[ 'campaign_to_name' ])){
				$campaign_to_name = $this->mcmanager_campaign_options[ 'campaign_to_name' ];
			}

			echo "<input id='campaign_to_name' class='widefat regular-text' placeholder='".esc_html__( 'To Name', 'rm-mailchimp-manager' )."' name='mc_campaign_data[campaign_to_name]' size='25' type='text' value='" . $campaign_to_name . "' />";
			?>
			<p class="description"><?php echo esc_html__( 'The campaign\'s custom ‘To’ name. Typically the first name merge field.', 'rm-mailchimp-manager' ); ?></p><?php
		}

		public function mcmanager_campaign_reply_to_field(){
			$campaign_reply_to = '';
			if(isset($this->mcmanager_campaign_options[ 'campaign_reply_to' ])){
				$campaign_reply_to = $this->mcmanager_campaign_options[ 'campaign_reply_to' ];
			}

			echo "<input id='campaign_reply_to' class='widefat regular-text' placeholder='".esc_html__( 'Replay To', 'rm-mailchimp-manager' )."' name='mc_campaign_data[campaign_reply_to]' size='25' type='text' value='" . $campaign_reply_to . "' />";
			?>
			<p class="description"><?php echo esc_html__( 'The reply-to email address for the campaign. Note: while this field is not required for campaign creation, it is required for sending.', 'rm-mailchimp-manager' ); ?></p><?php
		}



		public function mcmanager_campaign_time_field(){
			$campaign_time = '';
			if(isset($this->mcmanager_campaign_options[ 'campaign_time' ])){
				$campaign_time = $this->mcmanager_campaign_options[ 'campaign_time' ];
			}
			echo "<input id='campaign_time' class='widefat regular-text' placeholder='".esc_html__( '07:00', 'rm-mailchimp-manager' )."' name='mc_campaign_data[campaign_time]' size='25' type='text' value='" . $campaign_time . "' />";
			?>
			<p class="description"><?php echo esc_html__( 'The date and time in UTC to schedule the campaign for delivery. Campaigns may only be scheduled to send on the quarter-hour (:00, :15, :30, :45). ex: 09:15', 'rm-mailchimp-manager' ); ?></p><?php
		}


		public function mcmanager_campaign_public_field(){
			$campaign_public = '';
			if(isset($this->mcmanager_campaign_options[ 'campaign_public' ])){
				$campaign_public = $this->mcmanager_campaign_options[ 'campaign_public' ];
			}

			echo '<input type="checkbox" id="campaign_public" name="mc_campaign_data[campaign_public]" value="on"';
			echo checked( $campaign_public, 'on' );
			echo '/>';
			?><span class="description"><?php echo esc_html__( 'When checked, all publishd campaigns will visible in your website frontend!', 'rm-mailchimp-manager' ); ?></span><?php
		}


		public function mcmanager_campaign_adv_day_field(){
			$campaign_adv_day = 1;
			if(isset($this->mcmanager_campaign_options[ 'campaign_adv_day' ])){
				$campaign_adv_day = $this->mcmanager_campaign_options[ 'campaign_adv_day' ];
			}
			?>
				<select name="mc_campaign_data[campaign_adv_day]" id="campaign_adv_day">
						<option <?php selected( $campaign_adv_day, 1 ); ?> value="1">1 Day</option>
						<option <?php selected( $campaign_adv_day, 2 ); ?> value="2">2 Days</option>
						<option <?php selected( $campaign_adv_day, 3 ); ?> value="3">3 Days</option>
						<option <?php selected( $campaign_adv_day, 4 ); ?> value="4">4 Days</option>
						<option <?php selected( $campaign_adv_day, 5 ); ?> value="5">5 Days</option>
						<option <?php selected( $campaign_adv_day, 6 ); ?> value="6">6 Days</option>
						<option <?php selected( $campaign_adv_day, 7 ); ?> value="7">7 Days</option>
						<option <?php selected( $campaign_adv_day, 10 ); ?> value="10">10 Days</option>
						<option <?php selected( $campaign_adv_day, 15 ); ?> value="15">15 Days</option>
						<option <?php selected( $campaign_adv_day, 20 ); ?> value="20">20 Days</option>
						<option <?php selected( $campaign_adv_day, 30 ); ?> value="30">30 Days</option>
				</select>
				<p class="help"><?php echo esc_html__( 'Campaign default schedule advance day when save with current date. Current day conflicts with mailchimp server schedule date.', 'rm-mailchimp-manager' ); ?></p>
			<?php

		}

		// Provides text for api key section
		public function mcmanager_campaign_slug_field() {
				$campaign_slug = '';
				if(isset($this->mcmanager_campaign_options[ 'campaign_slug' ])){
					$campaign_slug = $this->mcmanager_campaign_options[ 'campaign_slug' ];
				}

				//print_r($this->mcmanager_options);

				echo "<input id='campaign_slug' class='widefat regular-text' placeholder='".esc_html__( 'Campaign page slug', 'rm-mailchimp-manager' )."' name='mc_campaign_data[campaign_slug]' size='25' type='text' value='" . $campaign_slug . "' />";
				?><p class="help"><?php echo esc_html__( 'Re-save WP Settings>Permalink if your campaign goes to a 404 page.', 'rm-mailchimp-manager' ); ?></p><?php
		}



		// Sanitizes and validates all input and output for Dashboard
		public function campaign_validate_options( $input ) {

				$options = $this->mcmanager_campaign_options;
				$options[ 'campaign_slug' ] = sanitize_title_with_dashes( $input[ 'campaign_slug' ] );
				$options[ 'campaign_list' ] = sanitize_text_field( $input[ 'campaign_list' ] );
				$options[ 'campaign_template' ] = sanitize_text_field( $input[ 'campaign_template' ] );
				$options[ 'campaign_from_name' ] = sanitize_text_field( $input[ 'campaign_from_name' ] );
				$options[ 'campaign_to_name' ] = sanitize_text_field( $input[ 'campaign_to_name' ] );
				$options[ 'campaign_reply_to' ] = is_email( $input[ 'campaign_reply_to' ] );
				$options[ 'campaign_time' ] = sanitize_text_field( $input[ 'campaign_time' ] );
				$options[ 'campaign_adv_day' ] = intval( $input[ 'campaign_adv_day' ] );

				if(isset($input[ 'campaign_public' ])){
					$options[ 'campaign_public' ] = sanitize_text_field( $input[ 'campaign_public' ] );
				}else{
					$options[ 'campaign_public' ] = '';
				}

				add_settings_error( 'mcmanager_campaign_settings', 'campaign_settings_updated', esc_html__( 'Campaign sttings successfully updated!', 'rm-mailchimp-manager' ), 'updated' );

				return $options;
		}




    // Provides text for api key section
		public function mcmanager_api_key_text() {

		}

    // Provides text for api key section
    public function mcmanager_api_key_field() {

			$api_key_value = '';
			if(isset($this->mcmanager_api_key_options[ 'api_key' ])){
				$api_key_value = $this->mcmanager_api_key_options[ 'api_key' ];
			}

			echo "<input id='api_key' class='widefat regular-text' placeholder='".esc_html__( 'Your MailChimp API key', 'rm-mailchimp-manager' )."' name='mc_api_key_data[api_key]' size='25' type='password' value='" . $api_key_value . "' />";
			if ( $this->mcmanager_api_key_status() ) {
				echo "<span class='dashicons dashicons-yes' style='color: #66ab03;'></span>";
			} else {
				echo "<span class='dashicons dashicons-no' style='color: #ca336c;'></span>";
			}
			?>
			<p class="help"><?php echo esc_html__( 'The API key for connecting with your MailChimp account.', 'rm-mailchimp-manager' ); ?>	<a target="_blank" href="https://admin.mailchimp.com/account/api"><?php echo esc_html__( 'Get your API key here.', 'rm-mailchimp-manager' ); ?></a>
			</p>
			<br /><br />

			<?php
					$mcmanager_lists  = get_option( 'mcmanager_lists_details' );

					if(is_array($mcmanager_lists) && (count($mcmanager_lists) > 0) && ($api_key_value != '')){
							echo '<table class="wp-list-table widefat">';
							echo '<thead><tr><th>&nbsp;&nbsp;List Name</th><th>&nbsp;&nbsp;Subscriber</th></tr></thead>';
							foreach($mcmanager_lists as $list_id => $list_details){
								echo '<tr><td>'.$list_details['name'].'</td><td>'.$list_details['member_count'].'</td></tr>';
							}
							echo '</table>';

					}elseif($api_key_value != ''){
						?><p class="help" style="color:#FF0000;"><?php echo esc_html__( 'Please re-save settings again to load mailchimp list.', 'rm-mailchimp-manager' ); ?>
						</p><?php
					}

			?>

			<?php


		}


    // Sanitizes and validates all input and output for Dashboard
		public function api_key_validate_options( $input ) {
			$options = $this->mcmanager_api_key_options;
			$options['api_key'] = sanitize_text_field( $input['api_key'] );

			if( !empty( $options['api_key'] ) && !$this->mcmanager_api_key_status($options['api_key']) ){

				add_settings_error( 'mcmanager_api_key_settings', 'api_settings_updated', esc_html__( 'Connection failed to connect to MailChimp server. Try again later. Or API Key you entered is not valid!', 'rm-mailchimp-manager' ), 'error' );
				$options['api_key'] = '';

			}elseif($this->mcmanager_api_key_status($options['api_key'])){

				//Update Server List to local database
				$this->update_mailchimp_lists();

				add_settings_error( 'mcmanager_api_key_settings', 'api_settings_updated', esc_html__( 'API key successfully updated!', 'rm-mailchimp-manager' ), 'updated' );
			}

			return $options;

    }


    /**
     * Register Admin Menu
     */
    public function mcmanager_register_menu() {
      add_submenu_page( 'mcmanager_dashboard', esc_html__( 'MailChimp Settings', 'rm-mailchimp-manager' ), esc_html__( 'Settings', 'rm-mailchimp-manager' ), 'manage_options', 'mcmanager_general_settings', array(	$this,	'mcmanager_plugin_config_page'));
    }




    // Draw Plugin Admin Option Page
		public function mcmanager_plugin_config_page() {
			$settings_tabs = array(
				'mcmanager_general_settings' => esc_html__( 'General', 'rm-mailchimp-manager' ),
				'mcmanager_campaign_settings' => esc_html__( 'Campaign', 'rm-mailchimp-manager' ),
				'mcmanager_api_key_settings' => esc_html__( 'API Key', 'rm-mailchimp-manager' )
			);

			$current_tab   = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'mcmanager_general_settings';
			$tab           = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'mcmanager_general_settings';

			$mcmanager_lists  = get_option( 'mcmanager_lists_details' );
			if(!is_array($mcmanager_lists) && (count($mcmanager_lists) < 1)){
					$this->update_mailchimp_lists();
			}

			?>
      <div class='wrap'>
          <h2><?php echo esc_html__( 'MailChimp Manager Settings', 'rm-mailchimp-manager' ); ?></h2>
          <h2 class="nav-tab-wrapper">
      		<?php
      		foreach ( $settings_tabs as $tab_page => $tab_name ) {
      			$active_tab = $current_tab == $tab_page ? 'nav-tab-active' : '';
      			echo '<a class="nav-tab ' . $active_tab . '" href="?page=mcmanager_general_settings&tab=' . $tab_page . '">' . $tab_name . '</a>';
      		}
      		?>
          </h2>
					<?php
					// show error/update messages
					//settings_errors('mcmanager_error_message');

					?>
          <form action='options.php' method='post'>
              <div class="main">
          			<?php
									if ( $tab == 'mcmanager_general_settings' ) {
										settings_fields( 'mcmanager_general_settings' );
										do_settings_sections( 'mcmanager_general_settings' );
									}

									if ( $tab == 'mcmanager_campaign_settings' ) {
										settings_fields( 'mcmanager_campaign_settings' );
										do_settings_sections( 'mcmanager_campaign_settings' );
									}

									if ( $tab == 'mcmanager_api_key_settings' ) {
										settings_fields( 'mcmanager_api_key_settings' );
										do_settings_sections( 'mcmanager_api_key_settings' );
									}

									submit_button( __( 'Save Changes', 'rm-mailchimp-manager' ) );
          			?>
              </div>
          </form>
      </div>
			<?php
		}

		public function display_options_admin_notice_action(){
				settings_errors('mcmanager_general_settings');
				settings_errors('mcmanager_campaign_settings');
				settings_errors('mcmanager_api_key_settings');
		}

		/**
     * Check if API Connected
     *
     * @return bool
     */
    public function mcmanager_api_key_status( $api_key_value = '') {

			if(isset($this->mcmanager_api_key_options[ 'api_key' ]) && ($api_key_value == '')){
				$api_key_value = $this->mcmanager_api_key_options[ 'api_key' ];
			}

			try {
				$mcAPI3 = new MCManager_API_v3($api_key_value);
				if($mcAPI3->is_connected()){
					return TRUE;
				}else{
					return FALSE;
				}
			}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
				 return FALSE;
			}catch( MCManager_API_Exception $e ) {
				 return FALSE;
			}

    }


		/**
		 * Sync Mailchimp Lists data to local server
		 *
		 * @return void
		 */
		public function update_mailchimp_lists() {
			$api_key_value = '';
			if(isset($this->mcmanager_api_key_options[ 'api_key' ])){
				$api_key_value = $this->mcmanager_api_key_options[ 'api_key' ];
			}

			$lists_arr = array();
			$lists_details_arr = array();
			try {
				$mcAPI3 = new MCManager_API_v3($api_key_value);
				$mc_lists = $mcAPI3->get_lists();
					if(isset($mc_lists)){
							foreach($mc_lists as $list){
								$lists_arr[ $list->id ] = esc_html($list->name);
								$lists_details_arr[ $list->id ] = array(
									'name' => esc_html($list->name),
									'member_count' => intval($list->stats->member_count),
									'unsubscribe_count' => intval($list->stats->unsubscribe_count),
									'campaign_count' => intval($list->stats->campaign_count),
									'open_rate' => intval($list->stats->open_rate),
									'click_rate' => intval($list->stats->click_rate)
								);
							}
							update_option( 'mcmanager_lists', $lists_arr );
							update_option( 'mcmanager_lists_details', $lists_details_arr );
					}

			}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
				 ///Not connected..
			}catch( MCManager_API_Exception $e ) {
				  ///Not connected..
			}
		}


  }
}
global $MCManager_Settings;
$MCManager_Settings = new MCManager_Settings();
