<?php
/**
 * @package     MailChimp Manager
 * @since       1.0.0
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MCManager_Campaign_Shortcodes' ) ) {
	class MCManager_Campaign_Shortcodes {

		/**
		 * Class properties.
		 *
		 * @var string
		 */
		public $mailchimp_api_key;

    public function __construct(){
				global $MCManager_Settings;
				$this->mailchimp_api_key = $MCManager_Settings->get_option('api_key', 'api_key');

				add_shortcode( 'mcmanager_campaign', array( $this, 'mcmanager_campaign_shortcode_func' ) );
				//add_action( 'wp_enqueue_scripts', array( $this, 'mcmanager_campaign_front_style' ) );
		}

		/**
		 * Register front style
		 */
		function mcmanager_campaign_front_style() {
			global $post;
			wp_register_style(  'mcmanager-campaign-style', MCManager_PLUGIN_URL.'/css/mcmgt-campaign.css' );
			if(( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'mcmanager_campaign') ) || is_singular('mcmanager_campaign') ) {
					if ( ! wp_script_is( 'jquery', 'done' ) ) {
							wp_enqueue_script( 'jquery' );
					}
					wp_enqueue_style('mcmanager-campaign-style');
			}

		}



		public function mcmanager_campaign_shortcode_func($atts, $content = null) {
			extract(shortcode_atts(array(
				'id' => '',
			), $atts));
			ob_start();
			$campaign_id = intval($id);
			wp_enqueue_style('mcmanager-campaign-style');

			$campaign_html = '';
			$search = array();
			$replace = array();

			$campaign_content = get_post($campaign_id);
			$campaign_content_html = $campaign_content->post_content;
			$campaign_content_html = apply_filters('the_content', $campaign_content_html);

			$search[] = '{mcm_campaign_content}';
			$replace[] = $campaign_content_html;

			if(intval(get_post_meta($campaign_id, '_template_id', true)) > 0){
				$template_post_id = get_post_meta($campaign_id, '_template_id', true);
				$template_content = get_post($template_post_id);
				$template_content_html = $template_content->post_content;
				$campaign_html = str_replace($search, $replace, $template_content_html);
			}else{
				$campaign_html = $campaign_content_html;
			}
			?>
			<div class="mcmanager-campaign">
					<?php echo $campaign_html; ?>
			</div>
			<?php
			$mc_campaign = ob_get_contents();
			ob_end_clean();
			return $mc_campaign;

		}





  }
}

$mcm_campaign_shortcodes = new MCManager_Campaign_Shortcodes();
