<?php
// Get existing settings
$settings = get_option('tm_lunch_menu_settings');
$save_data = get_option('tm_lunch_menu_save_data');

// Process data here
if(isset($_POST['tm_settings_update'])) {
    $settings = array();
    $settings['days'] = $_POST['days'];
    $settings['no_menu'] = esc_attr($_POST['action']);
    $settings['no_menu_msg'] = esc_attr($_POST['msg']);
    $settings['weeks'] = esc_attr($_POST['weeks']);
    $settings['format'] = esc_attr($_POST['date-format']);
    $save_data = esc_attr($_POST['tm_lunch_menu_save_data']);
    update_option('tm_lunch_menu_settings', $settings);
    update_option('tm_lunch_menu_save_data', $save_data);
} elseif(isset($_POST['tm_settings_reset'])) {
    $settings = $this->default_settings;
    $save_data = '';
    update_option('tm_lunch_menu_settings', $settings);
    update_option('tm_lunch_menu_save_data', $save_data);
}

if(!is_array($settings['days']))
    $settings['days'] = array(1,2,3,4,5);

// Shortcut variables
$selected = ' selected="selected"';
$checked = ' checked="checked"';

global $tm_lunch_menu_days, $tm_lunch_menu_months;
?>
<div class="wrap"><h2><?php _e('Lunch Menu Settings', 'tm-lunch-menu'); ?></h2>
	<form action="" method="post">
		<p><label><?php _e('Days to include in menus', 'tm-lunch-menu'); ?>:</label><br>
            <?php
            foreach($tm_lunch_menu_days as $key=>$day) {
                echo '<input type="checkbox" value="'.$key.'" name="days[]" id="days-'.$key.'"';
                echo (in_array($key, $settings['days']))?' '.$checked:'';
                echo ' /> <label for="days-'.$key.'">'.$day.'</label><br/>';
            }
            ?>
		<p><label for="tm-action"><?php _e('Action to take when there is no current menu', 'tm-lunch-menu'); ?>:</label><br>
			<select name="action" id="tm-action">
				<option value="hide"<?php if(isset($settings['no_menu']) && $settings['no_menu'] == 'hide') echo $selected; ?>><?php _e('Hide widget', 'tm-lunch-menu'); ?></option>
				<option value="display"<?php if(isset($settings['no_menu']) && $settings['no_menu'] == 'display') echo $selected; ?>><?php _e('Display custom message (see below)', 'tm-lunch-menu'); ?></option>
			</select>
		</p>
		<p><label for="tm-msg"><?php _e('Custom display message', 'tm-lunch-menu'); ?>:</label> <input type="text" name="msg" id="tm-msg" value="<?php if(isset($settings['no_menu_msg'])) echo stripslashes($settings['no_menu_msg']); ?>" /></p>
		<p><label for="tm-weeks"><?php _e('Maximum number of weeks into the future to pull menus from', 'tm-lunch-menu'); ?>:</label>
		<select name="weeks" id="tm-weeks">
			<?php
			$x = 0; $y = 10;
			while($x < $y) {
				$x++;
				echo '<option value="'.$x.'"';
				echo ($settings['weeks'] == $x)? $selected : '';
				echo '>'.$x.'</option>';
			} ?>
		</select> <?php _e('weeks', 'tm-lunch-menu'); ?>
		</p>
        
        <p>
            <label for="date-format"><?php _e('Date display format', 'tm-lunch-menu'); ?>:</label>
            <select name="date-format" id="date-format">
                <?php
                $formats = array(
                    '%D, %M %d',
                    '%D, %F %d',
                    '%l, %M %d',
                    '%l, %F %d',
                    '%F %d, %l',
                    '%F %d, %D',
                    '%M %d, %l',
                    '%M %d, %D',
                    '%M %d',
                    '%F %d',
                    '%D, %d',
                    '%l, %d',
                    '%D',
                    '%l',
                );
                foreach($formats as $format) {
                    $tmp = str_replace('%F', $tm_lunch_menu_months[1], $format);
                    $tmp = str_replace('%M', substr($tm_lunch_menu_months[1], 0, 3), $tmp);
                    $tmp = str_replace('%l', $tm_lunch_menu_days[1], $tmp);
                    $tmp = str_replace('%D', substr($tm_lunch_menu_days[1], 0, 3), $tmp);
                    $tmp = str_replace('%d', '21', $tmp);
                    echo '<option value="'.$format.'"';
                    echo ($format == $settings['format'])?' '.$selected:'';
                    echo '>'.$tmp.'</option>';
                }
                ?>
            </select>
        </p>
        
        <p>
            <input type="checkbox" name="tm_lunch_menu_save_data" id="tm_lunch_menu_save_data" value="delete"<?php if($save_data == 'delete') echo $checked; ?> />
            <label for="tm_lunch_menu_save_data"><?php _e('Delete all data when plugin is deactivated', 'tm-lunch-menu'); ?></label>
        </p>

        <p><input type="submit" class="button-primary" name="tm_settings_update" value="<?php _e('Update Settings', 'tm-lunch-menu'); ?>" />
            <input type="submit" class="button-primary" name="tm_settings_reset" value="<?php _e('Reset to defaults', 'tm-lunch-menu'); ?>" /></p>
	</form>
</div>
