<?php
/**
 * Plugin Name: Post Purchase offer
 * Description:  Post Purchase offer for WooCommerce.
 * Version: 1.1.0
 * Plugin URI:        https://woocommerce.com/products/post-purchase-offer-for-woocommerce/
 * Author:            Addify
 * Developed By:      Addify
 * Author URI:        https://woocommerce.com/vendor/addify/
 * Support:           https://woocommerce.com/vendor/addify/
 * Domain Path:       /languages
 * Text Domain:       woo_addf_ppp
 * WC requires at least: 3.0.9
 * WC tested up to: 6.*.*
 * Woo: 8949002:f66e345faff9375c4a31c1f590248551
 *
 * @package woo_addf_ppp
 */

if (!defined('ABSPATH')) {
	exit();
}

class Addify_Post_Purchase_Offers {



	public function __construct() {

		$this->addf_post_purchase_offer_global_constents_vars();
		add_action('plugin_loaded', array( $this, 'af_pc_wc_check' ));
		add_action('before_woocommerce_init', array( $this, 'af_ppp__HOPS_Compatibility' ));
		add_action('init', array( $this, 'af_ppp_check_woocommerce_is_defined_or_not' ));

		// add to cart from front end
		add_action('wp_ajax_add_to_cart_custom', array( $this, 'post_purchase_offer_add_to_cart_custom' ));
		add_action('wp_ajax_nopriv_add_to_cart_custom', array( $this, 'post_purchase_offer_add_to_cart_custom' ));
		
		// products ids store in wp_session
		add_action('wp_ajax_store_custom_ids', array( $this, 'post_purchase_offer_store_product_ids_session' ));
		add_action('wp_ajax_nopriv_store_custom_ids', array( $this, 'post_purchase_offer_store_product_ids_session' )); // For non-logged-in users
	}




	public function post_purchase_offer_store_product_ids_session() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'afppo-ajax-nonce' ) ) {
		die( esc_html__( 'Failed ajax security check!', 'woo_addf_ppp' ) );
		}
		
		
	
		$customIdsArray = isset($_POST['custom_ids']) ? sanitize_meta('', wp_unslash($_POST['custom_ids']), '') : array();
		$customIds = WC()->session->get('custom_ids', array());
		$customIds = array_merge($customIds, $customIdsArray);
		WC()->session->set('custom_ids', $customIds);
	}

	public function post_purchase_offer_add_to_cart_custom() {

	
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field(wp_unslash( $_POST['nonce'] ) ): '';

		if ( ! wp_verify_nonce( $nonce, 'afppo-ajax-nonce' ) ) {
			die( esc_html__( 'Failed ajax security check!', 'woo_addf_ppp' ) );
		}

		$accepted_offers = isset($_POST['acceptedOffers']) ? sanitize_meta('', wp_unslash($_POST['acceptedOffers']), '') : array();
		

		foreach ($accepted_offers as $offer) {
			$product_id = $offer['productId'];
			$custom_price = $offer['customPrice'];
			$rule_id = $offer['postId'];
			$custom_quantity = $offer['customQuantity'];
			$is_offer_product = $offer['isOfferProduct'];
			$Quantity_stock_select = $offer['Quantity_stock_select'];

			$cart_item_data = array(
				'rule_id' => $rule_id,
				'custom_quantity' => $custom_quantity,
				'custom_price' => $custom_price,
				'custom_is_offer_product' => $is_offer_product,

			);

			$cart_item_key = WC()->cart->add_to_cart($product_id, $Quantity_stock_select, 0, array(), $cart_item_data);

			$cart_item = WC()->cart->get_cart_item($cart_item_key);

			if ( $cart_item_key ) {
				// Send a success response
				echo json_encode( array( 'success' => true ) );
			} else {
				// Send an error response
				echo json_encode( array(
					'success' => false,
					'message' => 'Failed to add product to cart.',
				) );
			}
		}
	
			

		wp_die();
	}


	public function af_pc_wc_check() {
		if (!is_multisite() && ( !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true) )) {
			add_action('admin_notices', array( $this, 'af_ppp_wc_active' ));

		}
	}


	public function af_ppp_wc_active() {
		deactivate_plugins(__FILE__);
		?>
		<div id="message" class="error">
			<p>
				<strong>
					<?php echo esc_html__('Post purchase offer for WooCommerce plugin is inactive. WooCommerce plugin must be active in order to activate it.', 'woo_addf_ppp'); ?>
				</strong>
			</p>
		</div>
		<?php
	}
	

	public function af_ppp__HOPS_Compatibility() {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}


	public function af_ppp_check_woocommerce_is_defined_or_not() {
		if (defined('WC_PLUGIN_FILE')) {
	
			include ADDF_PPP_DIR . 'includes/general-functions.php';
	
			if (is_admin()) {
				include_once ADDF_PPP_DIR . '/admin/addify-post-purchase-offer-admin.php';
			} else {
				include_once ADDF_PPP_DIR . '/front/addify-post-purchase-offer-front.php';
			}
	
			add_action('wp_loaded', array( $this, 'addf_post_purchase_offer_load_text_domain' ));
	
	
			$labels = array(
				'name'                  => esc_html__( 'Post Purchase Offer', 'woo_addf_ppp' ),
				'singular_name'         => esc_html__( 'Post Purchase Offer', 'woo_addf_ppp' ),
				'add_new'               => esc_html__( 'Add New Offer', 'woo_addf_ppp' ),
				'add_new_item'          => esc_html__( 'Add Offer', 'woo_addf_ppp' ),
				'edit_item'             => esc_html__( 'Edit Offer', 'woo_addf_ppp' ),
				'new_item'              => esc_html__( 'New Offer', 'woo_addf_ppp' ),
				'view_item'             => esc_html__( 'View Offer', 'woo_addf_ppp' ),
				'search_items'          => esc_html__( 'Search Offer', 'woo_addf_ppp' ),
				'exclude_from_search'   => true,
				'not_found'             => esc_html__( 'No rule found', 'woo_addf_ppp' ),
				'not_found_in_trash'    => esc_html__( 'No Offer found in trash', 'woo_addf_ppp' ),
				'parent_item_colon'     => '',
				'all_items'             => esc_html__( 'All Offer', 'woo_addf_ppp' ),
				'menu_name'             => esc_html__( 'Post Purchase Offer', 'woo_addf_ppp' ),
			);
			
			
			$args = array(
				'labels' => $labels,
				'menu_icon' => ADDF_PPP_URL . 'includes/images/addify.jpg',
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'show_in_menu' => false,
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => true,
				'hierarchical' => false,
				'menu_position' => 30,
				'rewrite' => array(
					'slug' => 'post-purchase-offer-rule',
					'with_front' => false,
				),
				'supports' => array( 'title' ),
			);
			register_post_type('post_purchase_offer', $args);
	
		}
	}
	
	public function addf_post_purchase_offer_load_text_domain() {
		if (function_exists('load_plugin_textdomain')) {
			load_plugin_textdomain('woo_addf_ppp', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		}
	}

	public function addf_post_purchase_offer_global_constents_vars() {
		if (!defined('ADDF_PPP_URL')) {
			define('ADDF_PPP_URL', plugin_dir_url(__FILE__));
		}

		if (!defined('ADDF_PPP_BASENAME')) {
			define('ADDF_PPP_BASENAME', plugin_basename(__FILE__));
		}
		if (!defined('ADDF_PPP_DIR')) {
			define('ADDF_PPP_DIR', plugin_dir_path(__FILE__));
		}
	}
}

new Addify_Post_Purchase_Offers();



