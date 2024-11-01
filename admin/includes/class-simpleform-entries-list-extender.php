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
class SimpleForm_Entries_List_Extender extends SimpleForm_Entries_List {

	/**
	 * Class constructor.
	 *
	 * @since  2.1.0
	 */
	public function __construct() {

		// Switch the primary column for the entries list table.
		add_filter( 'list_table_primary_column', array( $this, 'entries_primary_column' ), 10, 2 );
		// Add conditional items into the bulk actions dropdown for entries list.
		add_filter( 'bulk_actions-toplevel_page_sform-entries', array( $this, 'register_sform_actions' ) );
		// Display a notice in case of hidden submissions.
		add_filter( 'hidden_submissions', array( $this, 'hidden_submissions_notice' ), 10, 2 );
	}

	/**
	 * Display a notice in case of hidden submissions
	 *
	 * @since 2.1.0
	 *
	 * @param string     $notice The value to filter.
	 * @param string|int $form   The ID of the form.
	 *
	 * @return string The admin notice.
	 */
	public function hidden_submissions_notice( $notice, $form ) {

		$hidden_messages = wp_cache_get( 'hidden_entries' );

		if ( false === $hidden_messages ) {

			global $wpdb;
			$hidden_messages = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE listable != '1' AND object != '' AND object != 'not stored'" ); // phpcs:ignore
			wp_cache_set( 'hidden_entries', $hidden_messages );

		}

		$notice = $hidden_messages > 0 && empty( $form ) ? '<span id="storing-notice" class="dashicons dashicons-warning"></span>' : '';

		return $notice;
	}

	/**
	 * Add conditional items into the bulk actions dropdown for entries list.
	 *
	 * @since 1.3.0
	 *
	 * @param string[] $bulk_actions Array of the available bulk actions for the entries table.
	 *
	 * @return string[] Array of the available bulk actions for the entries table.
	 */
	public function register_sform_actions( $bulk_actions ) {

		$validation = new SimpleForm_Submissions_Admin_Validation();
		$view       = $validation->sanitized_key( 'view' );
		$form       = $validation->sanitized_key( 'form' );

		if ( in_array( $view, array( 'spam', 'trash' ), true ) ) {

			$bulk_actions['bulk-restore'] = __( 'Restore', 'simpleform-contact-form-submissions' );
			$bulk_actions['bulk-delete']  = __( 'Delete permanently', 'simpleform-contact-form-submissions' );

		} else {

			if ( 'answered' !== $view ) {
				$bulk_actions['bulk-spam']  = __( 'Mark as Spam', 'simpleform-contact-form-submissions' );
				$bulk_actions['bulk-trash'] = __( 'Move to Trash', 'simpleform-contact-form-submissions' );
			}

			if ( ! empty( $form ) ) {
				$bulk_actions['bulk-move'] = __( 'Move to Form', 'simpleform-contact-form-submissions' );
			}
		}

		return $bulk_actions;
	}

	/**
	 * Display an admin notice whether the row/bulk action is successful
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function display_notice() {

		$transient_notice = get_transient( 'sform_action_notice' );
		$notice           = false !== $transient_notice ? strval( $transient_notice ) : '';

		global $sform_entries;
		$entries_columns = (array) get_column_headers( $sform_entries );
		$hidden_columns  = (array) get_hidden_columns( $sform_entries );
		$unused_columns  = array_intersect( array_keys( $entries_columns ), $hidden_columns );

		if ( in_array( 'id', $unused_columns, true ) && ! in_array( 'subject', array_keys( $entries_columns ), true ) ) {

			$notice = '<div class="notice notice-error"><p>' . __( 'ID column cannot be kept hidden. Click the Screen Options tab in the upper right of the screen and update the current selection of displayed columns.', 'simpleform-contact-form-submissions' ) . '</p></div>';

		}

		if ( ! in_array( 'id', array_keys( $entries_columns ), true ) && in_array( 'subject', $unused_columns, true ) ) {

			$notice = '<div class="notice notice-error"><p>' . __( 'Subject column cannot be kept hidden. Click the Screen Options tab in the upper right of the screen and update the current selection of displayed columns.', 'simpleform-contact-form-submissions' ) . '</p></div>';

		}

		if ( in_array( 'id', $unused_columns, true ) && in_array( 'subject', $unused_columns, true ) ) {

			$notice = '<div class="notice notice-error"><p>' . __( 'ID and Subject columns cannot both be kept hidden. Click the Screen Options tab in the upper right of the screen and update the current selection of displayed columns.', 'simpleform-contact-form-submissions' ) . '</p></div>';

		}

		echo '<div class="submission-notice">' . wp_kses_post( $notice ) . '</div>';
	}

	/**
	 * Add column in the entries table.
	 *
	 * @since 2.1.0
	 *
	 * @param string[] $columns Array of columns used in the table.
	 * @param string   $column  The ID of column.
	 *
	 * @return string[] Array of columns used in the table.
	 */
	public function add_column( $columns, $column ) {

		$util            = new SimpleForm_Submissions_Util();
		$validation      = new SimpleForm_Submissions_Admin_Validation();
		$default_columns = array( 'subject', 'firstname', 'message', 'email', 'date' );
		$data_columns    = (array) $util->get_sform_option( 1, 'settings', 'data_columns', $default_columns );
		$view            = $validation->sanitized_key( 'view' );

		$column_name = array(
			'id'        => __( 'ID', 'simpleform-contact-form-submissions' ),
			'subject'   => __( 'Subject', 'simpleform-contact-form-submissions' ),
			'progress'  => __( 'Status', 'simpleform-contact-form-submissions' ),
			'firstname' => __( 'Name', 'simpleform-contact-form-submissions' ),
			'lastname'  => __( 'Last Name', 'simpleform-contact-form-submissions' ),
			'from'      => __( 'From', 'simpleform-contact-form-submissions' ),
			'message'   => __( 'Message', 'simpleform-contact-form-submissions' ),
			'email'     => __( 'Email', 'simpleform-contact-form-submissions' ),
			'phone'     => __( 'Phone', 'simpleform-contact-form-submissions' ),
			'ip'        => __( 'IP', 'simpleform-contact-form-submissions' ),
			'date'      => __( 'Date', 'simpleform-contact-form-submissions' ),
		);

		if ( in_array( $column, $data_columns, true ) ) {
			$columns[ $column ] = $column_name[ $column ];
		}

		if ( 'progress' === $column && 'inbox' === $view ) {
			$columns['progress'] = $column_name['progress'];
		}

		return $columns;
	}

