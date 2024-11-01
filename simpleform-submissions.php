<?php
/**
 *
 * Plugin Name:       SimpleForm Contact Form Submissions
 * Description:       You are afraid of losing important messages? This addon for SimpleForm saves data into the WordPress database, and allows you to easily manage the messages from the dashboard.
 * Version:           2.1.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            SimpleForm Team
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simpleform-contact-form-submissions
 * Requires Plugins:  simpleform
 *
 * @package           SimpleForm Contact Form Submissions
 */

defined( 'WPINC' ) || exit;

/**
 * Plugin constants.
 *
 * @since 1.0.0
 */

define( 'SIMPLEFORM_SUBMISSIONS_NAME', 'SimpleForm Contact Form Submissions' );
define( 'SIMPLEFORM_SUBMISSIONS_VERSION', '2.1.0' );
define( 'SIMPLEFORM_SUBMISSIONS_DB_VERSION', '2.1.0' );
define( 'SIMPLEFORM_SUBMISSIONS_BASENAME', plugin_basename( __FILE__ ) );
if ( ! defined( 'SIMPLEFORM_VERSION_REQUIRED' ) ) {
	define( 'SIMPLEFORM_VERSION_REQUIRED', '2.2.0' );
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 *
 * @param bool $network_wide Whether to enable the plugin for all sites in the network
 *                           or just the current site. Multisite only. Default false.
 *
 * @return void
 */
function activate_simpleform_submissions( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-simpleform-submissions-activator.php';
	SimpleForm_Submissions_Activator::activate( $network_wide );
}

/**
 * Change table when a new site into a network is created.
 *
 * @since 1.0.0
 *
 * @param WP_Site $new_site New site object.
 *
 * @return void
 */
function simpleform_submissions_network( $new_site ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-simpleform-submissions-activator.php';
	SimpleForm_Submissions_Activator::on_create_blog( $new_site );
}

add_action( 'wp_insert_site', 'simpleform_submissions_network' );

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 *
 * @return void
 */
function deactivate_simpleform_submissions() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-simpleform-submissions-deactivator.php';
	SimpleForm_Submissions_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_simpleform_submissions' );
register_deactivation_hook( __FILE__, 'deactivate_simpleform_submissions' );

/**
 * The core plugin class.
 *
 * @since 1.0.0
 */

require plugin_dir_path( __FILE__ ) . '/includes/class-simpleform-submissions.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 *
 * @return void
 */
function run_simpleform_submissions() {

	$plugin = new SimpleForm_Submissions();
	$plugin->run();
}

run_simpleform_submissions();
