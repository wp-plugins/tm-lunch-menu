<?php
// set form variables
$instance = wp_parse_args( (array) $instance, array( 'title' => 'Lunch Menu', 'numberposts' => 2, 'show_partial' => 1 ) );
// Shortcut variables
$selected = ' selected="selected"';
?>
<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'tm-lunch-menu'); ?>:</label>
	<input id="<?php echo $this->get_field_id('title'); ?>"
    name="<?php echo $this->get_field_name('title'); ?>"
    type="text"
    value="<?php echo $instance['title']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id('numberposts'); ?>"><?php _e('Maximum number of menus to show at one time', 'tm-lunch-menu'); ?>:</label><br />
	<select id="<?php echo $this->get_field_id('numberposts'); ?>"
    name="<?php echo $this->get_field_name('numberposts'); ?>">
		<?php
		$x = 0; $y = 5;
		while($x < $y) {
			$x++;
			echo '<option value="'.$x.'"';
			echo ($instance['numberposts'] == $x)? $selected : '';
			echo '>'.$x.'</option>';
		}
		?>
    </select>
</p>
<p>
	<label for="<?php echo $this->get_field_id('show_partial'); ?>"><?php _e('Remove individual menu items after their date has passed?', 'tm-lunch-menu'); ?></label><br />
	<select id="<?php echo $this->get_field_id('show_partial'); ?>"
    name="<?php echo $this->get_field_name('show_partial'); ?>">
		<option value="0" <?php if($instance['show_partial'] == 0) echo $selected; ?>><?php _e('No', 'tm-lunch-menu'); ?></option>
		<option value="1" <?php if($instance['show_partial'] == 1) echo $selected; ?>><?php _e('Yes', 'tm-lunch-menu'); ?></option>
	</select>
</p>
