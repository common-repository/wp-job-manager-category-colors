<?php
/**
 * Plugin Name: WP Job Manager - Job Category Colors
 * Plugin URI:  https://github.com/tripflex/wp-job-manager-cat-colors
 * Description: Assign custom colors for each existing job category.
 * Author:      Myles McNamara
 * Author URI:  http://smyl.es
 * Version:     1.1
 * Text Domain: job_manager_cat_colors
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Job_Manager_Cat_Colors {

	private static $instance;

	public static function instance() {
		if ( ! isset ( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		$this->setup_actions();
	}

	private function setup_actions() {
		add_filter( 'job_manager_settings', array( $this, 'job_manager_settings' ) );
		add_action( 'wp_head', array( $this, 'output_colors' ) );

		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'colorpickers' ) );
			add_action( 'admin_footer', array( $this, 'colorpickersjs' ) );
		}
	}

	public function job_manager_settings( $settings ) {
		$settings[ 'job_cat_colors' ] = array(
			__( 'Job Category Colors', 'job_manager_cat_colors' ),
			$this->create_options()
		);

		return $settings;
	}

	private function create_options() {
		$terms   = get_terms( 'job_listing_category', array( 'hide_empty' => false ) );
		$options = array();

		$options[] = array(
			'name' 		  => 'job_manager_job_cat_what_color',
			'std' 		  => 'background',
			'placeholder' => '',
			'label' 	  => __( 'What', 'job_manager_cat_colors' ),
			'desc'        => __( 'Should these colors be applied to the text color, or background color?', 'job_manager_cat_colors' ),
			'type'        => 'select',
			'options'     => array(
				'background' => __( 'Background', 'job_manager_cat_colors' ),
				'text'       => __( 'Text', 'job_manager_cat_colors' )
			)
		);
		foreach ( $terms as $term ) {
			$options[] = array(
				'name' 		  => 'job_manager_job_cat_' . $term->slug . '_color',
				'std' 		  => '',
				'placeholder' => '#',
				'label' 	  => '<strong>' . $term->name . '</strong>',
				'desc'		  => __( 'Hex value for the color of this job category.', 'job_manager_cat_colors' ),
				'attributes'  => array(
					'data-default-color' => '#fff',
					'data-type'          => 'colorpicker'
				)
			);
		}
		$options[] = array(
			'name' 		  => 'job_manager_job_cat_usage_color',
			'label' 	  => __( 'Usage', 'job_manager_cat_colors' ),
			'desc'        => __( 'You will need to place this code somewhere in your template file to display the category with the styling:', 'job_manager_cat_colors' ). '<br/>' .
							'<code>&lt;li class=&quot;job-category &lt;?php echo get_the_job_category() ? sanitize_title( get_the_job_category()-&gt;slug ) : &#039;&#039;; ?&gt;&quot;&gt;&lt;?php the_job_category(); ?&gt;&lt;/li&gt;</code>' . '<br/>' . '<br/>' .
							__('You can customize the WP Job Manager templates by saving the template file from location below to the root of your theme directory.', 'job_manager_cat_colors') . '<br/>'.'<br/>' .
							'<code>/themes/jobify/content-single-job.php</code>' . __('  -  Would be the file for Jobify theme for job detail heading', 'job_manager_cat_colors') . '<br/>'.'<br/>' .
							'<code>/plugins/wp-job-manager/templates/content-single-job_listing.php</code>' . __('  -  WP Job Manager Template', 'job_manager_cat_colors') . '<br/>'.'<br/>' .
							'<code>/plugins/wp-job-manager/templates/content-job_listing.php</code>' . __('  -  WP Job Manager Template', 'job_manager_cat_colors') . '<br/>',
			'type'        => 'input',
			'attributes'  => array(
				'style' => 'display: none;'
				)
		);
		return $options;
	}

	public function output_colors() {
		$terms   = get_terms( 'job_listing_category', array( 'hide_empty' => false ) );

		echo "<style id='job_manager_cat_colors'>\n";

		echo ".job-category {font: bold 12px/normal 'Montserrat', sans-serif;text-transform: uppercase;color: #fff;padding: 3px 10px;border-radius: 4px;}li.job-category{text-align:center;padding: 3px 10px;border-right: 0;}";

		foreach ( $terms as $term ) {
			$what = 'background' == get_option( 'job_manager_job_cat_what_color' ) ? 'background-color' : 'color';

			printf( ".job-category.%s { %s: %s; } \n", $term->slug, $what, get_option( 'job_manager_job_cat_' . $term->slug . '_color', '#fff' ) );
		}

		echo "</style>\n";
	}

	public function colorpickers( $hook ) {
		$screen = get_current_screen();

		if ( 'job_listing_page_job-manager-settings' != $screen->id )
			return;

		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	public function colorpickersjs() {
		$screen = get_current_screen();

		if ( 'job_listing_page_job-manager-settings' != $screen->id )
			return;
		?>
			<script>
				jQuery(document).ready(function($){
					$( 'input[data-type="colorpicker"]' ).wpColorPicker();
				});
			</script>
		<?php
	}
}

/**
 * the_job_category function.
 *
 * @access public
 * @return void
 */
function the_job_category( $post = null ) {
	if ( $job_category = get_the_job_category( $post ) )
		echo $job_category->name;
}

/**
 * get_the_job_category function.
 *
 * @access public
 * @param mixed $post (default: null)
 * @return void
 */
function get_the_job_category( $post = null ) {
	$post = get_post( $post );
	if ( $post->post_type !== 'job_listing' )
		return;

	$categories = wp_get_post_terms( $post->ID, 'job_listing_category' );

	if ( $categories )
		$category = current( $categories );
	else
		$category = false;

	return apply_filters( 'the_job_category', $category, $post );
}
add_action( 'init', array( 'WP_Job_Manager_Cat_Colors', 'instance' ) );