<?php
defined('ABSPATH') || exit;
class Addify_Post_Purchase_Offer_Admin {
	
	public $nonce;

	public function __construct() {

		  $this->nonce = wp_create_nonce( 'afppo-ajax-nonce' );
			
			// add css and script
			add_action('admin_enqueue_scripts', array( $this, 'post_purchase_offer_Admin_enqueue_scripts' ));
			// add to menu
			add_action('admin_menu', array( $this, 'post_purchase_offer_register_woocommerce_menu' ));
			// add meta box
			add_action('add_meta_boxes', array( $this, 'post_purchase_offer_meta_box' ));
			// search product
			add_action('wp_ajax_post_purchase_offer_getproductsearch_2', array( $this, 'post_purchase_offer_getproductsearch_cb_2' ));
			add_action('wp_ajax_nopriv_post_purchase_offer_getproductsearch_2', array( $this, 'post_purchase_offer_getproductsearch_cb_2' ));

			add_action('wp_ajax_post_purchase_offer_getproductsearch', array( $this, 'post_purchase_offer_getproductsearch_cb' ));
			add_action('wp_ajax_nopriv_post_purchase_offer_getproductsearch', array( $this, 'post_purchase_offer_getproductsearch_cb' ));
			// save meta values
			add_action('save_post_post_purchase_offer', array( $this, 'post_purchase_offer_save_metaData' ), 10, 2);
			
			// save settings
			add_filter('woocommerce_settings_tabs_array', array( $this, 'post_purchase_offer_custom_settings_tab' ), 50);
			add_action('woocommerce_settings_tabs_post_purchase_offer_setting', array( $this, 'post_purchase_offer_custom_settings_fields' ));
			add_action('woocommerce_settings_save_post_purchase_offer_setting', array( $this, 'post_purchase_offer_save_custom_settings_fields' ));
	}




			
	public function get_tab_screen_ids() {
	 return array( 'woocommerce_page_wc-settings', 'edit-post_purchase_offer', 'post_purchase_offer' );
	}

	public function post_purchase_offer_Admin_enqueue_scripts() {
		$current_screen = get_current_screen();

		if ( $current_screen && in_array( $current_screen->id, $this->get_tab_screen_ids() ) ) {
			wp_enqueue_style('prc_admins', plugins_url('../includes/css/addf_ppp_admin.css', __FILE__), array(), '1.0');
			wp_enqueue_script('prc_admin', plugins_url('../includes/js/addf_ppp_admin.js', __FILE__), array( 'jquery' ), '1.0', $in_footer = false);
			wp_enqueue_script('jquery');

			// Enqueue Select2 JS CSS.
			wp_enqueue_style('select2', plugins_url('assets/css/select2.css', WC_PLUGIN_FILE), array(), '5.7.2');
			wp_enqueue_script('select2', plugins_url('assets/js/select2/select2.min.js', WC_PLUGIN_FILE), array( 'jquery' ), '4.0.3', true);
		}
	}

 
 // Add custom settings tab for post purchase
	public function post_purchase_offer_custom_settings_tab( $settings_tabs ) {
				$settings_tabs['post_purchase_offer_setting'] = __('Post Purchase Offer ', 'woocommerce');
				return $settings_tabs;
	}

