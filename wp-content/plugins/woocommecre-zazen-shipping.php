<?php
/*
Plugin Name: Woocommerce Zazen Shipping back-up
Plugin URI: http://wordpress.org/plugins
Description: This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.
Author: IZweb
Version: 1.1
Author URI: https://www.izweb.biz/
*/

    //function for making curl call using GET
    /*function curlUsingGet($url, $data) {

        if(empty($url) OR empty($data)) {
            return 'Error: invalid Url or Data';
        }

        //url-ify the data for the get  : Actually create datastring
        $fields_string = '';

        foreach($data as $key=>$value) {
            $fields_string[]=$key.'='.urlencode($value).'&';
        }

        $urlStringData = $url.'?'.implode('&',$fields_string);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10); # timeout after 10 seconds, you can increase it
        curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
        curl_setopt($ch, CURLOPT_URL, $urlStringData ); #set the url and get string together

        $return = curl_exec($ch);
        curl_close($ch);

        return $return;
    }

    /*$ziva_test_api_key = '5d74bbe6-588c-4eb1-b432-0c3eea374b2e';
    $ziva_test_api_url = 'http://ziva.azurewebsites.net/api/deliveryoptions';

    //example cart items as per ZIVA documentation.
    $example_cart_items = '{"items":[{"productId":"abc123","quantity":2},{"productId":"cdf345","quantity":3},{"productId":"jky432","quantity":20}],"deliveryAddress":{"suburb":"Newmarket","city":"Brisbane","state":"Queensland","postcode":"4051","country":"Australia"},"options":{"authorisedToLeave":true,"freeShipping":false}}';

    $data = array('API_Key' => $ziva_test_api_key, 'items' => $example_cart_items);
    $response = curlUsingGet($ziva_test_api_url, $data);

    echo($response); //outputs shipping options in json format (as per example in api spec)
*/


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    function IZ_zazen_shipping_method_init() {
        if ( ! class_exists( 'WC_Zazen_Shipping_Method' ) ) {
            class WC_Zazen_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'zazen_shipping_method'; // Id for your shipping method. Should be uunique.
                    $this->method_title       = __( 'Zazen Shipping Method' );  // Title shown in admin
                    $this->method_description = __( 'Description of your shipping method' ); // Description shown in admin

                    $this->init();
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

                    // Define user set variables
                    $this->enabled		  = $this->get_option( 'enabled' );
                    $this->title 		  = $this->get_option( 'title' );
                    $this->availability   = $this->get_option( 'availability' );
                    $this->countries 	  = $this->get_option( 'countries' );
                    $this->type 		  = $this->get_option( 'type' );
                    $this->api_key 		  = $this->get_option( 'api_key' );
                    $this->api_url 		  = $this->get_option( 'api_url' );


                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }

                /*init form fields*/
                function init_form_fields() {

                    $this->form_fields = array(
                        'enabled' => array(
                            'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
                            'type' 			=> 'checkbox',
                            'label' 		=> __( 'Enable this shipping method', 'woocommerce' ),
                            'default' 		=> 'no',
                        ),
                        'title' => array(
                            'title' 		=> __( 'Method Title', 'woocommerce' ),
                            'type' 			=> 'text',
                            'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                            'default'		=> __( 'Zazen Shipping', 'woocommerce' ),
                            'desc_tip'		=> true
                        ),
                        'availability' => array(
                            'title' 		=> __( 'Availability', 'woocommerce' ),
                            'type' 			=> 'select',
                            'default' 		=> 'all',
                            'class'			=> 'availability',
                            'options'		=> array(
                                'all' 		=> __( 'All allowed countries', 'woocommerce' ),
                                'specific' 	=> __( 'Specific Countries', 'woocommerce' ),
                            ),
                        ),
                        'countries' => array(
                            'title' 		=> __( 'Specific Countries', 'woocommerce' ),
                            'type' 			=> 'multiselect',
                            'class'			=> 'chosen_select',
                            'css'			=> 'width: 450px;',
                            'default' 		=> '',
                            'options'		=> WC()->countries->get_shipping_countries(),
                            'custom_attributes' => array(
                                'data-placeholder' => __( 'Select some countries', 'woocommerce' )
                            )
                        ),
                        'api_key' => array(
                            'title' 		=> __( 'API Key', 'woocommerce' ),
                            'type' 			=> 'text',
                            'class'			=> 'chosen_select',
                            'css'			=> 'width: 450px;',
                            'default' 		=> '',
                        ),
                        'api_url' => array(
                            'title' 		=> __( 'API URL', 'woocommerce' ),
                            'type' 			=> 'text',
                            'class'			=> 'chosen_select',
                            'css'			=> 'width: 450px;',
                            'default' 		=> '',
                        )
                    );
                }
                /*****************************/

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @return array
                 */
                function calculate_shipping() {
                    $cart_items = array(
                        'items'             => array(),
                        'deliveryAddress'   => array("suburb"=>"Newmarket","city"=>"Brisbane","state"=>"Queensland","postcode"=>"4051","country"=>"Australia"),
                        'options'           => array("authorisedToLeave"=>true,"freeShipping"=>false)
                    );
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                        $_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                        $cart_items['items'][] = array('productId'  => $_product->get_sku(),'quantity'=>$cart_item['quantity']);
                    }
                    $example_cart_items = json_encode($cart_items);
                    $data = array('API_Key' => $this->api_key, 'items' => $example_cart_items);

                    $result = (array)json_decode ($this->curlUsingGet($this->api_url, $data));
                   
                    if(!empty($result['data']))
                        foreach ($result['data'] as $rate){
                            $args = array(
                                'id' 	=> $rate->id,
                                'label' => $rate->days." days",
                                'cost' 	=> $rate->price,
                                'taxes' => false
                            );
                            $this->add_rate( $args );
                        }
                }

                /**
                 * is_available function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return bool
                 */
                function is_available( $package ) {

                    if ( $this->enabled == "no" ) return false;

                    $ship_to_countries = '';

                    if ( $this->availability == 'specific' )
                        $ship_to_countries = $this->countries;
                    else
                        $ship_to_countries = array_keys( WC()->countries->get_shipping_countries() );

                    if ( is_array( $ship_to_countries ) )
                        if ( ! in_array( $package['destination']['country'], $ship_to_countries ) )
                            return false;

                    return true;
                }


                //call API
                function curlUsingGet($url, $data) {

                    //url-ify the data for the get  : Actually create datastring
                    $fields_string = '';

                    foreach($data as $key=>$value) {
                        $fields_string[]=$key.'='.urlencode($value).'&';
                    }

                    $urlStringData = $url.'?'.implode('&',$fields_string);

                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10); # timeout after 10 seconds, you can increase it
                    curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
                    curl_setopt($ch, CURLOPT_URL, $urlStringData ); #set the url and get string together

                    $return = curl_exec($ch);
                    curl_close($ch);

                    return $return;
                }
            }
        }
    }

    add_action( 'woocommerce_shipping_init', 'IZ_zazen_shipping_method_init' );

    function IZ_zazen_shipping_method( $methods ) {
        $methods[] = 'WC_Zazen_Shipping_Method';
        return $methods;
    }

    add_filter( 'woocommerce_shipping_methods', 'IZ_zazen_shipping_method' );
}
