<?php

namespace Apperr\Api;

use WP_REST_Controller;

/**
 * REST_API Handler
 */
class ApperrService extends WP_REST_Controller
{

    /**
     * [__construct description]
     */
    public function __construct()
    {
        $this->namespace = 'apperr';
        $this->rest_base = 'v1';
    }

    /**
     * Register the routes
     *
     * @return void
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => $this->get_collection_params(),
                )
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . "/screenconfig",
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_screen_configs'),
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => function($request){
                        return true;
                    }
                )
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . "/get_custom_posts",
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_custom_posts'),
                    'args'                => $this->get_collection_params(),
                    'permission_callback' => function($request){
                        return true;
                    }
                )
            )
        );

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/menu/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => [$this, 'wp_menu_single'],
            'permission_callback' => function($request){
                return true;
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/menu', array(
            'methods' => 'GET',
            'callback' => [$this, 'wp_menu_route'],
            'permission_callback' => function($request){
                return true;
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/init', array(
            'methods' => 'GET',
            'callback' => [$this, 'init_plugin_options'],
            'permission_callback' => function($request){
                return true;
            }
        ));
        

        if(WOOCOMMERCE_ACTIVE){
            $this->register_woocommerce_routes();
        }

        
        
    }



    function register_woocommerce_routes(){

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/order/place', array(
            'methods' => 'POST',
            'callback' => [$this, 'apperr_create_order'],
            'permission_callback' => function ($request) {
                return is_user_logged_in();
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/cart/create', array(
            'methods' => 'POST',
            'callback' => [$this, 'apperr_add_to_cart'],
            'permission_callback' => function ($request) {
                return is_user_logged_in();
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/address/create', array(
            'methods' => 'POST',
            'callback' => [$this, 'apperr_add_address'],
            'permission_callback' => function ($request) {
                return is_user_logged_in();
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/address/update', array(
            'methods' => 'POST',
            'callback' => [$this, 'apperr_update_address'],
            'permission_callback' => function ($request) {
                return is_user_logged_in();
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/address/all', array(
            'methods' => 'GET',
            'callback' => [$this, 'apperr_all_address'],
            'permission_callback' => function ($request) {
                return is_user_logged_in();
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/woocommerce/pages', array(
            'methods' => 'GET',
            'callback' => [$this, 'apperr_woocommerce_pages'],
            'permission_callback' => function($request){
                return true;
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/myorders', array(
            'methods' => 'GET',
            'callback' => [$this, 'apperr_woocommerce_myorders'],
            'permission_callback' => function ($request) {
                return is_user_logged_in();
            }
        ));

        register_rest_route($this->namespace,
        '/' . $this->rest_base . '/woocommerce/currency', array(
            'methods' => 'GET',
            'callback' => [$this, 'apperr_woocommerce_currency'],
            'permission_callback' => function($request){
                return true;
            }
        ));



    }

    function init_plugin_options(){
        return ["isWooCommerceActive"=>WOOCOMMERCE_ACTIVE];
    }


    function get_menu_items_by_registered_slug($menu_slug) {

        $menu_items = array();

        if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[ $menu_slug ] ) ) {
            $menu = get_term( $locations[ $menu_slug ] );
            $menu_items = wp_get_nav_menu_items($menu->term_id);
        }
        return $menu_items;

    }


    function wp_menu_route() {
        return $this->get_menu_items_by_registered_slug('apper_hamburger_menu');
    }




    function apperr_woocommerce_currency($req)
    {

        $wooArray = array();

        $wooStrings = ['woocommerce_currency', 'woocommerce_currency_pos'];

        foreach ($wooStrings as $woo) {
            if (get_option($woo)) {
                $wooArray[$woo] = get_option($woo);
            }
        }

        $wooArray["woocommerce_currency_symbol"] = get_woocommerce_currency_symbol();


        return $wooArray;
    }


    function apperr_woocommerce_myorders($req)
    {

        $user_id = get_current_user_id();
        $orderArg = array(
            'customer_id' => $user_id,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $data = [];
        $orders = wc_get_orders($orderArg);

        foreach ($orders as  $orderData) {
            $items = $orderData->get_items();
            $itemsData = [];
            foreach ($items as $item) {
                array_push($itemsData, $item->get_data());
            }

            array_push($data, array('order' => $orderData->get_data(), 'products' => $itemsData));
        }
        return $data;
    }


    function apperr_woocommerce_pages($req)
    {

        $wooArray = array();

        $wooStrings = ['woocommerce_shop_page_id', 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_pay_page_id', 'woocommerce_thanks_page_id', 'woocommerce_myaccount_page_id', 'woocommerce_edit_address_page_id', 'woocommerce_view_order_page_id', 'woocommerce_terms_page_id'];

        foreach ($wooStrings as $woo) {
            if (get_option($woo)) {
                $wooArray[$woo] = get_option($woo,-1);
            }
        }



        return $wooArray;
    }

    function apperr_all_address($req)
    {
        $args = array(
            'author'        =>  get_current_user_id(),
            'orderby'       =>  'post_date',
            'order'         =>  'ASC',
            'post_type' => 'apperr_address',
            'posts_per_page' => 100,
            'post_status' => 'publish'
        );
        $addresses = [];
        foreach (get_posts($args) as $address) {
            array_push($addresses, get_post_meta($address->ID, 'apperr_address', true));
        }
        echo json_encode($addresses);
    }

    function apperr_update_address($req)
    {
        $data = $req->get_params();
        $post_id = $data['id'];
        //$data['address']['id'] = $post_id;
        update_post_meta($post_id, 'apperr_address', $data);
    }


    function apperr_add_address($req)
    {
        $my_post = array(
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'post_type' => 'apperr_address'
        );
        $post_id = wp_insert_post($my_post);
        $data = $req->get_params();
        $toSave = [];
        $data['id'] = $post_id;
        //$toSave['address'] = $data;
        //$toSave['id'] = $post_id;
        update_post_meta($post_id, 'apperr_address', $data);
    }

    function apperr_add_to_cart($req)
    {
        wc()->frontend_includes();

        WC()->session = new \WC_Session_Handler();
        WC()->session->init();
        WC()->customer = new \WC_Customer(get_current_user_id(), true);
        WC()->cart = new \WC_Cart();
        $data = $req->get_params();
        foreach ($data as $item) {

            WC()->cart->add_to_cart($item['productid'], $item['quantity']);
        }
        print(WC()->cart->get_cart_contents_total());
    }


    function apperr_create_order($req)
    {

        $data = $req->get_params();

        $items = $data['items'];

        $notes = $data['notes'];

        $payment = $data['payment'];

        $address = get_post_meta($data['address_id'], "apperr_address", true);


        $orderAddress = array(
            'first_name' => $address['firstName'], //add first name to order address
            'last_name'  => $address['lastName'], //add first name to order address
            'email'      => $address['email'], //add email to order address
            'phone'      => $address['mobile'], //add phone to order address
            'address_1'  => $address['addressLine'], //add address to order address
            'address_2'  => $address['addressLine'],
            'city'       => $address['city'], //add city to order address
            'state'      => $address['state'], //add state to order address
            'postcode'   => $address['pincode'], //add postcode to order address
            'country'    => $address['country'] //add country to order address
        );

        $userid = get_current_user_id();
        $cutomOrder = wc_create_order(array('customer_id' => $userid)); // create new object for order

        foreach ($notes as $note) {
            $cutomOrder->add_order_note($note);
        }

        foreach ($items as $item) {
            $productId = $item['id']; // set product id
            $quantity  = $item['quantity'];  // set product quantity
            $cutomOrder->add_product(get_product($productId), $quantity); // add product and quantity to order
        }

        $cutomOrder->set_address($orderAddress, 'billing'); // set billing address
        $cutomOrder->set_address($orderAddress, 'shipping');


        // set shipping address
        $cutomOrder->calculate_totals(); // calculate total
        $pay_now_url = esc_url( $cutomOrder->get_checkout_payment_url() );
        return ['checkout'=>$pay_now_url,'success'=>true];
        
        // update_post_meta($cutomOrder->id, '_payment_method', $payment['name']); //set payment method
        // update_post_meta($cutomOrder->id, '_payment_method_title', $payment['name']);

        // if ($payment['paid']) {
        //     $cutomOrder->update_status('processing');
        // }
    }


    function wp_menu_single($data)
    {
        $menuID = $data['id']; // Get the menu from the ID
        $primaryNav = wp_get_nav_menu_items($menuID); // Get the array of wp objects, the nav items for our queried location.
        return $primaryNav;
    }



    /**
     * Retrieves a collection of items.
     *
     * @param WP_REST_Request $request Full details about the request.
     *
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_items($request)
    {
        $items = [
            'foo' => 'bar'
        ];

        $response = rest_ensure_response($items);

        return $response;
    }

    /**
     * Checks if a given request has access to read the items.
     *
     * @param  WP_REST_Request $request Full details about the request.
     *
     * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check($request)
    {
        return current_user_can('administrator');
    }

    /**
     * Retrieves the query params for the items collection.
     *
     * @return array Collection parameters.
     */
    public function get_collection_params()
    {
        return [];
    }

    /* Apperr Screen config */
    public function get_screen_configs()
    {
        return get_option('apperr_options_data', [])->screenComponents;
    }

    public function get_custom_posts($request)
    {

        $params = $request->get_query_params();
        $args = [
            'post_type' => $params['post_type'],
            'numberposts' => -1,
        ];
        $posts = get_posts($args);

        return $posts;

        $posts = [];

        $data['id'] = $post[0]->ID;
        $data['slug'] = $post[0]->post_name;
        $data['title'] = $post[0]->post_title;
        $data['content'] = $post[0]->post_content;
        $data['excerpt'] = $post[0]->post_excerpt;

        return $data;
    }
}
