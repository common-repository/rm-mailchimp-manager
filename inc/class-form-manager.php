<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Form' ) ) {
	class MCManager_Form {

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
					add_filter( 'parent_file', array( $this, 'template_menu_highlight' ) );

					add_action( 'manage_mcmanager_form_posts_columns', array( $this, 'mcmanager_form_posts_columns' ) );
					add_action( 'manage_mcmanager_form_posts_custom_column', array( $this, 'mcmanager_show_form_columns' ) );

					add_action( 'add_meta_boxes_mcmanager_form', array( $this, 'mcmanager_form_meta_boxes' ) );
					add_action( 'save_post_mcmanager_form', array( $this, 'mcmanager_form_settings_save_meta_box_data' ) );
					add_action( 'save_post_mcmanager_form', array( $this, 'mcmanager_form_field_save_meta_box_data' ) );
					add_action( 'save_post_mcmanager_form', array( $this, 'mcmanager_form_message_save_meta_box_data' ) );

					add_action( 'admin_notices', array( $this, 'mcm_form_validation_admin_notice' ) );

					add_action( 'single_template', array( $this, 'mcmanager_form_page_template' ) );

    }

		/**
		 * Register a Form post type.
		 *
		 * @link http://codex.wordpress.org/Function_Reference/register_post_type
		 */
		function mcmanager_register_form_cpt() {
			global $MCManager_Settings;

			$labels = array(
				'name'               => _x( 'Forms', 'post type general name', 'rm-mailchimp-manager' ),
				'singular_name'      => _x( 'Form', 'post type singular name', 'rm-mailchimp-manager' ),
				'menu_name'          => _x( 'Forms', 'admin menu', 'rm-mailchimp-manager' ),
				'name_admin_bar'     => _x( 'Forms', 'add new on admin bar', 'rm-mailchimp-manager' ),
				'add_new'            => _x( 'Add New', 'Form', 'rm-mailchimp-manager' ),
				'add_new_item'       => esc_html__( 'Add New Form', 'rm-mailchimp-manager' ),
				'new_item'           => esc_html__( 'New Form', 'rm-mailchimp-manager' ),
				'edit_item'          => esc_html__( 'Edit Form', 'rm-mailchimp-manager' ),
				'view_item'          => esc_html__( 'View Form', 'rm-mailchimp-manager' ),
				'all_items'          => esc_html__( 'All Forms', 'rm-mailchimp-manager' ),
				'search_items'       => esc_html__( 'Search Forms', 'rm-mailchimp-manager' ),
				'parent_item_colon'  => esc_html__( 'Parent Forms:', 'rm-mailchimp-manager' ),
				'not_found'          => esc_html__( 'No Forms found.', 'rm-mailchimp-manager' ),
				'not_found_in_trash' => esc_html__( 'No Forms found in Trash.', 'rm-mailchimp-manager' )
			);

			$form_slug = $MCManager_Settings->get_option('general', 'form_slug');
			if(!$form_slug || ($form_slug == '')){
				$form_slug = 'mcmanager-form';
			}

			$args = array(
				'labels'             => $labels,
		    'description'        => esc_html__( 'Form Description.', 'rm-mailchimp-manager' ),
				'public'             => apply_filters( 'mcmanager_form_public', true ),
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => $form_slug ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => apply_filters( 'mcmanager_form_menu_position', 100 ),
				'supports'           => array( 'title' )
			);

			register_post_type( 'mcmanager_form', $args );
		}


    /**
     * Register Admin Menu
     */
    public function mcmanager_register_form_menu() {
      add_submenu_page( 'mcmanager_dashboard', esc_html__( 'Form Manager', 'rm-mailchimp-manager' ), esc_html__( 'Forms', 'rm-mailchimp-manager' ), 'manage_options', 'edit.php?post_type=mcmanager_form', NULL);
    }


		public function template_menu_highlight( $parent_file ){
			/* Get current screen */
			global $current_screen, $self;

	    if ( in_array( $current_screen->base, array( 'post', 'edit' ) ) && 'mcmanager_form' == $current_screen->post_type ) {
	        $parent_file = 'mcmanager_dashboard';
	    }

	    return $parent_file;

		}

		/**
		* Reorder Form admin column
		*/
		public function mcmanager_form_posts_columns($post_columns){
			$post_columns = array(
	        'cb' => $post_columns['cb'],
	        'title' => esc_html__( 'Form Name', 'rm-mailchimp-manager' ),
					'list_name' => esc_html__( 'MailChimp List', 'rm-mailchimp-manager' ),
					'shortcode' => esc_html__( 'Shortcode', 'rm-mailchimp-manager' )
	        );

	    return $post_columns;

		}


		/**
		* show custom order column values
		*/
		function mcmanager_show_form_columns($name){
		  global $post;

		  switch ($name) {
				case 'list_name':
					$list_id = esc_html(get_post_meta($post->ID, '_mailchimp_list_id', true));
					echo mcmanager_get_mc_list_name($list_id);
					break;
				case 'shortcode':
					echo '<code>[mcmanager_form id="'.$post->ID.'"]</code>';
					break;
		   default:
		      break;
		   }
		}


		/**
		 * Add meta box
		 *
		 * @param post $post The post object
		 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/add_meta_boxes
		 */
		function mcmanager_form_meta_boxes( $post ){
			add_meta_box( 'mcmanager_form_list_meta_box', esc_html__( 'MailChimp Settings', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_form_settings_build_meta_box'), 'mcmanager_form', 'normal', 'default' );

			add_meta_box( 'mcmanager_form_field_meta_box', esc_html__( 'Form Fields', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_form_fields_build_meta_box'), 'mcmanager_form', 'normal', 'default' );

			add_meta_box( 'mcmanager_form_message_meta_box', esc_html__( 'Message', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_form_message_build_meta_box'), 'mcmanager_form', 'normal', 'default' );
		}



		/**
		 * Build custom field meta box
		 *
		 * @param post $post The post object
		 */
		function mcmanager_form_settings_build_meta_box( $post ){
			// make sure the form request comes from WordPress
			wp_nonce_field( basename( __FILE__ ), 'form_meta_settings_nonce' );
			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){

				$mailchimp_list_id = esc_html(get_post_meta( $post->ID, '_mailchimp_list_id', true ));
				$redirect_url = esc_html(get_post_meta( $post->ID, '_redirect_url', true ));
				$double_opt_in = esc_html(get_post_meta( $post->ID, '_double_opt_in', true ));

			}else{

					$mailchimp_list_id = '';
					$redirect_url = '';
					$double_opt_in = 'yes';
			}
			wp_enqueue_style( 'mc-admin' );
			wp_enqueue_script('mc-admin-js');
			?>
			<div class='inside'>
				<table class="form-table">
				<tr>
				<th><label for="mailchimp_list_id"><?php echo esc_html__( 'Lists this form subscribes to', 'rm-mailchimp-manager' ); ?></label></th>
				<td>
						<p><select id="mailchimp_list_id" name="mailchimp_list_id" class="regular-text">
							<?php
							echo mcmanager_lists_drowpdown_options($mailchimp_list_id);
							?>
						</select>
					</p>
				<p class="description" id="mailchimp_list_id-description"><?php echo esc_html__( 'To which MailChimp lists should this form subscribe?', 'rm-mailchimp-manager' ); ?></p></td>
				</tr>
				<tr>
				<th>
					<label for="redirect_url"><?php echo esc_html__( 'Redirect to URL after successful sign-ups', 'rm-mailchimp-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="redirect_url" id="redirect_url" class="regular-text" value="<?php echo $redirect_url; ?>" />
					<p class="description" id="redirect_url-description"><?php echo esc_html__( 'Leave empty or enter 0 for no redirect. Otherwise, use complete (absolute) URLs, including http://', 'rm-mailchimp-manager' ); ?></p>
				</td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Enable double opt-in', 'rm-mailchimp-manager' ); ?></th>
					<td>
						<p>
							<select name="double_opt_in" id="double_opt_in">
									<option <?php selected('yes', $double_opt_in); ?> value="yes"><?php echo esc_html__( 'Yes', 'rm-mailchimp-manager' ); ?></option>
									<option <?php selected('no', $double_opt_in); ?> value="no"><?php echo esc_html__( 'No', 'rm-mailchimp-manager' ); ?></option>
							</select>
						</p>
						<p class="description" id="double_opt_in-description"><?php echo esc_html__( 'We strongly suggest keeping double opt-in enabled. Disabling double opt-in may result in abuse.', 'rm-mailchimp-manager' ); ?></p>
					</td>
				</tr>
				<?php
					$form_id = 0;
					if(isset($_GET['post']) && ($_GET['action'] == 'edit')){
						$form_id = intval($_GET['post']);
					}
				?>
				<tr>
				<th scope="row"><?php echo esc_html__( 'Form Shortcode', 'rm-mailchimp-manager' ); ?></th>
				<td><code><?php echo '[mcmanager_form id="'.$form_id.'"]'; ?></code></td>
				</tr>
				</table>
			</div>
			<?php
		}


		/**
		 * Build custom field meta box
		 *
		 * @param post $post The post object
		 */
		function mcmanager_form_fields_build_meta_box( $post ){
			// make sure the form request comes from WordPress
			wp_nonce_field( basename( __FILE__ ), 'form_meta_fields_nonce' );
			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){

				$mc_form_editor = mcmanager_kses(get_post_meta( $post->ID, '_mc_form_editor', true ));

			}else{

$mc_form_editor = '<p><label>Email address: </label><input type="email" name="email" placeholder="Your email address" required /></p>

<p><input type="submit" value="Sign up" /></p>';

			}
			?>
			<div class='inside'>
				<table class="form-table form-builder-table">
				<tr>
				<td class="form-buttons">
						<button type="button" class="button add-name-field" value="name"><?php echo esc_html__( 'Name', 'rm-mailchimp-manager' ); ?></button>
						<button type="button" class="button add-email-field" value="email"><?php echo esc_html__( 'Email', 'rm-mailchimp-manager' ); ?></button>
						<button type="button" class="button add-signup-field" value="signup"><?php echo esc_html__( 'Sign up', 'rm-mailchimp-manager' ); ?></button>
						<button type="button" class="button add-terms-field" value="terms"><?php echo esc_html__( 'Agree to terms', 'rm-mailchimp-manager' ); ?></button>
				</td>
				</tr>
				<tr>
				<td class="form-editor">
						<textarea name="mc_form_editor" id="mc-form-editor" cols="60" rows="10" class="mc-form-editor"><?php echo $mc_form_editor; ?></textarea>
				</td>
				</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * Build custom field meta box
		 *
		 * @param post $post The post object
		 */
		function mcmanager_form_message_build_meta_box( $post ){
			// make sure the form request comes from WordPress
			wp_nonce_field( basename( __FILE__ ), 'form_meta_message_nonce' );
			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){

				$msg_subscription_success = esc_html(get_post_meta( $post->ID, '_msg_subscription_success', true ));
				$msg_invalid_email = esc_html(get_post_meta( $post->ID, '_msg_invalid_email', true ));
				$msg_required_field_missing = esc_html(get_post_meta( $post->ID, '_msg_required_field_missing', true ));
				$msg_already_subscribed = esc_html(get_post_meta( $post->ID, '_msg_already_subscribed', true ));

			}else{

					$msg_subscription_success = esc_html__( 'Thank you, your sign-up request was successful! Please check your email inbox to confirm.', 'rm-mailchimp-manager' );
					$msg_invalid_email = esc_html__( 'Please provide a valid email address.', 'rm-mailchimp-manager' );
					$msg_required_field_missing = esc_html__( 'Please fill in the required fields.', 'rm-mailchimp-manager' );
					$msg_already_subscribed = esc_html__( 'Given email address is already subscribed, thank you!', 'rm-mailchimp-manager' );

			}

			?>
			<div class='inside'>
				<table class="form-table">
				<tr>
				<th>
					<label for="msg_subscription_success"><?php echo esc_html__( 'Successfully subscribed', 'rm-mailchimp-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="msg_subscription_success" id="msg_subscription_success" class="regular-text" value="<?php echo $msg_subscription_success; ?>" />
				</td>
				</tr>
				<tr>
				<th>
					<label for="msg_invalid_email"><?php echo esc_html__( 'Invalid email address', 'rm-mailchimp-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="msg_invalid_email" id="msg_invalid_email" class="regular-text" value="<?php echo $msg_invalid_email; ?>" />
				</td>
				</tr>
				<tr>
				<th>
					<label for="msg_required_field_missing"><?php echo esc_html__( 'Required field missing', 'rm-mailchimp-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="msg_required_field_missing" id="msg_required_field_missing" class="regular-text" value="<?php echo $msg_required_field_missing; ?>" />
				</td>
				</tr>
				<tr>
				<th>
					<label for="msg_already_subscribed"><?php echo esc_html__( 'Already subscribed', 'rm-mailchimp-manager' ); ?></label>
				</th>
				<td>
					<input type="text" name="msg_already_subscribed" id="msg_already_subscribed" class="regular-text" value="<?php echo $msg_already_subscribed; ?>" />
				</td>
				</tr>
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
		function mcmanager_form_settings_save_meta_box_data( $post_id ){
			// verify meta box nonce
			if ( !isset( $_POST['form_meta_settings_nonce'] ) || !wp_verify_nonce( $_POST['form_meta_settings_nonce'], basename( __FILE__ ) ) ){
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
			if ( isset( $_REQUEST['mailchimp_list_id'] ) ) {
				update_post_meta( $post_id, '_mailchimp_list_id', sanitize_text_field( $_POST['mailchimp_list_id'] ) );
			}
			if ( isset( $_REQUEST['redirect_url'] ) ) {
				update_post_meta( $post_id, '_redirect_url', sanitize_text_field( $_POST['redirect_url'] ) );
			}
			if ( isset( $_REQUEST['double_opt_in'] ) ) {
				update_post_meta( $post_id, '_double_opt_in', sanitize_text_field( $_POST['double_opt_in'] ) );
			}



		}


		/**
		 * Store custom field meta box data
		 *
		 * @param int $post_id The post ID.
		 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
		 */
		function mcmanager_form_field_save_meta_box_data( $post_id ){
			// verify meta box nonce
			if ( !isset( $_POST['form_meta_fields_nonce'] ) || !wp_verify_nonce( $_POST['form_meta_fields_nonce'], basename( __FILE__ ) ) ){
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
			if ( isset( $_REQUEST['mc_form_editor'] ) ) {
				update_post_meta( $post_id, '_mc_form_editor', mcmanager_kses( $_POST['mc_form_editor'] ) );
			}



		}

		/**
		 * Store custom field meta box data
		 *
		 * @param int $post_id The post ID.
		 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
		 */
		function mcmanager_form_message_save_meta_box_data( $post_id ){
			// verify meta box nonce
			if ( !isset( $_POST['form_meta_message_nonce'] ) || !wp_verify_nonce( $_POST['form_meta_message_nonce'], basename( __FILE__ ) ) ){
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
			if ( isset( $_REQUEST['msg_subscription_success'] ) ) {
				update_post_meta( $post_id, '_msg_subscription_success', sanitize_text_field( $_POST['msg_subscription_success'] ) );
			}
			if ( isset( $_REQUEST['msg_invalid_email'] ) ) {
				update_post_meta( $post_id, '_msg_invalid_email', sanitize_text_field( $_POST['msg_invalid_email'] ) );
			}
			if ( isset( $_REQUEST['msg_required_field_missing'] ) ) {
				update_post_meta( $post_id, '_msg_required_field_missing', sanitize_text_field( $_POST['msg_required_field_missing'] ) );
			}
			if ( isset( $_REQUEST['msg_already_subscribed'] ) ) {
				update_post_meta( $post_id, '_msg_already_subscribed', sanitize_text_field( $_POST['msg_already_subscribed'] ) );
			}

			//check if not premium vesrion
			if(!mcmanager_is_premium() && ($this->get_total_forms() > 1)){
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
				add_filter( 'redirect_post_location', array( $this, 'mcm_premium_restriction_notice_query_var' ), 99 );
			}

		}

		function mcm_premium_restriction_notice_query_var( $location ) {
			return add_query_arg( array( 'mcm_form_limit' => 'yes' ), $location );
		}


		function mcm_form_validation_admin_notice(){
			if(isset($_GET['mcm_form_limit']) && ($_GET['mcm_form_limit'] =='yes')){
				?>
				<div class="error">
					<p><?php _e( 'You have the Lite version of Mailchimp Manager, which limits you to one form. Please <a href="https://mailchimpmanager.com/" target="_blank">upgrade to</a> the premium license if you need more.', 'rm-mailchimp-manager' ); ?></p>
				</div>
				<?php
			}

		}

		function get_total_forms(){
			$count_forms = wp_count_posts('mcmanager_form');
			$published_forms = $count_forms->publish;

			return $published_forms;
		}


		/**
		 * Form details page template
		 */
		public function mcmanager_form_page_template($single_template) {
				global $post;
				if ($post->post_type == 'mcmanager_form' ) {
					$single_template = MCManager_BASE_FOLDER . '/template/single-form.php';
				}
				wp_reset_postdata();

				return $single_template;
		}

  }
}
global $MCManager_Form;
$MCManager_Form = new MCManager_Form();
