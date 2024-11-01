<?php
/**
 * File delegated to the plugin activation.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * The class instantiated during the plugin activation.
 */
class SimpleForm_Submissions_Activator {

	/**
	 * Run default functionality during plugin activation.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param bool $network_wide Whether to enable the plugin for all sites in the network
	 *                           or just the current site. Multisite only. Default false.
	 *
	 * @return void
	 */
	public static function activate( $network_wide ) {

		if ( class_exists( 'SimpleForm' ) ) {

			if ( function_exists( 'is_multisite' ) && is_multisite() ) {

				if ( $network_wide ) {

					global $wpdb;
					$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" ); // phpcs:ignore

					foreach ( $blog_ids as $blog_id ) {
						switch_to_blog( $blog_id );
						self::change_db();
						self::sform_settings();
						self::entries_data_recovery();
						self::entries_recalculation();
						restore_current_blog();
					}
				} else {
					self::change_db();
					self::sform_settings();
					self::entries_data_recovery();
					self::entries_recalculation();
				}
			} else {
				self::change_db();
				self::sform_settings();
				self::entries_data_recovery();
				self::entries_recalculation();
			}
		}
	}

	/**
	 * Modifies the database tables.
	 *
	 * @since 1.0.0
	 * @since 2.1.0 Added url field.
	 *
	 * @return void
	 */
	public static function change_db() {

		$current_db_version = SIMPLEFORM_SUBMISSIONS_DB_VERSION;
		$installed_version  = strval( get_option( 'sform_sub_db_version' ) );

		if ( $installed_version !== $current_db_version ) {

			global $wpdb;
			$submissions_table = $wpdb->prefix . 'sform_submissions';
			$charset_collate   = $wpdb->get_charset_collate();

			$sql_submissions = "CREATE TABLE {$submissions_table} (
				id int(11) NOT NULL AUTO_INCREMENT,
				form int(7) NOT NULL DEFAULT '1',
				moved_from int(7) NOT NULL DEFAULT '0',
				requester_type tinytext NOT NULL,
				requester_id int(15) NOT NULL DEFAULT '0',
				name tinytext NOT NULL,
				lastname tinytext NOT NULL,
				email VARCHAR(200) NOT NULL,
				ip VARCHAR(128) NOT NULL,	
				phone VARCHAR(50) NOT NULL,
				url VARCHAR(255) NOT NULL,
				subject tinytext NOT NULL,
				object text NOT NULL,
				date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				status tinytext NOT NULL,
				previous_status varchar(32) NOT NULL default '',
				trash_date datetime NULL,
				notes text NULL,
				hidden tinyint(1) NOT NULL DEFAULT '0',
				listable tinyint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY  (id)
			) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			dbDelta( $sql_submissions );
			update_option( 'sform_sub_db_version', $current_db_version );

		}
	}

	/**
	 * Save initial settings.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public static function sform_settings() {

		// Detect the parent plugin activation.
		$main_settings = (array) get_option( 'sform_settings', array() );
		global $wpdb;
		$shortcodes_table = $wpdb->prefix . 'sform_shortcodes';

		$new_settings = array(
			'data_storing'      => true,
			'ip_storing'        => true,
			'data_columns'      => array( 'subject', 'firstname', 'message', 'email', 'date' ),
			'counter'           => true,
			'deleting_messages' => false,
		);

		if ( $main_settings ) {

			$settings = array_merge( $main_settings, $new_settings );
			update_option( 'sform_settings', $settings );

		}

		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $shortcodes_table ) ) === $shortcodes_table ) { // phpcs:ignore			

			// Check if other forms have been created.
			$forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes WHERE id != '1'" ); // phpcs:ignore

			foreach ( $forms as $form ) {

				$old_form_settings = (array) get_option( 'sform_' . $form . '_settings', array() );

				if ( $old_form_settings ) {

					$form_settings = array_merge( $old_form_settings, $new_settings );
					update_option( 'sform_' . $form . '_settings', $form_settings );

				}
			}
		}
	}

	/**
	 * Retrieve message data.
	 *
	 * @since 2.1.0
	 *
	 * @param int    $form      The ID of form.
	 * @param string $option_id The option name.
	 * @param string $type      The type of data to be return.
	 *
	 * @return string Data to return.
	 */
	protected static function get_message_data( $form, $option_id, $type ) {

		$option      = get_option( "sform_{$option_id}_{$form}_message" ) !== false ? explode( '#', strval( get_option( "sform_{$option_id}_{$form}_message" ) ) ) : '';
		$option_time = $option && is_numeric( $option[0] ) ? $option[0] : '';
		$option_msg  = $option && isset( $option[1] ) ? $option[1] : '';

		if ( 'time' === $type ) {
			$option_value = $option_time;
		} else {
			$option_value = $option_msg;
		}

		return $option_value;
	}

	/**
	 * Retrieve the field value.
	 *
	 * @since 2.1.0
	 *
	 * @param string $message       The HTML markup for stored message.
	 * @param string $field         The ID of the field.
	 * @param string $field_wrapper The HTML wrapper for the field.
	 *
	 * @return string The field value to return.
	 */
	protected static function get_field_value( $message, $field, $field_wrapper ) {

		$tag = strpos( $message, ':</td><td>' ) !== false ? '</td>' : '<br>';

		$delimiter = array(
			'name'   => strpos( $message, '&nbsp;&nbsp;&lt;&nbsp;' ) !== false ? '&nbsp;&nbsp;&lt;&nbsp;' : $tag,
			'email'  => '&nbsp;&gt;',
			'object' => strpos( $message, ':</td><td>' ) !== false ? '</td>' : '</div>',
			'url'    => $tag,
			'other'  => $tag,
		);

		$separator     = $delimiter[ $field ];
		$needle        = strpos( $message, ':</td><td>' ) !== false ? $field_wrapper . ':</td><td>' : $field_wrapper . ':</b>&nbsp;&nbsp;';
		$split_message = 'email' === $field || 'url' === $field ? explode( $field_wrapper, $message ) : explode( $needle, $message );
		$field_value   = isset( $split_message[1] ) ? explode( $separator, $split_message[1] )[0] : '';

		return $field_value;
	}

	/**
	 * Edit entries by retrieving data from the latest messages received.
	 *
	 * @since 2.0.1
	 * @since 2.1.0 Added data related to url field.
	 *
	 * @return void
	 */
	public static function entries_data_recovery() {

		global $wpdb;
		$forms = $wpdb->get_col( "SELECT DISTINCT form FROM {$wpdb->prefix}sform_submissions" ); // phpcs:ignore

		foreach ( $forms as $form ) {

			$last_entry        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sform_submissions WHERE form = %d ORDER BY date DESC LIMIT 1", $form ) ); // phpcs:ignore
			$before_last_entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sform_submissions WHERE form = %d ORDER BY date DESC LIMIT 1 OFFSET 1", $form ) ); // phpcs:ignore

			$last_time            = self::get_message_data( $form, 'last', 'time' );
			$before_last_time     = self::get_message_data( $form, 'before_last', 'time' );
			$forwarded_last_time  = self::get_message_data( $form, 'forwarded_last', 'time' );
			$forwarded_2last_time = self::get_message_data( $form, 'forwarded_before_last', 'time' );
			$direct_last_time     = self::get_message_data( $form, 'direct_last', 'time' );
			$direct_2last_time    = self::get_message_data( $form, 'direct_before_last', 'time' );
			$moved_last_time      = self::get_message_data( $form, 'moved_last', 'time' );
			$moved_2last_time     = self::get_message_data( $form, 'moved_before_last', 'time' );

			$dates                          = array();
			$dates[ $last_time ]            = self::get_message_data( $form, 'last', 'message' );
			$dates[ $before_last_time ]     = self::get_message_data( $form, 'before_last', 'message' );
			$dates[ $forwarded_last_time ]  = self::get_message_data( $form, 'forwarded_last', 'message' );
			$dates[ $forwarded_2last_time ] = self::get_message_data( $form, 'forwarded_before_last', 'message' );
			$dates[ $direct_last_time ]     = self::get_message_data( $form, 'direct_last', 'message' );
			$dates[ $direct_2last_time ]    = self::get_message_data( $form, 'direct_before_last', 'message' );
			$dates[ $moved_last_time ]      = self::get_message_data( $form, 'moved_last', 'message' );
			$dates[ $moved_2last_time ]     = self::get_message_data( $form, 'moved_before_last', 'message' );

			// Remove empty array elements.
			$dates = array_filter( $dates );

			// Store submission data of last message if data are available.
			if ( $last_entry && '' === $last_entry->object ) {

				if ( array_key_exists( strtotime( $last_entry->date ), $dates ) ) {

					$last_message = $dates[ strtotime( $last_entry->date ) ];

					$name    = self::get_field_value( $last_message, 'name', __( 'From', 'simpleform' ) );
					$email   = self::get_field_value( $last_message, 'email', '&nbsp;&nbsp;&lt;&nbsp;' );
					$phone   = self::get_field_value( $last_message, 'other', __( 'Phone', 'simpleform' ) );
					$url     = self::get_field_value( $last_message, 'url', __( 'Website', 'simpleform' ) . ':</td><td>' );
					$subject = self::get_field_value( $last_message, 'other', __( 'Subject', 'simpleform' ) );
					$object  = self::get_field_value( $last_message, 'object', __( 'Message', 'simpleform' ) );

					$entries_data = array(
						'name'    => wp_strip_all_tags( $name ),
						'email'   => wp_strip_all_tags( $email ),
						'phone'   => $phone,
						'url'     => $url,
						'subject' => $subject,
						'object'  => $object,
						'status'  => 'read',
					);

					$wpdb->update( $wpdb->prefix . 'sform_submissions', $entries_data, array( 'id' => $last_entry->id ) ); // phpcs:ignore

				}
			}

			// Store submission data of before last message if data are available.
			if ( $before_last_entry && '' === $before_last_entry->object ) {

				if ( array_key_exists( strtotime( $before_last_entry->date ), $dates ) ) {

					$before_last_message = $dates[ strtotime( $before_last_entry->date ) ];

					$name    = self::get_field_value( $before_last_message, 'name', __( 'From', 'simpleform' ) );
					$email   = self::get_field_value( $before_last_message, 'email', '&nbsp;&nbsp;&lt;&nbsp;' );
					$phone   = self::get_field_value( $before_last_message, 'other', __( 'Phone', 'simpleform' ) );
					$url     = self::get_field_value( $before_last_message, 'url', __( 'Website', 'simpleform' ) . ':</td><td>' );
					$subject = self::get_field_value( $before_last_message, 'other', __( 'Subject', 'simpleform' ) );
					$object  = self::get_field_value( $before_last_message, 'object', __( 'Message', 'simpleform' ) );

					$entries_data = array(
						'name'    => wp_strip_all_tags( $name ),
						'email'   => wp_strip_all_tags( $email ),
						'phone'   => $phone,
						'url'     => $url,
						'subject' => $subject,
						'object'  => $object,
						'status'  => 'read',
					);

					$wpdb->update( $wpdb->prefix . 'sform_submissions', $entries_data, array( 'id' => $before_last_entry->id ) ); // phpcs:ignore

				}
			}

			$wpdb->query( "UPDATE {$wpdb->prefix}sform_submissions SET listable = '1'" ); // phpcs:ignore

		}

		$data_update   = (array) get_option( 'sform_data_update', array() );
		$data_recovery = array_merge( $data_update, array( 'data_recovery' => true ) );
		update_option( 'sform_data_update', $data_recovery );
	}

	/**
	 * Recalculation of entries assigned to each form.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public static function entries_recalculation() {

		global $wpdb;
		$submissions = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE object != '' AND object != 'not stored'" ); // phpcs:ignore

		if ( $submissions > 0 ) {

			$forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes" ); // phpcs:ignore

			foreach ( $forms as $form ) {

				$entries       = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE form = %d AND object != '' AND object != 'not stored'", $form ) ); // phpcs:ignore
				$moved_entries = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE moved_from = %d AND object != '' AND object != 'not stored'", $form ) ); // phpcs:ignore

				$data = array(
					'entries'       => $entries,
					'moved_entries' => $moved_entries,
				);

				$wpdb->update( $wpdb->prefix . 'sform_shortcodes', $data, array( 'id' => $form ) ); // phpcs:ignore

			}
		}

		$data_update     = (array) get_option( 'sform_data_update', array() );
		$updated_entries = array_merge( $data_update, array( 'entries_recalculation' => true ) );
		update_option( 'sform_data_update', $updated_entries );
	}

	/**
	 * Create a table whenever a new blog is created in a WordPress Multisite installation.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Site $new_site New site object.
	 *
	 * @return void
	 */
	public static function on_create_blog( $new_site ) {

		if ( is_plugin_active_for_network( 'simpleform-submissions/simpleform-submissions.php' ) ) {

			switch_to_blog( (int) $new_site->blog_id );
			self::change_db();
			self::sform_settings();
			self::entries_data_recovery();
			self::entries_recalculation();
			restore_current_blog();

		}
	}
}
