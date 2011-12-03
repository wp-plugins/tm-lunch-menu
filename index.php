<?php
/*
Plugin Name: TM Lunch Menu
Plugin URI:
Description: Designed for easy display, editing, & scheduling of lunch menus or other similar lists using a widget.
Version: 1.0
Author: David Wood
Author URI: http://iamdavidwood.com
============================================================================================================
This software is provided "as is" and any express or implied warranties, including, but not limited to, the
implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall
the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or
consequential damages (including, but not limited to, procurement of substitute goods or services; loss of
use, data, or profits; or business interruption) however caused and on any theory of liability, whether in
contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of
this software, even if advised of the possibility of such damage.

For full license details see license.txt
============================================================================================================
*/

// Define constants
define( 'TM_LM_URL', plugin_dir_url( __FILE__ ) ); // URL path to plugin folder
define( 'TM_LM_PATH', dirname(__FILE__) ); // Local path to plugin folder
define( 'TM_LM_FILE', __FILE__); // This file

// Include files
require(TM_LM_PATH . '/inc/post_type.php'); // Custom post type registration class
require(TM_LM_PATH . '/inc/meta_box.php'); // Custom meta box registration class

if(!class_exists('tm_lunch_menu')):
class tm_lunch_menu {
    private $version = '1.0',
        $default_settings = array(
            'days' => array(1,2,3,4,5), // Stored as numbers
            'no_menu' => 'hide',
            'no_menu_msg' => 'Sorry, there is no menu to display at this time.',
            'weeks' => 3
        ),
        $tm_help;

    function __construct() {
        // Update/Initialize if needed
        if(get_option('tm_lunch_menu_ver') != $this->version) $this->update();

        // Register deactivation hook
        register_deactivation_hook( TM_LM_FILE, array($this, 'deactivation_hook') );

        // Add Scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_print_styles', array($this, 'admin_enqueue_styles'));

        // Add admin settings page
        add_action('admin_menu', array($this, 'add_admin_page'));
        //add_action('admin_init', array($this, 'tm_lunchmenu_help_func'));

        // Register post type and meta boxes
        $this->register_post_type();
        $this->register_meta_box();

        // Manage display for post type
        add_filter('manage_edit-tm_lunch_menu_columns', array(&$this, 'add_post_columns'));
        add_action('manage_tm_lunch_menu_posts_custom_column', array(&$this, 'add_data_post_columns'));
        add_filter('manage_edit-tm_lunch_menu_sortable_columns', array($this, 'add_sortable_columns'));
        add_filter('request', array($this, 'menu_order_columns_by'));
    }

    function admin_enqueue_scripts(){
        wp_register_script('jquery-datepicker', plugins_url('/js/jquery.ui.datepicker.min.js', TM_LM_FILE), array('jquery-ui-core'), $this->version);
        wp_enqueue_script('tm-date-time', plugins_url('/js/date-time.js', TM_LM_FILE), array('jquery-datepicker'), $this->version);
        wp_enqueue_script('tm-lunchmenu-save', plugins_url('/js/tm_lunch_menusave.js', TM_LM_FILE), array('tm-date-time'), $this->version);
        wp_localize_script('tm-date-time', 'tmDateTime', array('pluginURL' => TM_LM_URL));
    }

    function admin_enqueue_styles(){
        wp_enqueue_style('jquery-lightness-css', plugins_url('/css/jquery-ui-smoothness.css', TM_LM_FILE));
    }

    function add_admin_page() {
        $this->tm_help = add_submenu_page('edit.php?post_type=tm_lunch_menu', 'Lunch Menu Settings', 'Settings', 'manage_options', 'tm_lunch_menu_settings', array($this, 'admin_settings'));
    }

    function admin_settings() {
        if(!current_user_can('manage_options'))
            wp_die('You do not have permission to view this page!');
        // Page and functionality
        require(TM_LM_PATH.'/inc/meta-boxes/admin-settings.php');
    }

