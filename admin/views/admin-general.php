<form method="post" action="options.php">
	<?php settings_fields($this->plugin_slug . '-group'); ?>
	<?php do_settings_sections($this->plugin_slug); ?>
	<?php submit_button(); ?>
</form>
<hr>