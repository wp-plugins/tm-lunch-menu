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
    $save_data = esc_attr($_POST['tm_lunch_menu_save_data']);
    update_option('tm_lunch_menu_settings', $settings);
    update_option('tm_lunch_menu_save_data', $save_data);
} elseif(isset($_POST['tm_settings_reset'])) {
    $settings = $this->default_settings;
    $save_data = '';
    update_option('tm_lunch_menu_settings', $settings);
    update_option('tm_lunch_menu_save_data', $save_data);
}

// Shortcut variables
$selected = ' selected="selected"';
$checked = ' checked="checked"';
?>
<div class="wrap"><h2>Lunch Menu Settings</h2>
	<form action="" method="post">
		<p><label>Days to include in menus:</label><br>
			<input type="checkbox" value="0" name="days[]"<?php if(is_array($settings['days']) && in_array(0, $settings['days'])) echo $checked; ?> /> Sunday<br>
            <input type="checkbox" value="1" name="days[]"<?php if(is_array($settings['days']) && in_array(1, $settings['days'])) echo $checked; ?> /> Monday<br>
            <input type="checkbox" value="2" name="days[]"<?php if(is_array($settings['days']) && in_array(2, $settings['days'])) echo $checked; ?> /> Tuesday<br>
            <input type="checkbox" value="3" name="days[]"<?php if(is_array($settings['days']) && in_array(3, $settings['days'])) echo $checked; ?> /> Wednesday<br>
            <input type="checkbox" value="4" name="days[]"<?php if(is_array($settings['days']) && in_array(4, $settings['days'])) echo $checked; ?> /> Thursday<br>
            <input type="checkbox" value="5" name="days[]"<?php if(is_array($settings['days']) && in_array(5, $settings['days'])) echo $checked; ?> /> Friday<br>
            <input type="checkbox" value="6" name="days[]"<?php if(is_array($settings['days']) && in_array(6, $settings['days'])) echo $checked; ?> /> Saturday</p>
		<p><label>Action to take when there is no current menu:</label><br>
			<select name="action">
				<option value="hide"<?php if(isset($settings['no_menu']) && $settings['no_menu'] == 'hide') echo $selected; ?>>Hide widget</option>
				<option value="display"<?php if(isset($settings['no_menu']) && $settings['no_menu'] == 'display') echo $selected; ?>>Display custom message (see below)</option>
			</select>
		</p>
		<p><label>Custom display message:</label> <input type="text" name="msg" value="<?php if(isset($settings['no_menu_msg'])) echo stripslashes($settings['no_menu_msg']); ?>" /></p>
		<p><label>Maximum number of weeks into the future to pull menus from:</label>
		<select name="weeks">
			<?php
			$x = 0; $y = 10;
			while($x < $y) {
				$x++;
				echo '<option value="'.$x.'"';
				echo ($settings['weeks'] == $x)? $selected : '';
				echo '>'.$x.'</option>';
			} ?>
		</select> weeks
		</p>
        
        <p>
            <input type="checkbox" name="tm_lunch_menu_save_data" value="delete"<?php if($save_data == 'delete') echo $checked; ?> /> <label>Delete all data when plugin is deactivated</label>
        </p>

        <p><input type="submit" class="button-primary" name="tm_settings_update" value="Update Settings" />
            <input type="submit" class="button-primary" name="tm_settings_reset" value="Reset to defaults" /></p>
	</form>
</div>
