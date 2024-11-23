<?php
defined('ABSPATH') || exit;

class Addify_Post_Purchase_Offer_Front {
	
 public $offer_product_category;
 public $offer_product_quantity;
 public $offer_product_stock;
 public $offer_product_sku;
 public $offer_product_description;
 public $offer_product_description_lenght;

	public function __construct() {

		$this->offer_product_category = get_option( 'offer_product_category' );
		$this->offer_product_quantity = get_option( 'offer_product_quantity' );
		$this->offer_product_stock = get_option( 'offer_product_stock' );
		$this->offer_product_sku = get_option( 'offer_product_sku' );
		$this->offer_product_description = get_option( 'offer_description' );
		$this->offer_product_description_lenght = get_option( 'offer_description_lenght' );

	   // add css and js
	   add_action('wp_enqueue_scripts', array( $this, 'addf_offers_enqueue_scripts_front' ));

	   // Show discount offer shorcode
	   add_shortcode('display-discount-offer', array( $this, 'display_discount_offer_checked' ));

	   // After ajax call at to cart products show the custom price from offer page to cart page
	   add_action( 'woocommerce_before_calculate_totals', array( $this, 'add_custom_price' ));
	
	   //check quantity with custom qunatity restrict_cart_quantity
	   add_filter('woocommerce_update_cart_validation', array( $this, 'restrict_cart_quantity' ), 10, 4);

	   // After they place an order, the show post purchase offer content here
	   add_action('woocommerce_thankyou', array( $this, 'add_custom_content_after_purchase_on_order_page' ));

	   //restrict coupan code on offer products
		$prevent_coupons = get_option( 'prevent_coupons' );
	   // if the show/hide checkbox checked and
		if ('yes' === $prevent_coupons) {
	  // echo "<script>alert('Your message here');</script>";
	add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'restrict_coupon_for_specific_products' ), 10, 4 );
		add_filter('woocommerce_coupon_get_discount_amount', array( $this, 'set_zero_discount_for_fixed_cart_coupons' ), 10, 5);
		
		}
	}



	public function addf_offers_enqueue_scripts_front() {
			wp_enqueue_style('ppp_front', plugins_url('../includes/css/addf_ppp_style_front.css', __FILE__), array(), '1.0');
			wp_enqueue_script('ppp_front', plugins_url('../includes/js/addf_ppp_front.js', __FILE__), array( 'jquery' ), '1.0', false);
			wp_enqueue_script('jquery');

			wp_localize_script('ppp_front', 'php_var', array( 
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('afppo-ajax-nonce'),
			));
	}


	public function restrict_cart_quantity( $passed, $cart_item_key, $values, $quantity ) {
		if ( isset( $values['custom_quantity'] ) && $quantity < (float) $values['custom_quantity']) {
			wc_add_notice( 'Minimum quantity of post purchase offer for product ' . $values['data']->get_name() . ' is ' . $values['custom_quantity'] , 'error');
			$passed =  false;
		}
		return $passed;
	}


	public function add_custom_price( $cart_object ) {

		// to show custom price
		foreach ( $cart_object->cart_contents as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['custom_price'] ) ) {
				$cart_item['data']->set_price( $cart_item['custom_price'] );

			}
		}
	}


	public function get_offer_product_ids_from_cart( $cart ) {
		$restricted_product_ids = array();
	
		foreach ($cart as $item_key => $item) {
			if (!empty($item['custom_is_offer_product']) && 'is_offer_product' === $item['custom_is_offer_product']) {
			$product_id = $item['product_id'];
	
				if (!empty($item['variation_id'])) {
					// For variable products, add both the parent and variation IDs
					$restricted_product_ids[] = $product_id;
					$restricted_product_ids[] = $item['variation_id'];
				} else {
				
					$restricted_product_ids[] = $product_id;
				}
			}
		}
	
		return $restricted_product_ids;
	}


	public function set_zero_discount_for_fixed_cart_coupons( $discount, $price, $cart_item, $single, $coupon ) {
	   // Array of product IDs where coupons should have zero discount
		$cart = WC()->cart->get_cart(); // Assuming WooCommerce cart
		$restricted_product_ids = $this->get_offer_product_ids_from_cart($cart);

		// Check if the coupon has a discount type of 'fixed_cart' and the product ID is in the excluded list
		if (in_array($cart_item['product_id'], $restricted_product_ids)) {
			// Set the discount amount to zero
			return 0;
		}

		// Return the original discount amount for other types of coupons and products
		return $discount;
	}

	public function restrict_coupon_for_specific_products( $valid, $product, $coupon, $values ) {
		// Array of product IDs to restrict coupon for

		$cart = WC()->cart->get_cart(); // Assuming WooCommerce cart
		$restricted_product_ids = $this->get_offer_product_ids_from_cart($cart);

		// echo '<pre>';
		// print_r($restricted_product_ids );

		// Check if the current product ID is in the restricted array
		if (in_array($product->get_id(), $restricted_product_ids)) {

			$valid = false; // Disallow the coupon for this product
			// wc_add_notice('Coupons cannot be applied to offer products like ' . $product->get_title(), 'error');
		}

		return $valid;
	}


	public function get_total_purchase_amount() {
			// Get the current user's ID
			$user_id = get_current_user_id();

			$total_purchase_amount = wc_get_customer_total_spent($user_id);
				
			// Get all orders for the current user
			$orders = wc_get_orders(array(
				'customer_id' => $user_id,
				'status' => array( 'completed', 'processing' ), // Include only completed and processing orders
				'orderby' => 'date',
				'order' => 'DESC',
				'limit' => 1, // Limit to 1 order to get the latest order
			));
				
			   
			$last_purchase_amount = 0;
				
		foreach ($orders as $order) {
			$last_purchase_amount = floatval($order->get_total());
		}
				
			// Return the total purchase amount and last purchase amount
			return array(
				'total_amount' => $total_purchase_amount,
				'last_purchase_amount' => $last_purchase_amount,
			);
	}


	public function get_current_user_role() {
		$current_user = wp_get_current_user();
		$user_roles = $current_user->roles;
		if (!empty($user_roles) && isset($user_roles[0])) {
			return $user_roles[0];
		}
		return false;
	}


	public function get_last_order_status() {
		// Get the current user ID
		$current_user_id = get_current_user_id();

		// Get the last order ID for the user
		$order_id = wc_get_customer_last_order( $current_user_id );

		// Get the order object
		$order = wc_get_order( $order_id );

		// Get the order status
		if ( $order ) {
			$order_status = $order->get_status();
			return $order_status;
			
		}

		return ''; // No orders found
	}
		

	public function get_user_country() {

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;

			
		$billing_country = get_user_meta($user_id, 'shipping_country', true);

		$geo_data = WC_Geolocation::geolocate_ip();
		$country = $geo_data['country'];

		if (''===$country) {
			return $billing_country;
		} else {
			return $country;
;
		}
	}




	public function get_product_ids_from_last_order() {
	// Get the current user
	$current_user = wp_get_current_user();

	// Get the last order for the user
	$last_order = wc_get_customer_last_order($current_user->ID);

		if (!$last_order) {
			return array(); // No last order found
		}

	// Get the order items
	$order_items = $last_order->get_items();

	$product_ids = array(); // Initialize array to store product IDs

		foreach ($order_items as $item_id => $item) {
			$product_id = $item->get_product_id();
			$product = $item->get_product();

			// If the product is a variation, add both the product ID and variation ID
			if ($product->is_type('variation')) {
				$variation_id = $product->get_id();
				$product_ids[] = $product_id;
				$product_ids[] = $variation_id;
			} else {
				$product_ids[] = $product_id; // Add only the product ID
			}
		}

	return $product_ids; // Return array of product IDs and variation IDs
	}

	


	public function get_product_ids_from_last_order_by_category() {
		$category_ids = array(); // Initialize $category_ids as an empty array

		// Get the current user's ID
		$customer_id = get_current_user_id();

		// Get the user's last order
		$orders = wc_get_orders(array(
			'limit' => 1,
			'customer' => $customer_id,
			'return' => 'ids',
			'orderby' => 'date',
			'order' => 'DESC',
		));

		if (!empty($orders)) {
			$order_id = reset($orders);
			$order = wc_get_order($order_id);

			// Loop through order items
			foreach ($order->get_items() as $item_id => $item) {
				$product_id = $item->get_product_id();
				$product_categories = wp_get_post_terms($product_id, 'product_cat', array( 'fields' => 'ids' ));

				if ($product_categories && !is_wp_error($product_categories)) {
					$category_ids = array_merge($category_ids, $product_categories);
				}
			}
		}

		// Get all subcategories of the retrieved categories
		$all_category_ids = array();
		foreach ($category_ids as $cat_id) {
			$all_category_ids[] = $cat_id;
			$all_category_ids = array_merge($all_category_ids, get_term_children($cat_id, 'product_cat'));
		}

		// Remove duplicate category IDs
		$all_category_ids = array_unique($all_category_ids);

		return $all_category_ids;
	}



	
	

	public function get_category_ids_from_last_order_by_categories( $selected_categories_ids ) {
					
			// Get all products IDs from the specified category IDs
			$product_ids = get_posts(array(
				'posts_per_page' => -1,
				'post_type' => 'product',
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
						'field' => 'term_id',
						'terms' => $selected_categories_ids,
						'operator' => 'IN',
					),
				),
				'fields' => 'ids',
			));

			// Initialize an empty array to store both product and variant IDs
			$product_and_variant_ids = array();

			// Loop through each product ID to get variant IDs
		foreach ($product_ids as $product_id) {
			// Get product variations
			$product = wc_get_product($product_id);
			if ($product && $product->is_type('variable')) {
				$variation_ids = $product->get_children();
				$product_and_variant_ids = array_merge($product_and_variant_ids, array( $product_id ), $variation_ids);
			} else {
				$product_and_variant_ids[] = $product_id;
			}
		}
	
		return $product_and_variant_ids;
	}
	

	
	
	
	
 

	public function add_custom_content_after_purchase_on_order_page( $order_id ) {
		// Get the order object
		$order = wc_get_order($order_id);

		// Get the user's last purchase
		$last_order_id = wc_get_customer_last_order($order->get_customer_id());
		$last_order = wc_get_order($last_order_id);

		// Output your custom HTML content
		// echo '<div class="custom-content">Your custom HTML content goes here</div>';

		// // Display the order details
		// echo '<h2>' . __('Order Details', 'woocommerce') . '</h2>';
		// echo '<p>' . __('Order Number:', 'woocommerce') . ' ' . $order->get_order_number() . '</p>';
		// echo '<p>' . __('Order Total:', 'woocommerce') . ' ' . $order->get_formatted_order_total() . '</p>';

		// Display offer page
		$this->display_discount_offer_checked();
	}


	public function display_discount_offer_checked() {


		// get custom options to show/hide offer products.
		$custom_show_hide_offer = get_option( 'custom_show_hide_offer' );
			
	 
			  // if the show/hide checkbox checked and
		if ('yes'===$custom_show_hide_offer) {
			$this->display_discount_offer();
		}
	}

	public function display_discount_offer() {

		//Call the function to get the current user role
		$current_user_role = $this->get_current_user_role();

		// last order status
		$order_status = $this->get_last_order_status();

		// get country of user
		$user_country = $this->get_user_country();

		//get product id from last order
		$products_ids_last_order = (array) $this->get_product_ids_from_last_order();
		$products_ids_last_order_categories = (array) $this->get_product_ids_from_last_order_by_category();
			

		// Call the function to get the total purchase amount and last purchase amount
		$purchase_amounts = $this->get_total_purchase_amount();


		$total_purchase_amounts = number_format($purchase_amounts['total_amount'], 2);
		$last_purchase_amounts = number_format($purchase_amounts['last_purchase_amount'], 2);
			

		  // echo 'total'. $total_purchase_amounts;
		  // echo 'last'. $last_purchase_amounts;
			
  

			$args = array(
				'post_type'      => 'post_purchase_offer', // Adjust post type if needed
				'post_status'    => 'publish',
				'posts_per_page' => -1, // Retrieve all posts
				'order'          => 'ASC', // Order by ascending
			);

			$query = new WP_Query($args);

				

				
				


			if ($query->have_posts()) {
				?>

					<div class="overlay"></div>
						<div class="modal">
						  <div class="modal-body">
							  <div class="close-btn">&times;</div>
						 

				<?php

				while ($query->have_posts()) {
					$query->the_post();

					$post_id = get_the_ID();
					$post_title = get_the_title();

						
					// Get custom meta data for the current post
					 $offer_description = get_post_meta($post_id, 'offer_description', true);
					 $selected_products = (array) get_post_meta($post_id, 'post_purchase_select_products', true);



					 $selected_roles = (array) get_post_meta($post_id, 'user_roles_select', true);

					 $selected_statuses_with_wc_underscore = (array) get_post_meta($post_id, 'order_statuses_select', true);

					 $selected_statuses = (array) array_map(fn( $element ) => str_replace('wc-', '', $element), $selected_statuses_with_wc_underscore);

					$selected_countries = (array) get_post_meta($post_id, 'user_countries_select', true);

					$selected_previous_total_amount = get_post_meta($post_id, 'previous_total_amount', true);

					$selected_last_purchase_amount = get_post_meta($post_id, 'last_purchase_amount', true);

					$selected_products_ids = (array) get_post_meta($post_id, 'addf_prc_product', true);
					

					$selected_categories_ids = (array) get_post_meta($post_id, 'addf_prc_categories', true);

					$product_ids_from_last_order = (array) $this->get_category_ids_from_last_order_by_categories($selected_categories_ids);

				



					if (!empty(array_filter($product_ids_from_last_order))) {
						$matching_products_ids = array();
	
						foreach ($selected_products as $product) {
							if (in_array($product['product_id'], $product_ids_from_last_order)) {
									$matching_products_ids[] = (array) $product;
							}
						}
						
						$selected_products = $matching_products_ids;
					}
					
					

					if (!empty(array_filter($selected_products_ids))) {
					$matching_products_ids = array();

						foreach ($selected_products as $product) {
							if (in_array($product['product_id'], $products_ids_last_order)) {
									$matching_products_ids[] = (array) $product;
							}
						}
					
					$selected_products = $matching_products_ids;
					}

					// echo '<pre>';
					// print_r($selected_products_ids);

					// echo '<pre>';
					// print_r($products_ids_last_order);
					// echo '<pre>';
					
					// print_r($selected_products);
					 


					// Check individual criteria
					$roles_condition = empty($selected_roles) || in_array($current_user_role, $selected_roles);
					$statuses_condition = empty($selected_statuses) || in_array($order_status, $selected_statuses);
					$countries_condition = empty($selected_countries) || in_array($user_country, $selected_countries);
					$last_purchase_amount_condition = empty($selected_last_purchase_amount) || $selected_last_purchase_amount >= $last_purchase_amounts;
					$previous_total_amount_condition = empty($selected_previous_total_amount) || $selected_previous_total_amount >= $total_purchase_amounts;
					$products_ids_condition = empty($selected_products_ids) || array_intersect($products_ids_last_order, $selected_products_ids);
					$categories_ids_condition = empty($selected_categories_ids) || array_intersect($products_ids_last_order_categories, $selected_categories_ids);

					// Check pairs of criteria
					$role_status_condition = $roles_condition && $statuses_condition;
					$role_country_condition = $roles_condition && $countries_condition;
					$status_country_condition = $statuses_condition && $countries_condition;
					$role_last_purchase_condition = $roles_condition && $last_purchase_amount_condition;
					$role_total_purchase_condition = $roles_condition && $previous_total_amount_condition;
					$status_last_purchase_condition = $statuses_condition && $last_purchase_amount_condition;
					$status_total_purchase_condition = $statuses_condition && $previous_total_amount_condition;
					$country_last_purchase_condition = $countries_condition && $last_purchase_amount_condition;
					$country_total_purchase_condition = $countries_condition && $previous_total_amount_condition;

					// Check all conditions
					if (
						$roles_condition && $statuses_condition && $countries_condition &&
						$last_purchase_amount_condition && $previous_total_amount_condition &&
						$products_ids_condition && $categories_ids_condition &&
						(
							// Check pairs of criteria
							$role_status_condition ||
							$role_country_condition ||
							$status_country_condition ||
							$role_last_purchase_condition ||
							$role_total_purchase_condition ||
							$status_last_purchase_condition ||
							$status_total_purchase_condition ||
							$country_last_purchase_condition ||
							$country_total_purchase_condition
						)
					) {
					$this->display_discount_offer_detail($post_id, $post_title, $offer_description, $selected_products);
					}
					//$this->display_discount_offer_detail($post_id, $post_title, $offer_description, $selected_products);

						
				}
					  wp_reset_postdata();
				?>

						</div>
						</div>
						

					<?php
			} 
	}

		
	public function display_discount_offer_detail( $post_id, $post_title, $offer_description, $selected_products ) {
 
		$product_Ids_wp_session = (array) WC()->session->get('custom_ids', array());

		if (!empty(array_filter($product_Ids_wp_session))) {
		 $not_matching_products_ids = array();

			foreach ($selected_products as $product) {
				if (!in_array($product['product_id'], $product_Ids_wp_session)) {
						  $not_matching_products_ids[] = (array) $product;
				}
			}
		
		 $selected_products = $not_matching_products_ids;
		}
						 
					
		?>

	  <div class="post-container">
   

			<?php 
			foreach ($selected_products as $item) : 

				$product_id = $item['product_id'];

				$product_title = $item['product_title'];
				$discount_type = $item['discount_type'];
				$discount_amount = $item['discount_amount'];
				$custom_quantity = $item['custom_quantity'];
			
				// Get the post thumbnail URL (featured image) by product ID
				$image_url_product = get_the_post_thumbnail_url($product_id);
				if ($image_url_product) {
					$image_url = get_the_post_thumbnail_url($product_id);
				} else {
					$image_url = ADDF_PPP_URL . 'includes/images/addify-placeholder.png';
				}

				// Get the price of the product by its ID
				$price = get_post_meta($product_id, '_price', true);

				$parent_product_id = wp_get_post_parent_id( $product_id );

				if (0 === $parent_product_id) {
					$product_categories = wp_get_post_terms($product_id, 'product_cat', array( 'fields' => 'names' ));
				} else {
				  // Get the categories of the product
				  $product_categories = wp_get_post_terms($parent_product_id, 'product_cat', array( 'fields' => 'names' ));
				}
				

				// echo '<pre>';
				// print_r($product_categories);

				// Get the Sku if the product
				$product_sku = get_post_meta($product_id, '_sku', true);

				// Get the stock status for the product
				$stock_status = get_post_meta($product_id, '_stock_status', true);

				// Get the maximum stock quantity allowed for the product
				$product = wc_get_product($product_id);
				$max_quantity = $product->get_stock_quantity();

				// Get the stock quantity for the product
				$stock_quantity = get_post_meta($product_id, '_stock', true);

		

				// Get the currency symbols
				$currency_symbol = get_woocommerce_currency_symbol();
				$product_url = get_permalink($product_id);



				$fixedPrice = $price;
				$discountType = $discount_type ; // or 'fixed' or 'fixed-price'
				$discountValue = $discount_amount; // percentage (for percentage discount) or fixed value (for fixed discount) or fixed price
				$discountedPrice =  $this->calculateDiscountedPrice($currency_symbol, $fixedPrice, $discountType, $discountValue);
				$discountedPrice_with_sym = $discountedPrice['discountedPrice_with_sym'];
				$discountedPrice = round($discountedPrice['discountedPrice']);
		
			

				?>
			<div class="post-content">

			<h2 class="post-title"> <?php echo esc_html_e( $post_title, 'woo_addf_ppp' ); ?></h2>
			<div class="custom_wp_message"></div>
			<div class="product-item" id="id_<?php echo esc_attr__($product_id); ?>">
				<img  src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr__($product_title); ?>" class="product-image">
				<div class="product-details">
					<h3 class="product-title"><a href="<?php echo esc_url($product_url); ?>"><?php echo esc_html_e( $product_title, 'woo_addf_ppp' ); ?></a></h3>
				
					<p class="product-price">
						<span class="actual_amount"><?php echo esc_attr__($currency_symbol . round($price)); ?></span>
					<?php echo wp_kses_post($discountedPrice_with_sym); ?>
					</p>
					<?php

					if ('yes'===$this->offer_product_description) {
						if (!empty($this->offer_product_description_lenght)) {
							// Input is not empty
							$trimmed_offer_description = wp_trim_words($offer_description, $this->offer_product_description_lenght, '');
						
							// Display the stock quantity input field with maximum limit
							echo '<p class="post-description">' . wp_kses_post($trimmed_offer_description, 'woo_addf_ppp') . ' <a href="#" class="read-more-button">Read More</a></p>';
							echo '<p class="full-description" style="display:none;">' . wp_kses_post($offer_description, 'woo_addf_ppp') . '</p>';
						} else {
							// Input is empty
							echo '<p class="post-description">' . wp_kses_post($offer_description, 'woo_addf_ppp') . ' </p>';
						}
					 
					}
					

					
					if ('yes'===$this->offer_product_stock && !empty($stock_status)) {
					   // Display the stock status message similar to WooCommerce product page
						if ('instock'===$stock_status) {
						  echo '<p class="stock in-stock">' . esc_attr__($stock_quantity) . ' ' . esc_html__( 'in stock', 'woo_addf_ppp' ) . '</p>';

						} elseif ('outofstock'===$stock_status) {
						 echo '<p class="stock  out-of-stock">' . esc_html__( 'Out of stock', 'woo_addf_ppp' ) . '</p>';
						} elseif ('onbackorder'===$stock_status) {
						 echo '<p class="stock  on-backorder">' . esc_html__( 'Available on backorder', 'woo_addf_ppp' ) . '</p>';
						} else {
					   echo '<p class="stock unknown-status">' . esc_html__( 'Stock status unknown', 'woo_addf_ppp' ) . '</p>';
						}
					}

					if ('yes'===$this->offer_product_quantity && 'outofstock' != $stock_status) { 
						// Display the stock quantity input field with maximum limit
						echo '<div class="woocommerce_stock_fields_custom">';
						echo '<input type="number" class="stock_quantity_custom" data-product-name="' . esc_attr__($product_title) . '" id="stock_quantity_custom_' . esc_attr__($product_id) . '" step="1" data-product-custom_quantity="' . esc_attr( $custom_quantity ) . '" max="' . esc_attr__( $max_quantity ) . '" value="' . esc_attr__( $custom_quantity ) . '" /></div>';
					} else {
						echo '<div class="woocommerce_stock_fields_custom" style="display:none;">';
						echo '<input type="number" class="stock_quantity_custom" data-product-name="' . esc_attr__($product_title) . '" id="stock_quantity_custom_' . esc_attr__($product_id) . '" step="1" data-product-custom_quantity="' . esc_attr( $custom_quantity ) . '" max="' . esc_attr__( $max_quantity ) . '" value="' . esc_attr__( $custom_quantity ) . '" /></div>';
					}


					 $output = '';

					if ('yes' === $this->offer_product_sku && !empty($product_sku)) {
						$output .= '<p class="product-sku"><strong>' . esc_html__( 'SKU', 'woo_addf_ppp' ) . '</strong>: ' . esc_attr__(strtoupper($product_sku)) . '</p>';
					}

					if ('yes' === $this->offer_product_category && !empty($product_categories)) {
						$output .= '<p class="product-categories"><strong>' . esc_html__( 'Category', 'woo_addf_ppp' ) . '</strong>: ' . esc_attr__(implode(', ', $product_categories)) . '</p>';
					}

					if (!empty($output)) {
						echo '<div class="product-info-inline">' . wp_kses_post($output, 'woo_addf_ppp') . '</div>';
					}

					?>
					<div class="button-container">
					
					<?php
					if ('outofstock' != $stock_status) {
						$button_text = esc_html__('Accept Offer', 'woo_addf_ppp');
						echo '<button class="accept_skip_count accept-offer" data-product-id="' . esc_attr__($product_id) . '" data-custom-price="' . esc_attr($discountedPrice) . '" data-post-id="' . esc_attr($post_id) . '" data-custom-quantity="' . esc_attr__($custom_quantity) . '">' . esc_html($button_text) . '</button>';
					}
					?>
					
					<button class="accept_skip_count skip" data-product-id="<?php echo esc_attr__($product_id); ?>"><?php echo esc_html_e( 'Skip', 'woo_addf_ppp' ); ?></button>
					<button class="accept_skip_count skip-all" id="<?php echo esc_attr__($product_id); ?>"><?php echo esc_html_e( 'Skip All', 'woo_addf_ppp' ); ?></button>
					<button class="accept_skip_count skip-remaining" style="display: none;"><?php echo esc_html_e( 'Skip Remaining Offers', 'woo_addf_ppp' ); ?></button>
				   </div>
				</div>
			</div>
			</div>
				<?php endforeach; ?>
</div>

<!-- For processing icon to show when add to card -->
<div id="processing-popup" class="processing-popup">
	 <div class="loading-spinner"></div>
	 <div class="loading-message"><?php echo esc_html_e( 'Processing...', 'woo_addf_ppp' ); ?></div>
</div>


		
	

		<?php
	}

	public function calculateDiscountedPrice( $currencySymbol, $fixedPrice, $discountType, $discountValue ) {
		$discountedPrice = 0;
		$discountedPrice_with_sym = '';

		if ('p_d' === $discountType) {
			$discountedPrice = $fixedPrice - ( $fixedPrice * ( $discountValue / 100 ) );
			$discountedPrice_with_sym = '<span class="discount_amount">' . esc_attr__($currencySymbol) . round($discountedPrice) . '</span><span class="discount_type"> (' . esc_attr__($discountValue) . '' . esc_html__('% OFF)', 'woo_addf_ppp' ) . '</span>';
		} elseif ('f_d' === $discountType) {
			$discountedPrice = $fixedPrice - $discountValue;
			$discountedPrice_with_sym = '<span class="discount_amount">' . esc_attr__($currencySymbol) . round($discountedPrice) . '</span><span class="discount_type"> ' . esc_html__( '(Fixed Discount)', 'woo_addf_ppp' ) . '</span>';
		} elseif ('f_p' === $discountType) {
			$discountedPrice = $discountValue;
			$discountedPrice_with_sym = '<span class="discount_amount">' . esc_attr__($currencySymbol) . round($discountedPrice) . '</span><span class="discount_type"> ' . esc_html__( '(Fixed Price)', 'woo_addf_ppp' ) . '</span>';
		} else {
			throw new Exception('Invalid discount type. Use "percentage", "fixed", or "fixed-price".');
		}

		return array(
			'discountedPrice' => $discountedPrice,
			'discountedPrice_with_sym' => $discountedPrice_with_sym,
		);
	}
}
	new Addify_Post_Purchase_Offer_Front();




add_action('wp_footer', function () {
	 WC()->session->set('custom_ids', null);

// echo '<pre>';


// foreach (wc()->cart as $cart_key => $cart_obj) {
//  print_r( $cart_obj );
// }
});