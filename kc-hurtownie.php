<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://kemuri.codes
 * @since             1.0.0
 * @package           Kc_Hurtownie
 *
 * @wordpress-plugin
 * Plugin Name:       Integracja z hurtowniami
 * Plugin URI:        https://kemuri.codes
 * Description:       Wtyczka, która integruje hurtownie ze sklepem promoprint.
 * Version:           1.0.0
 * Author:            Marcin Dymek
 * Author URI:        https://kemuri.codes/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       kc-hurtownie
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'KC_HURTOWNIE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-kc-hurtownie-activator.php
 */
function activate_kc_hurtownie() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kc-hurtownie-activator.php';
	Kc_Hurtownie_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-kc-hurtownie-deactivator.php
 */
function deactivate_kc_hurtownie() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-kc-hurtownie-deactivator.php';
	Kc_Hurtownie_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_kc_hurtownie' );
register_deactivation_hook( __FILE__, 'deactivate_kc_hurtownie' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-kc-hurtownie.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_kc_hurtownie() {

	$plugin = new Kc_Hurtownie();
	$plugin->run();

}
run_kc_hurtownie();