    function register_post_type() {
        if(class_exists('wordpress_custom_post_type')) {
            // Add Lunch Menu post type
            new wordpress_custom_post_type('tm_lunch_menu', array(
                'singular' => __('Lunch Menu', 'lunch-menu'),
                'plural' => __('Lunch Menus', 'lunch-menu'),
                'textdomain' => 'lunch-menu',
                'args' => array(
                    'labels' => array(
                        'name' => __('Lunch Menus', 'lunch-menu'),
                        'singular_name' => __('Lunch Menu', 'lunch-menu'),
                        'add_new' => __('Add Lunch Menu', 'lunch-menu'),
                        'add_new_item' => __('Add New Lunch Menu', 'lunch-menu'),
                        'edit_item' => __('Edit Lunch Menu', 'lunch-menu'),
                        'new_item' => __('New Lunch Menu', 'lunch-menu'),
                        'view_item' => __('View Lunch Menu', 'lunch-menu'),
                        'search_items' => __('Search Lunch Menus', 'lunch-menu'),
                        'not_found' => __('No lunch menus found', 'lunch-menu'),
                        'not_found_in_trash' => __('No lunch menus found in trash', 'lunch-menu')
                    ),
                    'supports' => array(''),
                    'public' => false,
                    'show_ui' => true,
                    /*'capability_type' => 's8_opportunity',
                    'capabilities' => array(
                        'edit_post' => 'edit_s8_opportunity',
                        'edit_posts' => 'edit_s8_opportunities',
                        'edit_published_posts' => 'edit_published_s8_opportunities',
                        'edit_others_posts' => 'edit_others_s8_opportunities',
                        'publish_posts' => 'publish_s8_opportunities',
                        'read_post' => 'read_s8_opportunity',
                        'read_private_posts' => 'read_private_s8_opportunities',
                        'delete_post' => 'delete_s8_opportunity',
                        'delete_posts' => 'delete_s8_opportunities',
                        'delete_published_posts' => 'delete_published_s8_opportunities',
                        'delete_others_posts' => 'delete_others_s8_opportunities'
                    ),
                    'map_meta_cap' => true*/
                )
            ) );
        }
    }

    function register_meta_box() {
        // Meta box for lunch menu
        new wordpress_meta_box(
            $id = 'tm_lunch_menu_meta_box',
            $title = 'Menu Details',
            $content_path = TM_LM_PATH . '/inc/meta-boxes/lunch-details.php',                $args = array('data' => array('_tm_lunch_date', '_tm_lunch_menu_day'),
                'post_type' => 'tm_lunch_menu',
                'context' => 'normal',
                'nonce_name' => 'tm_lunch_menu_meta_update',
                'nonce_action' => TM_LM_FILE
            )
        );
        add_filter('tm_lunch_menu-edit_meta_data', array($this, 'meta_process'), 10, 3);
    }

    function meta_process($data) {
        // Save date as timestamp
        if(isset($data['_tm_lunch_date'])) {
            $date = explode('/', $data['_tm_lunch_date']);
            $timestamp = mktime(0, 0, 0, $date[0], $date[1], $date[2]);
            $data['_tm_lunch_timestamp'] = $timestamp;
            unset($data['_tm_lunch_date']);
        }
        return $data;
    }

    function add_post_columns($posts_columns) {
        // Handles display of columns in custom post type screen
        $tmp = array();
        foreach($posts_columns as $key=>$column) {
            if($key == 'title'){
                $tmp[$key] = $column;
                $tmp['tm_menu_start'] = 'Menu Start Date';
            }
            elseif($key == 'date') continue;
            else
                $tmp[$key] = $column;
        }
        return $tmp;
    }

    function add_data_post_columns($column_name) {
        // Handles display of columns in custom post type screen
        global $post;
        if( $column_name == 'tm_menu_start' ) {
            $timestamp = get_post_meta($post->ID, '_tm_lunch_timestamp', true);
            echo (is_numeric($timestamp))? date('m/d/Y', $timestamp): 'An error occurred!';
        }
    }

    function add_sortable_columns($columns) {
        $columns['tm_menu_start'] = 'tm_menu_start';
        return $columns;
    }

