<?php
	
/**
 * Defines the public-specific functionality of the plugin.
 *
 * @since      1.0
 */

class SimpleForm_Submissions_Public {

	/**
	 * The name of this plugin.
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
	 * Get the client IP address.
	 *
	 * @since    1.0
	 */
	 
	public function get_client_ip() {
		
     // Nothing to do without any reliable information
    if (!isset ($_SERVER['REMOTE_ADDR'])) {
        $client_ip = 'UNKNOWN';
    }
    else {
      // Fetch the IP address when user is from shared Internet services
      if(isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '127.0.0.1') {
         $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
      }
      // Fetch the IP address when user is behind the proxy
      elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1') {
         $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
      }
      elseif(isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] != '127.0.0.1') {
         $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
      }
      elseif(isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] != '127.0.0.1') {
         $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
      }
      elseif(isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] != '127.0.0.1') {
         $ipaddress = $_SERVER['HTTP_FORWARDED'];
      }
      // In all other cases, REMOTE_ADDR is the only IP we can trust
      else {
         $ipaddress = $_SERVER['REMOTE_ADDR'];
      }
      // Check for multiple IP addresses that are passed through
      $ip_list = explode(',', $ipaddress);
         // Only last IP in the list) can be trusted
         if(isset($ip_list[1])) {
         $ipaddress = trim($ip_list[0]);
      }
      // Validate IP
      $client_ip = filter_var($ipaddress, FILTER_VALIDATE_IP) ? $ipaddress : 'INVALID';
      return $client_ip;
    }

    }

	/**
	 * Change form data values when form is submitted.
	 *
	 * @since    1.0
	 */ 
	 
     public function add_storing_fields_values($form_values, $form_id, $name, $requester_lastname, $email, $phone, $request_subject, $request, $flagged) { 

      if ( $form_id == '1' ) {
       $settings = get_option('sform_settings');
	  } else {
       $settings_option = get_option('sform_'. $form_id .'_settings');
       $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
      }

      $data_storing = ! empty( $settings['data_storing'] ) ? esc_attr($settings['data_storing']) : 'true';	
      $ip_storing = ! empty( $settings['ip_storing'] ) ? esc_attr($settings['ip_storing']) : 'true';	
      $ip_address = $ip_storing == 'true' ? $this->get_client_ip() : 'not stored';

	  if ( ! empty($name) ) { $requester_name = $name; }
      else {
	    if ( is_user_logged_in() ) {
		global $current_user;
		$requester_name = ! empty($current_user->user_name) ? $current_user->user_name : $current_user->display_name;
        }
        else { $requester_name = ''; }
      }
      
      $submitter = $requester_name != '' ? $requester_name : __( 'Anonymous', 'simpleform-contact-form-submissions' );

      if ( ! empty($email) ) { $requester_email = $email; }
      else {
	    if ( is_user_logged_in() ) {
		global $current_user;
		$requester_email = $current_user->user_email;
        }
        else { $requester_email = ''; }
      }

      $extra_values = array();
      $status = empty($flagged) ? 'new' : 'spam';      
      $listable = $data_storing == 'true' ? '1' : '0';
      
      // NEEDS TO FIX IF MOVING ENABLED ?
      
      if ( $data_storing == 'true' ) {
       $form_values = array(
       "name" => $submitter,
       "lastname" => $requester_lastname,
       "email" => $requester_email,
       "phone" => $phone,
       "subject" => $request_subject,
       "object" => $request,
       "ip" => $ip_address, 
       "status" => $status,
       "listable" => $listable
       );
      }    
      else {
       $form_values = array( 
       "name" => 'not stored',
       "lastname" => 'not stored',
       "email" => 'not stored',
       "phone" => 'not stored',
       "subject" => 'not stored',
       "object" => 'not stored',
       "ip" => 'not stored',
       "status" => '',
       "listable" => $listable
       ); 
      }   
     
      $form_data = array_merge($form_values, apply_filters( 'sform_akismet_values', $extra_values, $form_id, $name, $email ));
      
      return  $form_data;
     
     }

	/**
	 * Display confirmation message if notification email has been disabled.
	 *
	 * @since    1.0
	 */

     public function sform_display_message( $form_id, $mailing, $redirect, $redirect_url, $thank_you_message, $server_error_message ){ 
	 
      if ( $form_id == '1' ) {
       $settings = get_option('sform_settings');
	  } else {
       $settings_option = get_option('sform_'. $form_id .'_settings');
       $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
      }

      $data_storing = ! empty( $settings['data_storing'] ) ? esc_attr($settings['data_storing']) : 'true';	

      if ( $data_storing == 'true' || $mailing == 'true' ) {
       echo json_encode(array('error' => false, 'redirect' => $redirect, 'redirect_url' => $redirect_url, 'notice' => $thank_you_message ));
	   exit;
      }
      else { 
       echo json_encode(array('error' => true, 'notice' => $server_error_message, 'showerror' => true ));
	   exit;
      }
	 
     }	     
          
	/**
	 * Display confirmation message if notification email has been disabled and ajax is disabled.
	 *
	 * @since    1.0
	 */

    public function sform_display_post_message( $form_id, $mailing ){ 
	
     if ( $form_id == '1' ) {
      $settings = get_option('sform_settings');
	 } else {
      $settings_option = get_option('sform_'. $form_id .'_settings');
      $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
     }
     
     $data_storing = ! empty( $settings['data_storing'] ) ? esc_attr($settings['data_storing']) : 'true';	

     if ( $data_storing == 'true' || $mailing == 'true' ) { $error = ''; }
     else { $error = 'server_error'; }
	
     return $error;

    }	

} 