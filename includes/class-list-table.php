<?php

/**
 * The customized class that extends the WP_List_Table class.
 *
 * @since      1.0
 */
		
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SForms_Submissions_List_Table extends WP_List_Table  {

	/**
	 * Override the parent constructor to pass our own arguments.
	 *
	 * @since    1.0
	 */

    function __construct() {
        parent::__construct(array(
           'singular' => 'sform-entrie',
           'plural' => 'sform-entries',
           'ajax'      => false 
        ));
    }
     
	/**
	 * Return string of conditions for MySQL query.
	 *
	 * @since    1.3
	 */   
	
    function get_query_conditions() { 
        $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';        
        if ( $form_id == '' ) {
        $settings = get_option('sform_settings');
	    } else {
        $settings_option = get_option('sform_'. $form_id .'_settings');
        $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
        }
        $ip_storing = ! empty( $settings['ip_storing'] ) ? esc_attr($settings['ip_storing']) : 'true';
    	$keyword = ( isset( $_REQUEST['s'] ) ) ? sanitize_text_field($_REQUEST['s']) : '';
        if( $keyword != '') {	 
          if ( $ip_storing == 'true'  ) { $where_keyword = 'WHERE object != %s AND object != %s AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR ip LIKE %s OR email LIKE %s OR phone LIKE %s)'; }
          else { $where_keyword = 'WHERE object != %s AND object != %s AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR email LIKE %s OR phone LIKE %s)'; }
        }
        else { $where_keyword = 'WHERE object != %s AND object != %s'; }
	    $date = ( isset( $_REQUEST['date'] ) ? sanitize_text_field($_REQUEST['date']) : '');
        if( $date != ''){	 
	      if ( $date == 'last_day' ) { $where_date = " AND ( date >= UTC_TIMESTAMP() - INTERVAL 24 HOUR )"; }
	      if ( $date == 'last_week' ) { $where_date = " AND ( date >= UTC_TIMESTAMP() - INTERVAL 7 DAY )"; }
	      if ( $date == 'last_month' ) { $where_date = " AND ( date >= UTC_TIMESTAMP() - INTERVAL 30 DAY )"; }
	      if ( $date == 'current_year' ) { $where_date = " AND ( YEAR(date) = YEAR(CURDATE()) )"; }
	      if ( $date == 'last_year' ) { $where_date = " AND ( date >= UTC_TIMESTAMP() - INTERVAL 1 YEAR )"; }
	      if ( $date != 'last_day' && $date != 'last_week' && $date != 'last_month' && $date != 'current_year' && $date != 'last_year' ) { $where_date = " AND ( YEAR(date) = %d )"; }
	    }
        else { $where_date = ""; }
        if( $form_id != ''){ $where_form = " AND (form = %d) AND (hidden = '0')"; }
        else { $where_form = " AND (form != '0') AND listable = '1' AND (hidden = '0')"; }
        $where = $where_keyword . $where_date . $where_form;
        return $where;
    }
     
	/**
	 * Return array of placeholders for MySQL query.
	 *
	 * @since    1.3
	 */   
	
    function get_query_placeholders() { 
        $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';
        if ( $form_id == '' ) {
        $settings = get_option('sform_settings');
        $form_placeholder = array();
	    } else {
        $settings_option = get_option('sform_'. $form_id .'_settings');
        $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
        $form_placeholder = array($form_id); 
        }
        $ip_storing = ! empty( $settings['ip_storing'] ) ? esc_attr($settings['ip_storing']) : 'true';
    	global $wpdb;
    	$keyword = isset( $_REQUEST['s'] ) ? sanitize_text_field($_REQUEST['s']) : '';
		$search = !empty($keyword) ? '%'.$wpdb->esc_like($keyword).'%' : '';						
		$value1 = ''; 
		$value2 = 'not stored'; 
        if( $keyword != ''){	 
          if ( $ip_storing == 'true'  ) { $placeholders_array = array($value1, $value2, $search, $search, $search, $search, $search, $search, $search); }
          else { $placeholders_array = array($value1, $value2, $search, $search, $search, $search, $search, $search); }
        }
        else { $placeholders_array = array($value1, $value2); }
	    $date = ( isset( $_REQUEST['date'] ) ? sanitize_text_field($_REQUEST['date']) : '');
        if( $date != ''){	 
	      if ( $date == 'last_day' ) { $date_placeholders = array(); }
	      if ( $date == 'last_week' ) { $date_placeholders = array(); }
	      if ( $date == 'last_month' ) { $date_placeholders = array(); }
	      if ( $date == 'current_year' ) { $date_placeholders = array(); }
	      if ( $date == 'last_year' ) { $date_placeholders = array(); }
	      if ( $date != 'last_day' && $date != 'last_week' && $date != 'last_month' && $date != 'current_year' && $date != 'last_year' ) { $date_placeholders = array($date); }
	    }
        else { $date_placeholders = array(); }
        $placeholders = array_merge($placeholders_array, $date_placeholders, $form_placeholder );
        return $placeholders;
    }
     
	/**
	 * Return a list of views available for the submissions.
	 *
	 * @since    1.3
	 */   
	
	function get_views() { 
				
        $views = array();
        $current = isset($_REQUEST['view']) ? sanitize_text_field($_REQUEST['view']) : 'inbox';
        $search_order = isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc')) ? $_REQUEST['order'] : '';
        $search_orderby = isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array('subject', 'email', 'date')) ? $_REQUEST['orderby'] : ''; 
	    global $wpdb;
        $where = $this->get_query_conditions();
        $placeholders = $this->get_query_placeholders();        
        $sql = $wpdb->prepare("SELECT status FROM {$wpdb->prefix}sform_submissions $where", $placeholders );
        $status_array = $wpdb->get_col($sql);
        $count_sql_inbox = count($status_array) - count(array_keys($status_array, "trash")) - count(array_keys($status_array, "spam"));
        $count_sql_new = count(array_keys($status_array, "new"));
        $count_sql_answered = count(array_keys($status_array, "answered"));        
        $count_sql_trashed = count(array_keys($status_array, "trash"));
        $count_sql_spam = count(array_keys($status_array, "spam"));
        $count_inbox = !is_null($count_sql_inbox) ? $count_sql_inbox : 0;
        $class = ($current == 'inbox' ? ' class="current"' :'');
        $all_url = remove_query_arg( array( 'view', 'paged' ) );
        $views['inbox'] = "<a id='view-all' href='{$all_url }' {$class} >".__( 'Inbox', 'simpleform-contact-form-submissions' )."</a> (" . $count_inbox . ")";   
        $count_new = !is_null($count_sql_new) ? $count_sql_new : 0;
        $class = ($current == 'new' ? ' class="current"' :'');
        $old_query_or_uri = remove_query_arg('paged');
        $new_url = add_query_arg( 'view','new', $old_query_or_uri);
        $views['new'] = "<a id='view-new' href='{$new_url }' {$class} >".__( 'Unread', 'simpleform-contact-form-submissions' )."</a> (" . $count_new . ")";
        $count_answered = !is_null($count_sql_answered) ? $count_sql_answered : 0;
        $class = ($current == 'answered' ? ' class="current"' :'');
        $old_query_or_uri = remove_query_arg('paged');
        $answered_url = add_query_arg( 'view','answered', $old_query_or_uri);
        $views['answered'] = "<a id='view-answered' href='{$answered_url }' {$class} >".__( 'Answered', 'simpleform-contact-form-submissions' )."</a> (" . $count_answered . ")";   
        $count_spam = !is_null($count_sql_spam) ? $count_sql_spam : 0;
        $class = ($current == 'spam' ? ' class="current"' :'');
        $old_query_or_uri = remove_query_arg('paged');
        $spam_url = add_query_arg( 'view','spam', $old_query_or_uri);
        $views['spam'] = "<a id='view-spam' href='{$spam_url }' {$class} >".__( 'Junk', 'simpleform-contact-form-submissions' )."</a> (" . $count_spam . ")";   
        $count_trashed = !is_null($count_sql_trashed) ? $count_sql_trashed : 0;
        $old_query_or_uri = remove_query_arg('paged');
        $trashed_url = add_query_arg( 'view','trash', $old_query_or_uri);
        $class = ($current == 'trash' ? ' class="current"' :'');
        $views['trash'] = "<a id='view-trash' href='{$trashed_url}' {$class} >".__( 'Trash', 'simpleform-contact-form-submissions' )."</a> (" . $count_trashed . ")";
        $sform_screen_options = $current . ';' . $count_inbox . ';' . $count_trashed . ';' . $search_order . ';' . $search_orderby . ';' . $count_new . ';' . $count_spam;
        update_option( 'sform_screen_options', $sform_screen_options, false );
        
	    // If no submissions are found
	    $count_alls = $count_sql_inbox + $count_sql_spam + $count_sql_trashed;  
        if ( $count_alls == '0' ) {
           return;
        }		
        
        return $views;
    }
    
	/**
	 * Define the columns that are going to be used in the table.
	 *
	 * @since    1.4
	 */

    function get_columns() {
      $columns = array('cb' => '<input type="checkbox" />');      
      $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';
      if ( $form_id == '' ) {
        $settings = get_option('sform_settings');
      } else {
        $settings_option = get_option('sform_'. $form_id .'_settings');
        $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
      }
	  $data_columns = ! empty( $settings['data_columns'] ) ? esc_attr($settings['data_columns']) : 'subject,firstname,message,mail,date';	
      if (strpos($data_columns,'id') !== false) { $columns['id'] = __('Request ID', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'subject') !== false) { $columns['subject'] = __('Subject', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'firstname') !== false) { $columns['firstname'] = __('Name', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'family') !== false) { $columns['family'] = __('Last Name', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'from') !== false) { $columns['from'] = __('From', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'message') !== false) { $columns['object'] = __('Message', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'mail') !== false) { $columns['email'] = __('Email', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'phone') !== false) { $columns['phone'] = __('Phone', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'ip') !== false) { $columns['ip'] = __('IP', 'simpleform-contact-form-submissions'); }
      if (strpos($data_columns,'date') !== false) { $columns['entry'] = __('Date', 'simpleform-contact-form-submissions'); }
      return $columns;    
    }    
      
	/**
	 * Text displayed when no record data is available.
	 *
	 * @since    1.0
	 */

    function no_items() {
      _e('No entries found', 'simpleform-contact-form-submissions');
    }    
    
	/**
	 * Render the checkbox column.
	 *
	 * @since    1.0
	 */

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />',esc_attr($item['id']));
    }
    
    
 	/**
	 * Render the name column.
	 *
	 * @since    1.4
	 */

    function column_firstname($item) {
	    $sender = $item['name'] != '' ? esc_attr($item['name']) : __('Anonymous', 'simpleform-contact-form-submissions' );
        return stripslashes($sender);
    }

	/**
	 * Render the lastname column.
	 *
	 * @since    1.4
	 */

    function column_family($item) {
	    $sender = $item['lastname'] != '' ? esc_attr($item['lastname']) : '-';
        return stripslashes($sender);
    }
   
	/**
	 * Render the from column.
	 *
	 * @since    1.0
	 */

    function column_from($item) {
	    $from = $item['name'] != '' || $item['lastname'] != '' ? trim(esc_attr($item['name']) . ' ' . esc_attr($item['lastname'])) : __('Anonymous', 'simpleform-contact-form-submissions' );
        return stripslashes($from);
    }

	/**
	 * Render the id column with actions.
	 *
	 * @since    1.0
	 */

    function column_id($item) {
	   $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';
       $table_view = isset( $_REQUEST['view'] ) ? sanitize_text_field($_REQUEST['view']) : 'inbox';
       $form = !empty( $form_id ) ? '&form=' . $form_id : '';
       $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? '&order=' . $_REQUEST['order'] : '';
       $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array('subject', 'email', 'date'))) ? '&orderby=' . $_REQUEST['orderby'] : ''; 
       $date = isset ( $_REQUEST['date'] ) && !empty( $_REQUEST['date'] ) ? '&date=' . sanitize_text_field($_REQUEST['date']) : '';
       $search = isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) ? '&s=' . sanitize_text_field($_REQUEST['s']) : '';
       $view = isset( $_REQUEST['view'] ) && !empty( $_REQUEST['view'] ) ? '&view=' . sanitize_text_field($_REQUEST['view']) : '';
	   $pagenum = isset( $_REQUEST['paged'] ) ? absint($_REQUEST['paged']) : 0;
       $current_page = $this->get_pagenum();
       $per_page = $this->get_items_per_page('edit_submission_per_page', 20);
       $sform_screen_options = get_option( 'sform_screen_options' );
 	   if ( $item['status'] == 'trash' )  {    
		 $sform_count_trashed = explode(';', $sform_screen_options)[2];
         $total_pages = ceil( ($sform_count_trashed - 1) / $per_page );
		 if ( $current_page > $total_pages ) { $pagenum = $total_pages; } 
		 else { $pagenum = $pagenum; }
		 $page = isset($pagenum) && $pagenum != 0 ? '&paged=' . $pagenum : '';
  	     $query_args_restored = array(
			'action'	=> 'untrash',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'restore_nonce' ),
		 );
         $restore_link = esc_url( add_query_arg( $query_args_restored ).$form.$view.$page.$date.$search.$orderby.$order);
 	     $query_args_delete = array(
			'action'	=> 'delete',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'delete_nonce' ),
		 );
		 $delete_link = esc_url( add_query_arg( $query_args_delete ).$form.$view.$page.$date.$search.$orderby.$order);
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
            'restore' => '<a href="' . $restore_link . '">' . __( 'Restore', 'simpleform-contact-form-submissions') . '</a>',
            'delete' => '<a href="' . $delete_link . '">' . __( 'Delete Permanently', 'simpleform-contact-form-submissions' ) . '</a>',            
         );        
        }
        elseif ( $item['status'] == 'spam' )  {   
 	     $sform_count_spam =  explode(';', $sform_screen_options)[6];
         $total_pages = ceil( ($sform_count_spam - 1) / $per_page );
		 if ( $current_page > $total_pages ) { $pagenum = $total_pages; } 
		 else { $pagenum = $pagenum; }
		 $page = isset($pagenum) && $pagenum != 0 ? '&paged=' . $pagenum : '';
    	 $query_args_unspam = array(
			'action'	=> 'unspam',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'restore_nonce' ),
		 );
		 $unspam_link = esc_url( add_query_arg( $query_args_unspam ).$form.$view.$page.$date.$search.$orderby.$order);
 	     $query_args_delete = array(
			'action'	=> 'delete',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'delete_nonce' ),
		 );
		 $delete_link = esc_url( add_query_arg( $query_args_delete ).$form.$view.$page.$date.$search.$orderby.$order);
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
            'restore' => '<a href="' . $unspam_link . '">' . __( 'Restore', 'simpleform-contact-form-submissions') . '</a>',           
