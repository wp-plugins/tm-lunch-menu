<?php
/*
Plugin Name: TM Lunch Menu
Plugin URI:
Description: Designed for easy display, editing, & scheduling of lunch menus or other similar lists, TM Lunch Menu uses a custom widget to display your menu on any page of your site. This plugin allows you to easily add a daily menu by simply filling in the meal for any day (and any number of days) of the week, set a menu start date and hit publish. Once published it will automatically show up in the custom "Lunch Menu" widget when its time comes. After a menu (or menu item) has expired it will automatically remove itself from the widget display. An integrated calendar style date-picker makes it even easier to select your menu's start date and the date is always shown next to every menu item so there is no confusion about what day the menu is for. NOTE: If you find something that does not work, please post in the WordPress.org forums and tag it with "tm-lunch-menu" so I can fix it!
Version: 1.0.2
Author: David Wood
Author URI: http://technicalmastermind.com/about-david-wood/
Text Domain: tm-lunch-menu
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

global $tm_lunch_menu_days, $tm_lunch_menu_months;
$tm_lunch_menu_days = array(
    __('Sunday', 'tm-lunch-menu'),
    __('Monday', 'tm-lunch-menu'),
    __('Tuesday', 'tm-lunch-menu'),
    __('Wednesday', 'tm-lunch-menu'),
    __('Thursday', 'tm-lunch-menu'),
    __('Friday', 'tm-lunch-menu'),
    __('Saturday', 'tm-lunch-menu'),
);
$tm_lunch_menu_months = array(
    '',
    __('January', 'tm-lunch-menu'),
    __('February', 'tm-lunch-menu'),
    __('March', 'tm-lunch-menu'),
    __('April', 'tm-lunch-menu'),
    __('May', 'tm-lunch-menu'),
    __('June', 'tm-lunch-menu'),
    __('July', 'tm-lunch-menu'),
    __('August', 'tm-lunch-menu'),
    __('September', 'tm-lunch-menu'),
    __('October', 'tm-lunch-menu'),
    __('November', 'tm-lunch-menu'),
    __('December', 'tm-lunch-menu'),
);


if(!class_exists('tm_lunch_menu')):
class tm_lunch_menu {
    private $version = '1.0.2',
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

        // Add in translation if applicable
        add_action('init', array($this, 'init'));

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

    function init() {
        load_plugin_textdomain('tm-lunch-menu', false, TM_LM_PATH.'/languages/');
    }

    function admin_enqueue_scripts($hook){
        if($hook == 'post.php' || ($hook == 'post-new.php' && $_REQUEST['post_type'] == 'tm_lunch_menu')) {
            wp_register_script('jquery-datepicker', plugins_url('/js/jquery.ui.datepicker.min.js', TM_LM_FILE), array('jquery-ui-core'), $this->version);
            wp_enqueue_script('tm-date-time', plugins_url('/js/date-time.js', TM_LM_FILE), array('jquery-datepicker'), $this->version);
            wp_enqueue_script('tm-lunchmenu-save', plugins_url('/js/tm_lunch_menusave.js', TM_LM_FILE), array('tm-date-time'), $this->version);
            wp_localize_script('tm-date-time', 'tmDateTime', array('pluginURL' => TM_LM_URL));
        }
    }

    function admin_enqueue_styles(){
        wp_enqueue_style('jquery-lightness-css', plugins_url('/css/jquery-ui-smoothness.css', TM_LM_FILE));
    }

    function add_admin_page() {
        $this->tm_help = add_submenu_page('edit.php?post_type=tm_lunch_menu', __('Lunch Menu Settings', 'tm-lunch-menu'), __('Settings', 'tm-lunch-menu'), 'manage_options', 'tm_lunch_menu_settings', array($this, 'admin_settings'));
    }

    function admin_settings() {
        if(!current_user_can('manage_options'))
            wp_die(__('You do not have permission to view this page!', 'tm-lunch-menu'));
        // Page and functionality
        require(TM_LM_PATH.'/inc/meta-boxes/admin-settings.php');
    }

    function register_post_type() {
        if(class_exists('wordpress_custom_post_type')) {
            // Add Lunch Menu post type
            new wordpress_custom_post_type('tm_lunch_menu', array(
                'singular' => __('Lunch Menu', 'tm-lunch-menu'),
                'plural' => __('Lunch Menus', 'tm-lunch-menu'),
                'textdomain' => 'tm-lunch-menu',
                'args' => array(
                    'labels' => array(
                        'name' => __('Lunch Menus', 'tm-lunch-menu'),
                        'singular_name' => __('Lunch Menu', 'tm-lunch-menu'),
                        'add_new' => __('Add Lunch Menu', 'tm-lunch-menu'),
                        'add_new_item' => __('Add New Lunch Menu', 'tm-lunch-menu'),
                        'edit_item' => __('Edit Lunch Menu', 'tm-lunch-menu'),
                        'new_item' => __('New Lunch Menu', 'tm-lunch-menu'),
                        'view_item' => __('View Lunch Menu', 'tm-lunch-menu'),
                        'search_items' => __('Search Lunch Menus', 'tm-lunch-menu'),
                        'not_found' => __('No lunch menus found', 'tm-lunch-menu'),
                        'not_found_in_trash' => __('No lunch menus found in trash', 'tm-lunch-menu')
                    ),
                    'supports' => array(''),
                    'public' => false,
                    'show_ui' => true,
                )
            ) );
        }
    }

    function register_meta_box() {
        // Meta box for lunch menu
        new wordpress_meta_box(
            $id = 'tm_lunch_menu_meta_box',
            $title = __('Menu Details', 'tm-lunch-menu'),
            $content_path = TM_LM_PATH . '/inc/meta-boxes/lunch-details.php', $args = array('data' => array('_tm_lunch_date', '_tm_lunch_menu_day'),
                'post_type' => 'tm_lunch_menu',
                'context' => 'normal',
                'nonce_name' => 'tm_lunch_menu_meta_update',
                'nonce_action' => TM_LM_FILE
            )
        );
        add_filter('edit_meta_box-tm_lunch_menu_meta_box', array($this, 'meta_process'), 10, 3);
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
                $tmp['tm_menu_start'] = __('Menu Start Date', 'tm-lunch-menu');
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
            echo (is_numeric($timestamp))? date('m/d/Y', $timestamp): __('An error occurred!', 'tm-lunch-menu');
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
        $name = __('Lunch Menu', 'tm-lunch-menu'); // The widget name as users will see it
        $description = __('Displays current and upcoming menus from the Lunch Menu plugin.', 'tm-lunch-menu'); // The widget description as users will see it
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
        global $tm_lunch_menu_days, $tm_lunch_menu_months;
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
                            if(!$settings['format']) $settings['format'] = '%l, %M %d';
                            $week_day = date('w', $timestamp + (86400 * $x));
                            $month = date('n', $timestamp + (86400 * $x));
                            $day = date('j', $timestamp + (86400 * $x));
                            $tmp = str_replace('%F', $tm_lunch_menu_months[$month], $settings['format']);
                            $tmp = str_replace('%M', substr($tm_lunch_menu_months[$month], 0, 3), $tmp);
                            $tmp = str_replace('%l', $tm_lunch_menu_days[$week_day], $tmp);
                            $tmp = str_replace('%D', substr($tm_lunch_menu_days[$week_day], 0, 3), $tmp);
                            $tmp = str_replace('%d', $day, $tmp);
                            echo '<strong>'.$tmp.'</strong><br />';
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
