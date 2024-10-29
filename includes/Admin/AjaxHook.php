<?php
namespace Apperr;

class AjaxHook{
    public function __construct(){
        
        add_action( 'wp_ajax_post_data', array( $this, 'post_data') );
        add_action( 'wp_ajax_save_options', array( $this, 'save_options') );
        add_action( 'wp_ajax_get_options', array( $this, 'get_options') );
        add_action( 'wp_ajax_get_menus', array( $this, 'get_menus') );
        add_action( 'wp_ajax_get_taxonomies', array( $this, 'get_taxonomies') );
        add_action( 'wp_ajax_submit_build', array( $this, 'submit_build') );
        add_action( 'wp_ajax_install_jwt_auth_plgin', array( $this, 'apperr_auth_plugin_replace_plugin') );
        
    }

    public function post_data(){
        $custom_types = get_post_types(array('public'   => true,'_builtin' => false));
        $builtin_types = get_post_types(array('public'   => true,'_builtin' => true));
        $post_types = array_merge($builtin_types,$custom_types);
        $data = [];
        foreach($post_types as $post){
            $args = array(
                'post_type'=> $post,
                'posts_per_page'  => -1
            );
            $data[$post] = get_posts($args);
        }
        
        echo json_encode($data);
        wp_die();
    }

    public function get_menus(){
        
        echo json_encode(wp_get_nav_menus());
        wp_die();
    }
    public function get_taxonomies(){
        
        echo json_encode(get_taxonomies(['public'=>true],'object'));
        wp_die();
    }

    public function save_options(){

        $data = json_decode( stripslashes( $_POST['options_data'] ));
        
        update_option('apperr_options_data',$data);
        update_option( 'apperr_options_init', false);
        
        wp_die();
    
    }

    public function sanitizeObj($data){
        foreach ($data as &$value) {
            if (is_scalar($value)) {
                $value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                continue;
            }
    
            $value = $this->sanitizeObj($value);
        }
        return $data;
    }

    public function get_options(){
        
        $data = get_option('apperr_options_data',[]);
        if($data === []){
            $data = file_get_contents(plugin_dir_path(__FILE__)."/initial.json");
            $data = \json_decode($data);
            $data->settings->baseUrl = get_site_url();
        }
        $data = \json_encode($data);
        
        print_r($data);

        wp_die();
    
    }

    public function submit_build(){
        
        $data = json_decode( stripslashes( $_POST['data'] ) ) ;

        $data->splashLogo = base64_encode(file_get_contents($data->splashLogo));
        $data->appIcon = base64_encode(file_get_contents($data->appIcon));
        
        $url = "http://13.214.122.120:80/submit";
        
        $res = wp_remote_post($url,[
            'headers'   => [ 'Content-Type' => 'application/json' ],
            'body'       => json_encode($data),
            'data_format' => 'body'
        ]);

        print_r(($res['body']));
        wp_die();

    }





    function apperr_auth_plugin_replace_plugin() {
        
        $plugin_slug = 'jwt-authentication-for-wp-rest-api/jwt-auth.php';
        
        $plugin_zip = 'https://downloads.wordpress.org/plugin/jwt-authentication-for-wp-rest-api.latest-stable.zip';
        
        if ( $this->apperr_auth_plugin_is_plugin_installed( $plugin_slug ) ) {
        
        $this->apperr_auth_plugin_upgrade_plugin( $plugin_slug );
        $installed = true;
        } else {
        
        $installed = $this->apperr_auth_plugin_install_plugin( $plugin_zip );
        }
        
        if ( !is_wp_error( $installed ) && $installed ) {
        
        $activate = activate_plugin( $plugin_slug );
        echo 1;
        } else {
            echo 0;
        }
    }
     
    function apperr_auth_plugin_is_plugin_installed( $slug ) {
        if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        
        if ( !empty( $all_plugins[$slug] ) ) {
        return true;
        } else {
        return false;
        }
    }
   
    function apperr_auth_plugin_install_plugin( $plugin_zip ) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();
        
        $upgrader = new \Plugin_Upgrader();
        $installed = $upgrader->install( $plugin_zip );
    
        return $installed;
    }
   
    function apperr_auth_plugin_upgrade_plugin( $plugin_slug ) {
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        wp_cache_flush();
        
        $upgrader = new \Plugin_Upgrader();
        $upgraded = $upgrader->upgrade( $plugin_slug );
    
        return $upgraded;
    }
      


}


?>