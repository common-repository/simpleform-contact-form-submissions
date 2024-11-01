<?php

/**
 * Defines the admin-specific functionality of the plugin.
 *
 * @since      1.0
 */
	 
class SimpleForm_Submissions_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0
	 */
	
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0
	 */
	
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 */
	
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

    /**
     * Add new submenu page to Contacts admin menu.
     *
     * @since    1.0
     */  
     
    public function admin_menu() {
	    
	    global $sform_entrie_page;
	    $entrie = __('Entry Data','simpleform-contact-form-submissions');
        $sform_entrie_page = add_submenu_page(null, $entrie, $entrie, 'manage_options', 'sform-entrie', array ($this, 'entry_page') );

   }
  
    /**
     * Render the entry page for this plugin.
     *
     * @since    1.0
     */
     
    public function entry_page() {
      
      include_once( 'partials/entry.php' );
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0
	 */
    
    public function enqueue_styles($hook) {

	 wp_register_style('sform-submissions-style', plugins_url( 'css/admin.css', __FILE__ ),[], filemtime( plugin_dir_path( __FILE__ ) . 'css/admin.css' ) );
	 
     global $sform_entries;
	 global $sform_entrie_page;
	 if( $hook != $sform_entries && $hook != $sform_entrie_page )
	 return;
	 
	 wp_enqueue_style('sform-submissions-style'); 
	      
	}
	
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0
	 */
	
	public function enqueue_scripts($hook){
	    		
     global $sform_entries;
     global $sform_settings;
     global $sform_entrie_page;

	 if( $hook != $sform_entries && $hook != $sform_settings && $hook != $sform_entrie_page )
	 return;

 	 wp_enqueue_script('sform-submissions-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'js/admin.js' ) );
     
     wp_localize_script( 'sform-submissions-script', 'sform_submissions_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'enable' => __( 'Check if you want to add the entries list in the dashboard and enable the form data storing', 'simpleform-contact-form-submissions' ), 'disable' => __( 'Uncheck if you want to remove the entries list from the dashboard and disable the form data storing', 'simpleform-contact-form-submissions' ), 'saving' => __( 'Saving data in progress', 'simpleform-contact-form-submissions' ) )); 
	      
	}
	
	/**
	 * Add submissions related fields in settings page.
	 *
	 * @since    1.0
	 */
	
    public function submissions_settings_fields( $id, $extra_option ) {
	    
	 $main_settings = get_option('sform_settings'); 

	 if ( $id == '1' ) { 
     $settings = $main_settings; 
     $attributes = get_option('sform_attributes');
     } else { 
     $settings_option = get_option('sform_'. $id .'_settings');
     $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
     $attributes_option = get_option('sform_'.$id.'_attributes');
     $attributes = $attributes_option != false ? $attributes_option : get_option('sform_attributes');     
	 }
	 $color = ! empty( $settings['admin_color'] ) ? esc_attr($settings['admin_color']) : 'default';
     $data_storing = ! empty( $settings['data_storing'] ) ? esc_attr($settings['data_storing']) : 'true';
     $storage_notes_on = __('Uncheck if you want to remove the entries list from the dashboard and disable the form data storing', 'simpleform-contact-form-submissions' );
     $storage_notes_off = __('Check if you want to add the entries list in the dashboard and enable the form data storing', 'simpleform-contact-form-submissions' );
     $storage_notes = $data_storing !='true' ? $storage_notes_off : $storage_notes_on;
     $ip_storing = ! empty( $settings['ip_storing'] ) ? esc_attr($settings['ip_storing']) : 'true';
     $columns = ! empty( $settings['data_columns'] ) ? esc_attr($settings['data_columns']) : 'subject,firstname,message,mail,date';	
     $counter = ! empty( $main_settings['counter'] ) ? esc_attr($main_settings['counter']) : 'true';
     $mailto = ! empty( $settings['mailto'] ) ? esc_attr($settings['mailto']) : 'false';
     $deleting_messages = ! empty( $main_settings['deleting_messages'] ) ? esc_attr($main_settings['deleting_messages']) : 'false';
     $name_field = ! empty( $attributes['name_field'] ) ? esc_attr($attributes['name_field']) : 'visible';
     $lastname_field = ! empty( $attributes['lastname_field'] ) ? esc_attr($attributes['lastname_field']) : 'hidden';
     $email_field = ! empty( $attributes['email_field'] ) ? esc_attr($attributes['email_field']) : 'visible';
     $phone_field = ! empty( $attributes['phone_field'] ) ? esc_attr($attributes['phone_field']) : 'hidden';
     $disabled = 'disabled="disabled"';
     global $wpdb; 
     $count_forms = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sform_shortcodes WHERE id != '1'");
     ?>
		
     <h2 id="h2-storage" class="options-heading"><span class="heading" section="storage"><?php _e( 'Data Storage', 'simpleform-contact-form-submissions' ); ?><span class="toggle dashicons dashicons-arrow-up-alt2 storage"></span></span><?php if ( $id != '1' ) { ?><a href="<?php echo menu_page_url( 'sform-settings', false ); ?>"><span class="dashicons dashicons-edit icon-button <?php echo $color ?>"></span><span class="settings-page wp-core-ui button"><?php _e( 'Go to main settings for edit', 'simpleform' ) ?></span></a><?php } ?></h2>
 
     <div class="section storage"><table class="form-table storage"><tbody>		
	 
     <tr><th class="option"><span><?php _e('Form Data Storage','simpleform-contact-form-submissions') ?></span></th><td class="checkbox-switch notes"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="data-storing" id="data-storing" class="sform-switch" value="true" <?php checked( $data_storing, 'true') ?>><span></span></label><label for="data-storing" class="switch-label"><?php _e( 'Enable the form data storing in the database (data will be included only within the notification email if unchecked)','simpleform-contact-form-submissions') ?></label></div><p id="storing-description" class="description"><?php echo $storage_notes ?></p></td></tr>
	 
     <tr class="trstoring <?php if ($data_storing !='true') {echo 'unseen';} ?>"><th class="option"><span><?php _e('IP Address Storage','simpleform-contact-form-submissions') ?></span></th><td class="checkbox-switch"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="ip-storing" id="ip-storing" class="sform-switch" value="true" <?php checked( $ip_storing, 'true'); ?>><span></span></label><label for="ip-storing" class="switch-label"><?php _e( 'Enable IP address storing in the database','simpleform-contact-form-submissions') ?></label></div></td></tr>
	 
     <tr class="trstoring <?php if ($data_storing !='true') {echo 'unseen';} ?>" ><th class="option"><span><?php _e( 'Visible Data Columns', 'simpleform-contact-form-submissions' ) ?></span></th><td class="multicheckbox notes"><label for="id" class="multiselect"><input type="checkbox" name="columns[]" id="id" class="sform multiselect" value="id" <?php if (strpos($columns,'id') !== false) { echo 'checked'; } ?>><?php _e( 'Request ID', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><label for="subject" class="multiselect"><input type="checkbox" name="columns[]" id="subject" class="sform multiselect" value="subject" <?php if (strpos($columns,'subject') !== false) { echo 'checked'; } ?>><?php _e( 'Subject', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><?php if ( $name_field != 'hidden' ) { ?><label for="firstname" class="multiselect"><input type="checkbox" name="columns[]" id="firstname" class="sform multiselect" value="firstname" <?php if (strpos($columns,'firstname') !== false) { echo 'checked'; } ?>><?php _e( 'Name', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><?php } ?><?php if ( $lastname_field != 'hidden' ) { ?><label for="family" class="multiselect"><input type="checkbox" name="columns[]" id="family" class="sform multiselect" value="family" <?php if (strpos($columns,'family') !== false) { echo 'checked'; } ?>><?php _e( 'Last Name', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><?php } ?><?php if ( $name_field != 'hidden' && $lastname_field != 'hidden' ) { ?><label for="from" class="multiselect"><input type="checkbox" name="columns[]" id="from" class="sform multiselect" value="from" <?php if (strpos($columns,'from') !== false) { echo 'checked'; } ?>><?php _e( 'Full Name', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><?php } ?><label for="object" class="multiselect"><input type="checkbox" name="columns[]" id="object" class="sform multiselect" value="message" <?php if (strpos($columns,'message') !== false) { echo 'checked'; }?>><?php _e( 'Message', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><?php if ( $email_field != 'hidden' ) { ?><label for="mail" class="multiselect"><input type="checkbox" name="columns[]" id="mail" class="sform multiselect" value="mail" <?php if (strpos($columns,'mail') !== false) { echo 'checked'; } ?>><?php _e( 'Email', 'simpleform-contact-form-submissions' ); ?><span class="checkmark"></span></label><?php } ?><?php if ( $phone_field != 'hidden' ) { ?><label for="phone" class="multiselect"><input type="checkbox" name="columns[]" id="phone" class="sform multiselect" value="phone" <?php if (strpos($columns,'phone') !== false) { echo 'checked'; } ?>><?php _e( 'Phone', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><?php } ?><label for="ip" class="multiselect"><input type="checkbox" name="columns[]" id="ip" class="sform multiselect" value="ip" <?php if (strpos($columns,'ip') !== false) { echo 'checked'; }?>><?php _e( 'IP', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><label for="date" class="multiselect last"><input type="checkbox" name="columns[]" id="date" class="sform multiselect" value="date" <?php if (strpos($columns,'date') !== false) { echo 'checked'; }?>><?php _e( 'Date', 'simpleform-contact-form-submissions' ) ?><span class="checkmark"></span></label><p id="columns-description" class="description"><?php _e( 'Set the default columns that must be displayed in the entries list table. You can disable the visible columns at any time via "Screen options"', 'simpleform-contact-form-submissions' ) ?></p></td></tr>

     <tr class="trstoring <?php if ($data_storing !='true') {echo 'unseen';} ?>"><th class="option"><span><?php _e('Unread Count','simpleform-contact-form-submissions') ?></span></th><td class="checkbox-switch"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="counter" id="counter" class="sform-switch" value="true" <?php checked( $counter, 'true'); if ( $id != '1' ) { echo $disabled; } ?>><span></span></label><label for="counter" class="switch-label <?php if ( $id != '1' ) { echo 'disabled'; } ?>"><?php _e( 'Add a notification bubble to admin menu for unread messages','simpleform-contact-form-submissions'); if ($count_forms > 0 ) { ?><span class="head-bracket">(<?php _e( 'This applies to all forms','simpleform-contact-form-submissions') ?>)</span> <?php } ?></label></div></td></tr>

     <tr class="trstoring <?php if ($data_storing !='true') {echo 'unseen';} ?>"><th class="option"><span><?php _e('Mailto Link','simpleform-contact-form-submissions') ?></span></th><td class="checkbox-switch"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="mailto" id="mailto" class="sform-switch" value="false" <?php checked( $mailto, 'true') ?>><span></span></label><label for="mailto" class="switch-label"><?php _e( 'Show a mailto button to activate the default mail program for sending a reply','simpleform-contact-form-submissions') ?></label></div></td></tr>
	
	 <tr><th class="option"><span><?php _e( 'Deleting Messages', 'simpleform-contact-form-submissions' ) ?></span></th><td class="checkbox-switch last"><div class="switch-box"><label class="switch-input"><input type="checkbox" id="deleting-data" name="deleting-data" class="sform-switch" value="false" <?php checked( $deleting_messages, 'true'); if ( $id != '1' ) { echo $disabled; } ?>><span></span></label><label for="deleting-data" class="switch-label <?php if ( $id != '1' ) { echo 'disabled'; } ?>"><?php _e( 'Delete messages from the database when uninstalling the plugin', 'simpleform-contact-form-submissions' ); if ($count_forms > 0 ) { ?><span class="head-bracket">(<?php _e( 'This applies to all forms','simpleform-contact-form-submissions') ?>)</span> <?php } ?></label></div></td></tr>
	 
	 </tbody></table></div>
     
    <?php 
    }	

	/**
	 * Add submissions related fields values in the settings options array.
	 *
	 * @since    1.0
	 */
	
    public function add_array_submissions_settings() { 
  
       $id = isset( $_POST['form-id'] ) ? absint($_POST['form-id']) : '1';
       $main_settings = get_option('sform_settings'); 
       $data_storing = isset($_POST['data-storing']) ? 'true' : 'false';
       $mailto = isset($_POST['mailto']) ? 'true' : 'false';
       $ip_storing = isset($_POST['ip-storing']) ? 'true' : 'false';
       $columns = isset($_POST['columns']) ? esc_html(trim(implode(",", $_POST['columns']))) : '';
       $where = $data_storing == 'true' ? "AND object != '' AND object != 'not stored'" : '';
       global $wpdb;
	   $entries = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE form = '$id' $where");
 	   $moved_entries = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE moved_from = '$id' $where");       
       $storing = $data_storing == 'true' ? '1' : '0';
       $listable = $data_storing == 'true' ? '1' : '0';
	 
	   $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET listable = %d WHERE form = %d", $listable, $id) );   
	   $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_shortcodes SET entries = %d, moved_entries = %d, storing = %d WHERE id = %d", $entries, $moved_entries, $storing, $id) );   
       
       if ( $data_storing == 'false' )  {
	     $ip_storing = 'false'; 
	     $columns = '';
	   }
       
       if ( $id == '1' ) {
         $counter = isset($_POST['counter']) ? 'true' : 'false';
         $deleting_messages = isset($_POST['deleting-data']) ? 'true' : 'false';
         $new_items = array( 'data_storing' => $data_storing, 'ip_storing' => $ip_storing, 'data_columns' => $columns, 'mailto' => $mailto, 'counter' => $counter, 'deleting_messages' => $deleting_messages );
         
         $table = $wpdb->prefix . 'sform_shortcodes';
           $ids = $wpdb->get_col("SELECT id FROM `$table` WHERE id != '1'");	
           if ( $ids ) {
             foreach($ids as $id) { 
	         $form_settings = get_option('sform_'. $id .'_settings');
             if ( $form_settings != false ) {
			   $form_settings['counter'] = $counter;
			   $form_settings['deleting_messages'] = $deleting_messages;
               update_option('sform_'. $id .'_settings', $form_settings); 
	         }
             }
           }
                  
       }
       else {
         $new_items = array( 'data_storing' => $data_storing, 'ip_storing' => $ip_storing, 'data_columns' => $columns, 'mailto' => $mailto, 'counter' => $main_settings['counter'], 'deleting_messages' => $main_settings['deleting_messages'] );
       }
     
       return  $new_items;
       
    }

	/**
	 * Validate submissions related fields in Settings page.
	 *
	 * @since    1.0
	 */
	
    public function validate_submissions_fields(){
	    
       $data_storing = isset($_POST['data-storing']) ? 'true' : 'false';
       $notification = isset($_POST['notification']) ? 'true' : 'false';   
       $columns = isset($_POST['columns']) ? esc_html(trim(implode(",", $_POST['columns']))) : '';
     
       if ( strpos($columns,'firstname') !== false && strpos($columns,'from') !== false ) {
            echo json_encode(array('error' => true, 'update' => false, 'message' => __('The column Name and the column Full Name cannot both be selected', 'simpleform-contact-form-submissions')  ));
	        exit; 
       }

       if ( strpos($columns,'family') !== false && strpos($columns,'from') !== false ) {
            echo json_encode(array('error' => true, 'update' => false, 'message' => __('The column Last Name and the column Full Name cannot both be selected', 'simpleform-contact-form-submissions')  ));
	        exit; 
       }
	   
	   if ( $data_storing == 'false' && $notification == 'false' ) {
            echo json_encode(array('error' => true, 'update' => false, 'message' => __('The Data Storing option and the Enable Notification option cannot both be disabled. Please keep at least one option enabled!', 'simpleform-contact-form-submissions')  ));
	        exit; 
       }
  
    }
	
	/**
	 * Display submissions list in dashboard.
	 *
	 * @since    1.0
	 */

    public function display_submissions_list($id, $shortcode_ids, $last_message ){
    
     $main_settings = get_option('sform_settings'); 
     $admin_notices = ! empty( $main_settings['admin_notices'] ) ? esc_attr($main_settings['admin_notices']) : 'false';	

     if ( $id == '' ) {
      $settings = $main_settings;
      $where_form = " WHERE form != '0'";
      // $last_message = stripslashes(get_transient('sform_last_message'));
	 } else {
      $settings_option = get_option('sform_'. $id .'_settings');
      $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
      $where_form = " WHERE form = '". $id ."'";
     }
     
     $data_storing = ! empty( $settings['data_storing'] ) ? esc_attr($settings['data_storing']) : 'true';	
     $color = ! empty( $settings['admin_color'] ) ? esc_attr($settings['admin_color']) : 'default';

     // get all storing options values
     $all_data_storing = array($main_settings['data_storing']);
     global $wpdb;
     $shortcodes_table = $wpdb->prefix . 'sform_shortcodes';
     $ids = $wpdb->get_col("SELECT id FROM `$shortcodes_table` WHERE id != '1' AND status != 'trash'");	 
     if ( $ids ) {
	  foreach ( $ids as $form_id ) {
	  $form_settings = get_option('sform_'.$form_id.'_settings');
      $form_data_storing = $form_settings != false && ! empty( $form_settings['data_storing'] ) ? esc_attr($form_settings['data_storing']) : 'false';
      array_push($all_data_storing, $form_data_storing);
      }
     }
     
     // Show a table if data storing is enabled for at least one of form
     if ( in_array('true',$all_data_storing) ) {	

      if (  $id == '' || in_array($id, $shortcode_ids) ) {
	     
        if ( $id == '' || $data_storing == 'true' )  {
	        
	      $transient_notice = stripslashes(get_transient('sform_action_notice'));
          $notice = $transient_notice != '' ? $transient_notice : '';
          
          echo '<div class="submission-notice">' . $notice . '</div>'; 
          
          $table = new SForms_Submissions_List_Table();
          $table->prepare_items();
          $table->views(); 
          ?>
          <form id="submissions-table" method="get"><input type="hidden" name="page" value="<?php echo sanitize_key($_REQUEST['page']) ?>"/>
          <?php $table->search_box( __( 'Search' ), 'simpleform-contact-form-submissions');	
          $table->display(); 
          ?></form><?php
        }
        
        else { 
    	  if ( $admin_notices == 'false' ) {
	   	   $link = '<a href="' . admin_url('admin.php?page=sform-settings') . '&form='. $id . '" target="_blank" style="text-decoration: none">' . __( 'settings', 'simpleform-contact-form-submissions' ) . '</a>';
	   	   
 	       $add_notice = __('Please note that unsaved messages, where they exist, are also included in the count!', 'simpleform-contact-form-submissions' );
	   	   
 	       $notice = '<div class="notice notice-warning is-dismissible"><p>' . sprintf( __( 'By disabling the data storing for this form, you have chosen to remove the entries list. Go to %s for editing the option.', 'simpleform-contact-form-submissions' ), $link ) . ' ' . $add_notice . '</p></div>';
           echo $notice;
          }
          
          global $wpdb;
          $table_name = $wpdb->prefix . 'sform_submissions';
          $where_day = 'AND date >= UTC_TIMESTAMP() - INTERVAL 24 HOUR';
          $where_week = 'AND date >= UTC_TIMESTAMP() - INTERVAL 7 DAY';
          $where_month = 'AND date >= UTC_TIMESTAMP() - INTERVAL 30 DAY';
          $where_year = 'AND date >= UTC_TIMESTAMP() - INTERVAL 1 YEAR';
          $count_all = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form");
          $count_last_day = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_day");
          $count_last_week = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_week");
          $count_last_month = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_month");
          $count_last_year = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_year");
          $total_received = $count_all;
          ?>
          <div><ul id="submissions-data"><li class="type"><span class="label"><?php _e( 'Received', 'simpleform' ); ?></span><span class="value"><?php echo $total_received; ?></span></li><li class="type"><span class="label"><?php _e( 'This Year', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_year; ?></span></li><li class="type"><span class="label"><?php _e( 'Last Month', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_month; ?></span></li><li class="type"><span class="label"><?php _e( 'Last Week', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_week; ?></span></li><li><span class="label"><?php _e( 'Last Day', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_day; ?></span></li></ul></div>
<?php
	
	      if ( $last_message ) {
	       echo '<div id="last-submission"><h3><span class="dashicons dashicons-buddicons-pm"></span>'.__('Last Message Received', 'simpleform-contact-form-submissions' ).'</h3>' . wpautop($last_message) . '</div>'; echo '<div id="submissions-notice" class="unseen"><h3><span class="dashicons dashicons-editor-help"></span>'.__('Before you go crazy looking for the received messages', 'simpleform-contact-form-submissions' ).'</h3>'.__('Submissions data is not stored in the WordPress database. Open the General Tab in the Settings page, and check the Data Storing option for enabling the display of messages in the dashboard. By default, only last message is temporarily stored. Therefore, it is recommended that you verify the correct SMTP server configuration in case of use, and always keep the notification email enabled if you want to be sure to receive the messages.', 'simpleform-contact-form-submissions' ).'</div>';
	      }
	      else  {
	       echo '<div id="empty-submission"><h3><span class="dashicons dashicons-info"></span>'.__('Empty Inbox', 'simpleform-contact-form-submissions' ).'</h3>'.__('So far, no message has been received yet!', 'simpleform-contact-form-submissions' ).'<p>'.__('Submissions data is not stored in the WordPress database. Open the General Tab in the Settings page, and check the Data Storing option for enabling the display of messages in the dashboard. By default, only last message is temporarily stored. Therefore, it is recommended that you verify the correct SMTP server configuration in case of use, and always keep the notification email enabled if you want to be sure to receive the messages.', 'simpleform-contact-form-submissions' ).'</div>';
	      }
        }
 
 	  } 
	 
	  else {
       ?>
  <span><?php _e('It seems the form is no longer available!', 'simpleform' ) ?></span><p><span class="wp-core-ui button unavailable <?php echo $color ?>"><a href="<?php echo menu_page_url( 'sform-entries', false ); ?>"><?php _e('Reload the entries page','simpleform') ?></a></span><span class="wp-core-ui button unavailable <?php echo $color ?>"><a href="<?php echo menu_page_url( 'sform-new', false ); ?>"><?php _e('Add New Form','simpleform') ?></a></span><span class="wp-core-ui button unavailable <?php echo $color ?>"><a href="<?php echo self_admin_url('widgets.php'); ?>"><?php _e('Activate SimpleForm Contact Form Widget','simpleform') ?></a></span></p>
     <?php
      }
     
     } 

     // Show a summary list if data storing is not enabled for all forms     
     else {
 
      if ( $id == '' || in_array($id, $shortcode_ids) ) {
	      
    	  if ( $admin_notices == 'false' ) {
	   	   $link = '<a href="' . admin_url('admin.php?page=sform-settings') . '" target="_blank" style="text-decoration: none;">' . __( 'settings', 'simpleform-contact-form-submissions' ) . '</a>';
	   	   
 	       $add_notice = __('Please note that unsaved messages have also been included in the count!', 'simpleform-contact-form-submissions' );
	   	   
 	       $notice = '<div class="notice notice-warning is-dismissible"><p>' . sprintf( __( 'By disabling the data storing, you chose to remove the entries list. Go to the %s for editing the option.', 'simpleform-contact-form-submissions' ), $link ) . ' ' . $add_notice . '</p></div>';
 	       
 	       
echo $notice;
          }
	      
      global $wpdb;
      $table_name = $wpdb->prefix . 'sform_submissions';
      $where_day = 'AND date >= UTC_TIMESTAMP() - INTERVAL 24 HOUR';
      $where_week = 'AND date >= UTC_TIMESTAMP() - INTERVAL 7 DAY';
      $where_month = 'AND date >= UTC_TIMESTAMP() - INTERVAL 30 DAY';
      $where_year = 'AND date >= UTC_TIMESTAMP() - INTERVAL 1 YEAR';
      $count_all = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form");
      $count_last_day = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_day");
      $count_last_week = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_week");
      $count_last_month = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_month");
      $count_last_year = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where_form $where_year");
      $total_received = $count_all;
      ?>

      <div><ul id="submissions-data"><li class="type"><span class="label"><?php _e( 'Received', 'simpleform' ); ?></span><span class="value"><?php echo $total_received; ?></span></li><li class="type"><span class="label"><?php _e( 'This Year', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_year; ?></span></li><li class="type"><span class="label"><?php _e( 'Last Month', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_month; ?></span></li><li class="type"><span class="label"><?php _e( 'Last Week', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_week; ?></span></li><li><span class="label"><?php _e( 'Last Day', 'simpleform' ); ?></span><span class="value"><?php echo $count_last_day; ?></span></li></ul></div>
<?php
	  if ( $last_message ) {
	  echo '<div id="last-submission"><h3><span class="dashicons dashicons-buddicons-pm"></span>'.__('Last Message Received', 'simpleform-contact-form-submissions' ).'</h3>'.$last_message . '</div>'; echo '<div id="submissions-notice" class="unseen"><h3><span class="dashicons dashicons-editor-help"></span>'.__('Before you go crazy looking for the received messages', 'simpleform-contact-form-submissions' ).'</h3>'.__('Submissions data is not stored in the WordPress database. Open the General Tab in the Settings page, and check the Data Storing option for enabling the display of messages in the dashboard. By default, only last message is temporarily stored. Therefore, it is recommended that you verify the correct SMTP server configuration in case of use, and always keep the notification email enabled if you want to be sure to receive the messages.', 'simpleform-contact-form-submissions' ).'</div>';
	  }
	  else  {
	  echo '<div id="empty-submission"><h3><span class="dashicons dashicons-info"></span>'.__('Empty Inbox', 'simpleform-contact-form-submissions' ).'</h3>'.__('So far, no message has been received yet!', 'simpleform-contact-form-submissions' ).'<p>'.__('Submissions data is not stored in the WordPress database. Open the General Tab in the Settings page, and check the Data Storing option for enabling the display of messages in the dashboard. By default, only last message is temporarily stored. Therefore, it is recommended that you verify the correct SMTP server configuration in case of use, and always keep the notification email enabled if you want to be sure to receive the messages.', 'simpleform-contact-form-submissions' ).'</div>';
	  }
	  	  
      } else {
      ?>
  <span><?php _e('It seems the form is no longer available!', 'simpleform' ) ?></span><p><span class="wp-core-ui button unavailable <?php echo $color ?>"><a href="<?php echo menu_page_url( 'sform-entries', false ); ?>"><?php _e('Reload the entries page','simpleform') ?></a></span><span class="wp-core-ui button unavailable <?php echo $color ?>"><a href="<?php echo menu_page_url( 'sform-creation', false ); ?>"><?php _e('Add New Form','simpleform') ?></a></span><span class="wp-core-ui button unavailable <?php echo $color ?>"><a href="<?php echo self_admin_url('widgets.php'); ?>"><?php _e('Activate SimpleForm Contact Form Widget','simpleform') ?></a></span></p>
      <?php
     }
     }
	
    }

	/**
	 * Add screen option tab.
	 *
	 * @since    1.0
	 */

    public function submissions_table_options() {
	
	  global $sform_entries;
	  add_action("load-$sform_entries", array ($this, 'sforms_submissions_list_options') );
    
    }

	/**
	 * Setup function that registers the screen option.
	 *
	 * @since    1.0
	 */

    public function sforms_submissions_list_options() {
	    
      $id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';
     
      if ( $id == '' || $id == '1' ) {
        $settings = get_option('sform_settings');
      } else {
        $settings_option = get_option('sform_'. $id .'_settings');
        $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
      }
      
      $data_storing = ! empty( $settings['data_storing'] ) ? esc_attr($settings['data_storing']) : 'true';
      global $wpdb; 
      $table_name = "{$wpdb->prefix}sform_shortcodes"; 
      $shortcode_ids = $wpdb->get_col( "SELECT id FROM $table_name" );

      if ( $data_storing == 'true' && ( $id == '' || in_array($id, $shortcode_ids) ) ) {
	      
      global $table;
      global $sform_entries;
      $screen = get_current_screen();      
           
      if(!is_object($screen) || $screen->id != $sform_entries)
      return;
      $option = 'per_page';
      $args = array( 'label' => esc_attr__('Number of entries per page', 'simpleform-contact-form-submissions'),'default' => 20,'option' => 'edit_submission_per_page');
      
      add_screen_option( $option, $args );
      $table = new SForms_Submissions_List_Table(); 
      
     }
  
    }

	/**
	 * Save screen options.
	 *
	 * @since    1.0
	 */

    public function submissions_screen_option($status, $option, $value) {
      
      if ( 'edit_submission_per_page' == $option ) return $value;
      return $status;
    
    }
    
	/**
	 * Register a post type for change the pagination in Screen Options tab.
	 *
	 * @since    1.3
	 */

    public function submission_post_type() {
	
	    $args = array();
	    register_post_type( 'submission', $args );
	    
    }
	        
	/**
	 * Edit entry data
	 *
	 * @since    2.0
	 */
	
    public function edit_entry() {

      if( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {	die ( 'Security checked!'); }
      if ( ! wp_verify_nonce( $_POST['verification_nonce'], "ajax-verification-nonce")) { exit("Security checked!"); }      
      if ( ! current_user_can('manage_options')) { exit("Security checked!"); }   
   
      else { 
       global $wpdb; 
       $id = isset($_POST['entry']) ? intval($_POST['entry']) : '0';
       $selected_form = isset($_POST['selected-form']) ? intval($_POST['selected-form']) : '';
       $entries_counter = isset($_POST['entries-counter']) ? intval($_POST['entries-counter']) : '1';
       $entry_data = $wpdb->get_row( "SELECT form, moved_from, date, status, previous_status, trash_date, movable FROM {$wpdb->prefix}sform_submissions WHERE id = '{$id}'", 'ARRAY_A' );
       $current_status = $entry_data['status'];
       $status = isset($_POST['message-status']) ? sanitize_text_field($_POST['message-status']) : 'read';
       $entries_form = isset($_POST['entries-form']) ? sanitize_text_field($_POST['entries-form']) : '';       
       $entries_view = isset($_POST['entries-view']) ? sanitize_text_field($_POST['entries-view']) : '';
       $previous_status = $status == $current_status ? $entry_data['previous_status'] : $current_status;
       $entry_timestamp = strtotime($entry_data['date']);
       $form_id = $entry_data['form'];
       $entry_moved_from = $entry_data['moved_from'];
       $entry_movable = $entry_data['movable'];
       $movable = isset($_POST['moving']) ? '1' : '0';
	   $entry_trash_date = $status != 'trash' ? NULL : $entry_data['trash_date'];
	   $trash_date = $status == 'trash' && $status != $current_status ? date('Y-m-d H:i:s') : $entry_trash_date;
       $moveto = $movable == '1' && isset($_POST['moveto']) && $_POST['moveto'] != '' ? intval($_POST['moveto']) : $form_id;       
       $moved_from = $movable == '1' && $moveto != $form_id ? $form_id : $entry_moved_from;
       $forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes WHERE status != 'trash'" );
       $moving = $moveto != $form_id ? true : false;
       $view = !empty($entries_view) ? true : false;

       if ( empty($entries_view) ) { 	       
	     if ( ( $current_status != 'spam' && $current_status != 'trash' && ( $status == 'spam' || $status == 'trash' ) ) ) { $newcounter = $entries_counter - 1; }
	     elseif ( ( $current_status == 'spam' || $current_status == 'trash' ) && ( $status == 'spam' || $status == 'trash' ) ) { $newcounter = $entries_counter - 1; }
	     else  { $newcounter = $entries_counter; }
	   }
	   	   
       if ( $entries_view == 'new' ) {	     	       
	     if ( $status == 'new' && $moveto == $entries_form ) { $newcounter = $entries_counter + 1; }
	     else  { $newcounter = $entries_counter; }
	   }
	   
       if ( $entries_view == 'answered' ) { 	       
	     if ( $status != 'answered' || $moveto != $entries_form ) { $newcounter = $entries_counter - 1; }
	     else { $newcounter = $entries_counter; }
	   }
	   
       if ( $entries_view == 'spam' ) { 	       
	     if ( $status != 'spam' || $moveto != $entries_form ) { $newcounter = $entries_counter - 1; }
	     else  { $newcounter = $entries_counter; }
	   }
       
       if ( $entries_view == 'trash' ) { 	       
	     if ( $status != 'trash' || $moveto != $entries_form ) { $newcounter = $entries_counter - 1; }
	     else  { $newcounter = $entries_counter; }
	   }
	   
       if ( $selected_form && $entries_form != $moveto ) { 	       
	      $newcounter = $entries_counter - 1;
	   }
	          
       if ( !$entry_data) {
            echo json_encode(array('error' => true, 'message' => __( 'It seems the entry has been deleted!', 'simpleform-contact-form-submissions' ) ));
	        exit;
       }
       
       if ( ! in_array($status, array('new','read','answered','spam','trash')) ) {
            echo json_encode(array('error' => true, 'message' => __( 'Error occurred entry status changing', 'simpleform-contact-form-submissions' ) ));
	        exit;
       }

       if ( ! in_array($moveto, $forms) ) {
            echo json_encode(array('error' => true, 'message' => __( 'It seems the form has been deleted!', 'simpleform-contact-form-submissions' ) ));
	        exit;
       }

	   if ( in_array($status, array('new','read','answered','spam','trash')) ) { 
         
         $update_data = $wpdb->update($wpdb->prefix . 'sform_submissions', array( 'form' => $moveto, 'moved_from' => $moved_from, 'status' => $status, 'previous_status' => $previous_status, 'trash_date' => $trash_date, 'movable' => $movable ), array('id' => $id));
         
	     if ( $update_data ) {
		     
           $forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes" );
           foreach($forms as $form) {
	 		 $count_moved = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE moved_from = '$form' AND object != '' AND object != 'not stored'");
	 		 $count_entries = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE form = '$form' AND object != '' AND object != 'not stored'");
  	         $wpdb->update($wpdb->prefix . 'sform_shortcodes', array('entries' => $count_entries, 'moved_entries' => $count_moved ), array('id' => $form ) ); 
	       }
		   
           $util = new SimpleForm_Submissions_Util();      
           $util->update_last_messages( $entry_timestamp, $moved_from, $moveto ); 
           
		   $msg = '';
		   
		   if ( $current_status != 'trash' && $status == 'spam' ) {
			  if (has_filter('akismet_submit_spam')) {
                 $msg = apply_filters( 'akismet_submit_spam', $id, $msg );
              } 
           }   
		 
		   if ( $current_status == 'spam' && $status != 'trash' ) {
              if (has_filter('akismet_submit_ham')) {
                 $msg = apply_filters( 'akismet_submit_ham', $id, $msg );
              } 
           }   
           
           $form_name = $movable == '1' && $moveto != $form_id ? $wpdb->get_var("SELECT name FROM {$wpdb->prefix}sform_shortcodes WHERE id = '$moveto'") : $wpdb->get_var("SELECT name FROM {$wpdb->prefix}sform_shortcodes WHERE id = '$form_id'");
           
           $forms = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sform_shortcodes WHERE id != {$moveto} AND status != 'trash' ORDER BY name ASC", 'ARRAY_A' );
           $options = '<option value="">'. __('Select a form to move entry to', 'simpleform-contact-form-submissions' ) .'</option>';
           foreach ( $forms as $form_data ) { 
	         $formID = $form_data['id']; 
	         $formName = $form_data['name']; 
	         $options .= '<option value="'.$formID.'">'.$formName.'</option>'; 
	       }
      
           $message = __('Entry data has been updated', 'simpleform-contact-form-submissions' ) . $msg;
           
		   if ( $status == 'new' ) {    
	           echo json_encode(array('error' => false, 'update' => true, 'newstatus' => $status, 'prevstatus' => $current_status, 'bubble' => true, 'entries' => $newcounter, 'formname' => $form_name, 'view' => $view, 'moving' => $moving, 'options' => $options, 'message' => $message ));
	           exit;
	        }
	        
	        else {    
	           echo json_encode(array('error' => false, 'update' => true, 'newstatus' => $status, 'prevstatus' => $current_status, 'bubble' => false, 'entries' => $newcounter, 'formname' => $form_name, 'view' => $view, 'moving' => $moving, 'options' => $options, 'message' => $message ));
	           exit;
	        }
	        
	     }
	     
	     else {
		   
		   if ( $moveto == $form_id && $status == $current_status && $movable == $entry_movable ) { 
               echo json_encode(array('error' => false, 'update' => false, 'message' => __( 'Entry data has already been updated', 'simpleform-contact-form-submissions' ) ));
	           exit;
	       }
	       
	       else { 
               echo json_encode(array('error' => true, 'message' => __( 'Error occurred entry data changing', 'simpleform-contact-form-submissions' ) ));
	           exit;
	       }
	       
	     }
         
       }

       die();
       
      }	   

    }
    
 	/**
	 * Fallback for database table updating if plugin is already active.
	 *
	 * @since    1.4
	 */
    
    public function simpleform_db_version_check() {
    
       $current_version = SIMPLEFORM_SUBMISSIONS_DB_VERSION;
       $installed_version = get_option('sform_sub_db_version');
       $main_settings = get_option('sform_settings');
       
       if ( $installed_version != $current_version ) {
        require_once SIMPLEFORM_SUBMISSIONS_PATH . 'includes/class-activator.php';
	    SimpleForm_Submissions_Activator::change_db();
        SimpleForm_Submissions_Activator::entries_data_recovery();
       }
	   
       if( $main_settings && !isset($main_settings['data_storing']) ) {
        require_once SIMPLEFORM_SUBMISSIONS_PATH . 'includes/class-activator.php';	    
	    SimpleForm_Submissions_Activator::sform_submissions_settings();
       }
       
       $entries_option = get_option('sform_entries_view');
       
       if ( $entries_option == false ) {
         
         global $wpdb;
         $messages = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE object != '' AND object != 'not stored'");
         
         if ( $messages > 0 ) {
           $table_shortcodes = $wpdb->prefix . 'sform_shortcodes';
           $forms = $wpdb->get_col( "SELECT id FROM $table_shortcodes" );
           foreach($forms as $form) {
	           // AND storing = 1 ???
	 	     $count_entries = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE form = '$form' AND object != '' AND object != 'not stored'");
 	 	     $count_moved = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE moved_from = '$form' AND object != '' AND object != 'not stored'");
 	 	     $form_status = $wpdb->get_var("SELECT status FROM {$wpdb->prefix}sform_shortcodes WHERE id = '$form'");
             if ( $form_status != 'trash' ) { $status = $count_entries == '0' ? 'draft' : 'published'; }
             else { $status = 'trash'; }
  	         $wpdb->update($table_shortcodes, array('status' => $status, 'entries' => $count_entries, 'moved_entries' => $count_moved ), array('id' => $form ) ); 
           }
           $entries = $wpdb->get_var("SELECT SUM(entries) as total_entries FROM {$wpdb->prefix}sform_shortcodes");
           if ( $messages == $entries ) {
             add_option( 'sform_entries_view', 'updated' );
           }
         }
         
         else {
           add_option( 'sform_entries_view', 'updated' );
         }

       }

    }

 	/**
	 * Add conditional items into the Bulk Actions dropdown for submissions list.
	 *
	 * @since    1.3
	 */

    public function register_sform_actions($bulk_actions) { 
	    
	    $view = isset($_REQUEST['view']) && in_array($_REQUEST['view'], array('inbox', 'new', 'answered', 'spam', 'trash')) ? $_REQUEST['view'] : 'inbox';
	    $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';
	    
	    if ( ! empty($view) && $view == 'trash' ) { 
          $bulk_actions['bulk-untrash'] = __('Restore', 'simpleform-contact-form-submissions');
          $bulk_actions['bulk-delete'] = __('Delete permanently', 'simpleform-contact-form-submissions');
        } 
 	    elseif ( ! empty($view) && $view == 'spam' ) { 
          $bulk_actions['bulk-unspam'] = __('Restore', 'simpleform-contact-form-submissions');
          $bulk_actions['bulk-delete'] = __('Delete permanently', 'simpleform-contact-form-submissions');
        } 
  	    elseif ( ! empty($view) && $view == 'answered' ) { 
	  	  if ( !empty($form_id) ) { 
          $bulk_actions['bulk-move'] = __('Move to Form', 'simpleform-contact-form-submissions');
          }
        } 
        else {
          $bulk_actions['bulk-spam'] = __('Mark as Spam', 'simpleform-contact-form-submissions');
          $bulk_actions['bulk-trash'] = __('Move to Trash', 'simpleform-contact-form-submissions');
	  	  if ( !empty($form_id) ) { 
          $bulk_actions['bulk-move'] = __('Move to Form', 'simpleform-contact-form-submissions');
          }
       } 
        return $bulk_actions;
         
    }
    
 	/**
	 * Add a notification bubble to Contacts menu item.
	 *
	 * @since    1.4
	 */

     public function notification_bubble() {
	  
	  $main_settings = get_option('sform_settings');
      $counter = ! empty( $main_settings['counter'] ) ? esc_attr($main_settings['counter']) : 'true';	
      $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';

      if ( $form_id == '1' ) {
        $settings = $main_settings;
      } else {
        $settings_option = get_option('sform_'. $form_id .'_settings');
        $settings = $settings_option != false ? $settings_option : $main_settings;
      }
      
	  global $wpdb;
      $table_name = $wpdb->prefix . 'sform_submissions';      
      $data_storing = ! empty( $settings['data_storing'] ) ? esc_attr($settings['data_storing']) : 'true';
      // get all storing options values
      $all_data_storing = array($main_settings['data_storing']);
      $shortcodes_table = $wpdb->prefix . 'sform_shortcodes';
      $ids = $wpdb->get_col("SELECT id FROM `$shortcodes_table` WHERE id > '1'");	
      if ( $ids ) {
	    foreach ( $ids as $id ) {
	      $form_settings = get_option('sform_'.$id.'_settings');
          $form_data_storing = $form_settings != false && ! empty( $form_settings['data_storing'] ) ? esc_attr($form_settings['data_storing']) : 'false';
          array_push($all_data_storing, $form_data_storing);
        }
      }
     
      if ( ( $form_id != '' && $data_storing == 'true' && $counter == 'true' ) || ( $form_id == '' && in_array('true',$all_data_storing) && $counter == 'true' ) ):
      
        $id = isset( $_REQUEST['id'] ) ? absint($_REQUEST['id']) : '';   
        // and storing = 1 // and form is not trashed
        
        if( $form_id != '' ){
          $unread = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table_name WHERE status = 'new' AND object != '' AND object != 'not stored' AND form = %d AND listable = '1'", $form_id));
        } 
        elseif ( isset( $_REQUEST['page'] ) && ( $_REQUEST['page'] == 'sform-editor' || $_REQUEST['page'] == 'sform-settings' ) ) {
          $unread = $wpdb->get_col("SELECT id FROM $table_name WHERE status = 'new' AND object != '' AND object != 'not stored' AND form = '1' AND listable = '1'");
        } else {
          $unread = $wpdb->get_col("SELECT id FROM $table_name WHERE status = 'new' AND object != '' AND object != 'not stored' AND form != '0' AND listable = '1'");
        } 
        $real_count = isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'sform-entrie' && in_array($id, $unread) ? '1' : '0';
        $notification_count = count($unread) - $real_count;
        // Use transients instead of new query 
        $item = $notification_count ? sprintf(__('Contacts', 'simpleform') . ' <span id="unread-messages"><span class="sform awaiting-mod">%d</span></span>', $notification_count) : __('Contacts', 'simpleform') . ' <span id="unread-messages"></span>';
      else:
        $item = __('Contacts', 'simpleform');
      endif;
          
      return $item;

    }

   /**
	 * Add action links in the plugin meta row
	 *
	 * @since    1.4
	 */
	
    public function plugin_links( $plugin_actions, $plugin_file ){ 
     
     $new_actions = array();
     
	 if ( SIMPLEFORM_SUBMISSIONS_BASENAME === $plugin_file ) { 
     $new_actions['sform_settings'] = '<a href="' . menu_page_url( 'sform-entries', false ) . '">' . __('Dashboard', 'simpleform') . '</a> | <a href="' . menu_page_url( 'sform-settings', false ) . '">' . __('Settings', 'simpleform') . '</a>';
	 }

     return array_merge( $new_actions, $plugin_actions );

    }
    
	/**
	 * When user is on a SimpleForm related admin page, display footer text.
	 *
	 * @since    1.4.1
	 */

	public function admin_footer($text) {
		
      $settings = get_option('sform_settings');
      $admin_notices = ! empty( $settings['admin_notices'] ) ? esc_attr($settings['admin_notices']) : 'false';	

	  if ( $admin_notices == 'false' ) {
		global $current_screen;
	    global $wpdb;
        $table_name = $wpdb->prefix . 'sform_submissions'; 
        $count_all = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

		if ( ! empty( $current_screen->id ) && strpos( $current_screen->id, 'sform' ) !== false && strpos( $current_screen->id, 'sform-support' ) === false && $count_all > 2 ) {
			$plugin = '<strong>SimpleForm</strong>';
			$url1  = '<a href="https://wordpress.org/support/plugin/simpleform/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a>';
			$url2  = '<a href="https://wordpress.org/support/plugin/simpleform/reviews/?filter=5#new-post" target="_blank" rel="noopener noreferrer">WordPress.org</a>';
			$url3  = '<a href="https://wordpress.org/support/plugin/simpleform/" target="_blank" rel="noopener noreferrer">Forum</a>';
			/* translators: $1$s: SimpleForm plugin name; $2$s: WordPress.org review link; $3$s: WordPress.org review link; $4$s: WordPress.org support forum link. */
			$text = '<span id="footer-thankyou">' . sprintf( __( 'Do you like %1$s? Support its further development by leaving us a %2$s rating on %3$s. Found an issue or have a feature suggestion, please tell on %4$s. Thanks in advance!', 'simpleform-contact-form-submissions' ), $plugin, $url1, $url2, $url3 ) . '</span>';
		}
      }	  

      else { 
	      $wptext = sprintf( __( 'Thank you for creating with <a href="%s">WordPress</a>.' ), __( 'https://wordpress.org/' ));
	      $text = '<span id="footer-thankyou">'.$wptext.'</span>'; 
	  }	
        
	  return $text;
		
	}
	
    /**
	 * Add message in the plugin meta row if core plugin is missing
	 *
	 * @since    1.4.4
	 */
	
    public function plugin_meta( $plugin_meta, $file ) {

	  $plugin_file = 'simpleform/simpleform.php';
	  
      if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file )  && strpos( $file, SIMPLEFORM_SUBMISSIONS_BASENAME ) !== false ) {

 	  $plugin_url =  __( 'https://wordpress.org/plugins/simpleform/' );
      $message = '<a href="'.esc_url($plugin_url).'" target="_blank" style="color: orangered !important;">' . __('Install the SimpleForm plugin to allow this addon to work', 'simpleform-contact-form-submissions' ) . '</a>';
	  $plugin_meta[] = $message;
	  
	  }
				
	  return $plugin_meta;

	}

    /**
	 * Add plugin upgrade notification
	 *
	 * @since    1.5
	 */
	
    public function upgrade_notification( $plugin_data, $new_data ) {

	  $plugin_file = 'simpleform/simpleform.php';	  
     
      if ( isset( $plugin_data['update'] ) && $plugin_data['update'] && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {

	    $simpleform_data = get_plugin_data( WP_PLUGIN_DIR.'/simpleform/simpleform.php');
	    $version = '<b>'. SIMPLEFORM_VERSION_REQUIRED .'</b>';
        $message = sprintf( __( 'The new version requires SimpleForm version %s or greater installed. Please update SimpleForm to make it work properly!', 'simpleform-contact-form-submissions' ), $version );

        if ( version_compare ( $simpleform_data['Version'], SIMPLEFORM_VERSION_REQUIRED, '<') ) {
          echo '<br><span style="margin-left:26px"><b>'.__( 'Upgrade Notice', 'simpleform-contact-form-submissions' ).':</b> '.$message.'</span>';
        }
        
      }

	}
	
	/**
	 * Remove all unnecessary parameters leaving the original URL used before performing an action
	 *
	 * @since    1.6
	 */
    
    public function url_cleanup() {
	    
      global $sform_entries;
      $screen = get_current_screen();      
      if(!is_object($screen) || $screen->id != $sform_entries)
      return;
      
      $sform_list_table = new SForms_Submissions_List_Table();
      $doaction = $sform_list_table->current_action();
      
      if ( $doaction ) {

		  $referer_url = wp_get_referer();
		  if ( ! $referer_url ) {
		  $referer_url = admin_url( 'admin.php?page=sform-entries' );
	      }
	      
		  $view = isset(explode('&view=', $referer_url)[1]) ? explode('&', explode('&view=', $referer_url)[1])[0] : 'inbox';
          $sform_list_table->prepare_items();
          if ( $view == 'inbox' ) { $filter_by_view = "status != 'trash' AND status != 'spam'"; }
          if ( $view == 'trash' ) { $filter_by_view = "status = 'trash'"; }
          if ( $view == 'new' ) { $filter_by_view = "status = 'new'"; }
          if ( $view == 'spam' ) { $filter_by_view = "status = 'spam'"; }
          $where = $sform_list_table->get_query_conditions();
          $placeholders = $sform_list_table->get_query_placeholders();
          global $wpdb;
          $count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions $where AND $filter_by_view", $placeholders ) );
          $paged = isset( $_REQUEST['paged'] ) ? absint($_REQUEST['paged']) : '1';        
          $per_page = $sform_list_table->get_items_per_page('edit_submission_per_page', 20);
          $total_pages = ceil( $count / $per_page );
          if ( $paged > $total_pages ) { $pagenum = $total_pages; } 
		  else { $pagenum = $paged; }

          $url = remove_query_arg( array('paged', 'action', 'action2', 'id', '_wpnonce', '_wp_http_referer'), $referer_url );
		  if ( $pagenum > '1')
       	  $url = add_query_arg( 'paged', $pagenum, $url );

          wp_redirect($url);
          exit(); 
      
      }
      
      if ( ! empty( $_REQUEST['_wp_http_referer'] ) && ! $doaction ) {
	       
	      $referer_url = wp_get_referer();
		  if ( ! $referer_url ) {
		  $referer_url = stripslashes($_SERVER['REQUEST_URI']);
	      }
  
		  $referer_view = isset(explode('&view=', $referer_url)[1]) ? explode('&', explode('&view=', $referer_url)[1])[0] : 'inbox';
	      $view = $referer_view != 'inbox' ? '&view='.$referer_view : '';
	      $removed_args = array( 'action', 'action2', '_wp_http_referer', '_wpnonce' );
	      
	      if ( empty( $_REQUEST['date'] ) )
 	      $removed_args = array_merge($removed_args,array('date'));
         
 	      if ( empty( $_REQUEST['s'] ) )
 	      $removed_args = array_merge($removed_args,array('s'));

  	      if ( ! empty( $_REQUEST['paged'] ) && $_REQUEST['paged'] <= '1' )
 	      $removed_args = array_merge($removed_args,array('paged'));

          $url = remove_query_arg($removed_args, $_SERVER['REQUEST_URI']) . $view;
          wp_redirect($url);
          exit;
          
       } 
             
    }
    
    /**
	 * Add support links in the plugin meta row
	 *
	 * @since    1.6.2
	 */
	
    public function support_link( $plugin_meta, $file ) {

      if ( strpos( $file, SIMPLEFORM_SUBMISSIONS_BASENAME ) !== false ) {
		$plugin_meta[] = '<a href="https://wordpress.org/support/plugin/simpleform-contact-form-submissions/" target="_blank">'.__('Support', 'simpleform-contact-form-submissions').'</a>';
		}
		
	  return $plugin_meta;

	}
	
    /**
	 * Show the parent menu active for hidden sub-menu item
	 *
	 * @since    1.6.4
	 */
	
    public function contacts_menu_open($parent_file) {

      global $plugin_page;

      if ( $plugin_page === 'sform-entrie' ) {
        $plugin_page = 'sform-entries';
      } 
    
      return $parent_file;
      
    }
    
	/**
	 * Display a notice in case of hidden submissions
	 *
	 * @since    2.1
	 */

    public function hidden_submissions_notice($notice,$id){
     
     global $wpdb;
     $hidden_messages = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE listable != '1' AND object != '' AND object != 'not stored'");
	 $notice = $hidden_messages > 0 && $id == '' ? '<span id="storing-notice" class="dashicons dashicons-warning" style="margin-left: 5px; opacity: 0.25; cursor: pointer; width: 30px; padding-right: 5px;"></span>' : '';	
     
     return $notice;

    }
    
}