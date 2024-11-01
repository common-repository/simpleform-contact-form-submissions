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
class SimpleForm_Entries_List_Utils extends SimpleForm_Entries_List {

	/**
	 * Set the conditions for database query.
	 *
	 * @since 1.3.0
	 *
	 * @param string $type The type of condition to be excluded.
	 *
	 * @return string The where clause used to filter records.
	 */
	public function get_query_conditions( $type ) {

		$util          = new SimpleForm_Submissions_Util();
		$validation    = new SimpleForm_Submissions_Admin_Validation();
		$ip_storing    = $util->get_sform_option( 1, 'settings', 'ip_storing', true );
		$form          = $validation->sanitized_key( 'form' );
		$keyword       = $validation->sanitized_key( 's' );
		$date          = strval( $validation->sanitized_key( 'date' ) );
		$ip_clause     = $ip_storing ? 'name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR ip LIKE %s OR email LIKE %s OR phone LIKE %s' : 'name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR email LIKE %s OR phone LIKE %s';
		$where_keyword = ! empty( $keyword ) ? 'WHERE object != %s AND object != %s AND (' . $ip_clause . ')' : 'WHERE object != %s AND object != %s';

		if ( in_array( $date, array( '', 'last_day', 'last_week', 'last_month', 'current_year', 'last_year' ), true ) ) {

			$date_value = array(
				''             => '',
				'last_day'     => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 24 HOUR )',
				'last_week'    => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 7 DAY )',
				'last_month'   => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 30 DAY )',
				'current_year' => ' AND ( YEAR(date) = YEAR(CURDATE() ) )',
				'last_year'    => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 1 YEAR )',
			);

			$where_date = $date_value[ $date ];

		} else {
			$where_date = ' AND YEAR(date) = %d';
		}

		// Exclude entries whose form has the data retention disabled or has been trashed.
		$where_form = ! empty( $form ) ? " AND form = %d AND listable = '1' AND hidden = '0'" : " AND form != '0' AND listable = '1' AND hidden = '0'";

		$where = 'date' === strval( $type ) ? $where_keyword . $where_form : $where_keyword . $where_date . $where_form;

		return $where;
	}

	/**
	 * Set the placeholders for database query.
	 *
	 * @since 1.3.0
	 *
	 * @param string $type The type of condition to be excluded.
	 *
	 * @return mixed[] Array of placeholders used to filter records.
	 */
	public function get_query_placeholders( $type ) {

		global $wpdb;
		$validation = new SimpleForm_Submissions_Admin_Validation();
		$util       = new SimpleForm_Submissions_Util();
		$ip_storing = $util->get_sform_option( 1, 'settings', 'ip_storing', true );
		$form       = $validation->sanitized_key( 'form' );
		$keyword    = $validation->sanitized_key( 's' );
		$date       = strval( $validation->sanitized_key( 'date' ) );
		$argument_1 = '';
		$argument_2 = 'not stored';
		$search     = empty( $keyword ) ? '' : '%' . $wpdb->esc_like( $keyword ) . '%';

		if ( $ip_storing ) {
			$arguments_array = array( $argument_1, $argument_2, $search, $search, $search, $search, $search, $search, $search );
		} else {
			$arguments_array = array( $argument_1, $argument_2, $search, $search, $search, $search, $search, $search );
		}

		$keyword_placeholder = empty( $keyword ) ? array( $argument_1, $argument_2 ) : $arguments_array;

		if ( in_array( $date, array( '', 'last_day', 'last_week', 'last_month', 'current_year', 'last_year' ), true ) ) {

			$date_value = array(
				''             => array(),
				'last_day'     => array(),
				'last_week'    => array(),
				'last_month'   => array(),
				'current_year' => array(),
				'last_year'    => array(),
			);

			$date_placeholder = $date_value[ $date ];

		} else {
			$date_placeholder = array( $date );
		}

		$form_placeholder = empty( $form ) ? array() : array( $form );

		$placeholders = 'date' === strval( $type ) ? array_merge( $keyword_placeholder, $form_placeholder ) : array_merge( $keyword_placeholder, $date_placeholder, $form_placeholder );

		return $placeholders;
	}

	/**
	 * Hide checkbox column if no bulk actions are available.
	 *
	 * @since 2.1.0
	 *
	 * @param string $view The current view.
	 *
	 * @return string[] Array of columns.
	 */
	protected function get_cb_column( $view ) {

		$cb_column = 'answered' !== $view ? array( 'cb' => '<input type="checkbox" />' ) : array();

		return $cb_column;
	}

	/**
	 * Get disabled attribute.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The value of the input attribute.
	 */
	protected function get_disabled_attribute( $item ) {

		$attribute_value = 'answered' === $item['status'] ? 'disabled="disabled"' : '';

		return $attribute_value;
	}

	/**
	 * Get default value.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed[] $item          Array of database data.
	 * @param string  $item_id       The item name.
	 * @param string  $default_value The default value to return.
	 *
	 * @return string The item value to display.
	 */
	protected function get_default_value( $item, $item_id, $default_value ) {

		if ( 'from' === $item_id ) {

			$item_value = ( ! empty( $item['name'] ) && 'not stored' !== $item['name'] ) || ( ! empty( $item['lastname'] ) && 'not stored' !== $item['lastname'] ) ? $item['lastname'] . ' ' . $item['name'] : $default_value;

		} else {

			$item_value = ! empty( $item[ $item_id ] ) && 'not stored' !== $item[ $item_id ] ? $item[ $item_id ] : $default_value;

		}

		return trim( strval( $item_value ) );
	}

	/**
	 * Create a date option to add to the dropdown list.
	 *
	 * @since 2.1.0
	 *
	 * @param string $last_date The date of the last entry.
	 * @param string $option_id The option ID.
	 * @param string $date      The option value.
	 *
	 * @return string The option to add.
	 */
	protected function date_option( $last_date, $option_id, $date ) {

		$option = '';

		$date_value = array(
			'last_day'     => strtotime( '-1 days' ),
			'last_week'    => strtotime( '-7 days' ),
			'last_month'   => strtotime( '-30 days' ),
			'current_year' => strtotime( 'first day of january this year' ),
			'last_year'    => strtotime( '-1 year' ),
		);

		$date_name = array(
			'last_day'     => __( 'Last Day', 'simpleform-contact-form-submissions' ),
			'last_week'    => __( 'Last Week', 'simpleform-contact-form-submissions' ),
			'last_month'   => __( 'Last Month', 'simpleform-contact-form-submissions' ),
			'current_year' => __( 'Current Year', 'simpleform-contact-form-submissions' ),
			'last_year'    => __( 'Last Year', 'simpleform-contact-form-submissions' ),
		);

		$timestamp   = $date_value[ $option_id ];
		$option_name = $date_name[ $option_id ];

		// Show the option if the found entries are older.
		if ( strtotime( $last_date ) >= $timestamp ) {

			$selected = $option_id === $date && $timestamp <= strtotime( $last_date ) ? ' selected = "selected"' : '';
			$option  .= '<option value="' . $option_id . '" ' . $selected . '>' . $option_name . '</option>';

		}

		return $option;
	}

	/**
	 * Create a year option to add to the dropdown list.
	 *
	 * @since 2.1.0
	 *
	 * @param string $date_oldest The date of the oldest entry.
	 * @param string $date        The option value.
	 *
	 * @return string The option to add.
	 */
	protected function year_option( $date_oldest, $date ) {

		$current_year     = gmdate( 'Y', strtotime( 'now' ) );
		$oldest_year      = gmdate( 'Y', absint( strtotime( $date_oldest ) ) );
		$years_time_range = absint( $current_year ) - absint( $oldest_year );
		$option           = '';

		// Retrieve a list of years according to time interval found.
		for ( $i = 1; $i <= $years_time_range; $i++ ) {

			global $wpdb;
			$year       = strval( absint( $current_year ) - $i );
			$year_query = "SELECT * FROM {$wpdb->prefix}sform_submissions WHERE YEAR(date) = %d";
			$entry_year = $wpdb->get_results( $wpdb->prepare( $year_query, $year ), ARRAY_A ); // phpcs:ignore			

			// Show the year option if entries in the corresponding year are found.
			if ( $entry_year ) {
				$selected = $year === $date ? ' selected = "selected"' : '';
				$option  .= '<option value="' . $year . '" ' . $selected . '>' . $year . '</option>';
			}
		}

		return $option;
	}

	/**
	 * Get row actions.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string[] Array of actions.
	 */
	protected function get_row_actions( $item ) {

		$view_args = array(
			'page' => $this->_args['singular'],
			'id'   => $item['id'],
		);

		$referer     = remove_query_arg( array( 'form', 'view', 'date', 's', 'orderby', 'order', 'paged' ) );
		$view_link   = esc_url( add_query_arg( $view_args, $referer ) );
		$view_action = '<a href="' . $view_link . '">' . __( 'View', 'simpleform-contact-form-submissions' ) . '</a>';

		$spam_args = array(
			'action'   => 'spam',
			'id'       => $item['id'],
			'_wpnonce' => wp_create_nonce( 'spam_nonce' ),
		);

		$spam_link   = esc_url( add_query_arg( $spam_args ) );
		$spam_action = '<a href="' . $spam_link . '">' . __( 'Spam', 'simpleform-contact-form-submissions' ) . '</a>';

		$trash_args = array(
			'action'   => 'trash',
			'id'       => $item['id'],
			'_wpnonce' => wp_create_nonce( 'trash_nonce' ),
		);

		$trash_link   = esc_url( add_query_arg( $trash_args ) );
		$trash_action = '<a href="' . $trash_link . '">' . __( 'Trash', 'simpleform-contact-form-submissions' ) . '</a>';

		$restore_args = array(
			'action'   => 'restore',
			'id'       => $item['id'],
			'_wpnonce' => wp_create_nonce( 'restore_nonce' ),
		);

		$restore_link   = esc_url( add_query_arg( $restore_args ) );
		$restore_action = '<a href="' . $restore_link . '">' . __( 'Restore', 'simpleform-contact-form-submissions' ) . '</a>';

		$delete_args = array(
			'action'   => 'delete',
			'id'       => $item['id'],
			'_wpnonce' => wp_create_nonce( 'delete_nonce' ),
		);

		$delete_link   = esc_url( add_query_arg( $delete_args ) );
		$delete_action = '<a href="' . $delete_link . '">' . __( 'Delete Permanently', 'simpleform-contact-form-submissions' ) . '</a>';

		if ( 'trash' === $item['status'] || 'spam' === $item['status'] ) {

			$actions = array(
				'view'    => $view_action,
				'restore' => $restore_action,
				'delete'  => $delete_action,
			);

		} elseif ( 'answered' === $item['status'] ) {

			$actions = array( 'view' => $view_action );

		} else {

			$actions = array(
				'view'  => $view_action,
				'junk'  => $spam_action,
				'trash' => $trash_action,
			);

		}

		return $actions;
	}

	/**
	 * Create an html drop-down list to move items.
	 *
	 * @since 2.1.0
	 *
	 * @return string The drop-down list.
	 */
	protected function dropdown_list() {

		$validation    = new SimpleForm_Submissions_Admin_Validation();
		$entry_form    = $validation->sanitized_key( 'form' );
		$dropdown_list = '';

		// If a form has been selected.
		if ( ! empty( $entry_form ) ) {

			global $wpdb;
			$forms = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM {$wpdb->prefix}sform_shortcodes WHERE id != %d ORDER BY name ASC", $entry_form ), ARRAY_A ); // phpcs:ignore

			if ( $forms ) {

				$dropdown_list .= '<select name="moveto" id="moveto" class="moveto unseen"><option value="">' . __( 'Select Form', 'simpleform-contact-form-submissions' ) . '</option>';

				foreach ( $forms as $form ) {
					$dropdown_list .= '<option value="' . $form['id'] . '">' . $form['name'] . '</option>';
				}

				$dropdown_list .= '</select>';

			}
		}

		return $dropdown_list;
	}
}