    function menu_order_columns_by($vars) {
        if(isset($vars['orderby']) && 'tm_menu_start' == $vars['orderby']) {
            $vars = array_merge($vars, array(
                'meta_key' => '_tm_lunch_timestamp',
                'orderby' => 'meta_value'
            ));
        }
        return $vars;
    }

    function update() {
        // Store default settings in the database
        update_option('tm_lunch_menu_settings', $this->default_settings);
        update_option('tm_lunch_menu_save_data', '');
        update_option('tm_lunch_menu_ver', $this->version);
    }

    function deactivation_hook() {
        $save_data = get_option('tm_lunch_menu_save_data');
        if($save_data == 'delete') {
            // We are deleting data!
            $control = true;
            while($control) {
                $posts = get_posts(array('post_type' => 'tm_lunch_menu', 'number' => 500, 'post_status' => 'any'));
                if(!$posts) $control = false;
                else {
                    foreach($posts as $post)
                        wp_delete_post($post->ID, true);
                }
            }
            delete_option('tm_lunch_menu_settings');
            delete_option('tm_lunch_menu_save_data');
            delete_option('tm_lunch_menu_ver');
        }
    }
}
new tm_lunch_menu;
endif;

if(!class_exists('tm_lunch_menu_widget')):
class tm_lunch_menu_widget extends WP_Widget {
    function tm_lunch_menu_widget() {
        $name = __('Lunch Menu'); // The widget name as users will see it
        $description = __('Displays current and upcoming menus from the Lunch Menu plugin.'); // The widget description as users will see it
        $this->WP_Widget(
            $id_base = false,
            $name,
            $widget_options = array('classname' => strtolower(get_class($this)), 'description' => $description),
            $control_options = array()
        );
    }

    function form($instance) {
        include(TM_LM_PATH.'/inc/meta-boxes/widget-options.php');
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['numberposts'] = $new_instance['numberposts'];
        $instance['show_partial'] = $new_instance['show_partial'];
        return $instance;
    }

    function widget($args, $instance) {
        // Setup widget display elements
        $before_widget = $after_widget = $before_title = $after_title = '';
        extract($args, EXTR_IF_EXISTS);
        // Retrieve additional settings
        $settings = get_option('tm_lunch_menu_settings');
        // Prepare query
        $today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
        $start = mktime(0, 0, 0, date('n'), date('j')-7, date('Y'));
        $end = mktime(0, 0, 0, date('n'), date('j')+(7*$settings['weeks']), date('Y'));
        $query_args = array(
            'post_type' => 'tm_lunch_menu',
            'numberposts' => $instance['numberposts'],
            'order' => 'ASC',
            'orderby' => 'meta_value_num',
            'meta_query' => array( array(
                'key' => '_tm_lunch_timestamp',
                'value' => array($start, $end),
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC'
            ))
        );
        // Retrieve menus
        $menus = get_posts($query_args);
        if($menus || $settings['no_menu'] == 'display') {
            // Start widget output
            echo $before_widget;
            echo $before_title.$instance['title'].$after_title;
            // Process menus
            if($menus) {
                foreach($menus as $menu) {
                    $x=0; $y=7;
                    // Get menu info
                    $timestamp = get_post_meta($menu->ID, '_tm_lunch_timestamp', true);
                    $items = get_post_meta($menu->ID, '_tm_lunch_menu_day', true);
                    $start_day = date('w', $timestamp);
                    // While day of week is less than number of days in week
                    while($x < $y) {
                        if((in_array($start_day, $settings['days']) && !empty($items[$start_day])))
                        if(($instance['show_partial'] == 1 && ($today <= ($timestamp + (86400 * $x)))) || $instance['show_partial'] == 0) {
                            echo '<strong>'.date('l, M j', $timestamp + (86400 * $x)).'</strong><br />';
                            echo $items[$start_day].'<br/>';
                        }
                        $x++;
                        $start_day = ($start_day < 6)? $start_day+1 : 0;
                    }
                }
            } else echo '<p>'.$settings['no_menu_msg'].'</p>';
            echo $after_widget;
        }
    }
}
add_action('widgets_init', create_function('', 'return register_widget("tm_lunch_menu_widget");'));
endif;