<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Template' ) ) {
	class MCManager_Template {

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
					add_filter( 'user_can_richedit', array( $this, 'disable_for_richedit' ) );
					add_action( 'media_buttons', array( $this, 'add_mcm_template_media_buttons' ) );

					add_action( 'add_meta_boxes_mcmanager_template', array( $this, 'mcmanager_template_meta_boxes' ) );
					add_action( 'admin_init', array( $this, 'mcmanager_template_reset_to_prebuild' ) );

					add_action( 'save_post_mcmanager_template', array( $this, 'mcmanager_template_save_metabox_data' ) );

					add_action( 'admin_notices', array( $this, 'mcm_template_validation_admin_notice' ) );

					//add_action( 'admin_init', array( $this, 'mcmanager_template_sync_init' ) );
    }

		/**
		 * Register a Form post type.
		 *
		 * @link http://codex.wordpress.org/Function_Reference/register_post_type
		 */
		function mcmanager_register_form_cpt() {
			$labels = array(
				'name'               => _x( 'Templates', 'post type general name', 'rm-mailchimp-manager' ),
				'singular_name'      => _x( 'Template', 'post type singular name', 'rm-mailchimp-manager' ),
				'menu_name'          => _x( 'Templates', 'admin menu', 'rm-mailchimp-manager' ),
				'name_admin_bar'     => _x( 'Templates', 'add new on admin bar', 'rm-mailchimp-manager' ),
				'add_new'            => _x( 'Add New', 'Template', 'rm-mailchimp-manager' ),
				'add_new_item'       => esc_html__( 'Add New Template', 'rm-mailchimp-manager' ),
				'new_item'           => esc_html__( 'New Template', 'rm-mailchimp-manager' ),
				'edit_item'          => esc_html__( 'Edit Template', 'rm-mailchimp-manager' ),
				'featured_image' 		 => esc_html__( 'Template Preview', 'rm-mailchimp-manager' ),
				'set_featured_image' => esc_html__( 'Set Template Preview', 'rm-mailchimp-manager' ),
				'remove_featured_image' => esc_html__( 'Remove Template Preview', 'rm-mailchimp-manager' ),
				'use_featured_image' => esc_html__( 'Use as Template Preview', 'rm-mailchimp-manager' ),
				'view_item'          => esc_html__( 'View Template', 'rm-mailchimp-manager' ),
				'all_items'          => esc_html__( 'All Templates', 'rm-mailchimp-manager' ),
				'search_items'       => esc_html__( 'Search Templates', 'rm-mailchimp-manager' ),
				'parent_item_colon'  => esc_html__( 'Parent Templates:', 'rm-mailchimp-manager' ),
				'not_found'          => esc_html__( 'No Templates found.', 'rm-mailchimp-manager' ),
				'not_found_in_trash' => esc_html__( 'No Templates found in Trash.', 'rm-mailchimp-manager' )
			);
			$args = array(
				'labels'             => $labels,
		    'description'        => esc_html__( 'Templates Description.', 'rm-mailchimp-manager' ),
				'public'             => false,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'mcmanager-template' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 100,
				'supports'           => array( 'title', 'editor', 'thumbnail' )
			);

			register_post_type( 'mcmanager_template', $args );
		}

		public function disable_for_richedit( $default ){
			global $post;
	    if ( 'mcmanager_template' == get_post_type( $post ) ){
				return false;
			}
	    return $default;

		}

		public function add_mcm_template_media_buttons(){
			global $post;
			if ( 'mcmanager_template' == get_post_type( $post ) ){
				echo '<a href="#mcmanager_template_preview_meta_box" id="go-to-template-preview" class="button button-primary btn-template-preview mcm-btn-beside-media">'.esc_html__( 'Template Preview', 'rm-mailchimp-manager' ).'</a>';
				echo '<a href="#mcmanager_template_meta_box" id="go-to-ready-templates" class="button button-primary btn-ready-templates mcm-btn-beside-media">'.esc_html__( 'Add Pre-build Templates', 'rm-mailchimp-manager' ).'</a>';
			}
		}


		public function template_menu_highlight( $parent_file ){
			/* Get current screen */
			global $current_screen, $self;

	    if ( in_array( $current_screen->base, array( 'post', 'edit' ) ) && 'mcmanager_template' == $current_screen->post_type ) {
	        $parent_file = 'mcmanager_dashboard';
	    }

	    return $parent_file;

		}


    /**
     * Register Admin Menu
     */
    public function mcmanager_register_form_menu() {
      add_submenu_page( 'mcmanager_dashboard', esc_html__( 'Template Manager', 'rm-mailchimp-manager' ), esc_html__( 'Templates', 'rm-mailchimp-manager' ), 'manage_options', 'edit.php?post_type=mcmanager_template', NULL);
    }




		public function mcmanager_template_sync_init(){
			if ( current_user_can( 'delete_posts' ) ){
				add_action( 'wp_trash_post', array( $this, 'delete_mc_template' ), 10 );
			}

			if ( current_user_can('edit_posts') ) {
				add_action( 'save_post', array( $this, 'update_mc_template' ), 10 );
			}
		}

		/**
		 * Reset template using default template.
		 *
		 */
		public function mcmanager_template_reset_to_prebuild(){
			global $post;
			if ((isset($_GET['post'])) && (isset($_GET['mctplreset'])) ){
					$template_id = intval($_GET['post']);
					$template_name = esc_html($_GET['mctplreset']);

					switch ($template_name) {
					    case "1colupdated":
					        $template_html = file_get_contents('tpl/1_column_updated.html', FILE_USE_INCLUDE_PATH);
					        break;
					    case "1colfupdated":
					        $template_html = file_get_contents('tpl/1_column-f_updated.html', FILE_USE_INCLUDE_PATH);
					        break;
					    case "12colupdated":
					        $template_html = file_get_contents('tpl/1-2_column_updated.html', FILE_USE_INCLUDE_PATH);
									break;
							case "12colfupdated":
									$template_html = file_get_contents('tpl/1-2_column-f_updated.html', FILE_USE_INCLUDE_PATH);
									break;
							case "121colupdated":
					        $template_html = file_get_contents('tpl/1-2-1_column_updated.html', FILE_USE_INCLUDE_PATH);
									break;
							case "121colfupdated":
					        $template_html = file_get_contents('tpl/1-2-1_column-f_updated.html', FILE_USE_INCLUDE_PATH);
					        break;
							case "13colupdated":
					        $template_html = file_get_contents('tpl/1-3_column_updated.html', FILE_USE_INCLUDE_PATH);
									break;
							case "13colfupdated":
					        $template_html = file_get_contents('tpl/1-3_column-f_updated.html', FILE_USE_INCLUDE_PATH);
									break;
							case "textcolupdated":
					        $template_html = file_get_contents('tpl/simple_text_updated.html', FILE_USE_INCLUDE_PATH);
									break;
					    default:
					        $template_html = 'N/A';
					}


					// Update template post data
					$template_new_data = array(
							'ID'           => $template_id,
							'post_content' => $template_html,
					);

					// Update the post into the database
					wp_update_post( $template_new_data );

			}
		}


		/**
		 * Delete mailchimp template
		 *
		 * @param post_id $post The post object
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/templates/
		 */
		public function delete_mc_template($post_id){
			// If this is just a revision, don't send the email.
			if ( wp_is_post_revision( $post_id ) ){
					return;
			}

			$post_type = get_post_type($post_id);
			if ( "mcmanager_template" != $post_type ){
				 return;
			}

			$template_id = get_post_meta($post_id, '_mc_template_id', true);
			if($template_id){
				if( $this->check_mc_template_exist( $template_id ) ) {
					try {
						$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);
						$mcAPI3->delete_template($template_id);
					}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
						 return FALSE;
					}catch( MCManager_API_Exception $e ) {
						 return FALSE;
					}

				}
			}

		}


		/**
		 * Update or create template
		 *
		 * @param post $post The post object
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/templates/
		 */
		public function update_mc_template($post_id){
				// If this is just a revision, don't send the email.
				if ( wp_is_post_revision( $post_id ) ){
						return;
				}

				$post_type = get_post_type($post_id);
				if ( "mcmanager_template" != $post_type ){
					 return;
				}

				$template_id = get_post_meta($post_id, '_mc_template_id', true);
				$template_content = $this->get_template_content($post_id);
				if($template_id) {
					if( $this->check_mc_template_exist( $template_id ) ) {
						$this->update_mc_template_data( $post_id, $template_id, $template_content );
					} else {
						// create a new campaign
						$this->create_new_mc_template( $post_id, $template_content );
					}
				}else{
					// create a new campaign
					$this->create_new_mc_template( $post_id, $template_content );
				}


		}


		function get_template_content($post_id){
			$template_content = get_post($post_id);
			$template_content_html = $template_content->post_content;
			$template_content_html = apply_filters('the_content', $template_content_html);

			return $template_content_html;
		}


		/**
		 * Create template to mailchimp server
		 *
		 * @param post id and schedule date
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/templates/
		 */
		function create_new_mc_template($post_id, $template_content) {

			$template_name = get_the_title($post_id);

			$template_options = array('name' => $template_name, 'html' => $template_content);

			try {
				$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);
				$template_new = $mcAPI3->add_template($template_options);

				if(isset($template_new->id)){
					$template_id = $template_new->id;
					update_post_meta($post_id, '_mc_template_id', $template_id);

				}

			}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
				 return FALSE;
			}catch( MCManager_API_Exception $e ) {
				 return FALSE;
			}

		}


		/**
		 * Update template to mailchimp server
		 *
		 * @param post id and schedule date
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/templates/
		 */
		function update_mc_template_data($post_id, $template_id, $template_content) {

			$template_name = get_the_title($post_id);

			$template_options = array('name' => $template_name, 'html' => $template_content);

			try {
				$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);
				$template_new = $mcAPI3->update_template($template_id, $template_options);
			}catch( MCManager_API_Resource_Not_Found_Exception $e ) {
				 return FALSE;
			}catch( MCManager_API_Exception $e ) {
				 return FALSE;
			}

		}


		/**
		 * Check if mailchimp template exist
		 *
		 * @param template_id id and schedule date
		 * @link https://developer.mailchimp.com/documentation/mailchimp/reference/templates/
		 */
		function check_mc_template_exist($template_id) {

			$mcAPI3 = new MCManager_API_v3($this->mailchimp_api_key);

			$template_options = array('fields' => array('id', 'name'));

			try {
			  $template_details = $mcAPI3->get_template($template_id, $template_options);
				return true;
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
		function mcmanager_template_meta_boxes( $post ){
			add_meta_box( 'mcmanager_template_preview_meta_box', esc_html__( 'Preview', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_template_preview_build_meta_box'), 'mcmanager_template', 'normal', 'default' );
			add_meta_box( 'mcmanager_template_meta_box', esc_html__( 'Ready Templates', 'rm-mailchimp-manager' ), array( $this, 'mcmanager_template_build_meta_box'), 'mcmanager_template', 'normal', 'default' );
		}


		/**
		 * Build custom field meta box
		 *
		 * @param post $post The post object
		 */
		function mcmanager_template_preview_build_meta_box( $post ){
			// make sure the form request comes from WordPress
			wp_nonce_field( basename( __FILE__ ), 'template_preview_meta_box_nonce' );
			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){
					$template_id = $_GET['post'];
					$campaign_content = get_post($template_id);
					$campaign_content_html = $campaign_content->post_content;
			}else{
					$campaign_content_html = 'N/A';
			}
			?>
			<div class='inside'>
					<?php
						echo $campaign_content_html;
					?>
			</div>
			<?php
		}


		function get_template_reset_link($edit_url, $template){
				$tpl_edit_link = add_query_arg( 'mctplreset', $template, $edit_url );
				return $tpl_edit_link;
		}

		/**
		 * Build custom field meta box
		 *
		 * @param post $post The post object
		 */
		function mcmanager_template_build_meta_box( $post ){
			// make sure the form request comes from WordPress
			wp_nonce_field( basename( __FILE__ ), 'template_meta_box_nonce' );
			if(isset($_GET['post']) && ($_GET['action'] == 'edit')){
					$edit_url = get_edit_post_link( $_GET['post'] );

			}else{
					$edit_url = '';
			}
			?>
			<div class='inside'>

				<table class="form-table">
					<tr>
						<td><p class="description" id="template-info-description"><?php echo esc_html__( 'Template must include {mcm_campaign_content} tag to replace campaign content', 'rm-mailchimp-manager' ); ?></p></td>
					</tr>

					<tr>
							<td><?php echo esc_html__( 'Prebuild templates. Replace above content using below ready template and edit your own way.', 'rm-mailchimp-manager' ); ?></td>
					</tr>
					<tr>
							<td>
									<ul class="use-template">
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1_column_updated.svg" title="" /><h3><?php echo esc_html__( '1 Column', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '1colupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1_column-f_updated.svg" title="" /><h3><?php echo esc_html__( '1 Column - Full Width', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '1colfupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1-2_column_updated.svg" title="" /><h3><?php echo esc_html__( '1:2 Column', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '12colupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1-2_column-f_updated.svg" title="" /><h3><?php echo esc_html__( '1:2 Column - Full Width', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '12colfupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1-2-1_column_updated.svg" title="" /><h3><?php echo esc_html__( '1:2:1 Column', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '121colupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1-2-1_column-f_updated.svg" title="" /><h3><?php echo esc_html__( '1:2:1 Column - Full Width', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '121colfupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1-3_column_updated.svg" title="" /><h3><?php echo esc_html__( '1:3 Column', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '13colupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/1-3_column-f_updated.svg" title="" /><h3><?php echo esc_html__( '1:3 Column - Full Width', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, '13colfupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
										<li><div class="tpl-wrap"><img src="<?php echo MCManager_PLUGIN_URL;?>/img/tpl/simple_text_updated.svg" title="" /><h3><?php echo esc_html__( 'Simple Text', 'rm-mailchimp-manager' ); ?></h3><a class="use-this-template button  button-large" href="<?php echo $this->get_template_reset_link($edit_url, 'textcolupdated'); ?>"><?php echo esc_html__( 'Use This Template', 'rm-mailchimp-manager' ); ?></a></div></li>
									</ul>
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
		function mcmanager_template_save_metabox_data( $post_id ){
			// verify meta box nonce
			if ( !isset( $_POST['template_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['template_meta_box_nonce'], basename( __FILE__ ) ) ){
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
				//update_post_meta( $post_id, '_mailchimp_list_id', sanitize_text_field( $_POST['mailchimp_list_id'] ) );
			}

			//check if not premium vesrion
			if(!mcmanager_is_premium() && ($this->get_total_templates() > 1)){
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post_id ) );
				add_filter( 'redirect_post_location', array( $this, 'mcm_premium_restriction_notice_query_var' ), 99 );
			}

		}

		function mcm_premium_restriction_notice_query_var( $location ) {
			return add_query_arg( array( 'mcm_tpl_limit' => 'yes' ), $location );
		}


		function mcm_template_validation_admin_notice(){
			if(isset($_GET['mcm_tpl_limit']) && ($_GET['mcm_tpl_limit'] =='yes')){
				?>
				<div class="error">
					<p><?php _e( 'You have the Lite version of Mailchimp Manager, which limits you to one template. Please <a href="https://mailchimpmanager.com/" target="_blank">upgrade to</a> the premium license if you need more.', 'rm-mailchimp-manager' ); ?></p>
				</div>
				<?php
			}

		}

		function get_total_templates(){
			$count_templates = wp_count_posts('mcmanager_template');
			$published_templates = $count_templates->publish;

			return $published_templates;
		}

  }
}
global $MCManager_Template;
$MCManager_Template = new MCManager_Template();
