<?php
/**
 * Main file for the admin functionality of the plugin.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core class used to implement the admin-specific functionality of the plugin.
 */
class SimpleForm_Submissions_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @var    string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 * @var    string The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Load the screen options for pagination.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function entries_screen_options() {

		global $sform_entries;
		add_action( "load-$sform_entries", array( $this, 'register_screen_options' ) );
	}

	/**
	 * Register the screen options.
	 *
	 * @since   1.0.0
	 * @version 2.1.0
	 *
	 * @return void
	 */
	public function register_screen_options() {

		$util         = new SimpleForm_Submissions_Util();
		$form         = isset( $_GET['form'] ) ? absint( $_GET['form'] ) : 1; // phpcs:ignore
		$data_storing = $util->get_sform_option( $form, 'settings', 'data_storing', true );
		$forms        = $util->sform_ids();

		if ( $data_storing && in_array( $form, array_map( 'intval', $forms ), true ) ) {

			global $sform_entries;
			global $entries_table;
			$screen = get_current_screen();

			if ( ! is_object( $screen ) || $screen->id !== $sform_entries ) {
				return;
			}

			$args = array(
				'label'   => __( 'Number of entries per page', 'simpleform-contact-form-submissions' ),
				'default' => 20,
				'option'  => 'entries_per_page',
			);

			add_screen_option( 'per_page', $args );

			$entries_table = new SimpleForm_Entries_List();

		}
	}

	/**
	 * Save screen options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $screen_option The option value to save.
	 * @param string $option        The option name.
	 * @param int    $value         The option value to return.
	 *
	 * @return int The screen option value.
	 */
	public function save_screen_options( $screen_option, $option, $value ) {

		if ( 'entries_per_page' === $option ) {
			return $value;
		}

		return absint( $screen_option );
	}

	/**
	 * Add new submenu page to Contacts admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_menu() {

		global $sform_entry;
		$sform_entry = add_submenu_page( '', __( 'Entry Data', 'simpleform-contact-form-submissions' ), __( 'Entry Data', 'simpleform-contact-form-submissions' ), 'manage_options', 'sform-entry', array( $this, 'entry_page' ) );
	}

	/**
	 * Render the entry page for this plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function entry_page() {

		include_once 'partials/entry.php';
	}

	/**
	 * Show the parent menu active for hidden sub-menus
	 *
	 * @since 1.6.4
	 *
	 * @param string $parent_file The parent file.
	 *
	 * @return string The parent file that must be used.
	 */
	public function contacts_menu_open( $parent_file ) {

		global $plugin_page;

		if ( 'sform-entry' === $plugin_page ) {
			$plugin_page = 'sform-entries'; // phpcs:ignore
		}

		return $parent_file;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The hook that was called.
	 *
	 * @return void
	 */
	public function enqueue_styles( $hook ) {

		wp_register_style( $this->plugin_name . '-admin', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );

		global $sform_entries;
		global $sform_entry;

		$admin_pages = array(
			$sform_entries,
			$sform_entry,
		);

		if ( in_array( $hook, $admin_pages, true ) ) {

			wp_enqueue_style( $this->plugin_name . '-admin' );

		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook The hook that was called.
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {

		global $sform_entries;
		global $sform_settings;
		global $sform_entry;

		$admin_pages = array(
			$sform_entries,
			$sform_entry,
			$sform_settings,
		);

		if ( in_array( $hook, $admin_pages, true ) ) {

			wp_enqueue_script( 'sform-submissions-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), $this->version, false );

			$id_notice = '<div class="notice notice-error"><p>' . __( 'ID column cannot be kept hidden. Click the Screen Options tab in the upper right of the screen and update the current selection of displayed columns.', 'simpleform-contact-form-submissions' ) . '</p></div>';

			$subject_notice = '<div class="notice notice-error"><p>' . __( 'Subject column cannot be kept hidden. Click the Screen Options tab in the upper right of the screen and update the current selection of displayed columns.', 'simpleform-contact-form-submissions' ) . '</p></div>';

			$combo_notice = '<div class="notice notice-error"><p>' . __( 'ID and Subject columns cannot both be kept hidden. Click the Screen Options tab in the upper right of the screen and update the current selection of displayed columns.', 'simpleform-contact-form-submissions' ) . '</p></div>';

			wp_localize_script(
				'sform-submissions-script',
				'sform_submissions_object',
				array(
					'ajaxurl'        => admin_url( 'admin-ajax.php' ),
					'enable'         => __( 'Check if you want to add the entries list in the dashboard and enable the form data storing', 'simpleform-contact-form-submissions' ),
					'disable'        => __( 'Uncheck if you want to remove the entries list from the dashboard and disable the form data storing', 'simpleform-contact-form-submissions' ),
					'saving'         => __( 'Saving data in progress', 'simpleform-contact-form-submissions' ),
					'id_notice'      => $id_notice,
					'subject_notice' => $subject_notice,
					'combo_notice'   => $combo_notice,
				)
			);

		}
	}

	/**
	 * Add the new fields in the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $extra_option The value to filter.
	 * @param int    $form         The ID of the form.
	 *
	 * @return string The fields added by addon.
	 */
	public function settings_fields( $extra_option, $form ) {

		$util          = new SimpleForm_Submissions_Util();
		$data_storing  = $util->get_sform_option( $form, 'settings', 'data_storing', true );
		$storing_on    = __( 'Uncheck if you want to remove the entries list from the dashboard and disable the form data storing', 'simpleform-contact-form-submissions' );
		$storing_off   = __( 'Check if you want to add the entries list in the dashboard and enable the form data storing', 'simpleform-contact-form-submissions' );
		$storing_notes = ! $data_storing ? $storing_off : $storing_on;
		$ip_storing    = $util->get_sform_option( 1, 'settings', 'ip_storing', true );
		$columns       = (array) $util->get_sform_option( 1, 'settings', 'data_columns', array( 'subject', 'firstname', 'message', 'email', 'date' ) );
		$columns       = array_map( 'strval', $columns );
		$counter       = $util->get_sform_option( 1, 'settings', 'counter', true );
		$mailto        = $util->get_sform_option( $form, 'settings', 'mailto', false );
		$deletion      = $util->get_sform_option( 1, 'settings', 'deleting_messages', false );

		global $wpdb;
		$count_forms = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sform_shortcodes WHERE id != '1'" ); // phpcs:ignore

		$data_storage_class = ! $data_storing ? 'unseen' : '';
		$disabled_class     = 1 !== $form ? 'disabled' : '';
		$disabled_option    = 1 !== $form ? ' disabled="disabled"' : '';
		$id_checked         = $this->get_checked_attribute( 'id', $columns );
		$subject_checked    = $this->get_checked_attribute( 'subject', $columns );
		$firstname_checked  = $this->get_checked_attribute( 'firstname', $columns );
		$name_checkbox      = $this->get_field_markup( 'name', $disabled_class, $firstname_checked, $disabled_option );
		$lastname_checked   = $this->get_checked_attribute( 'lastname', $columns );
		$lastname_checkbox  = $this->get_field_markup( 'lastname', $disabled_class, $lastname_checked, $disabled_option );
		$fullname_checked   = $this->get_checked_attribute( 'from', $columns );
		$fullname_checkbox  = $this->get_field_markup( 'from', $disabled_class, $fullname_checked, $disabled_option );
		$message_checked    = $this->get_checked_attribute( 'message', $columns );
		$email_checked      = $this->get_checked_attribute( 'email', $columns );
		$mail_checkbox      = $this->get_field_markup( 'email', $disabled_class, $email_checked, $disabled_option );
		$phone_checked      = $this->get_checked_attribute( 'phone', $columns );
		$phone_checkbox     = $this->get_field_markup( 'phone', $disabled_class, $phone_checked, $disabled_option );
		$ip_checked         = $this->get_checked_attribute( 'ip', $columns );
		$date_checked       = $this->get_checked_attribute( 'date', $columns );
		$usage_note         = $count_forms > 0 ? '<span class="head-bracket">( ' . __( 'This applies to all forms', 'simpleform-contact-form-submissions' ) . ' )</span>' : '';

		$extra_option = '<h2 id="h2-storage" class="options-heading"><span class="heading" data-section="storage">' . __( 'Data Storage', 'simpleform-contact-form-submissions' ) . '<span class="toggle dashicons dashicons-arrow-up-alt2 storage"></span></span></h2><div class="section storage"><table class="form-table storage"><tbody>';

		$extra_option .= '<tr><th class="option"><span>' . __( 'Form Data Storage', 'simpleform-contact-form-submissions' ) . '</span></th><td class="checkbox-switch notes"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="storing" id="storing" class="sform-switch" value="' . $data_storing . '" ' . checked( $data_storing, true, false ) . '><span></span></label><label for="storing" class="switch-label">' . __( 'Enable the form data storing in the database (data will be included only within the notification email if unchecked)', 'simpleform-contact-form-submissions' ) . '</label></div><p id="storing-description" class="description">' . $storing_notes . '</p></td></tr>';

		$extra_option .= '<tr class="trstoring ' . $data_storage_class . '"><th class="option"><span>' . __( 'IP Address Storage', 'simpleform-contact-form-submissions' ) . '</span></th><td class="checkbox-switch"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="user_ip" id="user_ip" class="sform-switch" value="' . $ip_storing . '" ' . checked( $ip_storing, true, false ) . $disabled_option . '><span></span></label><label for="user_ip" class="switch-label ' . $disabled_class . '">' . __( 'Enable IP address storing in the database', 'simpleform-contact-form-submissions' ) . $usage_note . '</label></div></td></tr>';

		$extra_option .= '<tr class="trstoring ' . $data_storage_class . '"><th class="option"><span>' . __( 'Visible Data Columns', 'simpleform-contact-form-submissions' ) . '</span></th><td class="multicheckbox notes"><label for="id" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="id" class="sform multiselect" value="id" ' . $id_checked . $disabled_option . '>' . __( 'ID', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label><label for="subject" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="subject" class="sform multiselect" value="subject" ' . $subject_checked . $disabled_option . '>' . __( 'Subject', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label>' . $name_checkbox . $lastname_checkbox . $fullname_checkbox . '<label for="object" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="object" class="sform multiselect" value="message" ' . $message_checked . $disabled_option . '>' . __( 'Message', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label>' . $mail_checkbox . $phone_checkbox . '<label for="ip" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="ip" class="sform multiselect" value="ip" ' . $ip_checked . $disabled_option . '>' . __( 'IP', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label><label for="date" class="multiselect ' . $disabled_class . ' last"><input type="checkbox" name="columns[]" id="date" class="sform multiselect" value="date" ' . $date_checked . $disabled_option . '>' . __( 'Date', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label><p id="columns-description" class="description">' . __( 'Set the columns that must be displayed in the entries list table', 'simpleform-contact-form-submissions' ) . $usage_note . '</p></td></tr>';

		$extra_option .= '<tr class="trstoring ' . $data_storage_class . '"><th class="option"><span>' . __( 'Unread Count', 'simpleform-contact-form-submissions' ) . '</span></th><td class="checkbox-switch"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="counter" id="counter" class="sform-switch" value="' . $counter . '" ' . checked( $counter, true, false ) . $disabled_option . '><span></span></label><label for="counter" class="switch-label ' . $disabled_class . '">' . __( 'Add a notification bubble to admin menu for unread messages', 'simpleform-contact-form-submissions' ) . $usage_note . '</label></div></td></tr>';

		$extra_option .= '<tr class="trstoring ' . $data_storage_class . '"><th class="option"><span>' . __( 'Mailto Link', 'simpleform-contact-form-submissions' ) . '</span></th><td class="checkbox-switch"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="mailto" id="mailto" class="sform-switch" value="' . $mailto . '" ' . checked( $mailto, true, false ) . '><span></span></label><label for="mailto" class="switch-label">' . __( 'Show a mailto button to activate the default mail program for sending a reply', 'simpleform-contact-form-submissions' ) . '</label></div></td></tr>';

		$extra_option .= '<tr><th class="option"><span>' . __( 'Deleting Messages', 'simpleform-contact-form-submissions' ) . '</span></th><td class="checkbox-switch last"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="deletion" id="deletion" class="sform-switch" value="' . $deletion . '" ' . checked( $deletion, true, false ) . $disabled_option . '><span></span></label><label for="deletion" class="switch-label ' . $disabled_class . '">' . __( 'Delete messages from the database when uninstalling the plugin', 'simpleform-contact-form-submissions' ) . $usage_note . '</label></div></td></tr>';

		$extra_option .= '</tbody></table></div>';

		return $extra_option;
	}

	/**
	 * Get the checked attribute for a multiple checkbox field.
	 *
	 * @since 1.2.0
	 *
	 * @param string   $input The ID of the input to search for.
	 * @param string[] $value Array of values of the multiple checkbox field.
	 *
	 * @return string The attribute value.
	 */
	protected function get_checked_attribute( $input, $value ) {

		if ( in_array( $input, $value, true ) ) {
			$attribute = 'checked="checked"';
		} else {
			$attribute = '';
		}

		return $attribute;
	}

	/**
	 * Get the HTML markup for the field.
	 *
	 * @since 1.2.0
	 *
	 * @param string $field             The ID of the field.
	 * @param string $disabled_class    The disabled class.
	 * @param string $checked_attribute The checked attribute for the field.
	 * @param string $disabled_option   The disabled attribute.
	 *
	 * @return string The HTML markup for the field.
	 */
	protected function get_field_markup( $field, $disabled_class, $checked_attribute, $disabled_option ) {

		$util           = new SimpleForm_Submissions_Util();
		$name_field     = $util->get_sform_option( 1, 'attributes', 'name_field', 'visible' );
		$lastname_field = $util->get_sform_option( 1, 'attributes', 'lastname_field', 'hidden' );
		$email_field    = $util->get_sform_option( 1, 'attributes', 'email_field', 'visible' );
		$phone_field    = $util->get_sform_option( 1, 'attributes', 'phone_field', 'hidden' );

		$field_markup = array(
			'name'     => 'hidden' !== $name_field ? '<label for="name" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="name" class="sform multiselect" value="firstname" ' . $checked_attribute . $disabled_option . '>' . __( 'Name', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label>' : '',
			'lastname' => 'hidden' !== $lastname_field ? '<label for="lastname" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="lastname" class="sform multiselect" value="lastname" ' . $checked_attribute . $disabled_option . '>' . __( 'Last Name', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label>' : '',
			'from'     => 'hidden' !== $name_field && 'hidden' !== $lastname_field ? '<label for="from" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="from" class="sform multiselect" value="from" ' . $checked_attribute . $disabled_option . '>' . __( 'Full Name', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label>' : '',
			'email'    => 'hidden' !== $email_field ? '<label for="mail" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="mail" class="sform multiselect" value="email" ' . $checked_attribute . $disabled_option . '>' . __( 'Email', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label>' : '',
			'phone'    => 'hidden' !== $phone_field ? '<label for="phone" class="multiselect ' . $disabled_class . '"><input type="checkbox" name="columns[]" id="phone" class="sform multiselect" value="phone" ' . $checked_attribute . $disabled_option . '>' . __( 'Phone', 'simpleform-contact-form-submissions' ) . '<span class="checkmark"></span></label>' : '',
		);

		$markup = $field_markup[ $field ];

		return $markup;
	}

	/**
	 * Validate the new fields in the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $error The value to filter.
	 *
	 * @return string The filtered value after the checks.
	 */
	public function settings_validation( $error ) {

		$validation   = new SimpleForm_Submissions_Admin_Validation();
		$data_storing = $validation->sanitized_input( 'storing', 'tickbox' );
		$notification = $validation->sanitized_input( 'notification', 'tickbox' );
		$columns      = (array) $validation->sanitized_input( 'columns', 'checkboxes' );

		if ( $data_storing ) {

			if ( ! in_array( 'id', $columns, true ) && ! in_array( 'subject', $columns, true ) ) {

				$error = __( 'You need to display at least one column between ID and Subject', 'simpleform-contact-form-submissions' );

			}

			if ( in_array( 'firstname', $columns, true ) && in_array( 'from', $columns, true ) ) {

				$error = __( 'Name and Full Name columns cannot both be selected', 'simpleform-contact-form-submissions' );

			}

			if ( in_array( 'lastname', $columns, true ) && in_array( 'from', $columns, true ) ) {

				$error = __( 'Last Name and Full Name columns cannot both be selected', 'simpleform-contact-form-submissions' );

			}
		} else {

			$error = ! $notification ? __( 'Data Storing option and Alert Email option cannot both be disabled. Please keep at least one option enabled!', 'simpleform-contact-form-submissions' ) : '';

		}

		return $error;
	}

	/**
	 * Add the new settings values in the settings options array.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed[] The fields values added by addon.
	 */
	public function settings_storing() {

		$validation   = new SimpleForm_Submissions_Admin_Validation();
		$form         = $validation->sanitized_input( 'form_id', 'form' );
		$data_storing = $validation->sanitized_input( 'storing', 'tickbox' );
		$mailto       = $validation->sanitized_input( 'mailto', 'tickbox' );
		$listable     = $data_storing ? true : false;
		$storing      = $data_storing ? true : false;
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET listable = %d WHERE form = %d", $listable, $form) );// phpcs:ignore

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_shortcodes SET storing = %d WHERE id = %d", $storing, $form ) ); // phpcs:ignore

		$forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes WHERE id != '1'" ); // phpcs:ignore

		if ( 1 === $form ) {

			$ip_storing = $validation->sanitized_input( 'user_ip', 'tickbox' );
			$columns    = $validation->sanitized_input( 'columns', 'checkboxes' );
			$counter    = $validation->sanitized_input( 'counter', 'tickbox' );
			$deletion   = $validation->sanitized_input( 'deletion', 'tickbox' );

			if ( ! $data_storing ) {

				$ip_storing = false;
				$columns    = array( 'subject', 'firstname', 'message', 'email', 'date' );

			}

			$new_items = array(
				'data_storing'      => $data_storing,
				'ip_storing'        => $ip_storing,
				'data_columns'      => $columns,
				'mailto'            => $mailto,
				'counter'           => $counter,
				'deleting_messages' => $deletion,
			);

			foreach ( $forms as $form ) {

				$form_settings = (array) get_option( 'sform_' . $form . '_settings', array() );

				if ( false !== $form_settings ) {

					$form_settings['ip_storing']        = $ip_storing;
					$form_settings['data_columns']      = $columns;
					$form_settings['counter']           = $counter;
					$form_settings['deleting_messages'] = $deletion;
					update_option( 'sform_' . $form . '_settings', $form_settings );

				}
			}
		} else {

			$main_settings = (array) get_option( 'sform_settings', array() );

			$new_items = array(
				'data_storing'      => $data_storing,
				'ip_storing'        => $main_settings['ip_storing'],
				'data_columns'      => $main_settings['data_columns'],
				'mailto'            => $mailto,
				'counter'           => $main_settings['counter'],
				'deleting_messages' => $main_settings['deleting_messages'],
			);

		}

		return $new_items;
	}
}