'delete' => '<a href="' . $delete_link . '">' . __( 'Delete Permanently', 'simpleform-contact-form-submissions' ) . '</a>'
         );
         }
        elseif ( $item['status'] == 'answered' )  {   
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
         );
         }
        else  { 
	     $sform_count_inbox =  explode(';', $sform_screen_options)[1];
 	     $sform_count_new =  explode(';', $sform_screen_options)[5];
		 $total_pages = $table_view == 'new' ? ceil( ($sform_count_new - 1) / $per_page ) : ceil( ($sform_count_inbox - 1) / $per_page );
		 if ( $current_page > $total_pages ) { $pagenum = $total_pages; } 
		 else { $pagenum = $pagenum; }
		 $page = isset($pagenum) && $pagenum != 0 ? '&paged=' . $pagenum : '';
    	 $query_args_trash = array(
			'action'	=> 'trash',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'trash_nonce' ),
		 );
		 $trash_link = esc_url( add_query_arg( $query_args_trash ).$form.$view.$page.$date.$search.$orderby.$order );
    	 $query_args_spam = array(
			'action'	=> 'spam',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'spam_nonce' ),
		 );
		 $spam_link = esc_url( add_query_arg( $query_args_spam ).$form.$view.$page.$date.$search.$orderby.$order );
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
            'junk' => '<a href="' . $spam_link . '">' . __( 'Spam', 'simpleform-contact-form-submissions') . '</a>',           
            'trash' => '<a href="' . $trash_link . '">' . __( 'Trash', 'simpleform-contact-form-submissions' ) . '</a>'
         );
        }
        return sprintf('%s %s', $item['id'], $this->row_actions($actions));
    }

	/**
	 * Render the subject column with actions.
	 *
	 * @since    1.0
	 */

    function column_subject($item) {
	   $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';
       $table_view = isset( $_REQUEST['view'] ) ? sanitize_text_field($_REQUEST['view']) : 'inbox';
       $form = !empty( $form_id ) ? '&form=' . $form_id : '';
       $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? '&order=' . $_REQUEST['order'] : '';
       $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array('subject', 'email', 'date'))) ? '&orderby=' . $_REQUEST['orderby'] : ''; 
       $date = isset ( $_REQUEST['date'] ) && !empty( $_REQUEST['date'] ) ? '&date=' . sanitize_text_field($_REQUEST['date']) : '';
       $search = isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) ? '&s=' . sanitize_text_field($_REQUEST['s']) : '';
       $view = isset( $_REQUEST['view'] ) && !empty( $_REQUEST['view'] ) ? '&view=' . sanitize_text_field($_REQUEST['view']) : '';
	   $pagenum = isset( $_REQUEST['paged'] ) ? absint($_REQUEST['paged']) : 0;
       $current_page = $this->get_pagenum();
       $per_page = $this->get_items_per_page('edit_submission_per_page', 20);
       $sform_screen_options = get_option( 'sform_screen_options' );
 	   if ( $item['status'] == 'trash' )  {    
		 $sform_count_trashed = explode(';', $sform_screen_options)[2];
         $total_pages = ceil( ($sform_count_trashed - 1) / $per_page );
		 if ( $current_page > $total_pages ) { $pagenum = $total_pages; } 
		 else { $pagenum = $pagenum; }
		 $page = isset($pagenum) && $pagenum != 0 ? '&paged=' . $pagenum : '';
  	     $query_args_restored = array(
			'action'	=> 'untrash',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'restore_nonce' ),
		 );
         $restore_link = esc_url( add_query_arg( $query_args_restored ).$form.$view.$page.$date.$search.$orderby.$order );
 	     $query_args_delete = array(
			'action'	=> 'delete',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'delete_nonce' ),
		 );
		 $delete_link = esc_url( add_query_arg( $query_args_delete ).$form.$view.$page.$date.$search.$orderby.$order) ;
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
            'restore' => '<a href="' . $restore_link . '">' . __( 'Restore', 'simpleform-contact-form-submissions') . '</a>',
            'delete' => '<a href="' . $delete_link . '">' . __( 'Delete Permanently', 'simpleform-contact-form-submissions' ) . '</a>',            
         );        
        }
        elseif ( $item['status'] == 'spam' )  {   
 	     $sform_count_spam =  explode(';', $sform_screen_options)[6];
         $total_pages = ceil( ($sform_count_spam - 1) / $per_page );
		 if ( $current_page > $total_pages ) { $pagenum = $total_pages; } 
		 else { $pagenum = $pagenum; }
		 $page = isset($pagenum) && $pagenum != 0 ? '&paged=' . $pagenum : '';
    	 $query_args_unspam = array(
			'action'	=> 'unspam',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'restore_nonce' ),
		 );
		 $unspam_link = esc_url( add_query_arg( $query_args_unspam ).$form.$view.$page.$date.$search.$orderby.$order) ;
 	     $query_args_delete = array(
			'action'	=> 'delete',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'delete_nonce' ),
		 );
		 $delete_link = esc_url( add_query_arg( $query_args_delete ).$form.$view.$page.$date.$search.$orderby.$order );
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
            'restore' => '<a href="' . $unspam_link . '">' . __( 'Restore', 'simpleform-contact-form-submissions') . '</a>',           
