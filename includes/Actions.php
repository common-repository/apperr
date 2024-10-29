<?php

namespace Apperr;

class Actions
{
    /**
     * [__construct description]
     */
    public function __construct()
    {
        // Hooking up our function to theme setup
        //add_action('init', [$this, 'create_posttype']);
        //add_action( 'init', [ $this, 'wpb_custom_new_menu' ] );
        //add_filter('rest_product_collection_params', [$this, 'maximum_api_filter'], 10, 1);
        $this->create_posttype();
        $this->wpb_custom_new_menu();
        add_action('admin_enqueue_scripts', [$this,'enqueue_media_script']);
        add_action('rest_api_init', [$this,'register_rest_images'] );
    }



    function register_rest_images(){
        register_rest_field( array('post'),
            'featured_image',
            array(
                'get_callback'    => [$this,'get_rest_featured_image'],
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }

    function get_rest_featured_image( $object, $field_name, $request ) {
        if( $object['featured_media'] ){
            $img = wp_get_attachment_image_src( $object['featured_media'], 'app-thumb' );
            return $img[0];
        }
        return "None";
    }


    public function wpb_custom_new_menu() {
        register_nav_menu('apper_hamburger_menu',__( 'Apper Hamburger Menu' ));
    }


    function enqueue_media_script() {
        //Enqueue media.
        wp_enqueue_media();

    }
    function create_posttype()
    {

        register_post_type(
            'apperr_address',
            // CPT Options
            array(
                'labels' => array(
                    'name' => __('User Addresses'),
                    'singular_name' => __('User Address')
                ),
                'public' => true,
                'has_archive' => true,
                'rewrite' => array('slug' => 'apperr_address'),
                'show_in_rest' => false,
                'supports' => array('custom-fields'),

            )
        );
    }


}
