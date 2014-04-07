<?php
/**
 * Plugin Name.
 *
 * @package   WL_CrossPoster
 * @author    Eugene F <eugen@figursky.com>
 * @license   GPL-2.0+
 * @link      http://wildlabs.com
 * @copyright 2014 Eugene F / WildLabs
 */

define('XILIXMLRPC_VER','0.5.0'); /* used in admin UI */

include_once(ABSPATH . WPINC . '/class-IXR.php'); /* not included in wp-settings */
include_once(ABSPATH . WPINC . '/class-wp-http-ixr-client.php'); /* not included in wp-settings */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package WL_CrossPoster_Admin
 * @author  Eugene F <eugen@figursky.com>
 */
class WL_CrossPoster_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$this->plugin      = WL_CrossPoster::get_instance();
		$this->plugin_slug = $this->plugin->get_plugin_slug();
		$this->options     = $this->plugin->get_options();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Register options
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WL_CrossPoster::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), WL_CrossPoster::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Cross Poster', $this->plugin_slug ),
			__( 'Cross Poster', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);
	}

	/**
	 * Simple redirect
	 * @param  string $param used to pass another get variable to redirect
	 * @param  string $tab   pass the tab. if empty - grab the tab from $_GET
	 * @param  string $url   pass the full URL to redirect.
	 * @return null
	 */
	public function redirect($param = '', $tab = '', $url = '')
	{
		if (!$tab) {
			$tab = isset($_GET['tab']) ? '&tab=' . $_GET['tab'] : '';
		}

		if ($param) {
			$param = '&param=' . $param;
		}

		if (!$url) {
			$url = admin_url('admin.php?page=' . $this->plugin_slug . $tab);
		}

		wp_redirect($url);
		exit;
	}


	/**
	 * Register settings for the plugin
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {

		add_settings_section(
			$this->plugin_slug . '-group',
			'Plugin Settings',
			array($this, 'section_info'),
			$this->plugin_slug
		);

			add_settings_field(
				'xmlrpc_url',
				'XML-RPC URL',
				array($this, 'render_text'),
				$this->plugin_slug,
				$this->plugin_slug . '-group',
				array(
					'name' => 'xmlrpc_url',
				)
			);

			add_settings_field(
				'username',
				'Username',
				array($this, 'render_text'),
				$this->plugin_slug,
				$this->plugin_slug . '-group',
				array(
					'name' => 'username',
				)
			);

			add_settings_field(
				'password',
				'Password',
				array($this, 'render_text'),
				$this->plugin_slug,
				$this->plugin_slug . '-group',
				array(
					'name' => 'password',
					'type' => 'password',
				)
			);

		register_setting($this->plugin_slug . '-group', $this->plugin_slug, array($this, 'sanitize'));
	}

	public function section_info() {}

	/**
	 * Simple text renderer
	 * @param  array $args
	 * @return null
	 */
	public function render_text($args)
	{
		$default = isset($args['default']) ? $args['default'] : '';
		$value   = isset($this->options[$args['name']]) ? $this->options[$args['name']] : $default;

		$type = isset($args['type']) ? $args['type'] : 'text';

		?>
			<input type="<?php echo $type ?>" id="<?php echo $this->plugin_slug . '[' . $args['name'] . ']' ?>" name="<?php echo $this->plugin_slug . '[' . $args['name'] . ']' ?>" value="<?php echo esc_attr($value); ?>" />
			<?php if (isset($args['description']) && $args['description']): ?>
				<p class="description"><?php echo $args['description'] ?></p>
			<?php endif ?>
		<?php
	}


	/**
	 * Sanitize and filter post data
	 * @param  array $input
	 * @return array
	 */
	public function sanitize($input)
	{
		$input['xmlrpc_url'] = sanitize_text_field($input['xmlrpc_url']);
		$input['username'] = sanitize_text_field($input['username']);
		$input['password'] = sanitize_text_field($input['password']);

		return $input;
	}


	/**
	 * Add metaboxes to backend
	 */
	public function add_metaboxes()
	{
	    $screens = array( 'post' );

	    foreach ( $screens as $screen ) {
	        add_meta_box(
	            'wl-crossposter-post',
	            __( 'CrossPoster', $this->plugin_slug ),
	            array($this, 'display_crosspost_box'),
	            $screen,
	            'side',
	            'high'
	        );
	    }
	}

	/**
	 * Display regenerate metabox
	 */
	public function display_crosspost_box($post)
	{
		wp_nonce_field( 'wl_crossposter', 'wl_crossposter_nonce' );

		$value = get_post_meta( $post->ID, '_wl_crossposter_post', true );

		?>
			<input type="hidden" value="1" name="wl_crossposter_post">
			<input type="checkbox" value="1" name="wl_crossposter_post" id="wl_crossposter_post">
		<?php
	}
}
