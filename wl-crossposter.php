<?php
/**
 * @package   WL_CrossPoster
 * @author    Eugene F <eugen@figursky.com>
 * @license   GPL-2.0+
 * @link      http://wildlabs.com
 * @copyright 2014 Eugene F / WildLabs
 *
 * Plugin Name:       WedPro.gr CrossPoster
 * Plugin URI:        -
 * Description:       Custom plugin to cross-post to wedpro.gr blog.
 * Version:           1.0.0
 * Author:            Eugene F
 * Author URI:        http://figursky.com
 * Text Domain:       wl-crossposter-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-wl-crossposter.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/class-wl-xmlrpc.php' );

register_activation_hook( __FILE__, array( 'WL_CrossPoster', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WL_CrossPoster', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WL_CrossPoster', 'get_instance' ) );

if (!defined('XILIXMLRPC_VER')) {
	define('XILIXMLRPC_VER','0.5.0');
}

include_once(ABSPATH . WPINC . '/class-IXR.php');
include_once(ABSPATH . WPINC . '/class-wp-http-ixr-client.php');

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-wl-crossposter-admin.php' );
	add_action( 'plugins_loaded', array( 'WL_CrossPoster_Admin', 'get_instance' ) );

}