	/**
	 * Switch the primary column for the entries list table.
	 *
	 * @since 2.1.0
	 *
	 * @param string $preset Column name default for the list table.
	 * @param string $screen Screen ID for the list table.
	 *
	 * @return mixed The sanitized value.
	 */
	public function entries_primary_column( $preset, $screen ) {

		global $sform_entries;
		$entries_columns = (array) get_column_headers( $sform_entries );
		$hidden_columns  = (array) get_hidden_columns( $sform_entries );

		if ( 'toplevel_page_sform-entries' === $screen ) {

			$preset = ! in_array( 'id', array_keys( $entries_columns ), true ) || in_array( 'id', $hidden_columns, true ) ? 'subject' : 'id';

		}

		return $preset;
	}

	/**
	 * Die when the nonce check fails.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function invalid_nonce_redirect() {

		wp_die(
			esc_html( __( 'Sorry, you are using an invalid nonce to proceed.', 'simpleform-contact-form-submissions' ) ),
			esc_html( __( 'Error', 'simpleform-contact-form-submissions' ) ),
			array(
				'response'  => 500,
				'back_link' => true,
			)
		);
	}

	/**
	 * Retrieve items data for displaying.
	 * query the plugin database and return the data in array format
	 *
	 * @param string $view The current view.
	 * @param string $type The type of query to run.
	 *
	 * @since 2.1.0
	 *
	 * @return mixed[] Array of entries data.
	 */
	public function items_to_display( $view, $type ) {

		global $wpdb;

		$condition = array(
			''         => " AND status != 'trash' AND status != 'spam'",
			'inbox'    => " AND status != 'trash' AND status != 'spam'",
			'new'      => " AND status = 'new'",
			'answered' => " AND status = 'answered'",
			'spam'     => " AND status = 'spam'",
			'trash'    => " AND status = 'trash'",
		);

		$status       = $condition[ $view ];
		$utils_class  = new SimpleForm_Entries_List_Utils();
		$where_clause = $utils_class->get_query_conditions( '' ) . $status;
		$placeholders = $utils_class->get_query_placeholders( '' );

		if ( 'displaying' === $type ) {

			$data = wp_cache_get( $view . '_displayed_items' );

			if ( false === $data ) {

				$validation   = new SimpleForm_Submissions_Admin_Validation();
				$orderby      = strval( $validation->sanitized_key( 'orderby' ) );
				$order        = strval( $validation->sanitized_key( 'order' ) );
				$current_page = $this->get_pagenum();

				$per_page     = $this->get_items_per_page( 'entries_per_page', 20 );
				$offset       = 1 < $current_page ? $per_page * ( $current_page - 1 ) : 0;
				$placeholders = array_merge( $placeholders, array( $per_page, $offset ) );

				$query = "SELECT * FROM {$wpdb->prefix}sform_submissions {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
				$data = $wpdb->get_results( $wpdb->prepare( $query, $placeholders ), ARRAY_A ); // phpcs:ignore

				$displayed_items = count( $data );
				wp_cache_set( 'displayed_items', $displayed_items );
				wp_cache_set( $view . '_displayed_items', $data );

			}
		} else {

			$data = wp_cache_get( $view . '_total_items' );

			if ( false === $data ) {

				$query = "SELECT COUNT( id ) FROM {$wpdb->prefix}sform_submissions {$where_clause}";
				$data = $wpdb->get_var( $wpdb->prepare( $query, $placeholders ) ); // phpcs:ignore
				wp_cache_set( $view . '_total_items', $data );

			}
		}

		return $data;
	}
}
