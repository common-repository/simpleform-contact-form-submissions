<?php
/**
 * File delegated to implement displaying entries in a list table.
 *
 * @package SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the base subclass that extends the WP_List_Table class.
 */
class SimpleForm_Entries_List extends WP_List_Table {

	/**
	 * Override the parent constructor to pass arguments.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'sform-entry',
				'plural'   => 'sform-entries',
				'ajax'     => false,
			)
		);

		$this->includes();
	}

	/**
	 * Set a list of views available on this table.
	 *
	 * @since 1.3.0
	 *
	 * @return string[] Array of views that can be used in the table.
	 */
	protected function get_views() {

		global $wpdb;
		$views          = array();
		$extender_class = new SimpleForm_Entries_List_Extender();
		$utils_class    = new SimpleForm_Entries_List_Utils();
		$validation     = new SimpleForm_Submissions_Admin_Validation();
		$view           = strval( $validation->sanitized_key( 'view' ) );
		$where          = $utils_class->get_query_conditions( '' );
		$placeholders   = $utils_class->get_query_placeholders( '' );
		$sql            = $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}sform_submissions {$where}", $placeholders ); // phpcs:ignore
		$status_array   = $wpdb->get_col( $sql ); // phpcs:ignore

		$count_new      = (int) count( array_keys( $status_array, 'new', true ) );
		$count_answered = (int) count( array_keys( $status_array, 'answered', true ) );
		$count_spam     = (int) count( array_keys( $status_array, 'spam', true ) );
		$count_trash    = (int) count( array_keys( $status_array, 'trash', true ) );
		$count_inbox    = count( $status_array ) - $count_spam - $count_trash;

		$inbox_class = array(
			'inbox'    => 'class="current"',
			'new'      => '',
			'answered' => '',
			'spam'     => '',
			'trash'    => '',
		);

		$new_class = array(
			'inbox'    => '',
			'new'      => 'class="current"',
			'answered' => '',
			'spam'     => '',
			'trash'    => '',
		);

		$answered_class = array(
			'inbox'    => '',
			'new'      => '',
			'answered' => 'class="current"',
			'spam'     => '',
			'trash'    => '',
		);

		$spam_class = array(
			'inbox'    => '',
			'new'      => '',
			'answered' => '',
			'spam'     => 'class="current"',
			'trash'    => '',
		);

		$trash_class = array(
			'inbox'    => '',
			'new'      => '',
			'answered' => '',
			'spam'     => '',
			'trash'    => 'class="current"',
		);

		$referer = remove_query_arg( array( 'view', 'paged' ) );

		$views['inbox'] = '<a id="view-all" href="' . $referer . '" ' . $inbox_class[ $view ] . '>' . __( 'Inbox', 'simpleform-contact-form-submissions' ) . '</a> (' . $count_inbox . ')';

		$new_url      = esc_url( add_query_arg( 'view', 'new', $referer ) );
		$views['new'] = '<a id="view-new" href="' . $new_url . '" ' . $new_class[ $view ] . '>' . __( 'Unread', 'simpleform-contact-form-submissions' ) . '</a> (' . $count_new . ')';

		$answered_url      = esc_url( add_query_arg( 'view', 'answered', $referer ) );
		$views['answered'] = '<a id="view-answered" href="' . $answered_url . '" ' . $answered_class[ $view ] . '>' . __( 'Answered', 'simpleform-contact-form-submissions' ) . '</a> (' . $count_answered . ')';

		$spam_url      = esc_url( add_query_arg( 'view', 'spam', $referer ) );
		$views['spam'] = '<a id="view-spam" href="' . $spam_url . '" ' . $spam_class[ $view ] . '>' . __( 'Junk', 'simpleform-contact-form-submissions' ) . '</a> (' . $count_spam . ')';

		$trash_url      = esc_url( add_query_arg( 'view', 'trash', $referer ) );
		$views['trash'] = '<a id="view-trash" href="' . $trash_url . '" ' . $trash_class[ $view ] . '>' . __( 'Trash', 'simpleform-contact-form-submissions' ) . '</a> (' . $count_trash . ')';

		$extender_class->display_notice();

