<?php
/**
 * File delegated to manage the plugin.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the class that deals with the plugin management.
 */
class SimpleForm_Submissions_Management {

	/**
	 * Add an update notice for SimpleForm.
	 *
	 * @since 1.5.0
	 *
	 * @param mixed[] $plugin_data An array of plugin metadata.
	 * @param object  $new_data    An object of metadata about the available plugin update.
	 *
	 * @return void
	 */
	public function upgrade_notification( $plugin_data, $new_data ) {

		if ( isset( $plugin_data['update'] ) && $plugin_data['update'] && isset( $new_data->upgrade_notice ) && file_exists( WP_PLUGIN_DIR . '/simpleform/simpleform.php' ) ) {

			$simpleform_data = get_plugin_data( WP_PLUGIN_DIR . '/simpleform/simpleform.php' );
			$version         = '<b>' . SIMPLEFORM_VERSION_REQUIRED . '</b>';
			/* translators: %s: The required SimpleForm version. */
			$message = sprintf( __( 'The new version requires SimpleForm version %s or greater installed. Please also update SimpleForm to make it work properly!', 'simpleform-contact-form-submissions' ), $version );

			// Check if current version of SimpleForm plugin is obsolete.
			if ( version_compare( $simpleform_data['Version'], SIMPLEFORM_VERSION_REQUIRED, '<' ) ) {
				echo '<br><span style="margin-left:26px"><b>' . esc_html__( 'Upgrade Notice', 'simpleform-contact-form-submissions' ) . ':</b> ' . wp_kses_post( $message ) . '</span>';
			}
		}
	}

	/**
	 * Add message in the plugin meta row if core plugin is missing
	 *
	 * @since 1.4.4
	 *
	 * @param string[] $plugin_meta Array of the plugin's metadata.
	 * @param string   $file        Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[] Array of the plugin's metadata.
	 */
	public function plugin_meta( $plugin_meta, $file ) {

		if ( ! file_exists( WP_PLUGIN_DIR . '/simpleform/simpleform.php' ) && strpos( $file, 'simpleform-contact-form-submissions/simpleform-submissions.php' ) !== false ) {

			$plugin_url    = __( 'https://wordpress.org/plugins/simpleform/' );
			$message       = '<a href="' . esc_url( $plugin_url ) . '" target="_blank" style="color: orangered !important;">' . __( 'Install the SimpleForm plugin to allow this addon to work', 'simpleform-contact-form-submissions' ) . '</a>';
			$plugin_meta[] = $message;

		}

		return $plugin_meta;
	}

	/**
	 * Check data stored in the database.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function stored_data_checking() {

		$current_version   = SIMPLEFORM_SUBMISSIONS_DB_VERSION;
		$installed_version = get_option( 'sform_sub_db_version' );

		require_once plugin_dir_path( dirname( __DIR__ ) ) . 'includes/class-simpleform-submissions-activator.php';

		if ( $installed_version !== $current_version ) {

			SimpleForm_Submissions_Activator::change_db();

		}

		$settings = (array) get_option( 'sform_settings', array() );

		if ( ! isset( $settings['data_storing'] ) || ! is_bool( $settings['data_storing'] ) || ! is_array( $settings['data_columns'] ) ) {

			SimpleForm_Submissions_Activator::sform_settings();

		}

		$data_update = (array) get_option( 'sform_data_update', array() );

		if ( ! isset( $data_update['data_recovery'] ) ) {

			SimpleForm_Submissions_Activator::entries_data_recovery();

		}

		if ( ! isset( $data_update['entries_recalculation'] ) ) {

			SimpleForm_Submissions_Activator::entries_recalculation();

		}
	}

	/**
	 * Add action links in the plugin meta row
	 *
	 * @since 1.4.0
	 *
	 * @param string[] $plugin_actions Array of plugin action links.
	 * @param string   $plugin_file    Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[] Array of plugin action links.
	 */
	public function plugin_links( $plugin_actions, $plugin_file ) {

		$new_actions = array();

		if ( 'simpleform-contact-form-submissions/simpleform-submissions.php' === $plugin_file ) {

			$new_actions['sform_settings'] = '<a href="' . menu_page_url( 'sform-entries', false ) . '">' . __( 'Dashboard', 'simpleform' ) . '</a>';

		}

		return array_merge( $new_actions, $plugin_actions );
	}

	/**
	 * Add support links in the plugin meta row
	 *
	 * @since 1.6.2
	 *
	 * @param string[] $plugin_meta Array of the plugin's metadata.
	 * @param string   $file        Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[] Array of the plugin's metadata.
	 */
	public function support_link( $plugin_meta, $file ) {

		if ( strpos( $file, 'simpleform-contact-form-submissions/simpleform-submissions.php' ) !== false ) {

			$plugin_meta[] = '<a href="https://wordpress.org/support/plugin/simpleform-contact-form-submissions/" target="_blank">' . __( 'Support', 'simpleform-contact-form-submissions' ) . '</a>';

		}

		return $plugin_meta;
	}

