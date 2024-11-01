<?php
/**
 * Main file for the admin functionality of the plugin.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the class that deals with the utilities applied to entries table management.
 */
class SimpleForm_Data_Management {

	/**
	 * Add a notification bubble to Contacts menu item.
	 *
	 * @since 1.4.0
	 *
	 * @return string The text to be used for the menu item.
	 */
	public function notification_bubble() {

		$util       = new SimpleForm_Submissions_Util();
		$validation = new SimpleForm_Submissions_Admin_Validation();
		$counter    = $util->get_sform_option( 1, 'settings', 'counter', true );
		$form_arg   = absint( $validation->sanitized_key( 'form' ) );
		$entry_id   = $validation->sanitized_key( 'id' );

		// Get all data storing options and build an array.
		$forms            = $util->sform_ids();
		$storing_settings = array();
		foreach ( $forms as $form ) {
			$storing_setting = (bool) $util->get_sform_option( $form, 'settings', 'data_storing', true );
			array_push( $storing_settings, $storing_setting );
		}

		$item = __( 'Contacts', 'simpleform' );

		if ( $counter ) {

			// Get the number of unread messages.
			$unread = $this->unread_message_counter( $form_arg, $storing_settings );
			global $current_screen;

			if ( ! empty( $current_screen->id ) && strpos( $current_screen->id, 'sform-entry' ) !== false && ! empty( $entry_id ) ) {
				$current_status = strval( $util->entry_value( absint( $entry_id ), 'status' ) );
				$bubble         = 'new' === $current_status ? $unread - 1 : $unread;
			} else {
				$bubble = $unread;
			}

			$item = $bubble > 0 ? sprintf( __( 'Contacts', 'simpleform' ) . ' <span id="unread-messages"><span class="sform awaiting-mod">%d</span></span>', $bubble ) : __( 'Contacts', 'simpleform' ) . ' <span id="unread-messages"></span>';

		}

		return $item;
	}

	/**
	 * Get the number of unread messages.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $form_arg         The value of form parameter.
	 * @param bool[] $storing_settings Array of storing values.
	 *
	 * @return int The number of unread messages.
	 */
	protected function unread_message_counter( $form_arg, $storing_settings ) {

		global $wpdb;
		global $current_screen;
		$unread = 0;

		if ( ! empty( $form_arg ) ) {

			$util         = new SimpleForm_Submissions_Util();
			$data_storing = $util->get_sform_option( $form_arg, 'settings', 'data_storing', true );

			if ( $data_storing ) {
				$unread = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE status = 'new' AND object != '' AND object != 'not stored' AND form = %d AND listable = '1'", $form_arg ) ); // phpcs:ignore
			}
		} elseif ( in_array( true, $storing_settings, true ) ) {
			if ( ! empty( $current_screen->id ) && ( strpos( $current_screen->id, 'sform-editor' ) !== false || strpos( $current_screen->id, 'sform-settings' ) !== false ) ) {
				$unread = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE status = 'new' AND object != '' AND object != 'not stored' AND form = '1' AND listable = '1'" ); // phpcs:ignore
			} else {
				$unread = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE status = 'new' AND object != '' AND object != 'not stored' AND form != '0' AND listable = '1'" ); // phpcs:ignore 
			}
		}

		return $unread;
	}

