<?php defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );
/*
 * Plugin Name: TrackTech Integration
 * Plugin URI: http://www.tracktech.io
 * Description: Integrates the TrackTech.io Tracking System into your Wordpress Installation and WooCommerce Shop.
 * Version: 0.1
 * Author: Thomas Symann
 * Author URI: http://www.tracktech.io
 */


class Tracktech {


	public function __construct()
	{
		// Add Support Link
		add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( &$this, 'tracktech_links' ), 1 );

		// Add GA code
		add_action('wp_head', array( &$this, 'tracktech_script' ), 3 );

		// Add Admin menu
		add_action( 'admin_menu', array( $this, 'tracktech_admin_menu' ), 4 );

		// Load settings
		add_action( 'plugins_loaded', array( &$this, 'tracktech_settings' ), 1 );

		// Setting Initialization
		add_action( 'admin_init', array( &$this, 'tracktech_settings_init' ), 1 );

		// Install, Updates and Deinstall
		/*
		register_activation_hook(__FILE__, array($this, 'tracktech_install'));
		register_deactivation_hook(__FILE__, array($this, 'tracktech_deinstall'));
		add_action('tracktech_check_updates', array($this, 'tracktech_update'));
		*/
	}

	/*
	public function tracktech_install()
	{
	    if( ! wp_next_scheduled ('tracktech_check_updates'))
	    {
			//wp_schedule_event(time(), 'hourly', 'tracktech_check_updates');
	    }
	}


	public function tracktech_update()
	{
		//wp_mail('thomas.symann@tracktech.io', 'cronjob läuft', 'wp-cron.php lief um '.date('Y-m-d H:i:s'));
	}


	public function tracktech_deinstall()
	{
		wp_clear_scheduled_hook('tracktech_check_updates');
	}
	*/


	public function tracktech_script()
	{
		global $wp;
		$saved_options = get_option( 'tracktech_settings' );
		
		include_once(ABSPATH.'wp-admin/includes/plugin.php');
		if($saved_options['tracktech_id'] != '' && is_plugin_active('woocommerce/woocommerce.php')) { ?>
		<script type="text/javascript">
		function ttio()
		{
		    var ttio = new ttio_int();

		    <?php
		    	$this->ttio_set('websiteid');
		    	$this->ttio_set('userid');
		    	$this->ttio_set('debug');
		    	$this->ttio_set('pagetype');
		    	$this->ttio_set('basket');

		    	if(is_checkout() && ! empty( $wp->query_vars['order-received']))
		    	{
		    		echo 'ttio.track("conversion");';
		    	}
		    	else
		    	{
		    		echo 'ttio.track("pageview");';
		    	}
	    	?>
		}

		var ttio_cdn = "https://api.tracktech.io/tracker.js";
		if(typeof(ttio_int) != "function")
		{
		    var a = document.createElement("script");
		    a.type = "text/javascript", a.readyState ? a.onreadystatechange = function() {
		        ("loaded" == a.readyState || "complete" == a.readyState)
		    } : a.onload = function() {
		        ttio()
		    }, a.src = ttio_cdn, document.getElementsByTagName("head")[0].appendChild(a)
		}
		else { ttio(); }
		</script>
	<?php }
	}


	public function tracktech_links( $links ) {
		$links[] = '<a href="http://www.tracktech.io/contact/" target="_blank">Support</a>';
		return $links;
	}


	public function tracktech_admin_menu() {
		add_menu_page( 'TrackTech Attribution', 'TrackTech', 'manage_options', 'tracktech-menu', array( $this, 'tracktech_options_page'), 'dashicons-chart-line' );
	}


	public function tracktech_settings () {
		return array(
			array(
				'settings_type' => 'section',
				'id' => 'tracktech_section_settings_general',
				'title' => 'General Settings',
				'callback' => 'tracktech_description_section_callback',
				'page' => 'tracktech_page'
			),
			array (
				'settings_type' => 'field',
				'id' => 'tracktech_id',
				'title' => 'Website ID',
				'callback' => 'tracktech_settings_field_render',
				'page' => 'tracktech_page',
				'section' => 'tracktech_section_settings_general',
				'args' => 	array (
								'id' => 'tracktech_id',
								'type' => 'text',
								'class' => '',
								'name' => 'tracktech_id',
								'value' => 'tracktech_id',
								'label_for' => '',
								'description' => 'Add TrackTech Website ID.',
				)
			),

			// Advanced Settings
			array(
				'settings_type' => 'section',
				'id' => 'tracktech_section_settings_advanced',
				'title' => 'Advanced Settings',
				'callback' => 'tracktech_description_section_callback',
				'page' => 'tracktech_page'
			),
			array (
				'settings_type' => 'field',
				'id' => 'tracktech_userid',
				'title' => 'Debug',
				'callback' => 'tracktech_settings_field_render',
				'page' => 'tracktech_page',
				'section' => 'tracktech_section_settings_advanced',
				'args' => 	array (
									'id' => 'tracktech_debug',
									'type' => 'checkbox',
									'class' => '',
									'name' => 'tracktech_debug',
									'value' => 1,
									'label_for' => '',
									'description' => 'Activates the debug functions. Use this only during installation or problems for testing.',
				)
			),
		);
	}


	public function ttio_set($key, $value = NULL)
	{
		global $wp;
		global $order;

		$saved_options = get_option( 'tracktech_settings' );
		
		if($key == 'websiteid')
		{
			if(is_numeric($saved_options['tracktech_id']))
			{
				echo "ttio.data.websiteid = ".$saved_options['tracktech_id'].";\r\n";
			}
		}
		elseif($key == 'userid')
		{
			if(is_user_logged_in())
			{
				$current_user = wp_get_current_user();
				echo "ttio.data.userid = '".md5($current_user->ID)."';\r\n";
			}
		}
		elseif($key == 'debug')
		{
			if(isset($saved_options['tracktech_debug']))
			{
				echo "ttio.data.debug = true;\r\n";
			}
			else
			{
				echo "ttio.data.debug = false;\r\n";
			}
		}
		elseif($key == 'pagetype')
		{
			if(is_search())
			{
				echo "ttio.data.pagetype = 'search';\r\n";
				echo "ttio.data.searchterm = '".get_search_query()."';\r\n";
			}
			elseif(is_front_page())
			{
				echo "ttio.data.pagetype = 'homepage';\r\n";
			}
			elseif(is_cart())
			{
				echo "ttio.data.pagetype = 'cart';\r\n";
			}
			elseif(is_checkout() && ! empty( $wp->query_vars['order-received']))
			{
				echo "ttio.data.pagetype = 'success';\r\n";

				// Conversion data
				$order = new WC_Order(absint($wp->query_vars['order-received']));

				echo "ttio.data.conversion.number = '".$order->get_order_number()."';\r\n";
				
				echo "ttio.data.conversion.value = '".( $order->get_subtotal() - $order->get_total_discount() )."';\r\n";

				$count = 0;
				foreach($order->get_items() as $product)
				{
					$count += $product['qty'];
				}
				echo "ttio.data.conversion.count = '".$count."';\r\n";
				
				echo "ttio.data.conversion.currency = '".$order->get_order_currency()."';\r\n";
				
				if(is_user_logged_in())
				{
					echo "ttio.data.conversion.guestuser = '0';\r\n";
				}
				else
				{
					echo "ttio.data.conversion.guestuser = '1';\r\n";
				}
				
				echo "ttio.data.conversion.payment = '".$order->payment_method_title."';\r\n";
				if(is_array($order->get_used_coupons()))
				{
					echo "ttio.data.conversion.vouchercode = '".$order->get_used_coupons()[0]."';\r\n";
					echo "ttio.data.conversion.vouchername = '".$order->get_used_coupons()[0]."';\r\n";
				}
			}
			elseif(is_checkout())
			{
				echo "ttio.data.pagetype = 'checkout';\r\n";
			}
			elseif(is_product())
			{
				echo "ttio.data.pagetype = 'product';\r\n";
			}
			elseif(is_product_category())
			{
				echo "ttio.data.pagetype = 'category';\r\n";
			}
		}
		elseif($key == 'basket')
		{
			$value = 0;
			$count = 0;
			foreach(WC()->cart->cart_contents as $product)
			{
				$value += $product['line_total'];
				$count += $product['quantity'];
			}
			echo "ttio.data.basket.value = '".$value."';\r\n";
			echo "ttio.data.basket.count = '".$count."';\r\n";
		}
	}


	public function tracktech_description_section_callback( ) {	}

	
	public function tracktech_settings_init() {
		register_setting( 'tracktech_page', 'tracktech_settings' );
		foreach ( $this->tracktech_settings() AS $setting ) {
			if ($setting['settings_type'] === 'section') {
				add_settings_section(
					$setting['id'],
					$setting['title'],
					array( $this, $setting['callback'] ),
					$setting['page']
				);
			}
			if ($setting['settings_type'] === 'field') {
				add_settings_field(
					$setting['id'],
					$setting['title'],
					array( $this, $setting['callback'] ),
					$setting['page'],
					$setting['section'],
					$setting['args']
				);
			}
		}
	}


	/**
	 * Append a settings field to the the fields section.
	 */
	public function tracktech_settings_field_render( array $options = array() ) {
		$saved_options = get_option( 'tracktech_settings' );

		$atts = array(
			'id' => $options['id'],
			'type' => ( isset( $options['type'] ) ? $options['type'] : 'text' ),
			'class' => $options['class'],
			'name' => 'tracktech_settings[' . $options['name'] . ']',
			'value' => ( array_key_exists( 'default', $options ) ? $options['default'] : null ),
			'label_for' => ( array_key_exists( 'label_for', $options ) ? $options['label_for'] : false ),
			'description' => ( array_key_exists( 'description', $options ) ? $options['description'] : false )
		);

		if ( isset( $options['id'] ) ) {
			if ( isset( $saved_options[$options['id']] ) AND  ( $saved_options[$options['id']] != '') )  {
				$val = $saved_options[$options['id']];
			} else {
				$val = ( array_key_exists( 'default', $options ) ? $options['default'] : '' );
			}
			$atts['value'] = $val;
		}
		if ( isset( $options['type'] ) && $options['type'] == 'checkbox' ) {
			if ( $atts['value'] ) {
				$atts['checked'] = 'checked';
			}
				$atts['value'] = true;
		}


		/**
		 * Input type Checkbox
		 */
		if ($atts['type'] == 'checkbox') {
			//var_dump( $atts);
			$html = sprintf( '<input type="%1$s" class="%2$s" id="%3$s" name="%4$s" value="%5$s" %6$s />', $atts['type'], $atts['class'], $atts['id'], $atts['name'], $atts['value'], ( isset( $atts['checked'] ) ? "checked=".$atts['checked'] : '') );
			if ( array_key_exists( 'description', $atts ) ){
				$html .= sprintf( '<p class="description">%1$s</p>', $atts['description'] );
			}
			echo $html;
		}


		/**
		 * Input type Text
		 */
		if ($atts['type'] == 'text') {
			$html = sprintf( '<input type="%1$s" class="%2$s" id="%3$s" name="%4$s" value="%5$s"/>', $atts['type'], $atts['class'], $atts['id'], $atts['name'], $atts['value'] );
			if ( array_key_exists( 'description', $atts ) ){
				$html .= sprintf( '<p class="description">%1$s</p>', $atts['description'] );
			}
			echo $html;
		}


		/**
		 * Input type Textarea
		 */
		 if ($atts['type'] == 'textarea') {
 			$html = sprintf( '<textarea cols="60" rows="5" class="%1$s" id="%2$s" name="%3$s">%4$s</textarea>', $atts['class'], $atts['id'], $atts['name'], $atts['value'] );
 			if ( array_key_exists( 'description', $atts ) ){
 				$html .= sprintf( '<p class="description">%1$s</p>', $atts['description'] );
 			}
 			echo $html;
 		}
	}


	/**
	 * Generate settings form
	 */
	public function tracktech_options_page() {
		?>
		<div class="wrap">
			<form action='options.php' method='post'>
				<h1>TrackTech Settings</h1>
				<?php settings_errors(); ?>
				<?php
					settings_fields( 'tracktech_page' );
					do_settings_sections( 'tracktech_page' );
					submit_button();
				?>
			</form>
			<hr />
			<div style="font-size: 12px;">
				Tracking Technologies Limited |
				<a href="http://www.tracktech.io/privacy-policy/" target="_blank">Privacy Policy</a> |
				<a href="http://www.tracktech.io/terms-of-use/" target="_blank">Terms of Use</a> |
				<a href="http://www.tracktech.io/imprint/" target="_blank">Imprint</a>
			</div>
		</div>
		<?php
	}


}

// Instantiate the main class
new Tracktech();
