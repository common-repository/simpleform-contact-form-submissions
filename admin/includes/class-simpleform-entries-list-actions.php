<?php
/**
 * File delegated to extend the functions that takes care of listing the entries.
 *
 * @package SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integration Class that extends the SimpleForm_Entries_List parent subclass.
 */
class SimpleForm_Entries_List_Actions extends SimpleForm_Entries_List {

	/**
	 * Delete the entry.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $entry The ID of the entry.
	 *
	 * @return void
	 */
	protected function delete_entry( $entry ) {

		if ( ! empty( $entry ) ) {

			global $wpdb;

			if ( ! is_array( $entry ) ) {

				$forms   = $wpdb->get_results( $wpdb->prepare( "SELECT form, moved_from FROM {$wpdb->prefix}sform_submissions WHERE id = %d", $entry ), ARRAY_A ); // phpcs:ignore
				$success = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sform_submissions WHERE id = %d", $entry ) ); // phpcs:ignore.

				if ( false !== $success ) {

					// Clear cache if entry is deleted.
					$form = $forms['form'];
					$from = $forms['moved_from'];
					wp_cache_delete( 'sform_submissions_' . $form );
					wp_cache_delete( 'form_data_' . $form );
					wp_cache_delete( 'sform_moved_submissions_' . $from );
					wp_cache_delete( 'form_data_' . $from );

					/* translators: %s: The ID of entry just deleted. */
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Entry with ID %s permanently deleted', 'simpleform-contact-form-submissions' ), absint( $entry ) ) . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to delete. */
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( 'Error occurred while deleting the entry with ID %s', 'simpleform-contact-form-submissions' ), absint( $entry ) ) . '</p></div>';

				}
			} else {

				$ids_count          = count( $entry );
				$placeholders_array = array_fill( 0, $ids_count, '%d' );
				$placeholders       = implode( ',', $placeholders_array );
				$entries            = implode( ', ', $entry );

				$forms   = $wpdb->get_col( $wpdb->prepare( "SELECT form FROM {$wpdb->prefix}sform_submissions WHERE id IN({$placeholders})", $entry ) ); // phpcs:ignore
				$givers  = $wpdb->get_col( $wpdb->prepare( "SELECT moved_from FROM {$wpdb->prefix}sform_submissions WHERE id IN({$placeholders})", $entry ) ); // phpcs:ignore
				$success = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}sform_submissions WHERE id IN({$placeholders})", $entry ) ); // phpcs:ignore

				if ( false !== $success ) {

					// Clear cache if entries are deleted.
					foreach ( $forms as $form ) {
						wp_cache_delete( 'sform_submissions_' . $form );
						wp_cache_delete( 'form_data_' . $form );
					}
					foreach ( $givers as $from ) {
						wp_cache_delete( 'sform_moved_submissions_' . $from );
						wp_cache_delete( 'form_data_' . $from );
					}

					/* translators: %s: The ID of entry just deleted. */
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Entry with ID %s permanently deleted', 'Entries with IDs %s permanently deleted', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to delete. */
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( _n( 'Error occurred while deleting the entry with ID %s', 'Error occurred while deleting the entries with IDs %s', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . '</p></div>';

				}
			}

			// Save the admin notice.
			set_transient( 'sform_action_notice', $admin_notice, 2 );

		}
	}