	/**
	 * Display entries data when form data storage has been enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $form         The ID of the form.
	 * @param int[]  $forms        Array of form IDs.
	 * @param string $last_message The last submitted message.
	 * @param string $html_data    The HTML markup for the form entries.
	 *
	 * @return void
	 */
	public function display_entries_data( $form, $forms, $last_message, $html_data ) {

		$util          = new SimpleForm_Submissions_Util();
		$entries_table = new SimpleForm_Entries_List();
		$validation    = new SimpleForm_Submissions_Admin_Validation();
		$view          = strval( $validation->sanitized_key( 'view' ) );
		$current_view  = 'inbox' !== $view ? $view : '';

		$admin_notices = $util->get_sform_option( 1, 'settings', 'admin_notices', false );
		$data_storing  = $util->get_sform_option( $form, 'settings', 'data_storing', true );
		$storing_array = array( $util->get_sform_option( 1, 'settings', 'data_storing', true ) );
		$allowed_tags  = $util->sform_allowed_tags();

		foreach ( $forms as $form_id ) {

			$form_data_storing = $util->get_sform_option( $form_id, 'settings', 'data_storing', true );
			array_push( $storing_array, $form_data_storing );

		}

		// Show a table if data storing is enabled for at least one of form.
		if ( ( empty( $form ) && in_array( true, $storing_array, true ) ) || true === $data_storing ) {

			$top_entries_page = '<form id="submissions-table" method="get">';
			// Ensure that the form posts back to current page.
			$top_entries_page .= '<input type="hidden" name="page" value="sform-entries"/>';
			$top_entries_page .= '<input type="hidden" name="view" value="' . $current_view . '"/>';

			echo wp_kses( $top_entries_page, $allowed_tags );

			// Display entries list.
			$entries_table->prepare_items();
			$entries_table->views();
			$entries_table->search_box( __( 'Search' ), 'simpleform-contact-form-submissions' );
			$entries_table->display();

			$bottom_entries_page  = wp_nonce_field( 'simpleform_querying_data', 'simpleform_nonce', true, false );
			$listed_items         = wp_cache_get( 'displayed_items' );
			$bottom_entries_page .= '<input type="hidden" name="items" value="' . $listed_items . '"/>';
			$bottom_entries_page .= '</form>';

			echo wp_kses( $bottom_entries_page, $allowed_tags );

		} else {

			$link = '<a href="' . admin_url( 'admin.php?page=sform-settings' ) . '&form=' . $form . '" target="_blank" style="text-decoration: none">' . __( 'settings', 'simpleform-contact-form-submissions' ) . '</a>';
			/* translators: %s: Settings page link. */
			$add_notice = sprintf( __( 'By disabling the data storing for this form, you have chosen to remove the entries list. Go to %s for editing the option. ', 'simpleform-contact-form-submissions' ), $link ) . '&nbsp;' . __( 'Please note that unsaved messages, where they exist, are also included in the count!', 'simpleform-contact-form-submissions' );

			$notice = ! $admin_notices ? '<div class="notice notice-warning is-dismissible"><p>' . $add_notice . '</p></div>' : '';

			$html_data .= $last_message ? '<div id="submissions-notice" class="unseen"><h3><span class="dashicons dashicons-editor-help"></span>' . __( 'Before you go crazy looking for the received messages', 'simpleform-contact-form-submissions' ) . '</h3>' . __( 'Submissions data is not stored in the WordPress database. Open the General Tab in the Settings page, and check the Data Storing option for enabling the display of messages in the dashboard. By default, only last message is temporarily stored. Therefore, it is recommended that you verify the correct SMTP server configuration in case of use, and always keep the notification email enabled if you want to be sure to receive the messages. ', 'simpleform-contact-form-submissions' ) . '</div>' : '<div id="empty-submission"><h3><span class="dashicons dashicons-info"></span>' . __( 'Empty Inbox', 'simpleform-contact-form-submissions' ) . '</h3>' . __( 'So far, no message has been received yet!', 'simpleform-contact-form-submissions' ) . '<p>' . __( 'Submissions data is not stored in the WordPress database. Open the General Tab in the Settings page, and check the Data Storing option for enabling the display of messages in the dashboard. By default, only last message is temporarily stored. Therefore, it is recommended that you verify the correct SMTP server configuration in case of use, and always keep the notification email enabled if you want to be sure to receive the messages. ', 'simpleform-contact-form-submissions' ) . '</div>';

			$entries_page = $notice . $html_data;

			echo wp_kses( $entries_page, $allowed_tags );

		}
	}

