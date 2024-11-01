<?php
/**
 * Main file for the frontend functionality of the plugin.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/partials
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core class used to implement the frontend functionality of the plugin.
 */
class SimpleForm_Submissions_Public {

	/**
	 * Get the client IP address.
	 *
	 * @since 1.0.0
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 *
	 * @return string The client IP address.
	 */
	public function get_client_ip() {

		// Nothing to do without any reliable information.
		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) {

			$client_ip = 'UNKNOWN';

		} else {

			// Fetch the IP address when user is from shared Internet services.
			if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && '127.0.0.1' !== $_SERVER['HTTP_CLIENT_IP'] ) {

				$ipaddress = esc_url_raw( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );

			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && '127.0.0.1' !== $_SERVER['HTTP_X_FORWARDED_FOR'] ) {

				$ipaddress = esc_url_raw( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );

			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) && '127.0.0.1' !== $_SERVER['HTTP_X_FORWARDED'] ) {

				$ipaddress = esc_url_raw( wp_unslash( $_SERVER['HTTP_X_FORWARDED'] ) );

			} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) && '127.0.0.1' !== $_SERVER['HTTP_FORWARDED_FOR'] ) {

				$ipaddress = esc_url_raw( wp_unslash( $_SERVER['HTTP_FORWARDED_FOR'] ) );

			} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) && '127.0.0.1' !== $_SERVER['HTTP_CLIENT_IP'] ) {

				$ipaddress = esc_url_raw( wp_unslash( $_SERVER['HTTP_FORWARDED'] ) );

			} else {

				// In all other cases, REMOTE_ADDR is the only IP we can trust.
				$ipaddress = esc_url_raw( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

			}

			// Check for multiple IP addresses that are passed through.
			$ip_list = explode( ',', $ipaddress );

			// Only last IP in the list) can be trusted.
			if ( isset( $ip_list[1] ) ) {
				$ipaddress = trim( $ip_list[0] );
			}

			// Validate IP.
			$client_ip = filter_var( $ipaddress, FILTER_VALIDATE_IP ) ? $ipaddress : 'INVALID';

		}

		return $client_ip;
	}

	/**
	 * Change form data values when form is submitted.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $value          The value to filter.
	 * @param int      $form_id        The ID of the form used.
	 * @param string[] $submitter_data Array of data about the sender.
	 * @param string[] $message_data   Array of data about the message.
	 * @param string   $flagged        The result of Akismet checking.
	 *
	 * @return mixed[] Array of data to be stored in the database.
	 */
	public function storing_fields_values( $value, $form_id, $submitter_data, $message_data, $flagged ) {

		// Create a new object to access the class.
		$util         = new SimpleForm_Submissions_Util();
		$data_storing = $util->get_sform_option( $form_id, 'settings', 'data_storing', true );
		$ip_storing   = $util->get_sform_option( 1, 'settings', 'ip_storing', true );
		$ip_address   = $ip_storing ? $this->get_client_ip() : 'not stored';
		$extra_values = array();
		$status       = empty( $flagged ) ? 'new' : 'spam';
		$listable     = $data_storing ? '1' : '0';

		if ( $data_storing ) {

			$form_values = array(
				'name'     => $submitter_data['name'],
				'lastname' => $submitter_data['lastname'],
				'email'    => $submitter_data['email'],
				'phone'    => $message_data['phone'],
				'url'      => $message_data['website'],
				'subject'  => $message_data['subject'],
				'object'   => $message_data['message'],
				'ip'       => $ip_address,
				'status'   => $status,
				'listable' => $listable,
			);

		} else {

			$form_values = array(
				'name'     => 'not stored',
				'lastname' => 'not stored',
				'email'    => 'not stored',
				'phone'    => 'not stored',
				'url'      => 'not stored',
				'subject'  => 'not stored',
				'object'   => 'not stored',
				'ip'       => 'not stored',
				'status'   => $status,
				'listable' => $listable,
			);

		}

		// Add data used for spam detection.
		$value = array_merge( $form_values, apply_filters( 'spam_detection_parameters', $extra_values, $form_id, $submitter_data['name'], $submitter_data['email'] ) );

		return $value;
	}

	/**
	 * Display confirmation message if notification email has been disabled.
	 * $mailing bool
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $data            Array of data to filter.
	 * @param int     $form_id         The ID of the form used.
	 * @param bool    $mailing         The result of sending the notification email.
	 * @param bool    $redirect        The action type after submission.
	 * @param string  $redirect_url    The URL of the page to which the user is redirected.
	 * @param string  $success_message The confirmation message.
	 * @param string  $error_message   The error message.
	 *
	 * @return mixed[] Array of data to encode as JSON.
	 */
	public function sform_display_message( $data, $form_id, $mailing, $redirect, $redirect_url, $success_message, $error_message ) {

		// Create a new object to access the class.
		$util         = new SimpleForm_Submissions_Util();
		$data_storing = $util->get_sform_option( $form_id, 'settings', 'data_storing', true );

		if ( $data_storing || $mailing ) {
			$data = array(
				'error'        => false,
				'redirect'     => $redirect,
				'redirect_url' => $redirect_url,
				'notice'       => $success_message,
			);
		} else {
			$data = array(
				'error'     => true,
				'notice'    => $error_message,
				'showerror' => true,
			);
		}

		return $data;
	}

	/**
	 * When AJAX is disabled, display an error if submitted data have not been stored or delivered via email.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $errors   Array of errors found during form validation.
	 * @param int     $form_id  The ID of the form used.
	 * @param bool    $mailing  The result of sending the notification email.
	 *
	 * @return mixed The error encountered during validation.
	 */
	public function sform_display_post_message( $errors, $form_id, $mailing ) {

		// Create a new object to access the class.
		$util         = new SimpleForm_Submissions_Util();
		$data_storing = $util->get_sform_option( $form_id, 'settings', 'data_storing', true );

		if ( $data_storing || $mailing ) {
			$errors = '';
		} else {
			$errors = $form_id . ';server_error';
		}

		return $errors;
	}
}
