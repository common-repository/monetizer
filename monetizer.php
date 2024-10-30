<?php

/**
 * @link              https://www.monetizer.com/
 * @since             1.0.0
 * @package           Monetizer
 *
 * @wordpress-plugin
 * Plugin Name:       Monetizer
 * Plugin URI:        https://wordpress.org/plugins/monetizer/
 * Description:       Monetize your site with Monetizer monetization platform.
 * Version:           1.0.2
 * Author:            Monetizer
 * Author URI:        https://www.monetizer.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       monetizer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require __DIR__ . '/vendor/autoload.php';

/**
 * Currently plugin version.
 * Uses SemVer - https://semver.org
 */
define( 'MONETIZER_VERSION', '1.0.2' );

function activate_monetizer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-monetizer-activator.php';
	Monetizer_Activator::activate();
}

function deactivate_monetizer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-monetizer-deactivator.php';
	Monetizer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_monetizer' );
register_deactivation_hook( __FILE__, 'deactivate_monetizer' );

require plugin_dir_path( __FILE__ ) . 'includes/class-monetizer.php';

function run_monetizer() {
	$plugin = new Monetizer();
	$plugin->run();
}
run_monetizer();
