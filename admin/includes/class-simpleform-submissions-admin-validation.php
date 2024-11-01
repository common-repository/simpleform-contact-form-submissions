<?php
/**
 * File delegated to validate the selected admin options.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the class that deals with the additional input form validation.
 */
class SimpleForm_Submissions_Admin_Validation {

	/**
	 * Sanitize form data
	 *
	 * @since 2.2.0
	 *
	 * @param string $field The ID of input field.
	 * @param string $type  The type of input field.
	 *
	 * @return mixed The sanitized value.
	 */
	public function sanitized_input( $field, $type ) {

		if ( isset( $_POST[ $field ] ) && isset( $_POST['simpleform_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['simpleform_nonce'] ), 'simpleform_backend_update' ) ) {

			$sanitized_value = array(
				'form'       => absint( $_POST[ $field ] ),
				'number'     => absint( $_POST[ $field ] ),
				'tickbox'    => true,
				'text'       => sanitize_text_field( wp_unslash( $_POST[ $field ] ) ),
				'checkboxes' => explode( ',', sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ),

			);

			$value = $sanitized_value[ $type ];

		} else {

			$default_value = array(
				'form'       => 1,
				'number'     => 0,
				'tickbox'    => false,
				'text'       => '',
				'checkboxes' => array(),
			);

			$value = $default_value[ $type ];

		}

		return $value;
	}

	/**
	 * Sanitize URL argument.
	 *
	 * @since 2.1.0
	 *
	 * @param string $key The URL argument key.
	 *
	 * @return mixed The sanitized value.
	 */
	public function sanitized_key( $key ) {

		if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore

			$sanitized_value = array(
				'form'     => absint( $_GET[ $key ] ), // phpcs:ignore
				'view'     => sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), // phpcs:ignore
				'date'     => sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), // phpcs:ignore
				's'        => sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), // phpcs:ignore
				'orderby'  => sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), // phpcs:ignore
				'order'    => sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), // phpcs:ignore
				'id'       => is_array( $_GET[ $key ] ) ? array_map( 'absint', $_GET[ $key ] ) : absint( $_GET[ $key ] ), // phpcs:ignore
				'action'   => sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), // phpcs:ignore
				'moveto'   => absint( $_GET[ $key ] ), // phpcs:ignore
				'items'    => absint( $_GET[ $key ] ), // phpcs:ignore
				'_wpnonce' => sanitize_key( wp_unslash( $_GET[ $key ] ) ), // phpcs:ignore
			);

			$value = $sanitized_value[ $key ];

		} else {

			$default_value = array(
				'form'     => '',
				'view'     => 'inbox',
				'date'     => '',
				's'        => '',
				'orderby'  => 'date',
				'order'    => 'desc',
				'id'       => '',
				'action'   => '',
				'moveto'   => '',
				'items'    => 0,
				'_wpnonce' => '',
			);

			$value = $default_value[ $key ];

		}

		return $value;
	}

	/**
	 * Validate the entry data editing.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $form_id The form ID of the entry.
	 * @param string $status  The new status of the entry.
	 * @param int    $moveto  The form ID to move the entry to.
	 *
	 * @return string The error message.
	 */
	public function entry_data_validation( $form_id, $status, $moveto ) {

		$error = '';

		if ( ! $form_id ) {
			$error = __( 'It seems the entry has been deleted!', 'simpleform-contact-form-submissions' );
		}

		if ( ! in_array( $status, array( 'new', 'read', 'answered', 'spam', 'trash' ), true ) ) {
			$error = __( 'Error occurred entry status changing', 'simpleform-contact-form-submissions' );
		}

		global $wpdb;
		$forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes WHERE status != 'trash'" ); // phpcs:ignore

		if ( $moveto > 0 && ! in_array( $moveto, array_map( 'intval', $forms ), true ) ) {
			$error = __( 'It seems the form has been deleted!', 'simpleform-contact-form-submissions' );
		}

		return $error;
	}
}
