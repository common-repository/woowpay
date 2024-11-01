<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* Add a custom payment class to WC
  ------------------------------------------------------------ */
add_action('plugins_loaded', 'qcld_woocommerce_twocheckout', 0);

function qcld_woocommerce_twocheckout(){
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class is not available, do nothing
    if(class_exists('WC_Twocheckout'))
        return;

    class WC_Gateway_Twocheckout extends WC_Payment_Gateway{

        // Logging
        public static $log_enabled = false;
        public static $log = false;
        var $plugin_url;

        public function __construct(){

            $plugin_dir = plugin_dir_url(__FILE__);

            global $woocommerce;
            $this->plugin_url = WP_PLUGIN_URL . DIRECTORY_SEPARATOR . 'woowpay';

            $this->id = 'twocheckout';
            $this->icon                 = $this->plugin_url.'/images/inline-cc.png';
            $this->has_fields = true;

            $this->method_title         = esc_html__('2CO Inline Credit Card', 'qc-2co-payment-gateway');
            $this->method_description   = sprintf(
                esc_html__('Enable 2checkout Inline Credit Card payment', 'qc-2co-payment-gateway' )
            );

            // Load the settings
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title = $this->get_option('title') ? $this->get_option('title') : esc_html__( '2CO Inline Credit Card', 'qc-2co-payment-gateway' );
            $this->seller_id = $this->get_option('seller_id');
            $this->publishable_key = $this->get_option('publishable_key');
            $this->private_key = $this->get_option('private_key');
            $this->description = $this->get_option('description');
            $this->sandbox = $this->get_option('sandbox');
            $this->debug = $this->get_option('debug');

            self::$log_enabled = $this->debug;

            // Actions
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

            // Save options
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // Payment listener/API hook
            add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_ipn_response'));

            if (!$this->is_valid_for_use()){
                $this->enabled = false;
            }
        }

        /**
        * Logging method
        * @param  string $message
        */
        public static function log( $message ) {
            if ( self::$log_enabled ) {
                if ( empty( self::$log ) ) {
                    self::$log = new WC_Logger();
                }
                self::$log->add( 'twocheckout-credit-card', $message );
            }
        }

        /**
         * Check if this gateway is enabled and available in the user's country
         *
         * @access public
         * @return bool
         */
        function is_valid_for_use() {
          $supported_currencies = array(
            'AFN', 'ALL', 'DZD', 'ARS', 'AUD', 'AZN', 'BSD', 'BDT', 'BBD',
            'BZD', 'BMD', 'BOB', 'BWP', 'BRL', 'GBP', 'BND', 'BGN', 'CAD',
            'CLP', 'CNY', 'COP', 'CRC', 'HRK', 'CZK', 'DKK', 'DOP', 'XCD',
            'EGP', 'EUR', 'FJD', 'GTQ', 'HKD', 'HNL', 'HUF', 'INR', 'IDR',
            'ILS', 'JMD', 'JPY', 'KZT', 'KES', 'LAK', 'MMK', 'LBP', 'LRD',
            'MOP', 'MYR', 'MVR', 'MRO', 'MUR', 'MXN', 'MAD', 'NPR', 'TWD',
            'NZD', 'NIO', 'NOK', 'PKR', 'PGK', 'PEN', 'PHP', 'PLN', 'QAR',
            'RON', 'RUB', 'WST', 'SAR', 'SCR', 'SGF', 'SBD', 'ZAR', 'KRW',
            'LKR', 'SEK', 'CHF', 'SYP', 'THB', 'TOP', 'TTD', 'TRY', 'UAH',
            'AED', 'USD', 'VUV', 'VND', 'XOF', 'YER');

            if ( ! in_array( get_woocommerce_currency(), apply_filters( 'qcld_woocommerce_twocheckout_supported_currencies', $supported_currencies ) ) ) return false;

            return true;
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         *
         * @since 1.0.0
         */
        public function admin_options() {

            ?>
            <h3><?php esc_html_e( '2Checkout Credit Card', 'qc-2co-payment-gateway' ); ?></h3>
            <p><?php esc_html_e( '2Checkout - Credit Card', 'qc-2co-payment-gateway' ); ?></p>

            <?php if ( $this->is_valid_for_use() ) : ?>

                <table class="form-table">
                    <?php
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                    ?>
                </table><!--/.form-table-->

            <?php else : ?>
                <div class="inline error"><p><strong><?php esc_html_e( 'Gateway Disabled', 'qc-2co-payment-gateway' ); ?></strong>: <?php esc_html_e( '2Checkout does not support your store currency.', 'qc-2co-payment-gateway' ); ?></p></div>
            <?php
            endif;
        }


        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => esc_html__( 'Enable/Disable', 'qc-2co-payment-gateway' ),
                    'type' => 'checkbox',
                    'label' => esc_html__( 'Enable 2Checkout', 'qc-2co-payment-gateway' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => esc_html__( 'Title', 'qc-2co-payment-gateway' ),
                    'type' => 'text',
                    'description' => esc_html__( 'This controls the title which the user sees during checkout.', 'qc-2co-payment-gateway' ),
                    'default' => esc_html__( '2CO Inline Credit Card', 'qc-2co-payment-gateway' ),
                    'desc_tip'      => true,
                ),
                'description' => array(
                    'title' => esc_html__( 'Description', 'qc-2co-payment-gateway' ),
                    'type' => 'textarea',
                    'description' => esc_html__( 'This controls the description which the user sees during checkout.', 'qc-2co-payment-gateway' ),
                    'default' => esc_html__( 'Enable 2checkout Inline Credit Card payment', 'qc-2co-payment-gateway' )
                ),
                'seller_id' => array(
                    'title' => esc_html__( 'Seller ID', 'qc-2co-payment-gateway' ),
                    'type'          => 'text',
                    'description' => esc_html__( 'Please enter your 2Checkout account number; this is needed in order to take payment.', 'qc-2co-payment-gateway' ),
                    'default' => '',
                    'desc_tip'      => true,
                    'placeholder'   => ''
                ),
                'publishable_key' => array(
                    'title' => esc_html__( 'Publishable Key', 'qc-2co-payment-gateway' ),
                    'type'          => 'text',
                    'description' => esc_html__( 'Please enter your 2Checkout Publishable Key; this is needed in order to take payment.', 'qc-2co-payment-gateway' ),
                    'default' => '',
                    'desc_tip'      => true,
                    'placeholder'   => ''
                ),
                'private_key' => array(
                    'title' => esc_html__( 'Private Key', 'qc-2co-payment-gateway' ),
                    'type'          => 'text',
                    'description' => esc_html__( 'Please enter your 2Checkout Private Key; this is needed in order to take payment.', 'qc-2co-payment-gateway' ),
                    'default' => '',
                    'desc_tip'      => true,
                    'placeholder'   => ''
                ),
                'sandbox' => array(
                    'title' => esc_html__( 'Sandbox/Production', 'qc-2co-payment-gateway' ),
                    'type' => 'checkbox',
                    'label' => esc_html__( 'Use 2Checkout Sandbox', 'qc-2co-payment-gateway' ),
                    'default' => 'no',
                    'description' => esc_html__( '2CO has depreciated the Sandbox - so we could not provide this feature', 'qc-2co-payment-gateway' )
                ),
                'debug' => array(
                    'title'       => esc_html__( 'Debug Log', 'qc-2co-payment-gateway' ),
                    'type'        => 'checkbox',
                    'label'       => esc_html__( 'Enable logging', 'qc-2co-payment-gateway' ),
                    'default'     => 'no',
                    'description' => sprintf( esc_html__( 'Debug Information %s', 'qc-2co-payment-gateway' ), wc_get_log_file_path( 'twocheckout-credit-card' ) )
                )
            );

        }

        /**
         * Generate the credit card payment form
         *
         * @access public
         * @param none
         * @return string
         */
        function payment_fields() {
            $plugin_dir = plugin_dir_url(__FILE__);
            // Description of payment method from settings
            if ($this->description) { ?>
                <p><?php
                echo esc_html($this->description); ?>
                </p><?php
            } ?>

            <ul class="woocommerce-error" style="display:none" id="twocheckout_error_creditcard">
            <li><?php esc_html_e('Credit Card details are incorrect, please try again.', 'qc-2co-payment-gateway'); ?></li>
            </ul>

            <fieldset>

            <input id="sellerId" type="hidden" maxlength="16" width="20" value="<?php echo esc_attr($this->seller_id); ?>">
            <input id="publishableKey" type="hidden" width="20" value="<?php echo esc_attr($this->publishable_key); ?>">
            <input id="token" name="token" type="hidden" value="">

            <!-- Credit card number -->
            <p class="form-row form-row-first">
                <label for="ccNo"><?php esc_html_e( 'Credit Card number', 'qc-2co-payment-gateway' ) ?> <span class="required">*</span></label>
                <input type="text" class="input-text" id="ccNo" autocomplete="off" value="" />

            </p>

            <div class="clear"></div>

            <!-- Credit card expiration -->
            <p class="form-row form-row-first">
                <label for="cc-expire-month"><?php esc_html_e( 'Expiration date', 'qc-2co-payment-gateway') ?> <span class="required">*</span></label>
                <select id="expMonth" class="woocommerce-select woocommerce-cc-month">
                    <option value=""><?php esc_html_e( 'Month', 'qc-2co-payment-gateway' ) ?></option><?php
                    $months = array();
                    for ( $i = 1; $i <= 12; $i ++ ) {
                        $timestamp = mktime( 0, 0, 0, $i, 1 );
                        $months[ date( 'n', $timestamp ) ] = date( 'F', $timestamp );
                    }
                    foreach ( $months as $num => $name ) {
                        printf( '<option value="%02d">%s</option>', $num, $name );
                    } ?>
                </select>
                <select id="expYear" class="woocommerce-select woocommerce-cc-year">
                    <option value=""><?php esc_html_e( 'Year', 'qc-2co-payment-gateway' ) ?></option>
                    <?php
                    $years = array();
                    for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i ++ ) {
                        printf( '<option value="20%u">20%u</option>', $i, $i );
                    }
                    ?>
                </select>
            </p>
            <div class="clear"></div>

            <!-- Credit card security code -->
            <p class="form-row">
            <label for="cvv"><?php esc_html_e( 'Card security code', 'qc-2co-payment-gateway' ) ?> <span class="required">*</span></label>
            <input type="text" class="input-text" id="cvv" autocomplete="off" maxlength="4" style="width:55px" />
            <span class="help"><?php esc_html_e( '3 or 4 digits usually found on the signature strip.', 'qc-2co-payment-gateway' ) ?></span>
            </p>

            <div class="clear"></div>

            </fieldset>

           <script type="text/javascript">
                var formName = "order_review";
                var myForm = document.getElementsByName('checkout')[0];
                if(myForm) {
                    myForm.id = "tcoCCForm";
                    formName = "tcoCCForm";
                }
                jQuery('#' + formName).on("click", function(){
                    jQuery('#place_order').unbind('click');
                    jQuery('#place_order').click(function(e) {
                        e.preventDefault();
                        retrieveToken();
                    });
                });

                function successCallback(data) {
                    clearPaymentFields();
                    jQuery('#token').val(data.response.token.token);
                    jQuery('#place_order').unbind('click');
                    jQuery('#place_order').click(function(e) {
                        return true;
                    });
                    jQuery('#place_order').click();
                }

                function errorCallback(data) {
                    if (data.errorCode === 200) {
                        TCO.requestToken(successCallback, errorCallback, formName);
                    } else if(data.errorCode == 401) {
                        clearPaymentFields();
                        jQuery('#place_order').click(function(e) {
                            e.preventDefault();
                            retrieveToken();
                        });
                        jQuery("#twocheckout_error_creditcard").show();

                    } else{
                        clearPaymentFields();
                        jQuery('#place_order').click(function(e) {
                            e.preventDefault();
                            retrieveToken();
                        });
                        alert(data.errorMsg);
                    }
                }

                var retrieveToken = function () {
                    jQuery("#twocheckout_error_creditcard").hide();
                    if (jQuery('div.payment_method_twocheckout:first').css('display') === 'block') {
                        jQuery('#ccNo').val(jQuery('#ccNo').val().replace(/[^0-9\.]+/g,''));
                        TCO.requestToken(successCallback, errorCallback, formName);
                    } else {
                        jQuery('#place_order').unbind('click');
                        jQuery('#place_order').click(function(e) {
                            return true;
                        });
                        jQuery('#place_order').click();
                    }
                }

                function clearPaymentFields() {
                    jQuery('#ccNo').val('');
                    jQuery('#cvv').val('');
                    jQuery('#expMonth').val('');
                    jQuery('#expYear').val('');
                }

            </script>

            <?php
                if ($this->sandbox == 'yes'){
                    wp_enqueue_script('2checkout-credit-card-sandbox-seller-script', "https://sandbox.2checkout.com/checkout/api/script/publickey/<?php echo $this->seller_id ?>", array('jquery'), false, null);
                    wp_enqueue_script('2checkout-credit-card-sandbox-script', "https://sandbox.2checkout.com/checkout/api/2co.js", array('jquery'), false, null);
                }else{
                    wp_enqueue_script('2checkout-credit-card-sandbox-seller-script', "https://www.2checkout.com/checkout/api/script/publickey/<?php echo $this->seller_id ?>", array('jquery'), false, null);
                    wp_enqueue_script('2checkout-credit-card-sandbox-script', "https://www.2checkout.com/checkout/api/2co.js", array('jquery'), false, null);
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
            global $woocommerce;
            $order = new WC_Order($order_id);

            if ( 'yes' == $this->debug )
                $this->log( 'Generating payment form for order ' . $order->get_order_number() );

            // 2Checkout Args
            $twocheckout_args = array(
                                    'token'         => sanitize_text_field($_POST['token']),
                                    'sellerId'      => $this->seller_id,
                                    'currency' => get_woocommerce_currency(),
                                    'total'         => $order->get_total(),

                                    // Order key
                                    'merchantOrderId'    => $order->get_order_number(),

                                    // Billing Address info
                                    "billingAddr" => array(
                                        'name'          => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                                        'addrLine1'     => $order->get_billing_address_1(),
                                        'addrLine2'     => $order->get_billing_address_2(),
                                        'city'          => $order->get_billing_city(),
                                        'state'         => $order->get_billing_state(),
                                        'zipCode'       => $order->get_billing_postcode(),
                                        'country'       => $order->get_billing_country(),
                                        'email'         => $order->get_billing_email(),
                                        'phoneNumber'   => $order->get_billing_phone()
                                    )
                                );

            try {
                if ($this->sandbox == 'yes') {
                    TwocheckoutApi::setCredentials($this->seller_id, $this->private_key, 'sandbox');
                } else {
                    TwocheckoutApi::setCredentials($this->seller_id, $this->private_key);
                }
                $charge = Twocheckout_Charge::auth($twocheckout_args);
                if ( 'yes' == $this->debug ){
                    $this->log( 'Twocheckout Response ' . json_encode($charge) );
                }
                if (sanitize_text_field($charge['response']['responseCode']) == 'APPROVED') {
                    $this->log( 'Order arguments are ' . json_encode($twocheckout_args) );
                     $this->log( 'Order is completed for ' . $order->get_order_number() );
                    $order->payment_complete();
                    $this->log( "Order Payment Completed", $order->get_order_number() );
                    $order->update_status('completed');
                    $this->log( "Order Status Changed to Completed", $order->get_order_number() );
                    $order_redirect = $this->get_return_url( $order );
                    $order_redirect = add_query_arg('twoco','processed', $order_redirect);
                    return array(
                        'result' => 'success',
                        'redirect' => $order_redirect
                    );
                }
            } catch (Twocheckout_Error $e) {
                wc_add_notice($e->getMessage(), $notice_type = 'error' );
                return;
            }
        }

    }

    require_once(plugin_dir_path(__FILE__).'Twocheckout/TwocheckoutApi.php');

    /**
     * Add the gateway to WooCommerce
     **/
    function add_twocheckout_gateway($methods){
        $methods[] = 'WC_Gateway_Twocheckout';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_twocheckout_gateway');

}
