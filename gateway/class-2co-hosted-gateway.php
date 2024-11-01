<?php

function qctwoco_init_woo_gateway(){

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	class QCTWOCO_WC_Gateway extends WC_Payment_Gateway {
		
		// Logging
        public static $log_enabled = false;
        public static $log = false;

		var $seller_id;
		var $demo;
		var $plugin_url;

		public function __construct(){
			
			global $woocommerce;

			$this->plugin_url = WP_PLUGIN_URL . DIRECTORY_SEPARATOR . 'woowpay';
			
			$this->id 					= 'qcwoo_2co_hosted_payment_gateway';
			$this->has_fields   		= false;
			$this->checkout_url     	= 'https://www.2checkout.com/checkout/purchase';
			$this->checkout_url_sandbox	= 'https://sandbox.2checkout.com/checkout/purchase';
			$this->icon 				= $this->plugin_url.'/images/hosted.png';
			$this->method_title 		= esc_html__('2CO Hosted Checkout', 'qc-2co-payment-gateway');
			$this->method_description 	= sprintf(
				esc_html__('Enable 2checkout hosted payment', 'qc-2co-payment-gateway' )
			);
				
			$this->title 				= $this->get_option( 'title' ) ? $this->get_option( 'title' ) : esc_html__( '2CO Hosted Checkout', 'qc-2co-payment-gateway' );
			$this->description 			= $this->get_option( 'description' );
			$this->seller_id			= $this->get_option( 'seller_id' );
			$this->demo 				= $this -> get_option('demo');
			$this->debug 				= $this->get_option('debug');
			$this->pay_method 			= $this -> get_option('pay_method'); 
				
				
			$this->init_form_fields();
			$this->init_settings();
			
			self::$log_enabled = $this->debug;
				
			// Save options
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// add_action('process_2co_ipn_request', array( $this, 'successful_request' ), 1 );
			
			// Payment listener/API hook
			add_action( 'woocommerce_api_wc_gateway_qc_twocheckout', array( $this, 'twocheckout_response' ) );
				
		}


		function init_form_fields(){

			$this->form_fields = array(
					'enabled' => array(
							'title' => esc_html__( 'Enable', 'qc-2co-payment-gateway' ),
							'type' => 'checkbox',
							'label' => esc_html__( 'Yes', 'qc-2co-payment-gateway' ),
							'default' => 'yes'
					),
					
					'seller_id' => array(
							'title' => esc_html__( '2CO Account #', 'qc-2co-payment-gateway' ),
							'type' => 'text',
							'description' => esc_html__( 'This Seller ID issued by 2Checkout', 'qc-2co-payment-gateway' ),
							'default' => '',
							'desc_tip'      => false,
					),
				
					'title' => array(
							'title' => esc_html__( 'Title', 'qc-2co-payment-gateway' ),
							'type' => 'text',
							'description' => esc_html__( 'This controls the title which the user sees during checkout.', 'qc-2co-payment-gateway' ),
							'default' => esc_html__( '2CO Hosted Checkout', 'qc-2co-payment-gateway' ),
							'desc_tip'      => true,
					),
					'description' => array(
							'title' => esc_html__( 'Customer Message', 'qc-2co-payment-gateway' ),
							'type' => 'textarea',
							'default' => esc_html__('Buy with 2checkOut Hosted Payment Gateway. it will redirected you 2checkout hosted Purchase Page.', 'qc-2co-payment-gateway'),
					),
					'demo' => array(
							'title' => esc_html__( 'Enable Demo Mode', 'qc-2co-payment-gateway' ),
							'type' => 'checkbox',
							'label' => esc_html__( 'Yes', 'qc-2co-payment-gateway' ),
							'default' => 'no',
							'class' => 'qc2co-disable-field',
							'description' => esc_html__( '2CO has depreciated the Sandbox - so we could not provide this feature', 'qc-2co-payment-gateway' )
					),
					'debug' => array(
                        'title'       => esc_html__( 'Debug Log', 'qc-2co-payment-gateway' ),
                        'type'        => 'checkbox',
                        'label'       => esc_html__( 'Enable logging', 'qc-2co-payment-gateway' ),
                        'default'     => 'no',
                        'description' => sprintf( esc_html__( 'Debug Information %s', 'qc-2co-payment-gateway' ), wc_get_log_file_path( 'twocheckout-hosted' ) )
                    ),

			);
		}
		
		/**
        * Logging method
        * @param  string $message
        */
        public static function log( $message, $order_id=0 ) {
        	$context = array( 'source' => 'twocheckout-hosted' );
            if ( self::$log_enabled ) {
                if ( empty( self::$log ) ) {
                    self::$log = wc_get_logger();
                }
                
                $message = is_array($message) ? json_encode($message) : $message;
                self::$log->info( $message, $context );
            }
        }


		/**
		 * Process the payment and return the result
		 *
		 * @access public
		 * @param int $order_id
		 * @return array
		 */
		function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );


			$twoco_args = $this->get_twoco_args( $order );
			/*echo '<pre>';
			 print_r($twoco_args);
			echo '</pre>';
			exit;*/
			
			
			$twoco_args = http_build_query( $twoco_args, '', '&' );
			$this->log("========== Payment Procesing Started: args =========", $order_id);
			$this->log($twoco_args, $order_id);
			
			//if demo is enabled
			$checkout_url = '';
			if ($this->demo == 'yes'){
				$checkout_url =	$this->checkout_url_sandbox;
			}else{
				$checkout_url =	$this->checkout_url;
			}
			
			// var_dump($checkout_url.'?'.$twoco_args); exit;
			
			return array(
					'result' 	=> 'success',
					'redirect'	=> $checkout_url.'?'.$twoco_args
			);


		}


		/**
		 * Get 2Checkout Args for passing to PP
		 *
		 * @access public
		 * @param mixed $order
		 * @return array
		 */
		function get_twoco_args( $order ) {
			global $woocommerce;

			$order_id = $order->get_id();

			// 2Checkout Args
			$twoco_args = array(
					'sid' 					=> $this->seller_id,
					'mode' 					=> '2CO',
					'merchant_order_id'		=> $order_id,
					'currency_code'			=> $curr_code,
						
					// Billing Address info
					'first_name'			=> $order->get_billing_first_name(),
					'last_name'				=> $order->get_billing_last_name(),
					'street_address'		=> $order->get_billing_address_1(),
					'street_address2'		=> $order->get_billing_address_2(),
					'city'					=> $order->get_billing_city(),
					'state'					=> $order->get_billing_state(),
					'zip'					=> $order->get_billing_postcode(),
					'country'				=> $order->get_billing_country(),
					'email'					=> $order->get_billing_email(),
					'phone'					=> $order->get_billing_phone(),
			);

			// Shipping
			
			if ($order->needs_shipping_address()) {

				$twoco_args['ship_name']			= $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
				$twoco_args['company']				= $order->get_shipping_company();
				$twoco_args['ship_street_address']	= $order->get_shipping_address_1();
				$twoco_args['ship_street_address2']	= $order->get_shipping_address_2();
				$twoco_args['ship_city']			= $order->get_shipping_city();
				$twoco_args['ship_state']			= $order->get_shipping_state();
				$twoco_args['ship_zip']				= $order->get_shipping_postcode();
				$twoco_args['ship_country']			= $order->get_shipping_country();
			}
			
			$twoco_args['x_receipt_link_url'] 	= $this->get_return_url( $order );
			$twoco_args['return_url']			= str_replace('https', 'http', $order->get_cancel_order_url());
			$twoco_args['qc2co_checkout_type']	= 'hosted';
			$twoco_args['currency_code']	= 'USD';
			
			
			//setting payment method
			if ($this->pay_method)
				$twoco_args['pay_method'] = $this->pay_method;
			
			
			//if demo is enabled
			if ($this->demo == 'yes'){
				$twoco_args['demo'] =	'Y';
			}

			$item_names = array();

			if ( sizeof( $order->get_items() ) > 0 ){
				
				$twoco_product_index = 0;
				
				foreach ( $order->get_items() as $item ){
					if ( $item['qty'] )
						$item_names[] = $item['name'] . ' x ' . $item['qty'];
				
					/*echo '<pre>';
					print_r($item);
					echo '</pre>';
					exit;*/
					
					
					/**
					 * since version 1.6
					 * adding support for both WC Versions
					 */
					$_sku = '';
					if ( function_exists( 'get_product' ) ) {
							
						// Version 2.0
						$product = $order->get_product_from_item($item);
							
						// Get SKU or product id
						if ( $product->get_sku() ) {
							$_sku = $product->get_sku();
						} else {
							$_sku = $product->get_id();
						}
							
					} else {
							
						// Version 1.6.6
						$product = new WC_Product( $item['id'] );
							
						// Get SKU or product id
						if ( $product->get_sku() ) {
							$_sku = $product->get_sku();
						} else {
							$_sku = $item['id'];
						}	
					}
					
					$tangible = "N";
					
					$item_formatted_name 	= $item['name'] . ' (Product SKU: '.$item['product_id'].')';
				
					$twoco_args['li_'.$twoco_product_index.'_type'] 	= 'product';
					$twoco_args['li_'.$twoco_product_index.'_name'] 	= sprintf( esc_html__( 'Order %s' , 'qc-2co-payment-gateway'), $order->get_order_number() ) . " - " . $item_formatted_name;
					$twoco_args['li_'.$twoco_product_index.'_quantity'] = $item['qty'];
					$twoco_args['li_'.$twoco_product_index.'_price'] 	= $this -> get_price($order->get_item_total( $item, false ));
					$twoco_args['li_'.$twoco_product_index.'_product_id'] = $_sku;
					$twoco_args['li_'.$twoco_product_index.'_tangible'] = $tangible;
					
					$twoco_product_index++;
				}
				
				//getting extra fees since version 2.0+
				$extrafee = $order -> get_fees();
				if($extrafee){
				
					
					$fee_index = 1;
					foreach ( $order -> get_fees() as $item ) {
						
						$twoco_args['li_'.$twoco_product_index.'_type'] 	= 'product';
						$twoco_args['li_'.$twoco_product_index.'_name'] 	= sprintf( esc_html__( 'Other Fee %s' , 'qc-2co-payment-gateway'), $item['name'] );
						$twoco_args['li_'.$twoco_product_index.'_quantity'] = 1;
						$twoco_args['li_'.$twoco_product_index.'_price'] 	= $this->get_price( $item['line_total'] );

						$fee_index++;
						$twoco_product_index++;
	 				}	
				}
				
				// Shipping Cost
				if ( $order -> get_total_shipping() > 0 ) {
					
					
					$twoco_args['li_'.$twoco_product_index.'_type'] 		= 'shipping';
					$twoco_args['li_'.$twoco_product_index.'_name'] 		= esc_html__( 'Shipping charges', 'qc-2co-payment-gateway' );
					$twoco_args['li_'.$twoco_product_index.'_quantity'] 	= 1;
					$twoco_args['li_'.$twoco_product_index.'_price'] 		= $this->get_price( $order -> get_total_shipping() );
					$twoco_args['li_'.$twoco_product_index.'_tangible'] = 'Y';
					
					$twoco_product_index++;
				}
				
				// Taxes (shipping tax too)
				if ( $order -> get_total_tax() > 0 ) {
				
					$twoco_args['li_'.$twoco_product_index.'_type'] 		= 'tax';
					$twoco_args['li_'.$twoco_product_index.'_name'] 		= esc_html__( 'Tax', 'qc-2co-payment-gateway' );
					$twoco_args['li_'.$twoco_product_index.'_quantity'] 	= 1;
					$twoco_args['li_'.$twoco_product_index.'_price'] 		= $this->get_price( $order->get_total_tax() );
					
					$twoco_product_index++;
				}

				
			}

			
			
			$twoco_args = apply_filters( 'woocommerce_twoco_args', $twoco_args );
			
			return $twoco_args;
		}
		
		/**
		 * this function is return product object for two
		 * differetn version of WC
		 */
		function get_product_object(){
			
			return $product;
		}
		
		
		/**
		 * Check for 2Checkout IPN Response
		 *
		 * @access public
		 * @return void
		 */
		function twocheckout_response() {
		
			/**
			 * source code: https://github.com/craigchristenson/woocommerce-2checkout-api
			 * Thanks to: https://github.com/craigchristenson
			 */
			global $woocommerce;
			
			// qc2co_log($_REQUEST);
			if ( isset($_REQUEST['wc-api']) && sanitize_text_field($_REQUEST['wc-api']) == 'WC_Gateway_QC_TwoCheckout' ) {
				if( isset($_REQUEST['qc2co_checkout_type']) && sanitize_text_field($_REQUEST['qc2co_checkout_type']) == 'hosted' ){
					$wc_order_id = '';

					if( !isset($_REQUEST['merchant_order_id']) ) {
						if(isset($_REQUEST['vendor_order_id']) ) {
							$wc_order_id = sanitize_text_field($_REQUEST['vendor_order_id']);
						}
					} else {
						$wc_order_id = sanitize_text_field($_REQUEST['merchant_order_id']);
					}

					$this->log(esc_html__("== INS Response Received == ", "qc-2co-payment-gateway"), $wc_order_id );
					$this->log( $_REQUEST, $wc_order_id );
					
					
					
					
					$wc_order 		= new WC_Order( absint( $wc_order_id ) );
					$this->log("Order ID {$wc_order_id}", $wc_order_id);
					
					$this->log("WC API ==> ".sanitize_text_field($_GET['wc-api']), $wc_order_id);
					
					// If redirect after payment
					if( isset($_GET['wc-api']) && sanitize_text_field($_GET['wc-api']) == 'WC_Gateway_QC_TwoCheckout' && !isset($_REQUEST['fraud_status'] ) ) {
						
						$order_redirect = $this->get_return_url( $wc_order );
						$order_redirect = add_query_arg('twoco','processed', $order_redirect);
						$wc_order->update_status('completed');
						$this->log( "Order Status Changed to Completed", $wc_order_id );
						$wc_order->payment_complete();
						$this->log( "Order Payment Completed", $wc_order_id );
						wp_redirect( $order_redirect );
						exit;
					}
					
					$message_type	= isset($_REQUEST['message_type']) ? sanitize_text_field($_REQUEST['message_type']) : '';
					$sale_id		= isset($_REQUEST['sale_id']) ? sanitize_text_field($_REQUEST['sale_id']) : '';
					$invoice_id		= isset($_REQUEST['invoice_id']) ? sanitize_text_field($_REQUEST['invoice_id']) : '';
					$fraud_status	= isset($_REQUEST['fraud_status']) ? sanitize_text_field($_REQUEST['fraud_status']) : '';
					
					$this->log( "Message Type/Fraud Status: {$message_type}/{$fraud_status}", $wc_order_id );
					
					switch( $message_type ) {
						
						case 'ORDER_CREATED':
							$wc_order->add_order_note( sprintf(esc_html__('ORDER_CREATED with Sale ID: %d', 'qc-2co-payment-gateway'), $sale_id) );
							$this->log(sprintf(esc_html__('ORDER_CREATED with Sale ID: %d', 'qc-2co-payment-gateway'), $sale_id), $wc_order_id);
						break;
						
						case 'FRAUD_STATUS_CHANGED':
							if( $fraud_status == 'pass' ) {
								// Mark order complete
								$wc_order->payment_complete();
								$wc_order->add_order_note( sprintf(esc_html__('Payment Status Clear with Invoice ID: %d', 'qc-2co-payment-gateway'), $invoice_id) );
								$this->log(sprintf(esc_html__('Payment Status Clear with Invoice ID: %d', 'qc-2co-payment-gateway'), $invoice_id), $wc_order_id);
								add_action('twoco_order_completed', $order, $sale_id, $invoice_id);
								
							} elseif( $fraud_status == 'fail' ) {
								
								$wc_order->update_status('failed');
								$wc_order->add_order_note(  esc_html__("Payment Decliented", 'qc-2co-payment-gateway') );
								$this->log( esc_html__("Payment Decliented for Failed Response from 2CO", 'qc-2co-payment-gateway'), $wc_order_id );
							}
							
						break;
					}
					
					exit;
				}
			}
		}
		
		
		function get_price($price){
			
			$price = wc_format_decimal($price, 2);
			
			return apply_filters('nm_get_price', $price);
		}
		
		/*
		 * valid requoest posed from 2Checkout
		 */
		function successful_request($posted){
			
			//testing ipn request
			//qc2co_log($_REQUEST); exit;
			
			if($posted['invoice_status'] == 'approved'){
				
				global $woocommerce;

				$order_id = $posted['merchant_order_id'];
				
				//this was set for IPN Simulator
				//$order_id = $posted['merchant_order_id'];
				
				$order 		= new WC_Order( $order_id );
				
				// Store PP Details
				if ( ! empty( $posted['customer_email'] ) )
					update_post_meta( $order->get_id(), 'Customer email address', $posted['customer_email'] );
				if ( ! empty( $posted['sale_id'] ) )
					update_post_meta( $order->get_id(), 'Sale ID', $posted['sale_id'] );
				if ( ! empty( $posted['customer_first_name '] ) )
					update_post_meta( $order->get_id(), 'Payer first name', $posted['customer_first_name'] );
				if ( ! empty( $posted['customer_last_name '] ) )
					update_post_meta( $order->get_id(), 'Payer last name', $posted['customer_last_name'] );
				if ( ! empty( $posted['payment_type'] ) )
					update_post_meta( $order->get_id(), 'Payment type', $posted['payment_type'] );
				
				// Payment completed
				$order->add_order_note( esc_html__( 'IPN completed by 2CO', 'qc-2co-payment-gateway' ) );
				$order->payment_complete();
				
				$woocommerce -> cart -> empty_cart();
				
			}
		}

	}
	
}


function qc2co_log( $log ) {
	
	if ( true === WP_DEBUG ) {
      if ( is_array( $log ) || is_object( $log ) ) {
          $resp = error_log( print_r( $log, true ), 3, plugin_dir_path(__FILE__).'twoco.log' );
      } else {
          $resp = error_log( $log, 3, plugin_dir_path(__FILE__).'twoco.log' );
      }
      
      var_dump($resp);
  }
}