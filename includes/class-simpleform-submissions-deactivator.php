<?php
/**
 * File delegated to deactivate the plugin.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the class instantiated during the plugin's deactivation.
 */
class SimpleForm_Submissions_Deactivator {

	/**
	 * Run during plugin deactivation.
	 *
	 * @since 1.0.0
	 * @since 2.1.0 Refactoring of code.
	 *
	 * @return void
	 */
	public static function deactivate() {

		// Detect the parent plugin activation.
		$settings = (array) get_option( 'sform_settings', array() );
		global $wpdb;
		$shortcodes_table = $wpdb->prefix . 'sform_shortcodes';

		if ( $settings ) {

			// Resume the admin notification.
			$settings['notification'] = true;
			update_option( 'sform_settings', $settings );

		}

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $shortcodes_table ) ) === $shortcodes_table ) { // phpcs:ignore			

			// Recalculation of entries.
			$entries       = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE form = '1'" ); // phpcs:ignore
			$moved_entries = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE moved_from = '1'" ); // phpcs:ignore

			$data = array(
				'entries'       => $entries,
				'moved_entries' => $moved_entries,
				'storing'       => '1',
			);

			$wpdb->update( $wpdb->prefix . 'sform_shortcodes', $data, array( 'id' => '1' ) ); // phpcs:ignore

			// Check if other forms have been created.
			$forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes WHERE id != '1'" ); // phpcs:ignore

			foreach ( $forms as $form ) {

				$form_settings = (array) get_option( 'sform_' . $form . '_settings', array() );

				if ( $form_settings ) {

					$form_settings['notification'] = true;
					update_option( 'sform_' . $form . '_settings', $form_settings );

				}

				// Recalculation of entries assigned to each form.
				$form_entries       = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE form = %d", $form ) ); // phpcs:ignore
				$form_moved_entries = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE moved_from = %d", $form ) ); // phpcs:ignore

				$form_data = array(
					'entries'       => $form_entries,
					'moved_entries' => $form_moved_entries,
					'storing'       => '1',
				);

				$wpdb->update( $wpdb->prefix . 'sform_shortcodes', $form_data, array( 'id' => $form ) ); // phpcs:ignore

			}
		}

		delete_option( 'sform_data_update' );
	}
}
