<?php
/**
 * File delegated to list the most commonly used functions.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the the general utilities class.
 */
class SimpleForm_Submissions_Util {

	/**
	 * Retrieve the entry value.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $entry_id The ID of the entry.
	 * @param string $type     The type of data.
	 *
	 * @return mixed The entry value to return.
	 */
	public function entry_value( $entry_id, $type ) {

		$entry_data = wp_cache_get( 'entry_data_' . $entry_id );

		// Do a database query and save it to the cache if the there is no cache data with this key.
		if ( false === $entry_data ) {
			global $wpdb;
			$entry_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sform_submissions WHERE id = %d", $entry_id ) ); // phpcs:ignore.
			wp_cache_set( 'entry_data_' . $entry_id, $entry_data );
		}

		$entry_value = isset( $entry_data->$type ) ? $entry_data->$type : '';

		return $entry_value;
	}

	/**
	 * Sanitize values entered to update entry data
	 *
	 * @since 2.1.0
	 *
	 * @return mixed[] Array of sanitized values.
	 */
	public function sanitized_entry_data() {

		$validation                = new SimpleForm_Submissions_Admin_Validation();
		$values                    = array();
		$values['entry']           = $validation->sanitized_input( 'entry', 'number' );
		$values['entries-counter'] = $validation->sanitized_input( 'entries-counter', 'number' );
		$values['entry-form']      = $validation->sanitized_input( 'entry-form', 'form' );
		$values['entries-view']    = $validation->sanitized_input( 'entries-view', 'text' );
		$values['message-status']  = $validation->sanitized_input( 'message-status', 'text' );
		$values['moving']          = $validation->sanitized_input( 'moving', 'tickbox' );
		$values['moveto']          = $validation->sanitized_input( 'moveto', 'number' );
		$values['transfer']        = $values['moving'] && 0 !== $values['moveto'] ? true : false;

		return $values;
	}

	/**
	 * Update the forms data.
	 *
	 * @since 2.1.0
	 *
	 * @param int $entry_id   The ID of the entry.
	 * @param int $form_id    The form ID of the entry.
	 * @param int $moved_from The form ID from which the entry was moved.
	 * @param int $moveto     The form ID to move the entry to.
	 *
	 * @return void
	 */
	public function forms_data_updating( $entry_id, $form_id, $moved_from, $moveto ) {

		wp_cache_delete( 'entry_data_' . $entry_id );

		// Return if the entry will not be moved.
		if ( $moveto === $form_id || ! class_exists( 'SimpleForm_Util' ) ) {
			return;
		}

		$util        = new SimpleForm_Util();
		$forms_array = array( $form_id, $moved_from, $moveto );

		// Return an array containing only integers without the value 0.
		$forms = array_unique( array_filter( array_map( 'intval', $forms_array ), fn ( $item ) => 0 !== $item ) );

		foreach ( $forms as $form ) {

			global $wpdb;

			$data_storing  = (bool) $util->form_property_value( $form, 'storing', true );
			$where_clause  = $data_storing ? " AND object != '' AND object != 'not stored'" : '';
			$entries       = $util->form_submissions( $form, $where_clause, true );
			$moved_entries = $util->form_moved_submissions( $form, true );

			// Check if any entries have been moved from this form.
			$moved_to = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT form FROM {$wpdb->prefix}sform_submissions WHERE moved_from = %d {$where_clause}", $form ) ); // phpcs:ignore

			$form_data = array(
				'entries'       => $entries,
				'moved_entries' => $moved_entries,
				'moved_to'      => maybe_serialize( $moved_to ),
			);

			// Delete cache and update the data of the form.
			wp_cache_delete( 'form_data_' . $form );
			$wpdb->update( $wpdb->prefix . 'sform_shortcodes', $form_data, array( 'id' => $form ) ); // phpcs:ignore

		}

		// Update latest messages of the receiving form when the entry was moved.
		$this->update_last_messages( $entry_id, $form_id, $moveto );
	}