 // Add custom settings fields for post purchase
	public function post_purchase_offer_custom_settings_fields() {

		// Get all custom post titles of post purchase 
		$custom_posts = get_posts(array(
			'post_type' => 'post_purchase_offer',
			'posts_per_page' => -1, // Get all posts
			'fields' => 'ids', // Return only post IDs to improve performance
		));

		// Initialize an empty array to store unique titles
		$unique_titles = array();

		// Loop through the custom posts
		foreach ($custom_posts as $post_id) {
			$post_title = get_the_title($post_id); // Get the post title
			// Check if the title is not already in the array, then add it
			if (!in_array($post_title, $unique_titles)) {
					 $unique_titles[] = array(
						 'post_id' => $post_id,
						 'post_title' => $post_title,
					 );
			}
		}
	   
	  
		
		?>
	<input type="hidden" name="afppo-ajax-nonce" value="<?php echo esc_attr( $this->nonce ); ?>" />
	
	<h2><?php esc_html_e( 'Post Purchase Offer Setting', 'woo_addf_ppp' ); ?></h2>
	<p><?php esc_html_e( 'Configure settings for post-purchase offers.', 'woo_addf_ppp' ); ?></p>

	<table class="form-table">
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Show/Hide Offer', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
				<label class="toggle-switch" for="custom_show_hide_offer">
					<input type="checkbox" id="custom_show_hide_offer" name="custom_show_hide_offer" <?php esc_html_e(checked( get_option( 'custom_show_hide_offer' ), 'yes' )); ?> value="<?php echo esc_html_e(get_option( 'custom_show_hide_offer' )); ?>" />
					<div class="toggle-slider"></div>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to display the post-purchase offer, disable to hide it.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Prevent Coupons Discount', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
				<label class="toggle-switch" for="prevent_coupons">
					<input type="checkbox" id="prevent_coupons" name="prevent_coupons" value="<?php echo esc_html_e(get_option( 'prevent_coupons' )); ?>" <?php esc_html_e(checked( get_option( 'prevent_coupons' ), 'yes')); ?> />
					<div class="toggle-slider"></div>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to prevent coupons discount on post-purchase offer products, disable to allow coupons discount.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Product Category', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
				<label class="toggle-switch" for="offer_product_category">
					<input type="checkbox" id="offer_product_category" name="offer_product_category" value="<?php echo esc_html_e(get_option( 'offer_product_category' )); ?>" <?php esc_html_e(checked( get_option( 'offer_product_category' ), 'yes')); ?> />
					<div class="toggle-slider"></div>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to display the categories on post-purchase offer products, disable to hide them.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Product Quantity', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
				<label class="toggle-switch" for="offer_product_quantity">
					<input type="checkbox" id="offer_product_quantity" name="offer_product_quantity" value="<?php echo esc_html_e(get_option( 'offer_product_quantity' )); ?>" <?php esc_html_e(checked( get_option( 'offer_product_quantity' ), 'yes')); ?> />
					<div class="toggle-slider"></div>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to display the quantity on the post-purchase offer product, disable to hide it.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>

		 <tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Product Stock', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
				<label class="toggle-switch" for="offer_product_stock">
					<input type="checkbox" id="offer_product_stock" name="offer_product_stock" value="<?php echo esc_html_e(get_option( 'offer_product_stock' )); ?>" <?php esc_html_e(checked( get_option( 'offer_product_stock' ), 'yes')); ?> />
					<div class="toggle-slider"></div>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to display the stock of the post-purchase offer product, disable to hide it.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>

		 <tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Product Sku', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
				<label class="toggle-switch" for="offer_product_sku">
					<input type="checkbox" id="offer_product_sku" name="offer_product_sku" value="<?php echo esc_html_e(get_option( 'offer_product_sku' )); ?>" <?php esc_html_e(checked( get_option( 'offer_product_sku' ), 'yes')); ?> />
					<div class="toggle-slider"></div>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to display the quantity of the post-purchase offer product, disable to hide it.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>
		 <tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Product Description', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
				<label class="toggle-switch" for="offer_description">
					<input type="checkbox" id="offer_description" name="offer_description" value="<?php echo esc_html_e(get_option( 'offer_description' )); ?>" <?php esc_html_e(checked(get_option( 'offer_description' ), 'yes')); ?> />
					<div class="toggle-slider"></div>
				</label>
				<p class="description"><?php esc_html_e( 'Enable to show the post-purchase offer description, disable to hide it.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>
		<tr valign="top" id="offer_description_lenght_tr" style="display:none;">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Product description Length', 'woo_addf_ppp' ); ?></th>
			<td class="forminp">
			   
				<input type="number" id="offer_description_lenght" name="offer_description_lenght" min="10" value="<?php echo esc_html_e(get_option( 'offer_description_lenght' )); ?>" />
				  
				<p class="description"><?php esc_html_e( 'Lenght of post-purchase offer description.', 'woo_addf_ppp' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
	}


// Save custom settings fields post purchase offer
	public function post_purchase_offer_save_custom_settings_fields() {

	  $nonce = isset( $_POST['afppo-ajax-nonce'] ) ? sanitize_text_field(wp_unslash( $_POST['afppo-ajax-nonce'] ) ): '';

		if ( ! wp_verify_nonce( $nonce, 'afppo-ajax-nonce' ) ) {
			die( esc_html__( 'Failed ajax security check!', 'woo_addf_ppp' ) );
		}
	
		$prevent_copans = isset( $_POST['prevent_coupons'] ) ? 'yes' : 'no';
		$show_hide_offer = isset( $_POST['custom_show_hide_offer'] ) ? 'yes' : 'no';
		$offer_description = isset( $_POST['offer_description'] ) ? 'yes' : 'no';
		 $offer_product_category = isset( $_POST['offer_product_category'] ) ? 'yes' : 'no';
		  $offer_product_quantity = isset( $_POST['offer_product_quantity'] ) ? 'yes' : 'no';
		   $offer_product_stock = isset( $_POST['offer_product_stock'] ) ? 'yes' : 'no';
		   $offer_product_sku = isset( $_POST['offer_product_sku'] ) ? 'yes' : 'no';
		   // $offer_description_lenght = intval( $_POST['offer_description_lenght'] );
		   $offer_description_lenght =isset( $_POST['offer_description_lenght'] ) ? sanitize_text_field( wp_unslash( $_POST['offer_description_lenght'] ) ) : '';




		update_option( 'prevent_coupons', $prevent_copans );
		update_option( 'custom_show_hide_offer', $show_hide_offer );
		 update_option( 'offer_description', $offer_description );
		  update_option( 'offer_product_category', $offer_product_category );
		   update_option( 'offer_product_quantity', $offer_product_quantity );
			update_option( 'offer_product_stock', $offer_product_stock );
			 update_option( 'offer_product_sku', $offer_product_sku );
			 update_option( 'offer_description_lenght', $offer_description_lenght );
		// update_option( 'selected_offers', $selected_offers );
	}

	public function post_purchase_offer_register_woocommerce_menu() {
			add_submenu_page(
				'woocommerce',
				esc_html__('Post Purchase Offer ', 'woo_addf_ppp'),
				esc_html__('Post Purchase Offer', 'woo_addf_ppp'),
				'manage_options',
				'edit.php?post_type=post_purchase_offer'
			);
	}


	public function post_purchase_offer_meta_box() {
			
			add_meta_box(
				'offer product',
				esc_html__('Select Offer Product', 'woo_addf_ppp'),
				array( $this, 'post_purchase_offers_main_metabox_cb' ),
				'post_purchase_offer'
			);

			add_meta_box(
				'add rule on offer products',
				esc_html__('Add Rule On Offer Products', 'woo_addf_ppp'),
				array( $this, 'post_purchase_rule' ),
				'post_purchase_offer'
			);

			add_meta_box(
				'description post purchase offer',
				esc_html__('Description', 'woo_addf_ppp'),
				function () {
					ob_start();
					$this->post_purchase_offers_description();
					echo wp_kses_post(ob_get_clean());
				},
				'post_purchase_offer'
			);
	}



	public function post_purchase_offers_description() {
	$offer_description = get_post_meta(get_the_ID(), 'offer_description', true);
		?>
	<table class="addf_prc_table">
		<tr>
			<td>
				<?php
				$settings = array(
					'textarea_name' => 'offer_description',
					'editor_height' => 200,
					'media_buttons' => false,
					'tinymce'       => array(
						'toolbar1' => 'bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link unlink | charmap',
						'toolbar2' => '',
						'content_css' => get_stylesheet_directory_uri() . '/editor-style.css',
					),
					'quicktags'     => true,
					'drag_drop_upload' => true,
					'editor_class'  => 'offer-description-editor',
					'teeny'         => false,
					'dfw'           => false,
					'tinymce_adv'   => true,
				);
				?>
				<div id="offer_description_editor">
					<?php wp_editor($offer_description, 'offer_description', $settings); ?>
				</div>
				<p class="description"><?php echo esc_html__('Description about post-purchase offer', 'woo_addf_ppp'); ?></p>
			</td>
		</tr>
	</table>
	<?php
	}
	 


	public function post_purchase_rule() {
		?>
									<table class="addf_prc_table">

										<tr>
											<td class="addf_prc_text-td">
												<p class="addf_prc_heading">
													<?php echo esc_html__('User role', 'woo_addf_ppp'); ?>
												</p>
											</td>
											<td>
												<?php
												$roles = get_editable_roles();
												$selected_roles = get_post_meta(get_the_ID(), 'user_roles_select', true);
												
												?>
												<select id="user_roles_select" data-placeholder="<?php echo esc_html__(' Select user roles', 'woo_addf_ppp'); ?>"  name="user_roles_select[]" multiple="multiple" style="width: 70%;">
													<?php
													foreach ($roles as $role_name => $role_info) {
														if ('' === $selected_roles ) {
														echo '<option value="' . esc_attr__($role_name) . '">' . esc_attr__($role_info['name']) . '</option>';
														} else {
														$selected = in_array($role_name, $selected_roles) ? 'selected' : '';
														echo '<option value="' . esc_attr__($role_name) . '" ' . esc_attr__($selected) . '>' . esc_attr__($role_info['name']) . '</option>';
														}
					
													}
													?>
											   </select>
												
												<!-- for product cat -->

												<br>
												<p class="description"><?php echo esc_html__("Display a post-purchase offer based on the user's roles.", 'woo_addf_ppp'); ?></p>

											</td>
										</tr>

										<tr>
										<td class="addf_prc_text-td">
											<p class="addf_prc_heading">
													<?php echo esc_html__('Last order status', 'woo_addf_ppp'); ?>
											</p>
										</td>
										<td>
												<?php
												$order_statuses = wc_get_order_statuses();
												$selected_statuses = get_post_meta(get_the_ID(), 'order_statuses_select', true);

												?>
											<select id="order_statuses_select" data-placeholder="<?php echo esc_html__(' Select order statuses', 'woo_addf_ppp'); ?>" name="order_statuses_select[]" multiple="multiple" style="width: 70%;">
												<?php
												foreach ($order_statuses as $status_key => $status_name) {
													if ('' === $selected_statuses) {
														echo '<option value="' . esc_attr__($status_key) . '">' . esc_attr__($status_name) . '</option>';
													} else {
													$selected = in_array($status_key, $selected_statuses) ? 'selected' : '';
													echo '<option value="' . esc_attr__($status_key) . '" ' . esc_attr__($selected ) . '>' . esc_attr__($status_name) . '</option>';
													}
												}
												?>
											</select>

											<br>
											<p class="description"><?php echo esc_html__("Display a post-purchase offer when the selected statuses match the status of the user's last order.", 'woo_addf_ppp'); ?></p>
										</td>
									</tr>

										<tr>
											<td class="addf_prc_text-td">
												<p class="addf_prc_heading">
													<?php echo esc_html__('Country', 'woo_addf_ppp'); ?>
												</p>
											</td>
											<td>
												<?php
												$countries = WC()->countries->get_countries();
												$selected_countries = get_post_meta(get_the_ID(), 'user_countries_select', true);
												?>
												<select id="user_countries_select" data-placeholder="<?php echo esc_html__(' Select countries', 'woo_addf_ppp'); ?>" name="user_countries_select[]" multiple="multiple" style="width: 70%;">
													<?php
													foreach ($countries as $country_code => $country_name) {
														if ('' === $selected_countries) {
														echo '<option value="' . esc_attr__($country_code) . '">' . esc_attr__($country_name) . '</option>';
														} else {
														$selected = in_array($country_code, $selected_countries) ? 'selected' : '';
													   echo '<option value="' . esc_attr__($country_code) . '" ' . esc_attr__($selected) . '>' . esc_attr__($country_name) . '</option>';
														}
													}
													?>
												</select>
												
												<!-- for product cat -->

												<br>
												<p class="description"><?php echo esc_html__("Display a post-purchase offer when the selected countries match the user's country.", 'woo_addf_ppp'); ?></p>

											</td>
										</tr>
										
										<tr>
											<td class="addf_prc_text-td">
												<p class="addf_prc_heading">
													<?php echo esc_html__('Categories in last order', 'woo_addf_ppp'); ?>
												</p>
											</td>
											<td>
													<?php
												
													$addf_prc_p_cat = get_post_meta(get_the_ID(), 'addf_prc_categories', true);
													$addf_prc_p_cat = is_array($addf_prc_p_cat) ? $addf_prc_p_cat : array();

													$args = array(
														'taxonomy' => 'product_cat',
														'hide_empty' => false,
													// 'parent'  => 0
													);
													$product_cat = get_terms($args);

													?>
												<!-- for product cat (addf_prc_categories) -->
												
												<select data-placeholder="<?php echo esc_html__(' Select Categories', 'woo_addf_ppp'); ?>" name="addf_prc_categories[]" id="addf_prc_categories" multiple tabindex="-1" style="width:70%" >
													<?php foreach ($product_cat as $parent_product_cat) : ?>
																<option value="<?php echo esc_attr($parent_product_cat->term_id); ?>" 
																	<?php
																	if (!empty($addf_prc_p_cat) && in_array($parent_product_cat->term_id, $addf_prc_p_cat)) {
																		echo 'selected';
																	}
																	?>
																	>
																	<?php echo esc_html__($parent_product_cat->name, 'woo_addf_ppp'); ?>
																</option>
													<?php endforeach ?>
												</select>
												
												<!-- for product cat -->

												<br>
												<p class="description"><?php echo esc_html__("Display a post-purchase offer when the selected categories match the categories of products in the user's last order.", 'woo_addf_ppp'); ?></p>

											</td>
										</tr>

										<tr>
											<td class="addf_prc_text-td">
												<p class="addf_prc_heading">
														<?php echo esc_html__('Products in last order', 'woo_addf_ppp'); ?>
												</p>
											</td>
											<td colspan="2">
												<input class="addf_prc_input" type="hidden" ><br>
												<select name="addf_prc_product[]"   data-placeholder="<?php echo esc_html__(' Select Products', 'woo_addf_ppp'); ?>" class="js_multipage_select_product chosen-select " multiple="multiple" tabindex="-1" style="width:70%" >;								
														<?php
														$addf_prc_specific_product = get_post_meta(get_the_ID(), 'addf_prc_product', true);
														if (!empty($addf_prc_specific_product)) {
															foreach ($addf_prc_specific_product as $pro) {
																$prod_post = get_post($pro);
																?>
																			<option value="<?php echo intval($pro); ?>" selected="selected"><?php echo esc_html__($prod_post->post_title, 'woo_addf_ppp'); ?></option>
																			<?php
															}
														}
														?>
												</select>
												<p class="description"><?php echo esc_html__("Display a post-purchase offer when the selected products match the products in the user's last order.", 'woo_addf_ppp'); ?></p>
											</td>
										</tr>

										<tr>
											<td class="addf_prc_text-td">
												<p class="addf_prc_heading">
													<?php echo esc_html__('Minimum previous total amount', 'woo_addf_ppp'); ?>
												</p>
											</td>
											<td colspan="2">
													<?php
													$previous_total_amount = get_post_meta(get_the_ID(), 'previous_total_amount', true);
													?>
												 <input type="number" id="previous_total_amount" name="previous_total_amount" value="<?php echo esc_attr($previous_total_amount); ?>" style="width:70%"  placeholder="<?php echo esc_html__(' Enter minimum previous total amount', 'woo_addf_ppp'); ?>">
												
												<p class="description"><?php echo esc_html__("Display a post-purchase offer when the entered amount is equal to or greater than the user's previous total order amount.", 'woo_addf_ppp'); ?></p>
											</td>
										<tr>
											<td class="addf_prc_text-td">
												<p class="addf_prc_heading">
													<?php echo esc_html__('Minimum last purchase amount', 'woo_addf_ppp'); ?>
												</p>
											</td>
											<td colspan="2">
												<?php
												$last_purchase_amount = get_post_meta(get_the_ID(), 'last_purchase_amount', true);
												?>
												
												 <input type="number" id="last_purchase_amount" name="last_purchase_amount" value="<?php echo esc_attr($last_purchase_amount); ?>" style="width:70%"  placeholder="<?php echo esc_html__(' Enter minimum last purchase amount', 'woo_addf_ppp'); ?>">
												
												<p class="description"><?php echo esc_html__("Display a post-purchase offer when the entered amount is equal to or greater than the user's last order total amount."); ?></p>
											</td>
										</tr>


									</table>

										<?php
	}


	public function post_purchase_offers_main_metabox_cb() {
		
		?>
								   <input type="hidden" name="afppo-ajax-nonce" value="<?php echo esc_attr( $this->nonce ); ?>" />
									<table class="addf_prc_table">
										

										<tr>
											<td class="addf_prc_text-td">
												<p class="addf_prc_heading">
													<?php echo esc_html__('Select offer products', 'woo_addf_ppp'); ?>
												</p>
											</td>
											<td colspan="2">
												<select name="addf_offer_product[]"   data-placeholder="<?php echo esc_html__(' Select Products', 'woo_addf_ppp'); ?>" class="js_multipage_select_product_offer chosen-select " multiple="multiple" tabindex="-1" style="width:70%" >;								
													<?php
													$pro1 = '';
													$addf_offer_specific_product = get_post_meta(get_the_ID(), 'addf_offer_product', true);
													if (!empty($addf_offer_specific_product)) {
														foreach ($addf_offer_specific_product as $pro1) {
															$prod_post = get_post($pro1);
															?>
																			<option value="<?php echo intval($pro1); ?>" selected="selected"><?php echo esc_html__($prod_post->post_title, 'woo_addf_ppp'); ?></option>
																			<?php
														}
													}
													?>
												</select>
												<input type="button" class="add_options" data= "<?php echo intval($pro1); ?>" value="Add">
												<p class="description"><?php echo esc_html__('Select products to include in the post-purchase offer.', 'woo_addf_ppp'); ?></p>
											</td>
										</tr>


									</table>
									<div class="addf_prc_table_add_new_btn align_right" style="display: none">
										
									</div>

									<table class=" wp-list-table widefat fixed striped table-view-list responsive-table" style="display:noe;">
								  <thead>
									<tr>
									  <th scope="col">Name</th>
									  <th scope="col">Discount Type</th>
									   <th scope="col">Minimum Quantity </th>
									  <th scope="col">Product Price</th>
									  <th scope="col">Discount Price</th>
									  <th scope="col">Action</th>
									</tr>

										<?php 
										$post_purchase_select_products = get_post_meta( get_the_ID(), 'post_purchase_select_products', true );

										?>
								  </thead>
								  <tbody>
									   <?php 
										if (is_array($post_purchase_select_products)) {
											foreach ($post_purchase_select_products as $item) { 
												   $product_price = get_post_meta($item['product_id'], '_price', true);
												?>
								<tr>
									<td class="table-value">
													  <?php echo esc_html__($item['product_title']); ?> 
										<input name="post_purchase_select_products['<?php echo esc_attr__($item['product_id']); ?>'][product_id]" value="<?php echo esc_attr__($item['product_id']); ?>" type="hidden" >
										<input name="post_purchase_select_products['<?php echo esc_attr__($item['product_id']); ?>'][product_title]" value="<?php echo esc_html__($item['product_title']); ?>" type="hidden" >
									</td>
									<td class="table-value">
										<select name="post_purchase_select_products['<?php echo esc_attr__($item['product_id']); ?>'][discount_type]" class="wc-enhanced-select price_type_<?php echo esc_attr__($item['product_id']); ?>" aria-hidden="true">
												  <option value="p_d" 
												  <?php 
													if ('p_d' === $item['discount_type']) {
echo 'selected';} 
													?>
													>Percentage discount</option>
												  <option value="f_d" 
												  <?php 
													if ('f_d' === $item['discount_type']) {
echo 'selected';} 
													?>
													>Fixed discount</option>
												  <option value="f_p" 
												  <?php 
													if ('f_p' === $item['discount_type']) {
echo 'selected';} 
													?>
													>Fixed price</option>
										</select>
									</td>
									<td class="table-value">
										<input name="post_purchase_select_products['<?php echo esc_attr__($item['product_id']); ?>'][custom_quantity]" type="number" value="<?php echo esc_attr__($item['custom_quantity']); ?>" min="1">
									</td>
									 <td class="table-value">
										<input type="number" value="<?php echo esc_attr__($product_price); ?>" readonly>
									</td>
									<td class="table-value">
										<input class="discount_amount" data-product-id ="<?php echo esc_attr__($item['product_id']); ?>" data-product-amount ="<?php echo esc_attr__($product_price); ?>" name="post_purchase_select_products['<?php echo esc_attr__($item['product_id']); ?>'][discount_amount]" type="number" value="<?php echo esc_attr__($item['discount_amount']); ?>" min="0">
									</td>
								   
									<td class="table-value"><button class="delete-button">X</button></td>
								</tr>
											<?php } } ?>
								  </tbody>
								</table>

									<?php
	}


	public function post_purchase_offer_save_metaData( $post_id, $post ) {

		if ( ! empty( $_POST['action'] ) && 'editpost' == $_POST['action'] && 'post_purchase_offer' == $post->post_type) {

			$nonce = isset( $_POST['afppo-ajax-nonce'] ) ? sanitize_text_field(wp_unslash( $_POST['afppo-ajax-nonce'] ) ): '';

			if ( ! wp_verify_nonce( $nonce, 'afppo-ajax-nonce' ) ) {
				die( esc_html__( 'Failed ajax security check!', 'woo_addf_ppp' ) );
			}
	

			
			// save product
		   $addf_prc_product = isset($_POST['addf_prc_product']) ? sanitize_meta('', wp_unslash($_POST['addf_prc_product']), '') : array();
			update_post_meta($post_id, 'addf_prc_product', $addf_prc_product);
			
			// save product_offer
			$addf_offer_product = isset($_POST['addf_offer_product']) ? sanitize_meta('', wp_unslash($_POST['addf_offer_product']), '') : array();
			update_post_meta($post_id, 'addf_offer_product', $addf_offer_product);

			
		
			// save posts cats
		   $addf_prc_categories = isset($_POST['addf_prc_categories']) ? sanitize_meta('', wp_unslash($_POST['addf_prc_categories']), '') : array();
		   update_post_meta($post_id, 'addf_prc_categories', $addf_prc_categories);
		
			// save posts user role
		   $user_roles_select = isset($_POST['user_roles_select']) ? sanitize_meta('', wp_unslash($_POST['user_roles_select']), '') : array();
		   update_post_meta($post_id, 'user_roles_select', $user_roles_select);
		

			// save posts country
		  $user_countries_select = isset($_POST['user_countries_select']) ? sanitize_meta('', wp_unslash($_POST['user_countries_select']), '') : array();
		  update_post_meta($post_id, 'user_countries_select', $user_countries_select);
		

		// save posts order status
		 $order_statuses_select = isset($_POST['order_statuses_select']) ? sanitize_meta('', wp_unslash($_POST['order_statuses_select']), '') : array();
		  update_post_meta($post_id, 'order_statuses_select', $order_statuses_select);
		
		// save posts previous total amount
		$previous_total_amount = isset($_POST['previous_total_amount']) ? sanitize_text_field( wp_unslash($_POST['previous_total_amount']) ) : '';
		update_post_meta($post_id, 'previous_total_amount', $previous_total_amount);


		// save posts last purchase amount
		$last_purchase_amount = isset($_POST['last_purchase_amount']) ? sanitize_text_field( wp_unslash($_POST['last_purchase_amount']) ) : '';
		update_post_meta($post_id, 'last_purchase_amount', $last_purchase_amount);
		
		// save posts desc
		$offer_description = isset($_POST['offer_description']) ? sanitize_text_field( wp_unslash($_POST['offer_description']) ) : '';
		update_post_meta($post_id, 'offer_description', $offer_description);

		
		$post_purchase_select_products = isset($_POST['post_purchase_select_products']) ? sanitize_meta('', wp_unslash($_POST['post_purchase_select_products']), '') : array();
		  update_post_meta($post_id, 'post_purchase_select_products', $post_purchase_select_products);

	
		}
	}


	public function post_purchase_offer_getproductsearch_cb() {
			$return = array();
		if (isset($_GET['q'])) {
			$search = sanitize_text_field(wp_unslash($_GET['q']));
		}
		if (
				isset($_POST['search_fields_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['q'], 'search_fields_nonce')))
			) {
			die(esc_html__('Sorry, your nonce did not verify.', 'woo_addf_ppp'));
		}
			$search_results = new WP_Query(
				array(
					's' => $search,
					'post_type' => array( 'product' ),
					'post_status' => 'publish',
					'posts_per_page' => -1,
				)
			);
		if ($search_results->have_posts()) :
			while ($search_results->have_posts()) :
				$search_results->the_post();
				// print_r($search_results);
				// echo $search_results;
				global $product;
				//For variable products, get variations
				if ($product->is_type('variable')) {
					$variations = $product->get_available_variations();
					foreach ($variations as $variation) {
						$variation_obj = wc_get_product($variation['variation_id']);
						if ($variation_obj->is_in_stock() && !empty($variation_obj->get_price())) {
							$variation_title = $variation_obj->get_name();
							$return[] = array( $variation_obj->get_id(), $variation_title );
						}
					}
				} elseif ($product->is_type('simple')) { // Use elseif here
					// For simple products
					if (( $product->is_on_backorder() !== '' ) || ( $product->is_in_stock() !== '' && $product->get_price() !== '' )) {
						$title = ( mb_strlen($search_results->post->post_title) > 50 ) ? mb_substr($search_results->post->post_title, 0, 49) . '...' : $search_results->post->post_title;
						$return[] = array( $search_results->post->ID, $title );
					}
				}
				endwhile;
			endif;
			wp_send_json($return);
	}


	public function post_purchase_offer_getproductsearch_cb_2() {
			$return = array();
		if (isset($_GET['q'])) {
			$search = sanitize_text_field(wp_unslash($_GET['q']));
		}
		if (
				isset($_POST['search_fields_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['q'], 'search_fields_nonce')))
			) {
			die(esc_html__('Sorry, your nonce did not verify.', 'woo_addf_ppp'));
		}
			$search_results = new WP_Query(
				array(
					's' => $search,
					'post_type' => array( 'product' ),
					'post_status' => 'publish',
					'posts_per_page' => -1,
				)
			);
		if ($search_results->have_posts()) :
			while ($search_results->have_posts()) :
				$search_results->the_post();
				// print_r($search_results);
				// echo $search_results;
				global $product;
				//For variable products, get variations
				if ($product->is_type('variable')) {
					$variations = $product->get_available_variations();
					foreach ($variations as $variation) {
						$variation_obj = wc_get_product($variation['variation_id']);
						if ($variation_obj->is_in_stock() && !empty($variation_obj->get_price())) {
							$variation_title = $variation_obj->get_name();
							$return[] = array( $variation_obj->get_id(), $variation_title, $variation_obj->get_price() );
						}
					}
				} elseif ($product->is_type('simple')) { // Use elseif here
					// For simple products
					if (( $product->is_on_backorder() !== '' ) || ( $product->is_in_stock() !== '' && $product->get_price() !== '' )) {
						$title = ( mb_strlen($search_results->post->post_title) > 50 ) ? mb_substr($search_results->post->post_title, 0, 49) . '...' : $search_results->post->post_title;
						$return[] = array( $search_results->post->ID, $title, $product->get_price() );
					}
				}
				endwhile;
			endif;
			wp_send_json($return);
	}
}
new Addify_Post_Purchase_Offer_Admin();