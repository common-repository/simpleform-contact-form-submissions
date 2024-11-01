<?php

/**
 * The class instantiated during the plugin's deactivation.
 *
 * @since      1.0
 */

class SimpleForm_Submissions_Deactivator {

	/**
	 * Run during plugin deactivation.
	 *
	 * @since    1.0
	 */
	 
	public static function deactivate() {

	  // Resume the admin notification
	  $settings = get_option('sform_settings');

      if ( $settings ) {
	    $settings['notification'] = 'true';
        update_option('sform_settings', $settings);                       
      } 
      
      // Check if other forms have been activated
      global $wpdb; 
      if ( $result = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}sform_shortcodes'") ) {
        $ids = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}sform_shortcodes WHERE id != '1'");	
        if ( $ids ) {
        foreach($ids as $id) { 
	     $form_settings = get_option('sform_'. $id .'_settings');
         if ( $form_settings != false ) {
			  $form_settings['notification'] = 'true';
              update_option('sform_'. $id .'_settings', $form_settings); 
	     }
        }
        }
      }
      
      //  Re-calculation of messages assigned to forms
      $messages = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions");
         
      if ( $messages > 0 ) {
        $forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes" );
        foreach($forms as $form) {
	 	  $count_entries = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE form = '$form'");
 	 	  $count_moved = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions WHERE moved_from = '$form'");
  	      $update = $wpdb->update($wpdb->prefix . 'sform_shortcodes', array( 'entries' => $count_entries, 'moved_entries' => $count_moved, 'storing' => '1' ), array('id' => $form ) );
        }
        $entries = $wpdb->get_var("SELECT SUM(entries) as total_entries FROM {$wpdb->prefix}sform_shortcodes");
        if ( $entries == $messages ) {
          delete_option( 'sform_entries_view' );
        }
      }
      else  {
        $forms = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sform_shortcodes" );
        foreach($forms as $form) {
  	      $update = $wpdb->update($wpdb->prefix . 'sform_shortcodes', array( 'storing' => '1' ), array('id' => $form ) );
        }
        delete_option( 'sform_entries_view' );
      }
      
	}
      
}