	/**
	 * Update the latest messages of the receiving form where the entry was moved.
	 *
	 * @since 2.0.1
	 * @since 2.1.0 Refactoring of code.
	 *
	 * @param int|int[] $entry_id The ID of the entry or array of IDs of the entries.
	 * @param int       $form_id  The form ID from which the entry was moved.
	 * @param int       $moveto   The form ID to move the entry to.
	 *
	 * @return void
	 */
	public function update_last_messages( $entry_id, $form_id, $moveto ) {

		$last_date            = $this->data_entry( $form_id, 'last', 'date' );
		$last_message         = $this->data_entry( $form_id, 'last', 'message' );
		$before_last_date     = $this->data_entry( $form_id, 'before_last', 'date' );
		$before_last_msg      = $this->data_entry( $form_id, 'before_last', 'message' );
		$forwarded_last_date  = $this->data_entry( $form_id, 'forwarded_last', 'date' );
		$forwarded_last_msg   = $this->data_entry( $form_id, 'forwarded_last', 'message' );
		$forwarded_2last_date = $this->data_entry( $form_id, 'forwarded_before_last', 'date' );
		$forwarded_2last_msg  = $this->data_entry( $form_id, 'forwarded_before_last', 'message' );
		$direct_last_date     = $this->data_entry( $form_id, 'direct_last', 'date' );
		$direct_last_msg      = $this->data_entry( $form_id, 'direct_last', 'message' );
		$direct_2last_date    = $this->data_entry( $form_id, 'direct_before_last', 'date' );
		$direct_2last_msg     = $this->data_entry( $form_id, 'direct_before_last', 'message' );
		$moved_last_date      = $this->data_entry( $form_id, 'moved_last', 'date' );
		$moved_last_msg       = $this->data_entry( $form_id, 'moved_last', 'message' );
		$moved_2last_date     = $this->data_entry( $form_id, 'moved_before_last', 'date' );
		$moved_2last_msg      = $this->data_entry( $form_id, 'moved_before_last', 'message' );

		// Build array of messages indexed by dates and remove empty array elements.
		$dates                          = array();
		$dates[ $last_date ]            = $last_message;
		$dates[ $before_last_date ]     = $before_last_msg;
		$dates[ $forwarded_last_date ]  = $forwarded_last_msg;
		$dates[ $forwarded_2last_date ] = $forwarded_2last_msg;
		$dates[ $direct_last_date ]     = $direct_last_msg;
		$dates[ $direct_2last_date ]    = $direct_2last_msg;
		$dates[ $moved_last_date ]      = $moved_last_msg;
		$dates[ $moved_2last_date ]     = $moved_2last_msg;
		$dates                          = array_filter( $dates );

		// Check if the $entry_id is an array.
		if ( is_array( $entry_id ) ) {

			// Check data for transferring a bulk of entries.
			$this->bulk_entry_transfer( $entry_id, $dates, $moveto );

		} else {

			// Check data for a single entry.
			$this->single_entry_transfer( $entry_id, $dates, $moveto );

		}
	}

	/**
	 * Extract the data entry date.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $form_id The form ID.
	 * @param string $name    The message identifying name.
	 * @param string $type    The data entry type to be return.
	 *
	 * @return string|int The data entry.
	 */
	protected function data_entry( $form_id, $name, $type ) {

		$saved_message = strval( get_option( 'sform_' . $name . '_' . $form_id . '_message' ) );
		$data          = $saved_message ? explode( '#', $saved_message ) : false;
		$timestamp     = $data && is_numeric( $data[0] ) ? $data[0] : '';
		$message       = $timestamp && isset( $data[1] ) ? $data[1] : '';
		$data_entry    = 'date' === $type ? $timestamp : $message;

		return $data_entry;
	}

	/**
	 * Check data for transferring a bulk of entries.
	 *
	 * @since 2.1.0
	 *
	 * @param int[]   $entry_id Array of IDs of the entries.
	 * @param mixed[] $dates    Array of messages.
	 * @param int     $moveto   The form ID to move the entry to.
	 *
	 * @return void
	 */
	protected function bulk_entry_transfer( $entry_id, $dates, $moveto ) {

		foreach ( $entry_id as $id ) {

			global $wpdb;
			$entry_date      = $wpdb->get_var( "SELECT date FROM {$wpdb->prefix}sform_submissions WHERE id = '$id'" ); // phpcs:ignore
			$entry_timestamp = strtotime( $entry_date );

			if ( array_key_exists( $entry_timestamp, $dates ) ) {

				$moved_last_to        = strval( get_option( "sform_moved_last_{$moveto}_message", '' ) );
				$moved_before_last_to = strval( get_option( "sform_moved_before_last_{$moveto}_message", '' ) );
				$last_to_date         = $moved_last_to ? absint( explode( '#', $moved_last_to )[0] ) : 0;
				$before_last_to_date  = $moved_before_last_to ? absint( explode( '#', $moved_before_last_to )[0] ) : 0;

				if ( $entry_timestamp > $last_to_date ) {

					update_option( "sform_moved_last_{$moveto}_message", $dates[ $entry_timestamp ] );

					if ( $moved_last_to ) {
						update_option( "sform_moved_before_last_{$moveto}_message", $moved_last_to );
					}
				} elseif ( $entry_timestamp > $before_last_to_date ) {
					update_option( "sform_moved_before_last_{$moveto}_message", $dates[ $entry_timestamp ] );
				}
			}
		}
	}

