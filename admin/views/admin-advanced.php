<form method="post" action="" class="clear-cache-form" id="clear-cache-form">
	<h3>Clear plugin data</h3>
	<p>
		<?php wp_nonce_field('clear'); ?>
		<input type="submit" value="Clear cache" class="button">
	</p>
	<p class="description">
		If you want to delete all the data from the plugin - click this link.
	</p>

</form>