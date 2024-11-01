<?php
	
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the general utilities class.

 *
 * @since      2.0.1
 */

class SimpleForm_Submissions_Util {

	/**
	 * Update the last messages
     *
	 * @since    2.0.1
	 */
	
	public static function update_last_messages( $entry, $moved_from, $moveto ) {
	
       $dates = array();
       $form_last_message = get_option("sform_last_{$moved_from}_message") != false ? explode('#', get_option("sform_last_{$moved_from}_message") ) : '';
       $before_last_message = get_option("sform_before_last_{$moved_from}_message") != false ? explode('#', get_option("sform_before_last_{$moved_from}_message") ) : '';
       $forwarded_last_message = get_option("sform_forwarded_last_{$moved_from}_message") != false ? explode('#', get_option("sform_forwarded_last_{$moved_from}_message") ) : '';
       $forwarded_before_last_message = get_option("sform_forwarded_before_last_{$moved_from}_message") != false ? explode('#', get_option("sform_forwarded_before_last_{$moved_from}_message") ) : '';
       $direct_last_message = get_option("sform_direct_last_{$moved_from}_message") != false ? explode('#', get_option("sform_direct_last_{$moved_from}_message") ) : '';
       $direct_before_last_message = get_option("sform_direct_before_last_{$moved_from}_message") != false ? explode('#', get_option("sform_direct_before_last_{$moved_from}_message") ) : '';
       $moved_last_message = get_option("sform_moved_last_{$moved_from}_message") != false ? explode('#', get_option("sform_moved_last_{$moved_from}_message") ) : '';
       $moved_before_last_message = get_option("sform_moved_before_last_{$moved_from}_message") != false ? explode('#', get_option("sform_moved_before_last_{$moved_from}_message") ) : '';
       $last_message_timestamp = $form_last_message && is_numeric($form_last_message[0]) ? $form_last_message[0] : '';
       $before_last_message_timestamp = $before_last_message && is_numeric($before_last_message[0]) ? $before_last_message[0] : '';
       $forwarded_last_message_timestamp = $forwarded_last_message && is_numeric($forwarded_last_message[0]) ? $forwarded_last_message[0] : '';
       $forwarded_before_last_message_timestamp = $forwarded_before_last_message && is_numeric($forwarded_before_last_message[0]) ? $forwarded_before_last_message[0] : '';
       $direct_last_message_timestamp = $direct_last_message && is_numeric($direct_last_message[0]) ? $direct_last_message[0] : '';
       $direct_before_last_message_timestamp = $direct_before_last_message && is_numeric($direct_before_last_message[0]) ? $direct_before_last_message[0] : '';
       $moved_last_message_timestamp = $moved_last_message && is_numeric($moved_last_message[0]) ? $moved_last_message[0] : '';
       $moved_before_last_message_timestamp = $moved_before_last_message && is_numeric($moved_before_last_message[0]) ? $moved_before_last_message[0] : '';
       $dates[$last_message_timestamp] = $last_message_timestamp && isset($form_last_message[1]) ? $form_last_message[1] : '';
       $dates[$before_last_message_timestamp] = $before_last_message_timestamp && isset($before_last_message[1]) ? $before_last_message[1] : '';
       $dates[$forwarded_last_message_timestamp] = $forwarded_last_message_timestamp && isset($forwarded_last_message[1]) ? $forwarded_last_message[1] : '';
       $dates[$forwarded_before_last_message_timestamp] = $forwarded_before_last_message_timestamp && isset($forwarded_before_last_message[1]) ? $forwarded_before_last_message[1] : '';
       $dates[$direct_last_message_timestamp] = $direct_last_message_timestamp && isset($direct_last_message[1]) ? $direct_last_message[1] : '';
       $dates[$direct_before_last_message_timestamp] = $direct_before_last_message_timestamp && isset($direct_before_last_message[1]) ? $direct_before_last_message[1] : '';
       $dates[$moved_last_message_timestamp] = $moved_last_message_timestamp && isset($moved_last_message[1]) ? $moved_last_message[1] : '';
       $dates[$moved_before_last_message_timestamp] = $moved_before_last_message_timestamp && isset($moved_before_last_message[1]) ? $moved_before_last_message[1] : '';
       // Remove empty array elements
       $dates = array_filter($dates);
           
	   if ( is_array($entry) ) {
	     foreach( $entry as $id ) {
           global $wpdb;
           $entry_date = $wpdb->get_var("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE id = '$id'");
           $entry_timestamp = strtotime($entry_date);
           if ( array_key_exists($entry_timestamp, $dates) ) { 
	         $moved_last_message_to = get_option("sform_moved_last_{$moveto}_message") != false ? get_option("sform_moved_last_{$moveto}_message") : '';
             $moved_before_last_message_to = get_option("sform_moved_before_last_{$moveto}_message") != false ? get_option("sform_moved_before_last_{$moveto}_message") : '';
             $moved_last_message_timestamp_to = $moved_last_message_to && is_numeric(explode('#', $moved_last_message_to)[0]) ? explode('#', $moved_last_message_to)[0] : '';
             $moved_before_last_message_timestamp_to = $moved_before_last_message_to && is_numeric(explode('#', $moved_before_last_message_to)[0]) ? explode('#', $moved_before_last_message_to)[0] : '';
             if ( $entry_timestamp > $moved_last_message_timestamp_to ) { 
               update_option("sform_moved_last_{$moveto}_message", $dates[$entry_timestamp]);
               if ( $moved_last_message_to ) { 
           	     update_option("sform_moved_before_last_{$moveto}_message", $moved_last_message_to);
               }
             }
             else { 
               if ( $entry_timestamp > $moved_before_last_message_timestamp_to ) { 
                 update_option("sform_moved_before_last_{$moveto}_message", $dates[$entry_timestamp]);
               }
             }
           } 
         }
	   }
	   
	   else {
         if ( array_key_exists($entry, $dates) ) { 
	       $moved_last_message_to = get_option("sform_moved_last_{$moveto}_message") != false ? get_option("sform_moved_last_{$moveto}_message") : '';
           $moved_before_last_message_to = get_option("sform_moved_before_last_{$moveto}_message") != false ? get_option("sform_moved_before_last_{$moveto}_message") : '';
           $moved_last_message_timestamp_to = $moved_last_message_to && is_numeric(explode('#', $moved_last_message_to)[0]) ? explode('#', $moved_last_message_to)[0] : '';
           $moved_before_last_message_timestamp_to = $moved_before_last_message_to && is_numeric(explode('#', $moved_before_last_message_to)[0]) ? explode('#', $moved_before_last_message_to)[0] : '';
           if ( $entry > $moved_last_message_timestamp_to ) { 
             update_option("sform_moved_last_{$moveto}_message", $dates[$entry]);
             if ( $moved_last_message_to ) { 
           	   update_option("sform_moved_before_last_{$moveto}_message", $moved_last_message_to);
             }
           }
           else { 
             if ( $entry > $moved_before_last_message_timestamp_to ) { 
               update_option("sform_moved_before_last_{$moveto}_message", $dates[$entry]);
             }
           }
         } 
	   }
	   
	}

}
	
new SimpleForm_Submissions_Util();