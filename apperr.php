<?php

/*
Plugin Name: Apperr App Builder
Plugin URI: https://prasadkirpekar.me/
Description: Apperr is one stop solution for all your WordPress App needs. We have integration with WooCommerce and built in WordPress features to get you started with your WordPress site App
Version: 0.1.0
Author: Prasad Kirpekar
Author URI: mailto:prasadkirpekar@outlook.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: apperr
Domain Path: /languages
*/
/**
 * Copyright (c) 2022 Prasad Kirpekar (email: prasadkirpekar96@gmail.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */
// don't call the file directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !function_exists( 'app_fs' ) ) {
    // Create a helper function for easy SDK access.
    function app_fs()
    {
        global  $app_fs ;
        
        if ( !isset( $app_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $app_fs = fs_dynamic_init( array(
                'id'             => '9807',
                'slug'           => 'apperr',
                'type'           => 'plugin',
                'public_key'     => 'pk_0e6398f22b9f26e7687f387dccdc0',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug' => 'apperr',
            ),
                'is_live'        => true,
            ) );
        }
        
        return $app_fs;
    }
    
    // Init Freemius.
    app_fs();
    // Signal that SDK was initiated.
    do_action( 'app_fs_loaded' );
}

/**
 * ApperrPlugin class
 *
 * @class ApperrPlugin The class that holds the entire ApperrPlugin plugin
 */
final class ApperrPlugin
{
    /**
     * Plugin version
     *
     * @var string
     */
    public  $version = '0.1.0' ;
    /**
     * Holds various class instances
     *
     * @var array
     */
    private  $container = array() ;
    /**
     * Constructor for the ApperrPlugin class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    public function __construct()
    {
        $this->define_constants();
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
    }
    
    /**
     * Initializes the ApperrPlugin() class
     *
     * Checks for an existing ApperrPlugin() instance
     * and if it doesn't find one, creates it.
     */
    public static function init()
    {
        static  $instance = false ;
        if ( !$instance ) {
            $instance = new ApperrPlugin();
        }
        return $instance;
    }
    
    /**
     * Magic getter to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __get( $prop )
    {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[$prop];
        }
        return $this->{$prop};
    }
    
    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __isset( $prop )
    {
        return isset( $this->{$prop} ) || isset( $this->container[$prop] );
    }
    
    /**
     * Define the constants
     *
     * @return void
     */
    public function define_constants()
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        define( 'BASEPLUGIN_VERSION', $this->version );
        define( 'BASEPLUGIN_FILE', __FILE__ );
        define( 'BASEPLUGIN_PATH', dirname( BASEPLUGIN_FILE ) );
        define( 'BASEPLUGIN_INCLUDES', BASEPLUGIN_PATH . '/includes' );
        define( 'BASEPLUGIN_URL', plugins_url( '', BASEPLUGIN_FILE ) );
        define( 'BASEPLUGIN_ASSETS', BASEPLUGIN_URL . '/assets' );
        define( 'WOOCOMMERCE_ACTIVE', is_plugin_active( 'woocommerce/woocommerce.php' ) );
    }
    
    /**
     * Load the plugin after all plugis are loaded
     *
     * @return void
     */
    public function init_plugin()
    {
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate()
    {
        $installed = get_option( 'baseplugin_installed' );
        $apperr_options = get_option( 'apperr_options_init' );
        if ( !$installed ) {
            update_option( 'baseplugin_installed', time() );
        }
        
        if ( !$apperr_options ) {
            update_option( 'apperr_options_init', true );
        } else {
            update_option( 'apperr_options_init', false );
        }
        
        update_option( 'baseplugin_version', BASEPLUGIN_VERSION );
    }
    
    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate()
    {
    }
    
    /**
     * Include the required files
     *
     * @return void
     */
    public function includes()
    {
        require_once BASEPLUGIN_INCLUDES . '/Assets.php';
        if ( $this->is_request( 'admin' ) ) {
            require_once BASEPLUGIN_INCLUDES . '/Admin.php';
        }
        if ( $this->is_request( 'frontend' ) ) {
            require_once BASEPLUGIN_INCLUDES . '/Frontend.php';
        }
        if ( $this->is_request( 'ajax' ) ) {
            require_once BASEPLUGIN_INCLUDES . '/Admin/AjaxHook.php';
        }
        require_once BASEPLUGIN_INCLUDES . '/Api.php';
        require_once BASEPLUGIN_INCLUDES . '/Actions.php';
    }
    
    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks()
    {
        add_action( 'init', array( $this, 'init_classes' ) );
        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );
    }
    
    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes()
    {
        if ( $this->is_request( 'admin' ) ) {
            $this->container['admin'] = new Apperr\Admin();
        }
        if ( $this->is_request( 'frontend' ) ) {
            $this->container['frontend'] = new Apperr\Frontend();
        }
        if ( $this->is_request( 'ajax' ) ) {
            // $this->container['ajax'] =  new Apperr\Ajax();
            $this->container['ajax'] = new Apperr\AjaxHook();
        }
        $this->container['api'] = new Apperr\Api();
        $this->container['assets'] = new Apperr\Assets();
        $this->container['actions'] = new Apperr\Actions();
    }
    
    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup()
    {
        load_plugin_textdomain( 'baseplugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
    
    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or frontend.
     *
     * @return bool
     */
    private function is_request( $type )
    {
        switch ( $type ) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined( 'DOING_AJAX' );
            case 'rest':
                return defined( 'REST_REQUEST' );
            case 'cron':
                return defined( 'DOING_CRON' );
            case 'frontend':
                return (!is_admin() || defined( 'DOING_AJAX' )) && !defined( 'DOING_CRON' );
        }
    }

}
// ApperrPlugin
$baseplugin = ApperrPlugin::init();