	/**
	 * Move the entry to the trash.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $entry The ID of the entry.
	 *
	 * @return void
	 */
	protected function trash_entry( $entry ) {

		if ( ! empty( $entry ) ) {

			global $wpdb;
			$trash_date = gmdate( 'Y-m-d H:i:s' );

			if ( ! is_array( $entry ) ) {

				$success = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET previous_status = status, status = 'trash', trash_date = %s WHERE id = %d", $trash_date, $entry ) ); // phpcs:ignore

				if ( false !== $success ) {

					/* translators: %s: The ID of entry just moved to the trash. */
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Entry with ID %s successfully moved to the trash', 'simpleform-contact-form-submissions' ), absint( $entry ) ) . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to move to the trash. */
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( 'Error occurred while moving the entry with ID %s to the trash', 'simpleform-contact-form-submissions' ), absint( $entry ) ) . '</p></div>';

				}
			} else {

				$arguments          = $entry;
				$ids_count          = count( $entry );
				$placeholders_array = array_fill( 0, $ids_count, '%d' );
				$placeholders       = implode( ',', $placeholders_array );
				array_unshift( $arguments, $trash_date );
				$entries = implode( ', ', $entry );

				$success = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET previous_status = status, status = 'trash', trash_date = %s WHERE id IN({$placeholders})", $arguments ) ); // phpcs:ignore

				if ( false !== $success ) {

					/* translators: %s: The ID of entry just moved to the trash. */
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Entry with ID %s successfully moved to the trash', 'Entries with IDs %s successfully moved to the trash', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to move to the trash. */
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( _n( 'Error occurred while moving the entry with ID %s to the trash', 'Error occurred while moving the entries with IDs %s to the trash', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . '</p></div>';

				}
			}

			// Save the admin notice.
			set_transient( 'sform_action_notice', $admin_notice, 2 );

		}
	}

	/**
	 * Mark the entry as spam.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $entry The ID of the entry.
	 *
	 * @return void
	 */
	protected function spam_entry( $entry ) {

		if ( ! empty( $entry ) ) {

			global $wpdb;

			if ( ! is_array( $entry ) ) {

				$success = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET previous_status = status, status = 'spam', trash_date = NULL WHERE id = %d", $entry ) ); // phpcs:ignore

				if ( false !== $success ) {

					$append = apply_filters( 'akismet_submit_spam', $append = '', $entry );
					/* translators: %s: The ID of entry just marked as spam. */
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Entry with ID %s successfully marked as spam', 'simpleform-contact-form-submissions' ), absint( $entry ) ) . $append . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to mark as spam. */
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( 'Error occurred while marking the entry with ID %s as spam', 'simpleform-contact-form-submissions' ), absint( $entry ) ) . '</p></div>';

				}
			} else {

				$ids_count          = count( $entry );
				$placeholders_array = array_fill( 0, $ids_count, '%d' );
				$placeholders       = implode( ',', $placeholders_array );
				$entries            = implode( ', ', $entry );

				$success = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET previous_status = status, status = 'spam', trash_date = NULL WHERE id IN({$placeholders})", $entry ) ); // phpcs:ignore

				if ( false !== $success ) {

					$append = apply_filters( 'akismet_submit_spam', $append = '', $entry );
					/* translators: %s: The ID of entry just marked as spam. */
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Entry with ID %s successfully marked as spam', 'Entries with IDs %s successfully marked as spam', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . $append . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to mark as spam. */
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( _n( 'Error occurred while marking the entry with ID %s as spam', 'Error occurred while marking the entries with IDs %s as spam', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . '</p></div>';

				}
			}

			// Save the admin notice.
			set_transient( 'sform_action_notice', $admin_notice, 2 );

		}
	}

	/**
	 * Restore the entry from spam or trash.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed  $entry The ID of the entry.
	 * @param string $view  The current view.
	 *
	 * @return void
	 */
	protected function restore_entry( $entry, $view ) {

		if ( ! empty( $entry ) ) {

			global $wpdb;

			if ( ! is_array( $entry ) ) {

				$success = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET status = CASE previous_status WHEN 'new' THEN 'new' WHEN 'read' THEN 'read' ELSE 'read' END, previous_status = '', trash_date = NULL WHERE id = %d", $entry ) ); // phpcs:ignore

				if ( false !== $success ) {

					$notice       = array(
						'spam'  => array(
							/* translators: %s: The ID of entry just unmarked as spam. */
							'single_success' => __( 'Entry with ID %s successfully unmarked as spam', 'simpleform-contact-form-submissions' ),
						),
						'trash' => array(
							/* translators: %s: The ID of entry just restored. */
							'single_success' => __( 'Entry with ID %s successfully restored from the trash', 'simpleform-contact-form-submissions' ),
						),
					);
					$message      = $notice[ $view ]['single_success'];
					$append       = 'spam' === $view ? apply_filters( 'akismet_submit_ham', $append = '', $entry ) : '';
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( $message, absint( $entry ) ) . $append . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to restore. */
					$message      = __( 'Error occurred while restoring the entry with ID %s', 'simpleform-contact-form-submissions' );
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( $message, absint( $entry ) ) . '</p></div>';

				}
			} else {

				$ids_count          = count( $entry );
				$placeholders_array = array_fill( 0, $ids_count, '%d' );
				$placeholders       = implode( ',', $placeholders_array );
				$entries            = implode( ', ', $entry );

				$success = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET status = CASE previous_status WHEN 'new' THEN 'new' WHEN 'read' THEN 'read' ELSE 'read' END, previous_status = '', trash_date = NULL WHERE id IN({$placeholders})", $entry ) ); // phpcs:ignore

				if ( false !== $success ) {

					$notice       = array(
						'spam'  => array(
							/* translators: %s: The ID of entry just unmarked as spam. */
							'multi_success' => _n( 'Entry with ID %s successfully unmarked as spam', 'Entries with IDs %s successfully unmarked as spam', $ids_count, 'simpleform-contact-form-submissions' ),
						),
						'trash' => array(
							/* translators: %s: The ID of entry just restored. */
							'multi_success' => _n( 'Entry with ID %s successfully restored from the trash', 'Entries with IDs %s successfully restored from the trash', $ids_count, 'simpleform-contact-form-submissions' ),
						),
					);
					$message      = $notice[ $view ]['multi_success'];
					$append       = 'spam' === $view ? apply_filters( 'akismet_submit_ham', $append = '', $entry ) : '';
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( $message, $entries ) . $append . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to restore. */
					$message      = _n( 'Error occurred while restoring the entry with ID %s', 'Error occurred while restoring the entries with IDs %s', $ids_count, 'simpleform-contact-form-submissions' );
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( $message, $entries ) . '</p></div>';

				}
			}

			// Save the admin notice.
			set_transient( 'sform_action_notice', $admin_notice, 2 );

		}
	}

	/**
	 * Move the entry.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $entry      The ID of the entry.
	 * @param int   $entry_form The form ID of the entry.
	 * @param int   $moveto     The form ID to move the entry to.
	 *
	 * @return void
	 */
	protected function move_entry( $entry, $entry_form, $moveto ) {

		if ( is_array( $entry ) && ! empty( $entry ) ) {

			$ids_count          = count( $entry );
			$placeholders_array = array_fill( 0, $ids_count, '%d' );
			$placeholders       = implode( ',', $placeholders_array );
			$entries            = implode( ', ', $entry );

			if ( ! empty( $moveto ) ) {

				global $wpdb;
				$form_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}sform_shortcodes WHERE id = %d", $moveto ) ); // phpcs:ignore
				$name      = ! empty( $form_name ) ? $form_name : __( 'selected form', 'simpleform-contact-form-submissions' );
				$success   = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET moved_from = form, form = %d WHERE id IN({$placeholders})", array_unshift( $entry, $moveto ) ) ); // phpcs:ignore

				if ( false !== $success ) {

					// Updates the latest messages of the form to which the entry has been moved.
					$util = new SimpleForm_Submissions_Util();
					$util->update_last_messages( $entry, $entry_form, $moveto );

					/* translators: %s: The ID of entry just moved. */
					$admin_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Entry with ID: %1$s successfully moved to %2$s', 'Entries with IDs: %1$s successfully moved to %2$s', $ids_count, 'simpleform-contact-form-submissions' ), $entries, $name ) . '</p></div>';

					// Save the number of rows affected.
					set_transient( 'updated_items', $success, 2 );

				} else {

					/* translators: %s: The ID of entry you have tried to move. */
					$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( _n( 'Error occurred moving the entry with ID %s', 'Error occurred moving the entries with IDs %s', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . '</p></div>';

				}
			} else {

				/* translators: %s: The ID of entry you have tried to move. */
				$admin_notice = '<div class="notice notice-error is-dismissible"><p>' . sprintf( _n( 'Error occurred moving the entry with ID %s to the selected form', 'Error occurred moving the entries with IDs %s to the selected form', $ids_count, 'simpleform-contact-form-submissions' ), $entries ) . '</p></div>';

			}

			set_transient( 'sform_action_notice', $admin_notice, 2 );

		}
	}
}
