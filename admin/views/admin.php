<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   WL_CrossPoster
 * @author    Eugene F <eugen@figursky.com>
 * @license   GPL-2.0+
 * @link      http://wildlabs.com
 * @copyright 2014 Eugene F / WildLabs
 */
?>

<?php
	$current_page = admin_url( 'options-general.php?page=' . $this->plugin_slug );
	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
?>
<div class="wl-socialcount wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<br>
	<h2 class="nav-tab-wrapper">

		<a href="<?php echo add_query_arg( array( 'tab' => 'general', 'settings-updated' => false ), $current_page ); ?>" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
			<?php _e('General', $this->plugin_slug); ?>
		</a>
		<a href="<?php echo add_query_arg( array( 'tab' => 'advanced', 'settings-updated' => false ), $current_page ); ?>" class="nav-tab <?php echo $active_tab == 'advanced' ? 'nav-tab-active' : ''; ?>">
			<?php _e('Advanced', $this->plugin_slug); ?>
		</a>
	</h2>
	<?php include_once( 'admin-' . $active_tab . '.php' ); ?>
</div>