	/**
	 * Edit entry data
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function edit_entry() {

		if ( ! current_user_can( 'manage_options' ) ) {
			die( 'Security checked!' );
		} else {

			$util            = new SimpleForm_Submissions_Util();
			$validation      = new SimpleForm_Submissions_Admin_Validation();
			$values          = $util->sanitized_entry_data();
			$entry_id        = intval( $values['entry'] );
			$entries_view    = strval( $values['entries-view'] );
			$entries_counter = intval( $values['entries-counter'] );
			$entry_form      = intval( $values['entry-form'] );
			$form_id         = intval( $util->entry_value( $entry_id, 'form' ) );
			$current_status  = strval( $util->entry_value( $entry_id, 'status' ) );
			$status          = strval( $values['message-status'] );
			$entry_to        = intval( $values['moveto'] );
			$old_status      = strval( $util->entry_value( $entry_id, 'previous_status' ) );
			$previous_status = $status === $current_status ? $old_status : $current_status;
			$moved_from      = intval( $util->entry_value( $entry_id, 'moved_from' ) );

			if ( (bool) $values['transfer'] ) {
				$moveto     = $entry_to;
				$entry_from = $form_id;
			} else {
				$moveto     = $form_id;
				$entry_from = $moved_from;
			}

			// Make the changes validation.
			$error = $validation->entry_data_validation( $form_id, $status, $moveto );

			if ( ! empty( $error ) ) {

				$data = array(
					'error'   => true,
					'message' => $error,
				);

			} else {

				$entry_data = array(
					'form'            => $moveto,
					'moved_from'      => $moved_from === $moveto ? 0 : $entry_from,
					'status'          => $status,
					'previous_status' => $previous_status,
					'trash_date'      => $this->trash_date( $entry_id, $current_status, $status ),
				);

				global $wpdb;
				$update = $wpdb->update( $wpdb->prefix . 'sform_submissions', $entry_data, array( 'id' => $entry_id ) ); // phpcs:ignore

				if ( $update ) {

					// Update the forms data.
					$util->forms_data_updating( $entry_id, $form_id, $moved_from, $moveto );

					// Send to Akismet updated reports.
					$msg = '';
					$msg = apply_filters( 'akismet_submit_spam', $msg, $entry_id, $current_status, $status );
					$msg = apply_filters( 'akismet_submit_ham', $msg, $entry_id, $current_status, $status );

					$data = array(
						'error'          => false,
						'update'         => true,
						'current_form'   => strval( $util->form_property_value( $moveto, 'name', '' ) ),
						'current_status' => $current_status,
						'entries'        => $this->entries_counter( $entries_view, $entries_counter, $entry_form, $current_status, $status, $moveto ),
						'options'        => $this->moveto_selector( $entry_id, $form_id, $moveto ),
						'message'        => __( 'Entry data has been updated', 'simpleform-contact-form-submissions' ) . $msg,
					);

				} else {

					$update_message = array(
						'error'   => false,
						'update'  => false,
						'message' => __( 'Entry data has already been updated', 'simpleform-contact-form-submissions' ),
					);

					$error_message = array(
						'error'   => true,
						'message' => __( 'Error occurred entry data changing', 'simpleform-contact-form-submissions' ),
					);

					$data = ( $moveto === $form_id ) && ( $status === $current_status ) ? $update_message : $error_message;

				}
			}

			echo wp_json_encode( $data );
			wp_die();

		}
	}

	/**
	 * Set the trash date.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $entry_id       The ID of the entry.
	 * @param string $current_status The current status of the entry.
	 * @param string $status         The new status of the entry.
	 *
	 * @return null|string The trash date.
	 */
	protected function trash_date( $entry_id, $current_status, $status ) {

		$util = new SimpleForm_Submissions_Util();

		if ( 'trash' !== $status ) {
			$trash_date = null;
		} else {
			$trashed_date = strval( $util->entry_value( $entry_id, 'trash_date' ) );
			$trash_date   = $status !== $current_status ? gmdate( 'Y-m-d H:i:s' ) : $trashed_date;
		}

		return $trash_date;
	}

	/**
	 * Update the entries counter.
	 *
	 * @since 2.1.0
	 *
	 * @param string $entries_view    The selected view.
	 * @param int    $entries_counter The number of entries.
	 * @param int    $entry_form      The form ID of the entry.
	 * @param string $current_status  The current status of the entry.
	 * @param string $status          The new status of the entry.
	 * @param int    $moveto          The form ID to move the entry to.
	 *
	 * @return int The updated number of entries to return.
	 */
	protected function entries_counter( $entries_view, $entries_counter, $entry_form, $current_status, $status, $moveto ) {

		if ( empty( $entries_view ) ) {

			if ( ! in_array( $status, array( 'new', 'read', 'answered' ), true ) ) {
				$newcounter = $entries_counter - 1;
			} else {
				$newcounter = $entries_counter;
			}
		} else {

			$realtime_counter = 'new' === $status && $moveto === $entry_form ? $entries_counter + 1 : $entries_counter;
			$current_counter  = ( $current_status !== $status ) || ( $moveto !== $entry_form ) ? $entries_counter - 1 : $entries_counter;
			$newcounter       = 'new' !== $entries_view ? $current_counter : $realtime_counter;

		}

		return $newcounter;
	}

	/**
	 * Rebuild the "Move To" selector.
	 *
	 * @since 2.1.0
	 *
	 * @param int $entry_id The ID of the entry.
	 * @param int $form_id  The form ID of the entry.
	 * @param int $moveto   The form ID to move the entry to.
	 *
	 * @return string The HTML markup for the selector.
	 */
	protected function moveto_selector( $entry_id, $form_id, $moveto ) {

		$options = '';
		$moving  = $moveto !== $form_id ? true : false;
		$caching = $moving ? false : wp_cache_get( 'moveto_selector_' . $entry_id );

		// Do a database query if the there is no cache data with this key.
		if ( false === $caching ) {

			global $wpdb;

			$forms = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM {$wpdb->prefix}sform_shortcodes WHERE id != %d AND status != 'trash' ORDER BY name ASC", $moveto ), ARRAY_A ); // phpcs:ignore

			$options = '<option value="">' . __( 'Select a form to move entry to', 'simpleform-contact-form-submissions' ) . '</option>';

			foreach ( $forms as $form ) {
				$form_id   = $form['id'];
				$form_name = $form['name'];
				$options  .= '<option value="' . $form_id . '">' . $form_name . '</option>';
			}

			wp_cache_set( 'moveto_selector_' . $entry_id, $options );
		}

		return $options;
	}
}
