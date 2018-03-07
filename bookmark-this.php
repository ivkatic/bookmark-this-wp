<?php
/**
 * Plugin Name: Bookmark This
 * Plugin URI: https://devexus.net/
 * Description: Plugin koji omogućava logiranom korisniku da bookmarkira bilo koju stranicu ili post dok surfa po frontendu
 * Version: 1.0.0
 * Author: ikatic
 * Author URI: https://devexus.net/
 * Requires at least: 4.1
 * Tested up to: 4.9
 * Text Domain: bookmark-this
 * Domain Path: /languages/
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles core plugin hooks and action setup.
 *
 * @package dx-jobs-platform
 * @since 1.0.0
 */
class BookmarkThis {


	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
    protected $version;
    

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $dex-gallery    The string used to uniquely identify this plugin.
	 */
    protected $plugin_name;

    
    public function __construct() {
        
        $this->version = '1.0.0.';
        $this->plugin_name = 'Bookmark This';
        
        /**
         * Define some paths and links that could get handy later
         */
        define('BT_ABS', __DIR__);
        define('BT_CORE', BT_ABS .'/core');
        define('BT_TEMPLATES', BT_ABS .'/templates');
        define('BT_ASSETS', BT_ABS .'/assets');
        define('BT_ASSETS_URI', plugin_dir_url( __FILE__ ) .'assets');
        
        
        /**
         * Load main stuff
         */
        add_action( 'admin_bar_menu', array($this, 'add_button_bar'), 100);
        add_action( 'wp_ajax_save_bookmark', array($this, 'save_bookmark') );

        add_filter( 'query_vars', array($this, 'bthis_query_vars') );
        add_filter( 'template_include', array($this, 'bthis_templates') );

        /**
         * Register plugin activation and deactivation hooks
         */
        register_activation_hook( __FILE__, array($this, 'activate_bt_plugin') );
        register_deactivation_hook( __FILE__, array($this, 'deactivate_bt_plugin') );

		// Scripts
        add_action( 'plugins_loaded', array( $this, 'set_locale' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'public_scripts' ), 12 );  
                
    }

    /**
     * Add my-bookmarks to the rewrite urls
     * 
     * @since    1.0.0
     * @param mixed $wp_rewrite
     * @return mixed new set of rewrite rules
     */
    public function bthis_urls( $wp_rewrite ) {
        $feed_rules = array(
            'my-bookmarks/?$'  => 'index.php?bookmarks=true',
        );

        $wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
        return $wp_rewrite->rules;
    }

    /**
     * The code that runs during plugin activation.
	 *
	 * @since    1.0.0
     */
    public function activate_bt_plugin() {
        add_filter( 'generate_rewrite_rules', array($this, 'bthis_urls') );
        flush_rewrite_rules();
    }
    
    /**
     * The code that runs during plugin deactivation.
	 *
	 * @since    1.0.0
     */
    public function deactivate_bt_plugin() {
        flush_rewrite_rules();
    }

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 */
	public function set_locale() {
        load_plugin_textdomain(
			'bookmark-this',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
    }

    /**
     * Load css and js
     * 
     * @since 1.0.0
     */
    public function public_scripts() {
        wp_enqueue_style( 'bthis-css', BT_ASSETS_URI . '/bthis-public.css', array(), $this->version );

        wp_enqueue_script( 'bthis-public-js', BT_ASSETS_URI . '/bthis-public.js', array(), $this->version  );
        wp_localize_script( 'bthis-public-js', 'bthis',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    /**
     * Filter for which post types "bookmark this" is allowed
     * 
     * @since 1.0.0
     */
    public function bthis_post_types() {
        return apply_filters('bthis_allowed_post_types', array( 'post', 'page' ));
    }

    /**
     * Adds "Bookmark This" and "My bookmars" buttons to WP Admin Bar
     *
     * @param $admin_bar
     * @return void
     * @since 1.0.0
     */
    public function add_button_bar($admin_bar) {
        global $wp_query;

        if(!is_admin() && !isset($wp_query->query['bookmarks']) && in_array(get_post_type(), $this->bthis_post_types())) {
            $text = __('Bookmark This', 'bookmark-this');

            $u_bookmarks = get_user_meta(get_current_user_ID(), 'user_bookmarks', true);
            if(!empty($u_bookmarks) && in_array(get_the_ID(), $u_bookmarks))
                $text = __('Remove Bookmark', 'bookmark-this');
            
            $admin_bar->add_menu( array(
                'id'    => 'bthis-button',
                'title' => $text,
                'href'  => '#',
                'meta'  => array(
                    'title' => $text,            
                ),
            ));
        }

        $admin_bar->add_menu( array(
            'id'    => 'bthis-mybookmarks',
            'title' => 'My Bookmarks',
            'href'  => site_url('my-bookmarks/'),
            'meta'  => array(
                'title' => __('My Bookmarks', 'bookmark-this'),            
            ),
        ));
    }

    /**
     * Function triggered by WP Admin ajax that saves bookmarks to user meta
     *
     * @return string saved if user meta was updated
     * @since 1.0.0
     */
    public function save_bookmark() {
        global $post;

        $url     = wp_get_referer();
        $post_id = url_to_postid( $url );

        $user_id = get_current_user_id();

        $current_bookmarks = get_user_meta($user_id, 'user_bookmarks', true); 
        $new_bookmarks = $current_bookmarks;

        if(is_array($current_bookmarks) && !empty($current_bookmarks)) {
            if( !in_array($post_id, $current_bookmarks) )
                $new_bookmarks[] = $post_id;
            else
                $new_bookmarks = array_diff($new_bookmarks, array($post_id));
        } else {
            $new_bookmarks = array($post_id);
        }

        if(update_user_meta( $user_id, 'user_bookmarks', $new_bookmarks ) !== false ) {
            echo 'saved';
        }
        
        die();
    }

    /**
     * Needed to allow custom rewrite rules using your own arguments to work, 
     * or any other custom query variables you want to be publicly available.
     *
     * @param array $vars
     * @return void
     * @since 1.0.0
     */
    public function bthis_query_vars($vars) {
        $vars[] = 'bookmarks';
        return $vars;
    }

    /**
     * Returns template for displaying bookmarks
     * Either the default one in plugin dir, or user defined one in theme folder
     *
     * @param $template
     * @return $template template file that was located
     * @since 1.0.0
     */
    public function bthis_templates($template) {
        global $wp_query;

        if (isset($wp_query->query['bookmarks'])) {
            $template_path = 'bookmark-this/';
            $template_name = 'show-bookmarks.php';

            // Search template file in theme folder.
            $template = locate_template( array(
                $template_path . $template_name,
                $template_name
            ) );

            // Get plugins template file.
            if ( ! $template ) :
                $template = trailingslashit(BT_TEMPLATES) . $template_name;
            endif;
        }

        return $template;
    }
}

new BookmarkThis;