	/**
	 * When user is on a SimpleForm related admin page, display footer text.
	 *
	 * @since 1.4.1
	 *
	 * @param string $text The current text that is displayed.
	 *
	 * @return string The text to be displayed.
	 */
	public function admin_footer( $text ) {

		$util          = new SimpleForm_Submissions_Util();
		$admin_notices = $util->get_sform_option( 1, 'settings', 'admin_notices', false );

		if ( ! $admin_notices ) {

			global $current_screen;
			global $wpdb;

			$count_all = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions" ); // phpcs:ignore

			if ( ! empty( $current_screen->id ) && strpos( $current_screen->id, 'sform' ) !== false && $count_all > 0 ) {

				$plugin = '<strong>SimpleForm</strong>';
				$url1   = '<a href="https://wordpress.org/support/plugin/simpleform/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a>';
				$url2   = '<a href="https://wordpress.org/support/plugin/simpleform/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">WordPress.org</a>';
				$url3   = '<a href="https://wordpress.org/support/plugin/simpleform/" target="_blank" rel="noopener noreferrer">Forum</a>';
				/* translators: $1$s: SimpleForm plugin name; $2$s: WordPress.org review link; $3$s: WordPress.org review link; $4$s: WordPress.org support forum link. */
				$text = '<span id="footer-thankyou">' . sprintf( __( 'Please support the further development of %1$s by leaving us a %2$s rating on %3$s. Found an issue or have a feature suggestion, please tell on %4$s. Thanks in advance!', 'simpleform-contact-form-submissions' ), $plugin, $url1, $url2, $url3 ) . '</span>';

			}
		}

		return $text;
	}

	/**
	 * Clean up the admin URL after performing an action or filtering the results.
	 *
	 * @since 1.6.0
	 *
	 * @param object $current_screen Current WP_Screen object.
	 *
	 * @return void
	 */
	public function url_cleanup( $current_screen ) {

		global $sform_entries;

		if ( isset( $current_screen->id ) && $sform_entries !== $current_screen->id ) {
			return;
		}

		$referer_url = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=sform-entries' );

		// Get the value of query variables.
		$entries_list    = new SimpleForm_Entries_List();
		$validation      = new SimpleForm_Submissions_Admin_Validation();
		$view            = strval( $validation->sanitized_key( 'view' ) );
		$date            = strval( $validation->sanitized_key( 'date' ) );
		$keyword         = strval( $validation->sanitized_key( 's' ) );
		$paged           = $entries_list->get_pagenum();
		$doaction        = $entries_list->current_action();
		$displayed_items = intval( wp_cache_get( 'displayed_items' ) );
					update_option( 'sform_displayed_items_BEFORE', $displayed_items );

		// Clear URL after performing a bulk or row action.
		if ( $doaction ) {

			// Prepares the list of entries for displaying.
			$entries_list->prepare_items();

			$updated_items   = get_transient( 'updated_items' );
			$updated_items   = false !== $updated_items ? absint( $updated_items ) : 0;
			$displayed_items = absint( wp_cache_get( 'displayed_items' ) );
			$page            = 0 === ( $displayed_items - $updated_items ) ? $paged - 1 : $paged;

			$missed_args = array( 'date', 's', 'paged', 'action', 'action2', 'id', 'moveto', '_wpnonce', 'simpleform_nonce', '_wp_http_referer', 'items' );

			$url = remove_query_arg( $missed_args, $referer_url );
			// Add date and keyword args only if used.
			$url = $this->cleanup_url_argument( 'add', 'date', $date, '', $url );
			$url = $this->cleanup_url_argument( 'add', 's', $keyword, '', $url );
			// Update paged arg if items are not found in used pagination.
			$url = $this->cleanup_url_argument( 'add', 'paged', $page, 1, $url );

			wp_safe_redirect( $url );
			exit();

		} elseif ( isset( $_GET['simpleform_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['simpleform_nonce'] ), 'simpleform_querying_data' ) ) {

			// Clear URL after filtering the entries by date or keyword.
			$missed_args = array( 'date', 's', 'paged', 'action', 'action2', 'moveto', '_wpnonce', 'simpleform_nonce', '_wp_http_referer', 'items' );

			$url = remove_query_arg( $missed_args, $referer_url );
			$url = $this->cleanup_url_argument( 'add', 'view', $view, '', $url );
			$url = $this->cleanup_url_argument( 'add', 'date', $date, '', $url );
			$url = $this->cleanup_url_argument( 'add', 's', $keyword, '', $url );

			wp_safe_redirect( $url );
			exit;

		}
	}

	/**
	 * Clean up the URL argument.
	 *
	 * @since 2.1.0
	 *
	 * @param string     $type     The  type of cleaning to be performed.
	 * @param string     $arg      The URL argument key.
	 * @param string|int $value    The URL argument value.
	 * @param string|int $compared The URL argument value to be compared.
	 * @param string     $url      The referer URL.
	 *
	 * @return string The cleaned URL.
	 */
	public function cleanup_url_argument( $type, $arg, $value, $compared, $url ) {

		if ( 'remove' === $type ) {

			if ( empty( $value ) && '' === $compared ) {
				$url = remove_query_arg( $arg, $url );
			} elseif ( strval( $value ) === strval( $compared ) ) {
				$url = remove_query_arg( $arg, $url );
			}
		} elseif ( 'remove' !== $type ) {

			if ( is_int( $compared ) ) {

				if ( intval( $value ) > $compared ) {
					$url = add_query_arg( $arg, $value, $url );
				}
			} elseif ( strval( $compared ) !== strval( $value ) ) {
				$url = add_query_arg( $arg, $value, $url );
			}
		}

		return $url;
	}
}