'delete' => '<a href="' . $delete_link . '">' . __( 'Delete Permanently', 'simpleform-contact-form-submissions' ) . '</a>'
         );
        }
        elseif ( $item['status'] == 'answered' )  {   
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
         );
         }
        else  { 
	     $sform_count_inbox =  explode(';', $sform_screen_options)[1];
 	     $sform_count_new =  explode(';', $sform_screen_options)[5];
		 $total_pages = $table_view == 'new' ? ceil( ($sform_count_new - 1) / $per_page ) : ceil( ($sform_count_inbox - 1) / $per_page );
		 if ( $current_page > $total_pages ) { $pagenum = $total_pages; } 
		 else { $pagenum = $pagenum; }
		 $page = isset($pagenum) && $pagenum != 0 ? '&paged=' . $pagenum : '';
    	 $query_args_trash = array(
			'action'	=> 'trash',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'trash_nonce' ),
		 );
		 $trash_link = esc_url( add_query_arg( $query_args_trash ).$form.$view.$page.$date.$search.$orderby.$order );
    	 $query_args_spam = array(
			'action'	=> 'spam',
			'id'		=> esc_attr($item['id']),
			'_wpnonce'	=> wp_create_nonce( 'spam_nonce' ),
		 );
		 $spam_link = esc_url( add_query_arg( $query_args_spam ).$form.$view.$page.$date.$search.$orderby.$order );
         $actions = array(
            'view' => sprintf('<a href="?page='.$this->_args['singular'].'&id=%s'.$form.$view.'&paged='.$current_page.$date.$search.$orderby.$order.'">%s</a>', esc_attr($item['id']), __('View', 'simpleform-contact-form-submissions')),
            'junk' => '<a href="' . $spam_link . '">' . __( 'Spam', 'simpleform-contact-form-submissions') . '</a>',           
            'trash' => '<a href="' . $trash_link . '">' . __( 'Trash', 'simpleform-contact-form-submissions' ) . '</a>'
         );
        }
	    $subject = $item['subject'] != '' && $item['subject'] != 'not stored' ? stripslashes(esc_attr($item['subject'])) : esc_attr__( 'No Subject', 'simpleform-contact-form-submissions' );
        if ( $form_id == '' ) {
        $settings = get_option('sform_settings');
	    } else {
        $settings_option = get_option('sform_'. $form_id .'_settings');
        $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
        }
        $data_columns = ! empty( $settings['data_columns'] ) ? esc_attr($settings['data_columns']) : 'subject,name,message,mail,date';	
        if ( strpos($data_columns,'id') === false) { return sprintf('%s %s', $subject, $this->row_actions($actions)); }
	    else { return $subject; }
    }
    
	/**
	 * Render the message column.
	 *
	 * @since    1.0
	 */

    function column_object($item) {
        return stripslashes(esc_attr($item['object']));
    }

	/**
	 * Render the email column.
	 *
	 * @since    1.0
	 */

    function column_email($item) {
	   $email = $item['email'] != '' && $item['email'] != 'not stored' ? esc_attr($item['email']) : '-';	    
       return '<span style="letter-spacing: -0.5px;">' . $email . '</span>' ;
    }
    
 	/**
	 * Render the phone column.
	 *
	 * @since    1.4
	 */

    function column_phone($item) {
	   $phone = $item['phone'] != '' && $item['phone'] != 'not stored' ? esc_attr($item['phone']) : '-';	    
       return $phone;
    }
 
  	/**
	 * Render the IP column.
	 *
	 * @since    1.4
	 */

    function column_ip($item) {
       return $item['ip'];
    }
   
	/**
	 * Render the date column.
	 *
	 * @since    1.0
	 */

    function column_entry($item) {
        $tzcity = get_option('timezone_string'); 
        $tzoffset = get_option('gmt_offset');
        if ( ! empty($tzcity))  { 
        $current_time_timezone = date_create('now', timezone_open($tzcity));
        $timezone_offset =  date_offset_get($current_time_timezone);
        $submission_timestamp = strtotime($item['date']) + $timezone_offset; 
        }
        else { 
        $timezone_offset =  $tzoffset * 3600;
        $submission_timestamp = strtotime(esc_attr($item['date'])) + $timezone_offset;  
        }
        /* translators: at: used to indicate the time */
        $at = esc_html__('at', 'simpleform-contact-form-submissions');
        return date_i18n(get_option('date_format'),$submission_timestamp).' '.$at.' '.date_i18n(get_option('time_format'),$submission_timestamp );
    }

	/**
	 * Decide which columns to activate the sorting functionality on.
	 *
	 * @since    1.0
	 */

    function get_sortable_columns() {
        $sortable_columns = array('id' => array('id', true), 'subject' => array('subject', true), 'firstname' => array('firstname', true), 'family' => array('family', true), 'email' => array('email', true), 'entry' => array('date', true));
       return $sortable_columns;
    }

	/**
	 * Process the bulk actions.
	 *
	 * @since    1.0
	 */
	    
    function process_bulk_action() {
       
       global $wpdb;
       $msg = '';
       
       if ('delete' === $this->current_action()) {
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'delete_nonce' ) ) { $this->invalid_nonce_redirect(); }
            else { $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : '';
             if (!empty($id)) { 
	            $success = $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}sform_submissions WHERE ( status = 'trash' OR status = 'spam' ) AND id = %d", $id) ); 
   	            if ( $success ):    
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Submission with ID: %s permanently deleted', 'simpleform-contact-form-submissions' ), $id ) .'</p></div>'; 
 	            set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
	         }
            }
       }
       
       if ('trash' === $this->current_action() ) { 
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'trash_nonce' ) ) { $this->invalid_nonce_redirect(); }
			else { $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : '';
             if (!empty($id)) { 
	            $trash_date = date('Y-m-d H:i:s');
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'trash', trash_date = '$trash_date' WHERE status != 'trash' AND id = %d", $id) );
	            if ( $success ):    
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Submission with ID: %s successfully moved to the Trash', 'simpleform-contact-form-submissions' ), $id ) .'</p></div>'; 
 	            set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
             }
            }
       }
       
       if ('spam' === $this->current_action() ) { 
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'spam_nonce' ) ) { $this->invalid_nonce_redirect(); }
			else { $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : '';
             if (!empty($id)) { 
                if (has_filter('akismet_submit_spam')):
                  $msg = apply_filters( 'akismet_submit_spam', $id, $msg );
                endif;	    
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'spam' WHERE status != 'spam' AND id = %d", $id) );
	            if ( $success ):    
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Submission with ID: %s successfully marked as Spam', 'simpleform-contact-form-submissions' ), $id ) . $msg . '</p></div>';      
 	            set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
             }
            }
       }
       
       if ('untrash' === $this->current_action()) {
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'restore_nonce' ) ) { $this->invalid_nonce_redirect(); }
			else { $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : '';
             if (!empty($id)) { 
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'read', trash_date = '0000-00-00 00:00:00' WHERE status = 'trash' AND id = %d", $id) );
 	            if ( $success ):    
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Submission with ID: %s successfully restored from the Trash', 'simpleform-contact-form-submissions' ), $id ) .'</p></div>'; 
 	            set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
             }   
            }
       }
       
       if ('unspam' === $this->current_action()) {
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'restore_nonce' ) ) { $this->invalid_nonce_redirect(); }
			else { $id = isset($_REQUEST['id']) ? absint($_REQUEST['id']) : '';
             if (!empty($id)) { 
                if (has_filter('akismet_submit_ham')):
                  $msg = apply_filters( 'akismet_submit_ham', $id, $msg );
                endif;	    
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'read', trash_date = '0000-00-00 00:00:00' WHERE status = 'spam' AND id = %d", $id) );
 	            if ( $success ):    
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Submission with ID: %s successfully unmarked as Spam', 'simpleform-contact-form-submissions' ), $id ) . $msg .'</p></div>'; 
 	            set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
             }   
            }
       }
	   
	   if ( 'bulk-delete' === $this->current_action() || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-delete' ) ) {
		   		   
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-sform-entries' ) ) { $this->invalid_nonce_redirect(); }
			else {   	        
              // Force $ids to be an array if it's not already one by creating a new array and adding the current value
              $ids = isset($_REQUEST['id']) && is_array($_REQUEST['id']) ? $_REQUEST['id'] : array($_REQUEST['id']);
              // Ensure that the values passed are all positive integers
              $ids = array_map('absint', $ids);
              // Count the number of values
              $ids_count = count($ids);
              // Prepare the right amount of placeholders in an array
              $placeholders_array = array_fill(0, $ids_count, '%s');
              // Chains all the placeholders into a comma-separated string
              $placeholders = implode(',', $placeholders_array);
              if (!empty($ids)) { 	            
	            $success = $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}sform_submissions WHERE ( status = 'trash' OR status = 'spam' ) AND id IN($placeholders)", $ids) );
 	            if ( $success ):    
	            $messages = implode(',', $ids); 
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Submission with ID: %s permanently deleted', 'Submissions with ID: %s permanently deleted', $ids_count, 'simpleform-contact-form-submissions' ), $messages ) .'</p></div>'; 
                set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
              }
            }
	   }
	   
	   if ( 'bulk-trash' === $this->current_action() || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-trash' ) ) {
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-sform-entries' ) ) { $this->invalid_nonce_redirect(); }
			else {   	        
              $ids = isset($_REQUEST['id']) && is_array($_REQUEST['id']) ? $_REQUEST['id'] : array($_REQUEST['id']);
              $ids = array_map('absint', $ids);
              $ids_count = count($ids);
              $placeholders_array = array_fill(0, $ids_count, '%s');
              $placeholders = implode(',', $placeholders_array);
              if (!empty($ids)) { 	            
	            $trash_date = date('Y-m-d H:i:s');
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'trash', trash_date = '$trash_date' WHERE status != 'trash' AND id IN($placeholders)", $ids) ); 
 	            if ( $success ):    
	            $messages = implode(',', $ids); 
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Submission with ID: %s successfully moved to the Trash', 'Submissions with ID: %s successfully moved to the Trash', $ids_count, 'simpleform-contact-form-submissions' ), $messages ) .'</p></div>'; 
                set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
              }                
            }
	   }
	   
	   if ( 'bulk-spam' === $this->current_action() || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-spam' ) ) {
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-sform-entries' ) ) { $this->invalid_nonce_redirect(); }
			else {   	        
              $ids = isset($_REQUEST['id']) && is_array($_REQUEST['id']) ? $_REQUEST['id'] : array($_REQUEST['id']);
              $ids = array_map('absint', $ids);
              $ids_count = count($ids);
              $placeholders_array = array_fill(0, $ids_count, '%s');
              $placeholders = implode(',', $placeholders_array);
              if (!empty($ids)) {
                if (has_filter('akismet_submit_spam')):
                  $msg = apply_filters( 'akismet_submit_spam', $ids, $msg );
                endif;	   
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'spam' WHERE status != 'spam' AND id IN($placeholders)", $ids) ); 
 	            if ( $success ):    
	            $messages = implode(',', $ids); 
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Submission with ID: %s successfully marked as Spam', 'Submissions with ID: %s successfully marked as Spam', $ids_count, 'simpleform-contact-form-submissions' ), $messages ) . $msg .'</p></div>'; 
                set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
              }                
            }
	   }	   
	   
	   if ( 'bulk-untrash' === $this->current_action() || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-untrash' ) ) {
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-sform-entries' ) ) { $this->invalid_nonce_redirect(); }
			else {   	        
              $ids = isset($_REQUEST['id']) && is_array($_REQUEST['id']) ? $_REQUEST['id'] : array($_REQUEST['id']);
              $ids = array_map('absint', $ids);
              $ids_count = count($ids);
              $placeholders_array = array_fill(0, $ids_count, '%s');
              $placeholders = implode(',', $placeholders_array);
              if (!empty($ids)) { 	      
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'read', trash_date = '0000-00-00 00:00:00' WHERE status = 'trash' AND id IN($placeholders)", $ids) ); 
            	if ( $success ):
			    $messages = implode(',', $ids); 
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Submission with ID: %s successfully restored from the Trash', 'Submissions with ID: %s successfully restored from the Trash', $ids_count, 'simpleform-contact-form-submissions' ), $messages ) .'</p></div>'; 
	            set_transient( 'sform_action_notice', $action_notice, 5 );
 	            endif; 
              }                
            }
	    }
	   
	   if ( 'bulk-unspam' === $this->current_action() || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-unspam' ) ) {
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-sform-entries' ) ) { $this->invalid_nonce_redirect(); }
			else {   	        
              $ids = isset($_REQUEST['id']) && is_array($_REQUEST['id']) ? $_REQUEST['id'] : array($_REQUEST['id']);
              $ids = array_map('absint', $ids);
              $ids_count = count($ids);
              $placeholders_array = array_fill(0, $ids_count, '%s');
              $placeholders = implode(',', $placeholders_array);
              if (!empty($ids)) { 	 
                if (has_filter('akismet_submit_ham')):
                  $msg = apply_filters( 'akismet_submit_ham', $ids, $msg );
                endif;	    
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET status = 'read', trash_date = '0000-00-00 00:00:00' WHERE status = 'spam' AND id IN($placeholders)", $ids) ); 
            	if ( $success ):
			    $messages = implode(',', $ids); 
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Submission with ID: %s successfully unmarked as Spam', 'Submissions with ID: %s successfully unmarked as Spam', $ids_count, 'simpleform-contact-form-submissions' ), $messages ) . $msg .'</p></div>'; 
 	            set_transient( 'sform_action_notice', $action_notice, 5 );
 	            endif; 
              }     
            }
	   }

	   if ( 'bulk-move' === $this->current_action() || ( isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] === 'bulk-move' ) ) {
            $movefrom = isset($_REQUEST['form']) ? absint($_REQUEST['form']) : '';
	        $moveto = isset($_REQUEST['move2']) ? absint($_REQUEST['move2']) : '';
	        $nonce = isset ( $_REQUEST['_wpnonce'] ) ? wp_unslash($_REQUEST['_wpnonce']) : '';
			if ( ! wp_verify_nonce( $nonce, 'bulk-sform-entries' ) ) { $this->invalid_nonce_redirect(); }
			else {   
              $ids = isset($_REQUEST['id']) && is_array($_REQUEST['id']) ? $_REQUEST['id'] : array($_REQUEST['id']);
              $ids = array_map('absint', $ids);
              $ids_count = count($ids);
              $placeholders_array = array_fill(0, $ids_count, '%s');
              $placeholders = implode(',', $placeholders_array);
              if (!empty($ids) && !empty($moveto)) { 
                $form_name = $wpdb->get_var( "SELECT name FROM {$wpdb->prefix}sform_shortcodes WHERE id = '{$moveto}'");
	            $name = ! empty($form_name) ? $form_name : __( 'selected form', 'simpleform-contact-form-submissions' );
	            $success = $wpdb->query( $wpdb->prepare("UPDATE {$wpdb->prefix}sform_submissions SET moved_from = form, form = '{$moveto}' WHERE id IN($placeholders)", $ids) );
 	            if ( $success ):
                $util = new SimpleForm_Submissions_Util();      
                $util->update_last_messages( $ids, $movefrom, $moveto ); 
	            $messages = implode(',', $ids); 
	            $action_notice = '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( 'Submission with ID: %1$s successfully moved to %2$s', 'Submissions with ID: %1$s successfully moved to %2$s', $ids_count, 'simpleform-contact-form-submissions' ), $messages, $name ) .'</p></div>'; 
                set_transient( 'sform_action_notice', $action_notice, 5 );
                endif; 
              }
            }
	   }
	   
    }

	/**
	 * Die when the nonce check fails.
	 *
	 * @since    1.0
	 */

	function invalid_nonce_redirect() {
		wp_die( __( 'Invalid Nonce', 'simpleform-contact-form-submissions' ),__( 'Error', 'simpleform-contact-form-submissions' ), array( 'response' => 403, 'back_link' =>  esc_url( add_query_arg( array( 'page' => wp_unslash( $_REQUEST['page'] ) ) , admin_url( 'admin.php' ) ) ) ) );
	}
	
	/**
	 * Overwrite the pagination.
	 *
	 * @since 1.0
	 */
	 
	function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) { return; }
		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) { $infinite_scroll = $this->_pagination_args['infinite_scroll']; }
		if ( 'top' === $which && $total_pages > 1 ) { $this->screen->render_screen_reader_content( 'heading_pagination' ); }
		$output = '<span class="displaying-num">' . sprintf(_n( '%s entry', '%s entries', $total_items, 'simpleform-contact-form-submissions' ),number_format_i18n( $total_items )) . '</span>';
		$current = $this->get_pagenum();
		// An array of query variable names to remove from a URL
		$removable_query_args = wp_removable_query_args();
		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( $removable_query_args, $current_url );
		$page_links = array();
		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';
		$disable_first = false;
		$disable_last  = false;
		$disable_prev  = false;
		$disable_next  = false;
		if ( $current == 1 ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( $current == 2 ) {
			$disable_first = true;
		}
		if ( $current == $total_pages ) {
			$disable_last = true;
			$disable_next = true;
		}
		if ( $current == $total_pages - 1 ) {
			$disable_last = true;
		}
		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}
		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}
		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[]     = $total_pages_before . sprintf(
			_x( '%1$s of %2$s', 'paging' ),
			$html_current_page,
			$html_total_pages
		) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}
		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}
		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';
		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
		echo $this->_pagination;
	}
	
	/**
	 * Append the selector to move entries in the bulk actions menu.
	 *
	 * @since 2.0
	 */
	
    function bulk_actions( $which = '' ) {
      
      $id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : ''; 
      $moving_option = '';
      
      // Prevent duplicate queries in bottom bulk actions
      if ( !empty($id) && 'top' === $which ) {
        global $wpdb; 
        $forms = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}sform_shortcodes WHERE id != '{$id}' ORDER BY name ASC", 'ARRAY_A' );
        if ( count($forms) > 1 ) { 
	      $moving_option .= '<select name="moveto" id="moveto" class="moveto unseen"><option value="">' . __( 'Select Form', 'simpleform-contact-form-submissions' ) . '</option>';
	      foreach($forms as $form) { $moving_option .= '<option value="'.$form['id'].'">'. $form['name'].'</option>'; } 
	      $moving_option .= '</select>';
        }
      } 
       
      if ( is_null( $this->_actions ) ) {
        $this->_actions = $this->get_bulk_actions();
        $this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
        $two = '';
      } else {
        $two = '2';
      }
 
      if ( empty( $this->_actions ) ) {
        return;
      }
 
      echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
      echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
      echo '<option value="-1">' . __( 'Bulk actions' ) . "</option>\n";
 
      foreach ( $this->_actions as $key => $value ) {
        if ( is_array( $value ) ) {
            echo "\t" . '<optgroup label="' . esc_attr( $key ) . '">' . "\n";
 
            foreach ( $value as $name => $title ) {
                $class = ( 'edit' === $name ) ? ' class="hide-if-no-js"' : '';
 
                echo "\t\t" . '<option value="' . esc_attr( $name ) . '"' . $class . '>' . $title . "</option>\n";
            }
            echo "\t" . "</optgroup>\n";
        } else {
            $class = ( 'edit' === $key ) ? ' class="hide-if-no-js"' : '';
 
            echo "\t" . '<option value="' . esc_attr( $key ) . '"' . $class . '>' . $value . "</option>\n";
        }
      }
 
      echo "</select>\n";
      
      echo $moving_option;
     
      submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
      echo "\n";
    
    }	
        
	/**
	 * Add a date filter.
	 *
	 * @since 1.2
	 */
	 
    function extra_tablenav( $which ) {
	    
	    // If no submissions are found
	    if ( empty( $this->_actions ) ) {
           return;
        }
    
        global $wpdb;
        if ( $which == "top" ){
			$keyword = ( isset( $_REQUEST['s'] ) ) ? sanitize_text_field($_REQUEST['s']) : '';
	    	$search = !empty($keyword) ? '%'.$wpdb->esc_like($keyword).'%' : '';						
		    $value1 = ''; 
		    $value2 = 'not stored'; 
            $form_id = isset( $_REQUEST['form'] ) ? absint($_REQUEST['form']) : '';
            if ( $form_id == '' ) {
            $settings = get_option('sform_settings');
	        } else {
            $settings_option = get_option('sform_'. $form_id .'_settings');
            $settings = $settings_option != false ? $settings_option : get_option('sform_settings');
            }
            $ip_storing = ! empty( $settings['ip_storing'] ) ? esc_attr($settings['ip_storing']) : 'true';
            $view = isset( $_REQUEST['view'] ) ? sanitize_text_field($_REQUEST['view']) : 'inbox';
            if( $keyword != ''){	 
             if ( $ip_storing == 'true'  ) {
	         if( $form_id != ''){   
	         $sql_oldest_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR ip LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form = %d) ORDER BY date LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search, $search , $form_id);
	         $sql_last_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR ip LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form = %d) ORDER BY date DESC LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search, $search, $form_id );
             } else {
	         $sql_oldest_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR ip LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form != '0') ORDER BY date LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search, $search );
	         $sql_last_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR ip LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form != '0') ORDER BY date DESC LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search, $search );
             }
             }
             else {	
	         if( $form_id != ''){   
	         $sql_oldest_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form = %d) ORDER BY date LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search, $form_id );
	         $sql_last_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form = %d) ORDER BY date DESC LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search, $form_id );
             } else {
	         $sql_oldest_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form != '0') ORDER BY date LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search );
	         $sql_last_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE ( object != %s AND object != %s ) AND (name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR email LIKE %s OR phone LIKE %s) AND (form != '0') ORDER BY date DESC LIMIT 1", $value1, $value2, $search, $search, $search, $search, $search, $search );
             }
             }
            }
            else {	 
	         if( $form_id != ''){   
	         $sql_oldest_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE form = %d ORDER BY date LIMIT 1", $form_id );
 	         $sql_last_date = $wpdb->prepare("SELECT date FROM {$wpdb->prefix}sform_submissions WHERE form = %d ORDER BY date DESC LIMIT 1", $form_id );
             } else {
	         $sql_oldest_date = "SELECT date FROM {$wpdb->prefix}sform_submissions WHERE form != '0' ORDER BY date LIMIT 1";
 	         $sql_last_date = "SELECT date FROM {$wpdb->prefix}sform_submissions WHERE form != '0' ORDER BY date DESC LIMIT 1";
             }
            }
            $date_oldest = $wpdb->get_var($sql_oldest_date);
            $last_date = $wpdb->get_var($sql_last_date);
            $current_year = date('Y', strtotime('now'));
            $oldest_year = date('Y', strtotime($date_oldest));
            $years_time_range = $current_year - $oldest_year;
    	    if ( strtotime($date_oldest) <= strtotime('-1 days') ) {
            echo '<select name="date" class="" id="date" style="max-width: none !important;">
            <option value="">' . __('All Dates', 'simpleform-contact-form-submissions') .'</option>';
    	      if ( strtotime($last_date) >= strtotime('-1 days') ) {
		       $selected = '';
               if( isset($_REQUEST['date']) && $_REQUEST['date'] == 'last_day' && strtotime('-1 days') <= strtotime($last_date) ){
               $selected = ' selected = "selected"';   
               }
               echo '<option value="last_day" '.$selected.'>' . __('Last Day', 'simpleform-contact-form-submissions') .'</option>';
               }
    	       if ( strtotime($last_date) >= strtotime('-7 days') ) {
		       $selected = '';
               if( isset($_REQUEST['date']) && $_REQUEST['date'] == 'last_week' && strtotime('-7 days') <= strtotime($last_date) ){
               $selected = ' selected = "selected"';   
               }
               echo '<option value="last_week" '.$selected.'>' . __('Last Week', 'simpleform-contact-form-submissions') .'</option>';
               }
    	       if ( strtotime($last_date) >= strtotime('-30 days') ) {
		       $selected = '';
               if( isset($_REQUEST['date']) && $_REQUEST['date'] == 'last_month'  && strtotime('-30 days') <= strtotime($last_date) ){
               $selected = ' selected = "selected"';   
               }
               echo '<option value="last_month" '.$selected.'>' . __('Last Month', 'simpleform-contact-form-submissions') .'</option>';
               }
    	       if ( strtotime($last_date) >= strtotime('first day of january this year') ) {
		       $selected = '';
               if( isset($_REQUEST['date']) && $_REQUEST['date'] == 'current_year'  && strtotime('first day of january this year') <= strtotime($last_date) ){
               $selected = ' selected = "selected"';   
               }
               echo '<option value="current_year" '.$selected.'>' . __('Current Year', 'simpleform-contact-form-submissions') .'</option>';
               }
    	       if ( strtotime($last_date) >= strtotime('-1 year') ) {
		       $selected = '';
               if( isset($_REQUEST['date']) && $_REQUEST['date'] == 'last_year'  && strtotime('-1 year') <= strtotime($last_date) ){
               $selected = ' selected = "selected"';   
               }
               echo '<option value="last_year" '.$selected.'>' . __('Last Year', 'simpleform-contact-form-submissions') .'</option>';
               }
               for ($i=1; $i<=$years_time_range; $i++) {
               $option_year = $current_year - $i;
               $request_year = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sform_submissions WHERE YEAR(date)='$option_year' ", ARRAY_A);            
	           if ( $request_year ) { 
		           $selected = '';
	               if( isset($_REQUEST['date']) && $_REQUEST['date'] == $option_year ) {
                   $selected = ' selected = "selected"';                    	
                   }
                   echo '<option value="'.$option_year.'" '.$selected.'>' . $option_year .'</option>';
	           }
            }
	        echo '</select>';
        }
        
               $form_id = isset($_REQUEST['form']) ? absint($_REQUEST['form']) : '';

        echo '<input type="hidden" id="form" name="form" value="'.$form_id.'">';
        echo '<input type="hidden" id="move2" name="move2" value="">';
        echo '<input id="my-post-query-submit" class="button" type="submit" value="'. __("Filter", "simpleform-contact-form-submissions") .'" name="" style="margin-right: 10px;">';
        } 
    } 
    
	/**
	 * Override search_box() function.
	 *
	 * @since    1.1
	 */
    
    function search_box( $text, $input_id ) {
	    
	  // If no submissions are found
      if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
        return;
      }
	    
      echo '<p class="search-box"><label class="screen-reader-text" for="'.$input_id.'">'.$text.':</label><input type="search" id="'.$input_id.'" name="s" value="';
      _admin_search_query(); 
      echo '" placeholder="' . __('Enter keyword', 'simpleform-contact-form-submissions') .'" />';
      submit_button( $text, 'button', false, false, array('id' => 'search-submit') );
      echo '</p>';
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements.
	 *
	 * @since    1.0
	 */
 
 	function prepare_items() {
	    $per_page = $this->get_items_per_page('edit_submission_per_page', 20);
        $view = isset($_REQUEST['view']) ? sanitize_text_field($_REQUEST['view']) : 'inbox';
        if ($view == 'inbox') { $filter_by_view = " AND status != 'trash' AND status != 'spam'"; }
        if ($view == 'trash') { $filter_by_view = " AND status = 'trash'"; }
        if ($view == 'new') { $filter_by_view = " AND status = 'new'"; }
        if ($view == 'answered') { $filter_by_view = " AND status = 'answered'"; }
        if ($view == 'spam') { $filter_by_view = " AND status = 'spam'"; }
	    $current_page = $this->get_pagenum();
		if ( 1 < $current_page ) { $paged = $per_page * ( $current_page - 1 ); } 
		else { $paged = 0; }
        $this->process_bulk_action();
	    $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'date'; 
		$order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
		global $wpdb;
        $where = $this->get_query_conditions() . $filter_by_view;
        $placeholders = $this->get_query_placeholders();
        $pagination_placeholders = array_merge($placeholders, array($per_page, $paged) );
        $sql1 = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}sform_submissions $where ORDER BY $orderby $order LIMIT %d OFFSET %d", $pagination_placeholders );
	    $sql2 = $wpdb->prepare("SELECT COUNT(id) FROM {$wpdb->prefix}sform_submissions $where", $placeholders );
        $items = $wpdb->get_results( $sql1, ARRAY_A );
		$this->_column_headers = $this->get_column_info();
        $count = $wpdb->get_var( $sql2 );
		$this->items = $items;
		$this->set_pagination_args( array('total_items' => $count,'per_page' => $per_page,'total_pages' => ceil( $count / $per_page )) );
    }
		
}