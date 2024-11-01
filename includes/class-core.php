<?php

/**
 * The core plugin class
 *
 * @since      1.0
 */

class SimpleForm_Submissions {

	/**
	 * The loader responsible for maintaining and registering all hooks.
	 *
	 * @since    1.0
	 */
	 
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0
	 */

	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0
	 */

	protected $version;
	
	/**
	 * The error message.
	 *
	 * @since    1.0
	 */
    protected $error = null;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0
	 */

	public function __construct() {
		
		if ( defined( 'SIMPLEFORM_SUBMISSIONS_VERSION' ) ) { $this->version = SIMPLEFORM_SUBMISSIONS_VERSION; } 
		else { $this->version = '1.6.5'; }
		$this->plugin_name = 'simpleform-submissions';
		$this->requirements_matching();
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Define the controls for the plugin compatibility.
	 *
	 * @since    1.0
	 */
	 
	private function requirements_matching() {

		if ( ! $this->is_core_active() ) {
						
			global $pagenow;
			if ($pagenow == 'plugins.php') {
			$addon = '<b>'.SIMPLEFORM_SUBMISSIONS_NAME.'</b>';
			$plugin_file = 'simpleform/simpleform.php';
            if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
	          $core_plugin = '<a href="'.esc_url( 'https://wordpress.org/plugins/simpleform/' ).'" target="_blank" style="text-decoration: none;">'.__('SimpleForm', 'simpleform-contact-form-submissions' ).'</a>';
		      if ( is_multisite() ) {   
			  $url = '<a href="'.network_admin_url('plugin-install.php?tab=search&type=tag&s=simpleform-addon').'" style="text-decoration: none;">'.__('WordPress Plugin Directory', 'simpleform-contact-form-submissions' ).'</a>';
		      } else {
			  $url = '<a href="'.admin_url('plugin-install.php?tab=search&type=tag&s=simpleform-addon').'" style="text-decoration: none;">'.__('WordPress Plugin Directory', 'simpleform-contact-form-submissions' ).'</a>';
		      } 
		       /* translators: %1$s: SimpleForm Contact Form Submissions addon name, %2$s: WordPress.org core plugin link, %3$s: URL to admin page that let browsing the WordPress Plugin Directory */
	          $message =  sprintf( __( 'In order to use the %1$s plugin you need to install and activate %2$s plugin. Search it in the %3$s.', 'simpleform-contact-form-submissions' ), $addon, $core_plugin, $url );
	        }
	        else {
			  $core_plugin = '<b>'.__('SimpleForm', 'simpleform-contact-form-submissions' ).'</b>';
		      $message =  sprintf( __( 'In order to use the %1$s plugin you need to activate the %2$s plugin.', 'simpleform-contact-form-submissions' ), $addon, $core_plugin );
	        }
			
			$this->add_error( $message);
			
			}
		}

		if ( ! $this->is_version_compatible() ) {
           $settings = get_option('sform_settings');
           $admin_notices = ! empty( $settings['admin_notices'] ) ? esc_attr($settings['admin_notices']) : 'false';	
	       if ( $admin_notices == 'false' ) {
		     global $pagenow;
			 if ( ( isset($_GET['page']) && ( 'sform-entries' === $_GET['page'] || 'sform-editor' === $_GET['page'] || 'sform-settings' === $_GET['page'] || 'sform-entrie' === $_GET['page'] ) ) || $pagenow == 'plugins.php' ) {
			$addon = SIMPLEFORM_SUBMISSIONS_NAME;
			$version = '<b>'. SIMPLEFORM_VERSION_REQUIRED .'</b>';
			$this->add_error( sprintf( __( '%s requires SimpleForm version %s or greater installed. Please update SimpleForm to make it work properly!', 'simpleform-contact-form-submissions' ), $addon, $version) );
			}
		   }		   
	       else {
		     global $pagenow;
			 if ( $pagenow == 'plugins.php' ) {
			$addon = SIMPLEFORM_SUBMISSIONS_NAME;
			$version = '<b>'. SIMPLEFORM_VERSION_REQUIRED .'</b>';
			$this->add_error( sprintf( __( '%s requires SimpleForm version %s or greater installed. Please update SimpleForm to make it work properly!', 'simpleform-contact-form-submissions' ), $addon, $version) );
			}
		   }					   			
		}

		if ( is_a( $this->error, 'WP_Error' ) ) {
		    add_action( 'admin_notices', array( $this, 'display_error' ), 10, 0 );
			return false;
		}		

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0
	 */
	 
	private function load_dependencies() {

		// The class responsible for orchestrating the actions and filters of the core plugin.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-loader.php';

		// The class responsible for defining all actions that occur in the admin area.		 
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-admin.php';

		// The class responsible for defining utilities	 
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-utilities.php';

		// The class responsible for defining all actions that occur in the public-facing side of the site.		 
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-public.php';
		
		// The base class for displaying a list of submissions in an ajaxified HTML table.
        if ( ! class_exists( 'WP_List_Table' ) ) {
	    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }
        
        // The customized class that extends the base class
        require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/class-list-table.php';

		$this->loader = new SimpleForm_Submissions_Loader();

	}

	/**
	 * Register all hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0
	 */
	
	private function define_admin_hooks() {
		
	   $plugin_admin = new SimpleForm_Submissions_Admin( $this->get_plugin_name(), $this->get_version() );
	   
	   // Check for core plugin listed in the active plugins
	   if ( $this->is_core_active() ) {
	     // Check for core plugin updated
	     if ( $this->is_version_compatible() ) {
	       // Add new submenu page to Contact admin menu
	       $this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );
	       // Add submissions related fields in settings page
		   $this->loader->add_filter( 'submissions_settings_filter', $plugin_admin, 'submissions_settings_fields', 10, 2 );
		   // Register the stylesheets for the admin area
		   $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		   // Register the scripts for the admin area
		   $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
           // Add screen option tab
		   $this->loader->add_action( 'load_submissions_table_options', $plugin_admin, 'submissions_table_options' );
           // Remove all unnecessary parameters leaving the original URL used before performing an action
	   	   $this->loader->add_action( 'current_screen', $plugin_admin, 'url_cleanup' );	   	   
		   // Add notification bubble to Contacts menu item
		   $this->loader->add_filter( 'sform_notification_bubble', $plugin_admin, 'notification_bubble' );
           // Add submissions related fields values in the settings options array
		   $this->loader->add_filter( 'sform_submissions_settings_filter', $plugin_admin, 'add_array_submissions_settings' );
           // Validate submissions related fields in Settings page
		   $this->loader->add_action( 'sforms_validate_submissions_settings', $plugin_admin, 'validate_submissions_fields' );
           // Display submissions list in dashboard
		   $this->loader->add_action( 'submissions_list', $plugin_admin, 'display_submissions_list', 10, 3 );
           // Save screen options
		   $this->loader->add_filter( 'set-screen-option', $plugin_admin, 'submissions_screen_option', 10, 3 );		
		   // Register a post type for change the pagination in Screen Options tab
		   $this->loader->add_action( 'init', $plugin_admin, 'submission_post_type' );
		   // Add conditional items into the Bulk Actions dropdown for submissions list
		   $this->loader->add_action( 'bulk_actions-toplevel_page_sform-entries', $plugin_admin, 'register_sform_actions' );
		   // Fallback for database table updating if code that runs during plugin activation fails 
		   $this->loader->add_action( 'plugins_loaded', $plugin_admin, 'simpleform_db_version_check' );
		   // Add action links in the plugin meta row	    
           $this->loader->add_filter( 'plugin_action_links', $plugin_admin, 'plugin_links', 10, 2 );
		   // Admin footer text
		   $this->loader->add_action( 'admin_footer_text', $plugin_admin, 'admin_footer', 1, 2 );
	       // Register ajax callback for entry data editing
	       $this->loader->add_action('wp_ajax_edit_entry', $plugin_admin, 'edit_entry');
		   // Add support links in the plugin meta row	    
		   $this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'support_link', 10, 2 );
		   // Show the parent menu active for hidden sub-menu item
		   $this->loader->add_filter( 'parent_file', $plugin_admin, 'contacts_menu_open', 1, 2 );
		   // Display a notice in case of hidden submissions
		   $this->loader->add_filter( 'hidden_submissions', $plugin_admin, 'hidden_submissions_notice', 10, 2 );
				   
         }
         else {
	       // Add plugin upgrade notification
	       $this->loader->add_action( 'in_plugin_update_message-simpleform-contact-form-submissions/simpleform-submissions.php', $plugin_admin, 'upgrade_notification', 10, 2 );
		 }
	   }
	   else {
	    // Add message in the plugin meta row if core plugin is missing    
	    $this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'plugin_meta', 10, 2 );
	    // Add plugin upgrade notification
	    $this->loader->add_action( 'in_plugin_update_message-simpleform-contact-form-submissions/simpleform-submissions.php', $plugin_admin, 'upgrade_notification', 10, 2 );
	   }

	}

	/**
	 * Register all hooks related to the public-facing functionality of the plugin.
	 *
	 * @since    1.0
	 */
	
	private function define_public_hooks() {

	   // Check for core plugin listed in the active plugins
	   if ( $this->is_core_active() ) {

		$plugin_public = new SimpleForm_Submissions_Public( $this->get_plugin_name(), $this->get_version() );

		// Change form data values when form is submitted 
		$this->loader->add_filter( 'sform_storing_values', $plugin_public, 'add_storing_fields_values', 10, 9 );
        // Display confirmation message if notification email has been disabled 
		$this->loader->add_action( 'sform_ajax_message', $plugin_public, 'sform_display_message', 10, 6 );
        // Display confirmation message if notification email has been disabled and ajax is disabled 
 		$this->loader->add_filter( 'sform_post_message', $plugin_public, 'sform_display_post_message', 10, 2 );

	   }

	}

	/**
	 * Check if the core plugin is listed in the active plugins in the WordPress database.
	 *
	 * @since    1.0
	 */

	protected function is_core_active() {

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		if ( is_plugin_active_for_network( 'simpleform/simpleform.php' ) ) {
        return true;
        }
        
        else {		
    		if ( in_array( 'simpleform/simpleform.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		    } 
		    else { return false; }
		}

	}

	/**
	 * Check if the core plugin is is compatible with this addon.
	 *
	 * @since    1.5
	 */

	protected function is_version_compatible() {

		if ( ! $this->is_core_active() ) {
			return true;
		}

		$plugin_data = get_plugin_data( WP_PLUGIN_DIR.'/simpleform/simpleform.php');
        if ( version_compare ( $plugin_data['Version'], SIMPLEFORM_VERSION_REQUIRED, '<') ) {
			return false;
		}

		return true;

	}

	/**
	 * Add a new error to the WP_Error object and create the object if it doesn't exist yet.
	 *
	 * @since    1.0
	 */

	public function add_error( $message ) {
		if ( ! is_object( $this->error ) || ! is_a( $this->error, 'WP_Error' ) ) {
			$this->error = new WP_Error();
		}
		$this->error->add( 'addon_error', $message );
	}

	/**
	 * Display error. Get all the error messages and display them in the admin notices.
	 *
	 * @since    1.0
	 */

	public function display_error() {
		if ( ! is_a( $this->error, 'WP_Error' ) ) {
			return;
		}
		$message = $this->error->get_error_messages(); ?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				if ( count( $message ) > 1 ) {
					echo '<ul>';
					foreach ( $message as $msg ) {
						echo "<li>$msg</li>";
					}
					echo '</li>';
				} else {
					echo $message[0];
				}
				?>
			</p>
		</div>
	<?php
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0
	 */
	 
	public function run() {
		
		$this->loader->run();
		
	}

	/**
	 * Retrieve the name of the plugin.
	 *
	 * @since     1.0
	 */
	 
	public function get_plugin_name() {
		
		return $this->plugin_name;
		
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0
	 */
	 
	public function get_loader() {
		
		return $this->loader;
		
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0
	 */
	 
	public function get_version() {
		
		return $this->version;
		
	}
	
}