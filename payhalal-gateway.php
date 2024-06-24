<?php
/**
 * Plugin Name: PayHalal for WooCommerce
 * Plugin URI: payhalal.my
 * Description: Payment Without Was-Was
 * Author:  Nafeiz Nahar (Souqa Fintech Sdn Bhd)
 * Author URI: https://payhalal.my
 * Version: 1.0.3
 */

if (!defined('ABSPATH')) {

    exit; // Exit if accessed directly

}

add_action('plugins_loaded', 'payhalal_init_gateway_class');

function payhalal_init_gateway_class()
{
    
    add_filter('woocommerce_payment_gateways', 'payhalal_add_gateway');
	
    function payhalal_add_gateway( $methods )
    {
        // Add the PayHalal Gateway to the list of available payment gateways
        $methods[] = 'WC_Payhalal_Gateway';
        return $methods;

    }

    class WC_Payhalal_Gateway extends WC_Payment_Gateway
    {
        
        public function __construct()
        {
            // Basic gateway settings
            $this->id = 'payhalal'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'PayHalal Gateway';
            $this->method_description = 'Payment Without Was-Was'; // will be displayed on the options page

            // Can only supports product
            $this->supports = array(
                'products'
            );

            // Initialize settings and form fields
            $this->init_form_fields();
            $this->init_settings();

            // Get settings option
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            $this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
            $this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');
            $this->action_url = $this->testmode ? 'https://api-testing.payhalal.my/pay' : 'https://api.payhalal.my/pay';
            $this->product_description = 'WooCommerce';

            // Register actions for saving settings, handling requests, and handling callbacks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_api_payhalalrequest', array($this, 'request_handler'));
            add_action('woocommerce_api_wc_payhalal_gateway', array($this, 'callback_handler'));
            add_action('woocommerce_api_payhalalstatus', array($this, 'check_status'));
        }

        /** 
         * Define the settings form fields
         */
        public function init_form_fields()
        {
            
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable Payhalal Gateway',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Pay with PayHalal',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'Description during Checkout.',
                    'default' => '<img src="https://payhalal.my/images/pay-with-payhalal-wc.png" />',
                ),
                'testmode' => array(
                    'title' => 'Test mode',
                    'label' => 'Enable Test Mode',
                    'type' => 'checkbox',
                    'description' => 'Place the payment gateway in test mode.',
                    'default' => 'yes',
                    'desc_tip' => true,
                ),
                'test_publishable_key' => array(
                    'title' => 'Test App ID',
                    'type' => 'text'
                ),
                'test_private_key' => array(
                    'title' => 'Test Secret Key',
                    'type' => 'text',
                ),
                'publishable_key' => array(
                    'title' => 'Live App ID',
                    'type' => 'text'
                ),
                'private_key' => array(
                    'title' => 'Live Secret Key',
                    'type' => 'text'
                )
            );

        }

        /**
         * Process the payment
         */ 
        public function process_payment($order_id)
        {
            // Redirect to custom payment processing handler
            return array(
                'result' => 'success',
                'redirect' => get_home_url() . '/wc-api/payhalalrequest/?order_id=' . $order_id
            );

        }

        /**
         * Handle the payment request 
         */ 
        public function request_handler()
        {
            
            $order_id = $_GET['order_id'];
            if ($order_id > 0) {

                $order = wc_get_order($order_id);

                if ($order != "") {
                    
                    $data_out = array(
                        'app_id' => $this->publishable_key,
                        'amount' => WC()->cart->total,
                        'currency' => $order->get_currency(),
                        'product_description' => $this->product_description,
                        'order_id' => $order->get_order_number(),
                        'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                        'customer_email' => $order->get_billing_email(),
                        'customer_phone' => $order->get_billing_phone()
                    );
                    
                    // Create the hash for security purposes
                    $data_out['hash'] = hash('sha256', $this->private_key 
                        . $data_out['amount'] 
                        . $data_out['currency'] 
                        . $data_out['product_description'] 
                        . $data_out['order_id'] 
                        . $data_out['customer_name'] 
                        . $data_out['customer_email'] 
                        . $data_out['customer_phone']);
                    ?>

                    <form id="payhalal" method="post" action="<?= $this->action_url; ?>" >
                        <?php foreach ($data_out as $key => $value) { ?> 
                            <input type="hidden" name="<?= $key; ?>" value="<?= $value; ?>" >
                        <?php } ?>
                        <div style="display: grid; align-items: center; margin: auto;">
					        <button type="submit" style="margin: auto; align-items: center; text-align: center;">
                                Please click here if you are not redirected within a few seconds.
                            </button>
                        </div>
                    </form>

                    <script type="text/javascript">
                        document.getElementById("payhalal").submit()
                    </script>

                    <?php 

                } else {
                    
                    wc_add_notice('Invalid Order ID', 'error');
                    wp_redirect(WC()->cart->get_cart_url());

                }

            } else {

                wc_add_notice('Invalid Order ID!', 'error');
                wp_redirect(WC()->cart->get_cart_url());

            }

            exit;

        }

        /**
         * Check payment status
         */
        public function check_status()
        {
            
            $order_id = $_GET["order_id"];
            $order = wc_get_order($order_id);
            $allowed_order_status = array("processing", "completed");
            if (in_array($order->status, $allowed_order_status)) {
                
                wp_redirect($this->get_return_url($order));

            } else {

                wc_add_notice('Transaction was not processed or complete.', 'error');
                wp_redirect(WC()->cart->get_cart_url());

            }

            exit;
            
        }

        /**
         * Handle the callback from PayHalal
         */
        public function callback_handler()
        {
            
            $post_array = $_POST;

            if (!empty($post_array)) {

                $order = wc_get_order($post_array['order_id']);

                if ($order) {

                    $mode = $this->testmode;
                    $key = $mode ? $this->get_option('test_private_key') : $this->get_option('private_key');
                    $app = $mode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');    
		    
                    $data_out = array(
                        "app_id" => $app,
                        "amount" => $order->get_total(),
                        "currency" => $order->get_currency(),
                        "product_description" => $this->product_description,
                        "order_id" => $post_array["order_id"],
                        "customer_name" => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
                        "customer_email" => $order->get_billing_email(),
                        "customer_phone" => $order->get_billing_phone(),
                        "status" => $post_array["status"]
                    );
		
                    $dataout_hash = self::ph_sha256($data_out, $key);

                    if ($dataout_hash == $post_array['hash'] && $post_array['amount'] == $order->total) {

                        switch ($post_array['status']) {

                            case 'SUCCESS':
                                // Payment successful
                                WC()->cart->empty_cart(); // Clear the cart
                                $order->payment_complete();
                                $order->add_order_note(__('Payment Success. Transaction ID: ' . $post_array['transaction_id']));
                                $order->add_order_note(__('Payment method : ' . $post_array["channel"]));
                                wp_redirect($this->get_return_url($order));
                                break;

                            case 'FAIL':
                                // Payment failed
                                $order->update_status('failed', 'Payment Failed.');
                                wc_add_notice('Payment Failed. Please try again.', 'error');
                                wp_redirect(WC()->cart->get_cart_url());
                                break;

                            case 'PENDING':
                                // Payment pending
                                $order->update_status('pending', 'Payment In Pending.');
                                wc_add_notice('Payment In Pending.', 'notice');
                                wp_redirect($this->get_return_url($order));
                                break;

                            case 'TIMEOUT':
                                // Payment timeout
                                $order->update_status('failed', 'Payment Timeout.');
                                wc_add_notice('Payment Timeout.', 'error');
                                wp_redirect(WC()->cart->get_cart_url());
                                break;

                            default:
                                wp_redirect(WC()->cart->get_cart_url());
                                break;

                        }

                    } else {

                        wc_add_notice('Invalid hash.', 'error');
                        $order->update_status('failed', 'Payment Error.');
                        wp_redirect(WC()->cart->get_cart_url());

                    }

                } else {

                    wc_add_notice('Invalid Order ID.', 'error');
                    wp_redirect(WC()->cart->get_cart_url());

                }

            } else {

                wc_add_notice('No data was sent.', 'error');
                wp_redirect(WC()->cart->get_cart_url());

            }

            exit;

        }

        /**
         * Check if SSL is enabled for checkout pages
         */
        public function do_ssl_check()
        {
            
            if ($this->enabled == "yes" && get_option('woocommerce_force_ssl_checkout') == "no") {

                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";

            }


        }

        /**
         * Generate SHA-256 hash
         */
        public function ph_sha256($data, $secret)
        {
            
            $hash = hash('sha256', $secret . $data["amount"] . $data["currency"] . $data["product_description"] . $data["order_id"] . $data["customer_name"] . $data["customer_email"] . $data["customer_phone"] . $data["status"]);
            return $hash;

        }

    }

}

?>