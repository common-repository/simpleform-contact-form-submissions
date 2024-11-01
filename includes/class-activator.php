<?php

/**
 * The class instantiated during the plugin activation.
 *
 * @since      1.0
 */

class SimpleForm_Submissions_Activator {

	/**
     * Run default functionality during plugin activation.
     *
     * @since    1.0
     */

    public static function activate($network_wide) {
	    
     if ( function_exists('is_multisite') && is_multisite() ) {
	  if($network_wide) {
        global $wpdb;
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
         switch_to_blog( $blog_id );
         self::change_db();
         self::entries_data_recovery();
         self::sform_submissions_settings();
         restore_current_blog();
        }
      } else {
         self::change_db();
         self::entries_data_recovery();
         self::sform_submissions_settings();
      }
     } else {
        self::change_db();
        self::entries_data_recovery();
        self::sform_submissions_settings();
     }
    
    }
    
    /**
     *  Save initial settings.
     *
     * @since    1.4
     */

    public static function sform_submissions_settings() {
	    
	   $main_settings = get_option('sform_settings');

       if( $main_settings ) {
	       	       
	      if ( isset($main_settings['data_storing']) && in_array($main_settings['data_storing'], array('true', 'false')) )
          return;

          $new_settings = array(
	             'data_storing' => 'true',
	             'ip_storing' => 'true',
                 'data_columns' => 'subject,firstname,message,mail,date',
                 'counter' => 'true',
                 'deleting_messages' => 'false',
          ); 
 	    
          $settings = array_merge($main_settings,$new_settings);
          update_option('sform_settings', $settings);
          
          global $wpdb;	      
          $listable_data = $wpdb->get_col("SELECT listable FROM {$wpdb->prefix}sform_submissions");
          
          if(count($listable_data) != 0) { $wpdb->query("UPDATE {$wpdb->prefix}sform_submissions SET listable = '1'"); }
          
          $shortcodes_table = $wpdb->prefix . 'sform_shortcodes';
          if ( $result = $wpdb->get_results("SHOW TABLES LIKE '".$shortcodes_table."'") ) {
             $ids = $wpdb->get_col("SELECT id FROM `$shortcodes_table` WHERE id != '1'");	
             if ( $ids ) {
	          foreach ( $ids as $id ) {
	          $old_form_settings = get_option('sform_'.$id.'_settings');
              if ( $old_form_settings != false ) {
              $form_settings = array_merge($old_form_settings,$new_settings);
              update_option('sform_'.$id.'_settings', $form_settings); 
              }
              }
             }
          }             

       }
              
    }

    /**
     * Modifies the database table.
     *
     * @since    1.0
     */
 
    public static function change_db() {

        $current_version = SIMPLEFORM_SUBMISSIONS_DB_VERSION;
        $installed_version = get_option('sform_sub_db_version');
       
        if ( $installed_version != $current_version ) {
        
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
          $submissions_table = $prefix . 'sform_submissions';
          $sql = "CREATE TABLE {$submissions_table} (
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
            subject tinytext NOT NULL,
            object text NOT NULL,
            date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status tinytext NOT NULL,
            previous_status varchar(32) NOT NULL default '',
            trash_date datetime NULL,
            notes text NULL,
            listable tinyint(1) NOT NULL DEFAULT 1,
            hidden tinyint(1) NOT NULL DEFAULT '0',
            movable tinyint(1) NOT NULL DEFAULT '0',         
            PRIMARY KEY  (id)
          ) ". $charset_collate .";";
          dbDelta($sql);
          update_option('sform_sub_db_version', $current_version);
        }
   
    }
    
    /**
     * Edit entries recovering data from last received messages.
     *
     * @since    1.0
     */

    public static function entries_data_recovery() {
	  
        global $wpdb;
        $submissions_table = $wpdb->prefix . 'sform_submissions';
        if ( $result = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}sform_submissions'") ) {
          $forms = $wpdb->get_col("SELECT DISTINCT form FROM {$wpdb->prefix}sform_submissions");
          if ( $forms ) {
	        foreach ( $forms as $form ) {
              $last_entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sform_submissions WHERE form = %d ORDER BY date DESC LIMIT 1", $form) );
              $before_last_entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}sform_submissions WHERE form = %d ORDER BY date DESC LIMIT 1 OFFSET 1", $form) );
              $last_message = get_option("sform_last_{$form}_message") != false ? explode('#', get_option("sform_last_{$form}_message") ) : '';
              $before_last_message = get_option("sform_before_last_{$form}_message") != false ? explode('#', get_option("sform_before_last_{$form}_message") ) : '';
              $forwarded_last_message = get_option("sform_forwarded_last_{$form}_message") != false ? explode('#', get_option("sform_forwarded_last_{$form}_message") ) : '';
              $forwarded_before_last_message = get_option("sform_forwarded_before_last_{$form}_message") != false ? explode('#', get_option("sform_forwarded_before_last_{$form}_message") ) : '';
              $direct_last_message = get_option("sform_direct_last_{$form}_message") != false ? explode('#', get_option("sform_direct_last_{$form}_message") ) : '';
              $direct_before_last_message = get_option("sform_direct_before_last_{$form}_message") != false ? explode('#', get_option("sform_direct_before_last_{$form}_message") ) : '';
              $moved_last_message = get_option("sform_moved_last_{$form}_message") != false ? explode('#', get_option("sform_moved_last_{$form}_message") ) : '';
              $moved_before_last_message = get_option("sform_moved_before_last_{$form}_message") != false ? explode('#', get_option("sform_moved_before_last_{$form}_message") ) : '';
              $last_message_timestamp = $last_message && is_numeric($last_message[0]) ? $last_message[0] : '';
              $before_last_message_timestamp = $before_last_message && is_numeric($before_last_message[0]) ? $before_last_message[0] : '';
              $forwarded_last_message_timestamp = $forwarded_last_message && is_numeric($forwarded_last_message[0]) ? $forwarded_last_message[0] : '';
              $forwarded_before_last_message_timestamp = $forwarded_before_last_message && is_numeric($forwarded_before_last_message[0]) ? $forwarded_before_last_message[0] : '';
              $direct_last_message_timestamp = $direct_last_message && is_numeric($direct_last_message[0]) ? $direct_last_message[0] : '';
              $direct_before_last_message_timestamp = $direct_before_last_message && is_numeric($direct_before_last_message[0]) ? $direct_before_last_message[0] : '';
              $moved_last_message_timestamp = $moved_last_message && is_numeric($moved_last_message[0]) ? $moved_last_message[0] : '';
              $moved_before_last_message_timestamp = $moved_before_last_message && is_numeric($moved_before_last_message[0]) ? $moved_before_last_message[0] : '';
              $dates = array();
              $dates[$last_message_timestamp] = $last_message_timestamp && isset($last_message[1]) ? $last_message[1] : '';
              $dates[$before_last_message_timestamp] = $before_last_message_timestamp && isset($before_last_message[1]) ? $before_last_message[1] : '';
              $dates[$forwarded_last_message_timestamp] = $forwarded_last_message_timestamp && isset($forwarded_last_message[1]) ? $forwarded_last_message[1] : '';
              $dates[$forwarded_before_last_message_timestamp] = $forwarded_before_last_message_timestamp && isset($forwarded_before_last_message[1]) ? $forwarded_before_last_message[1] : '';
              $dates[$direct_last_message_timestamp] = $direct_last_message_timestamp && isset($direct_last_message[1]) ? $direct_last_message[1] : '';
              $dates[$direct_before_last_message_timestamp] = $direct_before_last_message_timestamp && isset($direct_before_last_message[1]) ? $direct_before_last_message[1] : '';
              $dates[$moved_last_message_timestamp] = $moved_last_message_timestamp && isset($moved_last_message[1]) ? $moved_last_message[1] : '';
              $dates[$moved_before_last_message_timestamp] = $moved_before_last_message_timestamp && isset($moved_before_last_message[1]) ? $moved_before_last_message[1] : '';
              // Remove empty array elements
              $dates = array_filter($dates);
              if ( $last_entry && esc_attr($last_entry->object) == '' ) {
                $last_date = esc_attr($last_entry->date);
                $entry_id = esc_attr($last_entry->id);
                if ( array_key_exists(strtotime($last_date), $dates) ) {
	              $message = $dates[strtotime($last_date)];
                  $split_mail = explode('&nbsp;&nbsp;&lt;&nbsp;', $message);
	              $email = isset($split_mail[1]) ? explode('&nbsp;&gt;', $split_mail[1])[0] : '';
	              $name_separator =  strpos($message, ':</td><td>') !== false ? __('From', 'simpleform') . ':</td><td>' : __('From', 'simpleform') . ':</b>&nbsp;&nbsp;';
	              $separator =  strpos($message, ':</td><td>') !== false ? '</td>' : '<br>';
                  $split_name = explode($name_separator, $message);
                  $email_separator = ! empty($email) ? '&nbsp;&nbsp;&lt;&nbsp;' : $separator;                  
	              $name = isset($split_name[1]) ? explode($email_separator, $split_name[1])[0] : '';
	              $phone_separator =  strpos($message, ':</td><td>') !== false ? __('Phone', 'simpleform') . ':</td><td>' : __('Phone', 'simpleform') . ':</b>&nbsp;&nbsp;';
                  $split_phone = explode($phone_separator, $message);
	              $phone = isset($split_phone[1]) ? explode($separator, $split_phone[1])[0] : '';
	              $subject_separator =  strpos($message, ':</td><td>') !== false ? __('Subject', 'simpleform') . ':</td><td>' : __('Subject', 'simpleform') . ':</b>&nbsp;&nbsp;';
	              $split_subject = explode($subject_separator, $message);
	              $subject = isset($split_subject[1]) ? explode($separator, $split_subject[1])[0] : '';
	              $object_separator = strpos($message, ':</td><td>') !== false ? __('Message', 'simpleform') . ':</td><td>' : __('Message', 'simpleform') . ':</b>&nbsp;&nbsp;';
                  $split_object = explode($object_separator, $message);
	              $closing_separator =  strpos($message, ':</td><td>') !== false ? '</td>' : '</div>';
	              $object = isset($split_object[1]) ? explode($closing_separator, $split_object[1])[0] : '';
		          $wpdb->update( $wpdb->prefix . 'sform_submissions', array( 'name' => strip_tags($name), 'email' => strip_tags($email), 'phone' => $phone, 'subject' => $subject, 'object' => $object, 'status' => 'read' ), array('id' => $entry_id ) );
	            } 
              } 
              if ( $before_last_entry && esc_attr($before_last_entry->object) == '' ) {
                $before_last_date = esc_attr($before_last_entry->date);
                $before_entry_id = esc_attr($before_last_entry->id);
                if ( array_key_exists(strtotime($before_last_date), $dates) ) {
	              $message = $dates[strtotime($before_last_date)];
                  $split_mail = explode('&nbsp;&nbsp;&lt;&nbsp;', $message);
	              $email = isset($split_mail[1]) ? explode('&nbsp;&gt;', $split_mail[1])[0] : '';
	              $name_separator = strpos($message, ':</td><td>') !== false ? __('From', 'simpleform') . ':</td><td>' : __('From', 'simpleform') . ':</b>&nbsp;&nbsp;';
	              $separator =  strpos($message, ':</td><td>') !== false ? '</td>' : '<br>';
                  $split_name = explode($name_separator, $message);
                  $email_separator = ! empty($email) ? '&nbsp;&nbsp;&lt;&nbsp;' : $separator;                  
	              $name = isset($split_name[1]) ? explode($email_separator, $split_name[1])[0] : '';
	              $phone_separator = strpos($message, ':</td><td>') !== false ? __('Phone', 'simpleform') . ':</td><td>' : __('Phone', 'simpleform') . ':</b>&nbsp;&nbsp;';
                  $split_phone = explode($phone_separator, $message);
	              $phone = isset($split_phone[1]) ? explode($separator, $split_phone[1])[0] : '';
	              $subject_separator = strpos($message, ':</td><td>') !== false ? __('Subject', 'simpleform') . ':</td><td>' : __('Subject', 'simpleform') . ':</b>&nbsp;&nbsp;';
	              $split_subject = explode($subject_separator, $message);
	              $subject = isset($split_subject[1]) ? explode($separator, $split_subject[1])[0] : '';
	              $object_separator = strpos($message, ':</td><td>') !== false ? __('Message', 'simpleform') . ':</td><td>' : __('Message', 'simpleform') . ':</b>&nbsp;&nbsp;';
                  $split_object = explode($object_separator, $message);
	              $closing_separator = strpos($message, ':</td><td>') !== false ? '</td>' : '</div>';
	              $object = isset($split_object[1]) ? explode($closing_separator, $split_object[1])[0] : '';
		          $wpdb->update( $wpdb->prefix . 'sform_submissions', array( 'name' => strip_tags($name), 'email' => strip_tags($email), 'phone' => $phone, 'subject' => $subject, 'object' => $object, 'status' => 'read' ), array('id' => $before_entry_id ) );
	            } 
              } 
            }
          }
        }             

    }


    /**
     *  Create a table whenever a new blog is created in a WordPress Multisite installation.
     *
     * @since    1.0
     */

    public static function on_create_blog($params) {
       
       if ( is_plugin_active_for_network( 'simpleform-submissions/simpleform-submissions.php' ) ) {
       switch_to_blog( $params->blog_id );
       self::change_db();
       self::entries_data_recovery();
       self::sform_submissions_settings();
       restore_current_blog();
       }

    }    
  
}