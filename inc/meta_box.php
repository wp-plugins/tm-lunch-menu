<?php
/*
 * WordPress Meta Box
 *
 * Contains the wordpress_meta_box class. Requires PHP version 5+ and WordPress version 2.9 or greater.
 *
 * @version 1.0.1
 * @author Micah Wood
 * @copyright Copyright (c) 2011 - Micah Wood
 * @license GPL 3 - http://www.gnu.org/licenses/gpl.txt
 */

if( !class_exists('wordpress_meta_box') ){

    /*
    * WordPress Meta Box Class
    *
    * A class that handles the registration of WordPress meta boxes and takes care of all the
    * dirty work for you.
    *
    * @package WordPress Meta Box
    */
    class wordpress_meta_box {

        private $id,                            // HTML id for meta box
            $title,                         // Title for meta box
            $content_path,                  // Path to the file containing the meta box content
            $data = array(),                // Array containing the names of data values to be saved
            $post_type = 'post',            // Post type that displays meta box
            $context = 'advanced',          // Location on page: normal, advanced, side
            $priority = 'default',          // Priority on page: high, core, default, low
            $callback_args = array(),       // Array of arguments to pass to callback function
            $user_capability = 'edit_post', // User capability required to save meta data
            $nonce_name = '_wpnonce',       // Nonce name used to save meta data
            $nonce_action = '-1',           // Value used to add context to the nonce
            $errors = array();              // A collection of meta names and error codes

        function __construct( $id, $title, $content_path, $args = array() ){
            // Set class properties
            $this->id = $id;
            $this->title = $title;
            $this->content_path = $content_path;
            $this->data = ( empty( $args['data'] ) ) ? $this->data : $args['data'];
            $this->post_type = ( empty( $args['post_type'] ) ) ? $this->post_type : $args['post_type'];
            $this->context = ( empty( $args['context'] ) ) ? $this->context : $args['context'];
            $this->priority = ( empty( $args['priority'] ) ) ? $this->priority : $args['priority'];
            $this->callback_args = ( empty( $args['callback_args'] ) ) ? $this->callback_args : $args['callback_args'];
            $this->user_capability = ( empty( $args['user_capability'] ) ) ? $this->user_capability : $args['user_capability'];
            $this->nonce_name = ( empty( $args['nonce_name'] ) ) ? $this->nonce_name : $args['nonce_name'];
            $this->nonce_action = ( empty( $args['nonce_action'] ) ) ? $this->nonce_action : $args['nonce_action'];
            // Add meta boxes
            add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
            // Validate and save data
            if( !empty( $this->data ) )
                add_action( 'save_post', array( $this, 'save_meta_data' ) );
            // Display error messages
            add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        }

        function add_meta_boxes(){
            add_meta_box(
                $this->id,
                $this->title,
                array( $this, 'meta_box_content' ),
                $this->post_type,
                $this->context,
                $this->priority
            );
        }

        function meta_box_content( $post ){
            extract( $this->callback_args );
            include( $this->content_path );
        }

        function save_meta_data( $post_id ){
            // Has form been submitted?
            if( empty( $_POST ) )
                return $post_id;
            // If this is an auto save our form has not been submitted by the user, so we don't want to do anything.
            if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
                return $post_id;
            // Is the post type correct?
            if ( $_POST['post_type'] != $this->post_type )
                return $post_id;
            // Verify this came from our screen and with proper authorization, because save_post can be triggered at other times
            if ( !wp_verify_nonce( $_POST[$this->nonce_name], $this->nonce_action ) )
                return $post_id;
            // Is user allowed to edit this post?
            if ( !current_user_can($this->user_capability, $post_id) )
                return $post_id;
            // Setup array for the data we are saving
            $data = $this->data;
            // Use this filter to add post meta to save for this meta box
            $data = apply_filters( 'save_meta_box-' . $this->id, $data );
            // Construct array of meta data
            $new_data = array();
            foreach($data as $meta) {
                $new_data[$meta] = $_POST[$meta];
            }
            // Open editing of results
            $data = apply_filters( 'edit_meta_box-' . $this->id, $new_data);

            // Save meta data
            foreach( $data as $name=>$value ){
                // Get existing meta value
                $current_meta = get_post_meta( $post_id, $name, TRUE );
                // If nothing has changed, do nothing
                if( $current_meta == $value )
                    continue;
                // Validate post meta
                $validation = $this->validate_post_meta( $name, $value );
                // If validation is disabled or meta value is valid
                if( $validation['valid'] ){
                    // If the new meta is empty, delete the current meta
                    if ( empty( $value ) ){
                        delete_post_meta( $post_id, $name );
                        // Otherwise, update the meta
                    } else {
                        update_post_meta( $post_id, $name, $value );
                    }
                    // Validation is enabled and meta value is invalid
                } else {
                    // Store general error if not already done
                    if( !$this->errors[$this->id] ){
                        $this->errors[$this->id] = 1;
                    }
                    // If an error code is provided, store it
                    if( !empty( $validation['code'] ) ){
                        $this->errors[$name] = $validation['code'];
                    }
                }
            }
            if( !empty( $this->errors ) ){
                add_filter('redirect_post_location', array( $this, 'meta_box_error' ) );
            }
        }

        function validate_post_meta( $name, $value ){
            $validation = array( 'valid' => true, 'code' => 1 );
            $validation = apply_filters( 'validate_meta_box-' . $this->id, $validation, $name, $value );
            return $validation;
        }

        function meta_box_error( $location ) {
            $arr_params = $this->errors;
            $arr_params[$this->id] = 1;
            return add_query_arg( $arr_params, $location );
        }

        function admin_notices(){
            if( isset( $_GET[$this->id] ) && $_GET[$this->id] == 1 ){
                echo '<div class="error"><p>Invalid entry in the <strong>' . $this->title . '</strong> box. Please check your entries.</p></div>';
            }
        }
    }
}
