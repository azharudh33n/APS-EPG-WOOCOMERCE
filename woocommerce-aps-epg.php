<?php
/*
Plugin name: WooCommerce APS Payment Gateway
Plugin URI: 
Description: Take credit card payments on your store.
Author: 
Author URI: 
Version: 1.0.2
*/

add_filter('woocommerce_payment_gateways', 'pay_add_gateway_class');
function pay_add_gateway_class($gateways)
{
    $gateways[] = 'WC_pay_Gateway'; // your class name is here
    return $gateways;
}

add_action('plugins_loaded', 'pay_init_gateway_class');
function pay_init_gateway_class()
{
    
    class WC_pay_Gateway extends WC_Payment_Gateway
    {
        
        
        public function __construct()
        {
            
            
            $this->id                 = 'pay'; // payment gateway plugin ID
            $this->method_title       = __("APS Payment", 'aps-payment');
            $this->icon               = apply_filters( 'woo_aps_icon', plugins_url( 'assets/mcvisa.png' , __FILE__ ) );; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields         = true; // in case you need a custom credit card form
            $this->method_title       = 'APS gateway';
            $this->method_description = 'APS payment gateway'; // will be displayed on the options page
            
            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this tutorial we begin with simple payments
            $this->supports = array(
                'products'
            );
            
            // Method with all the options fields
            $this->init_form_fields();
            
            // Load the settings.
            $this->init_settings();
            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled     = $this->get_option('enabled');
            $this->testmode    = 'yes' === $this->get_option('testmode');
            $this->gatewayUrt  = $this->testmode ? $this->get_option('test_url') : $this->get_option('live_url');
            $this->username    = $this->testmode ? $this->get_option('test_username') : $this->get_option('live_username');
            $this->password    = $this->testmode ? $this->get_option('test_password') : $this->get_option('live_password');
            
            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ));
            
            // We need custom JavaScript to obtain a token
            add_action('wp_enqueue_scripts', array(
                $this,
                'payment_scripts'
            ));
            
            // You can also register a webhook here
            // add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
            
            add_action('woocommerce_api_return_pay', array(
                $this,
                'check_payment'
            ));
            
        }
        
        
        public function init_form_fields()
        {
            
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'label' => 'Enable APS Gateway',
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Credit Card',
                    'desc_tip' => true
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'Pay with your credit card via our super-cool payment gateway.'
                ),
                'testmode' => array(
                    'title' => 'Test mode',
                    'label' => 'Enable Test Mode',
                    'type' => 'checkbox',
                    'description' => 'Place the payment gateway in test mode using test Url',
                    'default' => 'yes',
                    'desc_tip' => true
                ),
                'test_url' => array(
                    'title' => 'Test EPG Url',
                    'type' => 'text'
                ),
                'test_username' => array(
                    'title' => 'Test Merchant UserName',
                    'type' => 'text'
                ),
                'test_password' => array(
                    'title' => 'Test Merchant Password',
                    'type' => 'password'
                ),
                'live_url' => array(
                    'title' => 'Live EPG Url',
                    'type' => 'text'
                ),
                'live_username' => array(
                    'title' => 'Live Merchant UserName',
                    'type' => 'text'
                ),
                'live_password' => array(
                    'title' => 'Live Merchant Password',
                    'type' => 'password'
                )
            );
        }
        public function payment_fields()
        {
        }
        public function payment_scripts()
        {
        }
        public function validate_fields()
        {
        }
        
        public function process_payment($order_id)
        {
            
            global $woocommerce;
            
            
            $order = wc_get_order($order_id);
            
            
            $EpgUrl   = $this->gatewayUrt;
            $user     = $this->username;
            $password = $this->password;
            
            
            $currency = $order->get_currency();
            $amount   = $order->get_total();
            
            
            $DueAmount    = 0;
            $currencyCode = 0;
            
            if ($currency == 'USD') {
                $DueAmount    = $amount * 100;
                $currencyCode = 840;
            } elseif ($currency == 'IQD') {
                $DueAmount    = $amount * 1000;
                $currencyCode = 368;
            }
            
            $dateTimestamp = date('YmdHis');
            $EpgOrderid    = $order_id . '_' . $dateTimestamp;
            $redirectUrl   = home_url('/') . 'wc-api/return_pay?order_id=' . $EpgOrderid . '_success';
            $failUrl       = home_url('/') . 'wc-api/return_pay?order_id=' . $EpgOrderid . '_failed';
            
            $PayUrl = "$EpgUrl/rest/register.do?userName=$user&password=$password&orderNumber=$EpgOrderid&amount=$DueAmount&currency=$currencyCode&returnUrl=$redirectUrl&failUrl=$failUrl";
            
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $PayUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => array(
                    "Accept: */*",
                    "Accept-Encoding: gzip, deflate",
                    "Cache-Control: no-cache",
                    "Connection: keep-alive",
                    "Postman-Token: 14d2880b-2c66-40b7-8a32-489ff212fa50,bada3f2b-5320-4c52-b309-ec671d8ce0c0",
                    "Referer: $PayUrl",
                    "User-Agent: PostmanRuntime/7.18.0",
                    "cache-control: no-cache"
                )
            ));
            
            $response = curl_exec($curl);
            $err      = curl_error($curl);
            
            curl_close($curl);
            
            if ($err) {
                throw new Exception('Request failed: ' . $err);
            } else {
                
                if (strpos($response, 'formUrl') !== false) {
                    $arrayRes = json_decode($response, true);
                    $formUrl  = $arrayRes['formUrl'];
                    
                    if (empty($formUrl)) {
                        throw new Exception('Request failed: Please try again');
                    } else {
                        return array(
                            'result' => 'success',
                            'redirect' => $formUrl
                        );
                    }
                } else {
                    throw new Exception('Request failed: Please try again');
                }
            }
            
        }
        
        public function check_payment()
        {
            global $woocommerce;
            
            $arrayOrder  = explode("_", $_GET['order_id']);
            $orderid     = $arrayOrder[0];
            $orderStatus = $arrayOrder[2];
            
            if ($orderStatus == 'success') {
                $order = new WC_Order($orderid);
                $order->payment_complete();
                $order->update_status('processing');
                $order->reduce_order_stock();
                $woocommerce->cart->empty_cart();
                $order->add_order_note('Paid Successfully');
                wp_redirect($this->get_return_url($order));
                return;
            }
            
            if ($orderStatus == 'failed') {
                $FailOrder = new WC_Order($orderid);
                $FailOrder->update_status('failed');
                $FailOrder->add_order_note('failed');
                wp_redirect($FailOrder->get_cancel_order_url());
                return;
            }
            
            wp_redirect(home_url('/') . 'cart/');
            return;
        }
        
        
    }
}

?>