		return $views;
	}

	/**
	 * Set a list of columns.
	 *
	 * @since 1.4.0
	 *
	 * @return string[] Array of columns that can be used in the table.
	 */
	public function get_columns() {

		$utils_class    = new SimpleForm_Entries_List_Utils();
		$extender_class = new SimpleForm_Entries_List_Extender();
		$validation     = new SimpleForm_Submissions_Admin_Validation();
		$view           = strval( $validation->sanitized_key( 'view' ) );

		$columns = $utils_class->get_cb_column( $view );
		$columns = $extender_class->add_column( $columns, 'id' );
		$columns = $extender_class->add_column( $columns, 'subject' );
		$columns = $extender_class->add_column( $columns, 'progress' );
		$columns = $extender_class->add_column( $columns, 'firstname' );
		$columns = $extender_class->add_column( $columns, 'lastname' );
		$columns = $extender_class->add_column( $columns, 'from' );
		$columns = $extender_class->add_column( $columns, 'message' );
		$columns = $extender_class->add_column( $columns, 'email' );
		$columns = $extender_class->add_column( $columns, 'phone' );
		$columns = $extender_class->add_column( $columns, 'ip' );
		$columns = $extender_class->add_column( $columns, 'date' );

		return $columns;
	}

	/**
	 * Render the bulk checkbox column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the checkbox column.
	 */
	protected function column_cb( $item ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$disabled    = $utils_class->get_disabled_attribute( $item );

		return sprintf( '<input type="checkbox" name="id[]" value="%s" ' . $disabled . '/>', absint( $item['id'] ) );
	}

	/**
	 * Render the ID column with actions.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the ID column.
	 */
	protected function column_id( $item ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$actions     = $utils_class->get_row_actions( $item );

		return sprintf( '%s %s', absint( $item['id'] ), '<div id="id-actions" class="">' . $this->row_actions( $actions ) . '</div>' );
	}

	/**
	 * Render the subject column with actions.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the subject column.
	 */
	protected function column_subject( $item ) {

		global $sform_entries;
		$entries_columns = (array) get_column_headers( $sform_entries );
		$hidden_columns  = (array) get_hidden_columns( $sform_entries );
		$actions_class   = ! in_array( 'id', array_keys( $entries_columns ), true ) || in_array( 'id', $hidden_columns, true ) ? '' : 'hidden';
		$utils_class     = new SimpleForm_Entries_List_Utils();
		$actions         = $utils_class->get_row_actions( $item );
		$subject         = $utils_class->get_default_value( $item, 'subject', __( 'No Subject', 'simpleform-contact-form-submissions' ) );

		return sprintf( '%s %s', $subject, '<div id="subject-actions" class="' . $actions_class . '">' . $this->row_actions( $actions ) . '</div>' );
	}

	/**
	 * Render the status column.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the status column.
	 */
	protected function column_progress( $item ) {

		$progress_class = array(
			'new'      => 'dashicons-email-alt red',
			'read'     => 'dashicons-buddicons-pm',
			'answered' => 'dashicons-format-status green',
		);

		$icon = $progress_class[ $item['status'] ];

		$progress_note = array(
			'new'      => __( 'unread', 'simpleform' ),
			'read'     => __( 'read', 'simpleform' ),
			'answered' => __( 'answered', 'simpleform' ),
		);

		$desc = $progress_note[ $item['status'] ];

		$status = '<span class="entry-status dashicons ' . $icon . '"></span><span>' . $desc . '</span>';

		return $status;
	}

	/**
	 * Render the name column.
	 *
	 * Being "name" in the default list of hidden columns, it is kept hidden from the screen options.
	 * Hence, a different column ID is used.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the name column.
	 */
	protected function column_firstname( $item ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$firstname   = $utils_class->get_default_value( $item, 'name', __( 'Anonymous', 'simpleform-contact-form-submissions' ) );

		return $firstname;
	}

	/**
	 * Render the lastname column.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the lastname column.
	 */
	protected function column_lastname( $item ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$lastname    = $utils_class->get_default_value( $item, 'lastname', '-' );

		return $lastname;
	}

	/**
	 * Render the from column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the from column.
	 */
	protected function column_from( $item ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$from        = $utils_class->get_default_value( $item, 'from', __( 'Anonymous', 'simpleform-contact-form-submissions' ) );

		return $from;
	}

	/**
	 * Render the message column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the message column.
	 */
	protected function column_message( $item ) {

		return strval( $item['object'] );
	}

	/**
	 * Render the email column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the email column.
	 */
	protected function column_email( $item ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$email       = $utils_class->get_default_value( $item, 'email', '-' );

		return '<span style="letter-spacing: -0.5px;">' . $email . '</span>';
	}

	/**
	 * Render the phone column.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the phone column.
	 */
	protected function column_phone( $item ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$phone       = $utils_class->get_default_value( $item, 'phone', '-' );

		return $phone;
	}

	/**
	 * Render the IP column.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the ip column.
	 */
	protected function column_ip( $item ) {

		return strval( $item['ip'] );
	}

	/**
	 * Render the date column.
	 * Display the date in localized format according to the date format and timezone of the site.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $item Array of database data.
	 *
	 * @return string The HTML for the item in the date column.
	 */
	protected function column_date( $item ) {

		// Get the site's timezone offset in seconds from UTC.
		$timezone_offset = date_offset_get( current_datetime() );

		/* translators: at: used to indicate the time */
		$prefix     = __( 'at', 'simpleform-contact-form-submissions' );
		$local_date = strtotime( strval( $item['date'] ) ) + $timezone_offset;
		$entry_date = date_i18n( strval( get_option( 'date_format' ) ), $local_date );
		$entry_time = date_i18n( strval( get_option( 'time_format' ) ), $local_date );

		return $entry_date . ' ' . $prefix . ' ' . $entry_time;
	}

	/**
	 * Set a list of sortable columns.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed[] Array of sortable columns.
	 */
	protected function get_sortable_columns() {

		$sortable_columns = array(
			'id'        => array( 'id', true ),
			'subject'   => array( 'subject', true ),
			'firstname' => array( 'name', true ),
			'lastname'  => array( 'lastname', true ),
			'from'      => array( 'lastname', true ),
			'email'     => array( 'email', true ),
			'date'      => array( 'date', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Process the actions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function process_bulk_action() {

		$current_action = $this->current_action();
		$validation     = new SimpleForm_Submissions_Admin_Validation();
		$extender_class = new SimpleForm_Entries_List_Extender();
		$actions_class  = new SimpleForm_Entries_List_Actions();
		$nonce          = strval( $validation->sanitized_key( '_wpnonce' ) );
		$entry          = $validation->sanitized_key( 'id' );
		$view           = strval( $validation->sanitized_key( 'view' ) );
		$entry_form     = absint( $validation->sanitized_key( 'form' ) );
		$moveto         = absint( $validation->sanitized_key( 'moveto' ) );

		$nonce_action = array(
			'spam'         => 'spam_nonce',
			'trash'        => 'trash_nonce',
			'restore'      => 'restore_nonce',
			'delete'       => 'delete_nonce',
			'bulk-spam'    => 'bulk-' . $this->_args['plural'],
			'bulk-trash'   => 'bulk-' . $this->_args['plural'],
			'bulk-restore' => 'bulk-' . $this->_args['plural'],
			'bulk-delete'  => 'bulk-' . $this->_args['plural'],
			'bulk-move'    => 'bulk-' . $this->_args['plural'],
		);

		if ( false === $current_action ) {
			return;
		}

		if ( ! wp_verify_nonce( $nonce, $nonce_action [ $current_action ] ) ) {

			$extender_class->invalid_nonce_redirect();

		} else {

			if ( strpos( $current_action, 'delete' ) !== false ) {

				$actions_class->delete_entry( $entry );

			}

			if ( strpos( $current_action, 'trash' ) !== false ) {

				$actions_class->trash_entry( $entry );

			}

			if ( strpos( $current_action, 'spam' ) !== false ) {

				$actions_class->spam_entry( $entry );

			}

			if ( strpos( $current_action, 'restore' ) !== false ) {

				$actions_class->restore_entry( $entry, $view );

			}

			if ( 'bulk-move' === $current_action ) {

				$actions_class->move_entry( $entry, $entry_form, $moveto );

			}
		}
	}

	/**
	 * Displays the bulk actions dropdown.
	 * Overridden for append the selector to move entries.
	 *
	 * @since 2.0.0
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *
	 * @return void
	 */
	protected function bulk_actions( $which = '' ) {

		$util          = new SimpleForm_Submissions_Util();
		$utils_class   = new SimpleForm_Entries_List_Utils();
		$allowed_tags  = $util->sform_allowed_tags();
		$dropdown_list = 'top' === $which ? $utils_class->dropdown_list() : '';
		$bulk_actions  = array();
		$bulk_actions  = $this->get_bulk_actions();
		$bulk_actions  = apply_filters( "bulk_actions-{$this->screen->id}", $bulk_actions ); // phpcs:ignore
		$two           = '';

		if ( empty( $bulk_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html__( 'Select bulk action' ) . '</label>';
		echo '<select name="action' . esc_attr( $two ) . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . esc_html__( 'Bulk actions' ) . "</option>\n";

		foreach ( $bulk_actions as $key => $value ) {
			if ( is_array( $value ) ) {
				echo "\t" . '<optgroup label="' . esc_attr( $key ) . '">' . "\n";
				foreach ( $value as $name => $title ) {
					$class = ( 'edit' === $name ) ? 'hide-if-no-js' : '';
					echo "\t\t" . '<option value="' . esc_attr( $name ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $title ) . "</option>\n";
				}
				echo "\t</optgroup>\n";
			} else {
				$class = ( 'edit' === $key ) ? 'hide-if-no-js' : '';
				echo "\t" . '<option value="' . esc_attr( $key ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $value ) . "</option>\n";
			}
		}

		echo "</select>\n";

		echo wp_kses( $dropdown_list, $allowed_tags );

		submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );

		echo "\n";
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 * Insert a date filter.
	 *
	 * @since 1.2.0
	 *
	 * @param string $which The location of the extra controls: 'top' or 'bottom'.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ) {

		$utils_class = new SimpleForm_Entries_List_Utils();
		$validation  = new SimpleForm_Submissions_Admin_Validation();
		$date        = strval( $validation->sanitized_key( 'date' ) );
		$total_items = $this->get_pagination_arg( 'total_items' );

		// If items found are less than one and the date filter has not been used.
		if ( $total_items <= 1 && empty( $date ) ) {
			return;
		}

		if ( 'top' === $which ) {

			// Prepare the query to be used to filter entries.
			global $wpdb;
			$where_clause     = $utils_class->get_query_conditions( 'date' );
			$placeholders     = $utils_class->get_query_placeholders( 'date' );
			$query_for_oldest = "SELECT date FROM {$wpdb->prefix}sform_submissions {$where_clause} ORDER BY date LIMIT 1";
			$date_oldest      = $wpdb->get_var( $wpdb->prepare( $query_for_oldest, $placeholders ) ); // phpcs:ignore
			$query_for_last   = "SELECT date FROM {$wpdb->prefix}sform_submissions {$where_clause} ORDER BY date DESC LIMIT 1";
			$last_date        = $wpdb->get_var( $wpdb->prepare( $query_for_last, $placeholders ) ); // phpcs:ignore

			// Show a date filter if entries older than 1 day are found.
			if ( strtotime( $date_oldest ) <= strtotime( '-1 days' ) ) {

				$date_filter  = '<select id="date" name="date" class="">';
				$date_filter .= '<option value="">' . __( 'All Dates', 'simpleform-contact-form-submissions' ) . '</option>';
				$date_filter .= $utils_class->date_option( $last_date, 'last_day', $date );
				$date_filter .= $utils_class->date_option( $last_date, 'last_week', $date );
				$date_filter .= $utils_class->date_option( $last_date, 'last_month', $date );
				$date_filter .= $utils_class->date_option( $last_date, 'current_year', $date );
				$date_filter .= $utils_class->date_option( $last_date, 'last_year', $date );
				$date_filter .= $utils_class->year_option( $date_oldest, $date );
				$date_filter .= '</select>';
				$date_filter .= '<input type="submit" class="button" value="' . __( 'Filter', 'simpleform-contact-form-submissions' ) . '" >';

				$util         = new SimpleForm_Submissions_Util();
				$allowed_tags = $util->sform_allowed_tags();

				echo wp_kses( $date_filter, $allowed_tags );

			}
		}
	}

	/**
	 * Displays the search box.
	 * Overridden to change the displayed text.
	 *
	 * @since 1.1.0
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id ) {

		$validation  = new SimpleForm_Submissions_Admin_Validation();
		$keyword     = $validation->sanitized_key( 's' );
		$total_items = $this->get_pagination_arg( 'total_items' );

		// If items found are less than one and the keyword filter has not been used.
		if ( $total_items <= 1 && empty( $keyword ) ) {
			return;
		}

		echo '<p class="search-box"><label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . ':</label><input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="';
		_admin_search_query();
		echo '" placeholder="' . esc_attr__( 'Enter keyword', 'simpleform-contact-form-submissions' ) . '" />';
		submit_button( $text, 'button', '', false, array( 'id' => 'search-submit' ) );
		echo '</p>';
	}

	/**
	 * Prepare the list of items for displaying.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function prepare_items() {

		$extender_class = new SimpleForm_Entries_List_Extender();
		$validation     = new SimpleForm_Submissions_Admin_Validation();
		$view           = strval( $validation->sanitized_key( 'view' ) );

		// Process the bulk actions.
		$this->process_bulk_action();

		// Build the table columns.
		$this->_column_headers = $this->get_column_info();

		// Pagination.
		$per_page = $this->get_items_per_page( 'entries_per_page', 20 );

		// Run queries to find the entries.
		$items       = $extender_class->items_to_display( $view, 'displaying' );
		$total_items = absint( $extender_class->items_to_display( $view, 'counter' ) );
		$this->items = $items;

		// Set pagination arguments.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Include functions that extends the SimpleForm_Entries_List subclass.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function includes() {

		include_once 'class-simpleform-entries-list-utils.php';
		include_once 'class-simpleform-entries-list-extender.php';
		include_once 'class-simpleform-entries-list-actions.php';
	}
}