	/**
	 * Check data for transferring a single entry.
	 *
	 * @since 2.1.0
	 *
	 * @param int     $entry_id The ID of the entry.
	 * @param mixed[] $dates    Array of messages.
	 * @param int     $moveto   The form ID to move the entry to.
	 *
	 * @return void
	 */
	protected function single_entry_transfer( $entry_id, $dates, $moveto ) {

		global $wpdb;
		$entry_date = $wpdb->get_var( $wpdb->prepare( "SELECT date FROM {$wpdb->prefix}sform_submissions WHERE id = %d", $entry_id ) ); // phpcs:ignore

		$date = strtotime( $entry_date );

		// Search the date of entry which has been moved within the array of dates.
		if ( array_key_exists( $date, $dates ) ) {

			$moved_last_to       = get_option( 'sform_moved_last_' . $moveto . '_message' );
			$last_to_date        = intval( $this->data_entry( $moveto, 'moved_last', 'date' ) );
			$before_last_to_date = intval( $this->data_entry( $moveto, 'moved_before_last', 'date' ) );

			// Check if the entry date is more recent than the one used in the last saved message.
			if ( $date > $last_to_date ) {

				// Replace the last message.
				$moved_last_msg = $date . '#' . $dates[ $date ];
				update_option( 'sform_moved_last_' . $moveto . '_message', $moved_last_msg );

				// Move the last to before last.
				if ( $moved_last_to ) {
					update_option( 'sform_moved_before_last_' . $moveto . '_message', $moved_last_to );
				}
			} elseif ( $date > $before_last_to_date ) {

				// Replace the before last message if the entry date is more recent than the one used in the before last saved message.
				$moved_2last_msg = $date . '#' . $dates[ $date ];
				update_option( 'sform_moved_before_last_' . $moveto . '_message', $moved_2last_msg );

			}
		}
	}

	/**
	 * Retrieve the option value.
	 *
	 * @since 2.1.0
	 *
	 * @param int                      $form_id The ID of the form.
	 * @param string                   $type    The type of the option.
	 * @param string                   $key     The key of the option.
	 * @param bool|string|int|string[] $preset  The default value to return if the option does not exist.
	 *
	 * @return mixed The value to return.
	 */
	public function get_sform_option( $form_id, $type, $key, $preset ) {

		if ( 1 === (int) $form_id ) {
			$option = (array) get_option( 'sform_' . $type );
		} else {
			$option = false !== get_option( 'sform_' . $form_id . '_' . $type ) ? (array) get_option( 'sform_' . $form_id . '_' . $type ) : (array) get_option( 'sform_' . $type );
		}

		if ( $key ) {
			if ( isset( $option[ $key ] ) ) {
				if ( is_bool( $option[ $key ] ) ) {
					$value = $option[ $key ] ? true : false;
				} else {
					$value = ! empty( $option[ $key ] ) ? $option[ $key ] : $preset;
				}
			} else {
				$value = $preset;
			}
		} else {
			$value = $option;
		}

		return $value;
	}

	/**
	 * Expand the list of allowed HTML tags and their allowed attributes.
	 *
	 * @since 2.1.0
	 *
	 * @return array<string, mixed[]> Multidimensional array of allowed HTML tags.
	 */
	public function sform_allowed_tags() {

		$allowed_tags = wp_kses_allowed_html( 'post' );

		$allowed_tags['div']['tabindex'] = true;

		$allowed_tags['form'] = array(
			'id'     => true,
			'method' => true,
			'class'  => true,
			'form'   => true,
		);

		$allowed_tags['input'] = array(
			'type'        => true,
			'id'          => true,
			'name'        => true,
			'class'       => true,
			'value'       => true,
			'checked'     => true,
			'placeholder' => true,
			'min'         => true,
			'max'         => true,
			'box'         => true,
			'parent'      => true,
			'disabled'    => true,
			'readonly'    => true,
			'tabindex'    => true,
		);

		$allowed_tags['noscript'] = true;

		$allowed_tags['optgroup'] = array(
			'label' => true,
		);

		$allowed_tags['option'] = array(
			'value'    => true,
			'selected' => true,
			'tag'      => true,
			'disabled' => true,
		);

		$allowed_tags['select'] = array(
			'id'       => true,
			'name'     => true,
			'class'    => true,
			'style'    => true,
			'field'    => true,
			'box'      => true,
			'parent'   => true,
			'disabled' => true,
		);

		$allowed_tags['svg'] = array(
			'xmlns'   => true,
			'viewBox' => true,
			'path'    => true,
		);

		$allowed_tags['textarea']['placeholder'] = true;
		$allowed_tags['textarea']['parent']      = true;

		return $allowed_tags;
	}

	/**
	 * Search all shortcodes ids
	 *
	 * @since 2.1.0
	 *
	 * @return int[] All IDs of created forms.
	 */
	public function sform_ids() {

		$form_ids = wp_cache_get( 'sform_ids' );

		// Do a database query and save it to the cache if the there is no cache data with this key.
		if ( false === $form_ids ) {
			global $wpdb;
			$form_ids = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes" ); // phpcs:ignore.
			wp_cache_set( 'sform_ids', $form_ids );
		}

		return $form_ids;
	}

	/**
	 * Retrieve the form properties value.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $form_id The ID of the form.
	 * @param string $type    The type of data.
	 * @param mixed  $preset  The default value to return if the property value does not exist.
	 *
	 * @return mixed The property value to return.
	 */
	public function form_property_value( $form_id, $type, $preset ) {

		$form_data = wp_cache_get( 'form_data_' . $form_id );

		// Do a database query and save it to the cache if the there is no cache data with this key.
		if ( false === $form_data ) {
			global $wpdb;
			$form_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sform_shortcodes WHERE id = %d", $form_id ) ); // phpcs:ignore.
			wp_cache_set( 'form_data_' . $form_id, $form_data );
		}

		$property_value = isset( $form_data->$type ) ? $form_data->$type : $preset;

		return $property_value;
	}
}
