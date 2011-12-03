<?php
// Setup WP Nonce field
wp_nonce_field( TM_LM_FILE, 'tm_lunch_menu_meta_update' );

// Retrieve existing data if any
global $post;
$timestamp = get_post_meta($post->ID, '_tm_lunch_timestamp', true);
$data = get_post_meta($post->ID, '_tm_lunch_menu_day', true);

// Retrieve settings regarding functionality
$settings = get_option('tm_lunch_menu_settings');

// Shortcut variables
$selected = ' selected="selected"';
$checked = ' checked="checked"';
?>
<p>
    <label for="datepicker">Menu starts on </label>
    <input id="datepicker" type="text" name="_tm_lunch_date" value="<?php echo (is_numeric($timestamp))? date('m/d/Y', $timestamp) : date('m/d/Y'); ?>" />
    <input type="hidden" name="post_title" id="tm_post_title" value="Week of..." />
</p>
<p><strong>Menu Days</strong><br />
<?php
if(!isset($settings['days'])) $settings['days'] = array(1,2,3,4,5);
foreach($settings['days'] as $day) {
	$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	echo '<label>'.$days[$day].'</label> <input type="text" name="_tm_lunch_menu_day['.$day.']" value="';
	echo (!empty($data[$day]))? $data[$day] : '';
	echo '" size="40" /><br />';
}
