<?php
/**
 * File delegated to show the entry data page.
 *
 * @package    SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin/partials
 */

defined( 'ABSPATH' ) || exit;

$entries_class = new SimpleForm_Entries_List();
$validation    = new SimpleForm_Submissions_Admin_Validation();
$util          = new SimpleForm_Submissions_Util();
$admin_notices = $util->get_sform_option( 1, 'settings', 'admin_notices', false );
$notice_class  = $admin_notices ? 'invisible' : '';
$version_alert = get_transient( 'sform_version_alert' );
$notice_class .= false !== $version_alert ? ' unseen' : '';
$wrap_class    = false !== $version_alert ? 'spaced' : '';
$notice        = '';
$color         = strval( $util->get_sform_option( 1, 'settings', 'admin_color', 'default' ) );
$referer_url   = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=sform-entries' );
// Return an array of query variables.
if ( isset( wp_parse_url( $referer_url )['query'] ) ) {
	parse_str( wp_parse_url( $referer_url )['query'], $params );
}
$query_page    = isset( $params['page'] ) && ! empty( $params['page'] ) ? $params['page'] : 'sform-entries';
$form          = isset( $params['form'] ) && ! empty( $params['form'] ) ? $params['form'] : '';
$view          = isset( $params['view'] ) && ! empty( $params['view'] ) ? $params['view'] : '';
$date          = isset( $params['date'] ) && ! empty( $params['date'] ) ? $params['date'] : '';
$keyword       = isset( $params['s'] ) && ! empty( $params['s'] ) ? $params['s'] : '';
$query_orderby = isset( $params['orderby'] ) && ! empty( $params['orderby'] ) ? $params['orderby'] : '';
$query_order   = isset( $params['order'] ) && ! empty( $params['order'] ) ? $params['order'] : '';
$pagenum       = isset( $params['paged'] ) && ! empty( $params['paged'] ) ? $params['paged'] : '';
// Save query args if you are come from entries page.
if ( 'sform-entries' === $query_page ) {
	unset( $params['id'] );
	update_option( 'sform_entries_params', $params );
}
$entries_params = (array) get_option( 'sform_entries_params', array( 'page' => 'sform-entries' ) );
$back_url       = add_query_arg( $entries_params, $referer_url );
$back_url       = remove_query_arg( array( 'id', 'info', 'editing' ), $back_url );
$back_button    = '<a href="' . esc_url( $back_url ) . '"><span class="dashicons dashicons-list-view icon-button admin ' . esc_attr( $color ) . '"></span><span class="wp-core-ui button admin back-list ' . esc_attr( $color ) . '">' . __( 'Back to entries', 'simpleform-contact-form-submissions' ) . '</span></a>';
$entry_id       = absint( $validation->sanitized_key( 'id' ) );
$entry_form     = absint( $util->entry_value( $entry_id, 'form' ) );
$requester_type = strval( $util->entry_value( $entry_id, 'requester_type' ) );
$requester_id   = absint( $util->entry_value( $entry_id, 'requester_id' ) );
$entry_name     = strval( $util->entry_value( $entry_id, 'name' ) );
$entry_lastname = strval( $util->entry_value( $entry_id, 'lastname' ) );
$entry_email    = strval( $util->entry_value( $entry_id, 'email' ) );
$entry_phone    = strval( $util->entry_value( $entry_id, 'phone' ) );
$entry_subject  = strval( $util->entry_value( $entry_id, 'subject' ) );
$entry_message  = strval( $util->entry_value( $entry_id, 'object' ) );
$entry_ip       = strval( $util->entry_value( $entry_id, 'ip' ) );
$entry_date     = strval( $util->entry_value( $entry_id, 'date' ) );
$entry_status   = strval( $util->entry_value( $entry_id, 'status' ) );
$description    = __( 'All submitted data, and more, at a glance. ', 'simpleform-contact-form-submissions' );
$unspam_desc    = __( 'For restoring the entry from spam change its status. ', 'simpleform-contact-form-submissions' );
$restoring_desc = __( 'For restoring the entry from trash change its status. ', 'simpleform-contact-form-submissions' );
$description   .= 'spam' === $entry_status ? '&nbsp;' . $unspam_desc . '&nbsp;' . __( 'For permanently deleting the entry, back to entries list and use the specific action. ', 'simpleform-contact-form-submissions' ) : '';
$description   .= 'trash' === $entry_status ? '&nbsp;' . $restoring_desc . '&nbsp;' . __( 'For permanently deleting the entry, back to entries list and use the specific action. ', 'simpleform-contact-form-submissions' ) : '';
global $wpdb;
$form_param    = isset( $entries_params['form'] ) ? $entries_params['form'] : '';
$view_param    = isset( $entries_params['view'] ) ? $entries_params['view'] : '';
$date_param    = isset( $entries_params['date'] ) ? $entries_params['date'] : '';
$keyword_param = isset( $entries_params['s'] ) ? $entries_params['s'] : '';
if ( in_array( $date_param, array( '', 'last_day', 'last_week', 'last_month', 'current_year', 'last_year' ), true ) ) {
	$date_value       = array(
		''             => '',
		'last_day'     => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 24 HOUR )',
		'last_week'    => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 7 DAY )',
		'last_month'   => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 30 DAY )',
		'current_year' => ' AND ( YEAR(date ) = YEAR(CURDATE() ) )',
		'last_year'    => ' AND ( date >= UTC_TIMESTAMP() - INTERVAL 1 YEAR )',
	);
	$where_date       = $date_value[ $date_param ];
	$date_placeholder = array();
} else {
	$where_date       = ' AND YEAR(date ) = %d';
	$date_placeholder = array( $date_param );
}
$ip_storing          = $util->get_sform_option( 1, 'settings', 'ip_storing', true );
$ip_clause           = $ip_storing ? 'name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR ip LIKE %s OR email LIKE %s OR phone LIKE %s' : 'name LIKE %s OR lastname LIKE %s OR subject LIKE %s OR object LIKE %s OR email LIKE %s OR phone LIKE %s';
$where_keyword       = ! empty( $keyword_param ) ? 'WHERE object != %s AND object != %s AND ( ' . $ip_clause . ')' : 'WHERE object != %s AND object != %s';
$search_val          = empty( $keyword_param ) ? '' : '%' . $wpdb->esc_like( $keyword_param ) . '%';
$arguments_list      = $ip_storing ? array( '', 'not stored', $search_val, $search_val, $search_val, $search_val, $search_val, $search_val, $search_val ) : array( '', 'not stored', $search_val, $search_val, $search_val, $search_val, $search_val, $search_val );
$keyword_placeholder = empty( $keyword_param ) ? array( '', 'not stored' ) : $arguments_list;
$where_form          = ! empty( $form_param ) ? " AND form = %d AND listable = '1' AND hidden = '0'" : " AND form != '0' AND listable = '1' AND hidden = '0'";
$form_placeholder    = empty( $form_param ) ? array() : array( $form_param );
$condition           = array(
	''         => " AND status != 'trash' AND status != 'spam'",
	'inbox'    => " AND status != 'trash' AND status != 'spam'",
	'new'      => " AND status = 'new'",
	'answered' => " AND status = 'answered'",
	'spam'     => " AND status = 'spam'",
	'trash'    => " AND status = 'trash'",
);
$where_status        = $condition[ $view_param ];
$where_clause        = $where_keyword . $where_date . $where_form . $where_status;
$placeholders        = array_merge( $keyword_placeholder, $date_placeholder, $form_placeholder );
$counter_query       = "SELECT COUNT( id ) FROM {$wpdb->prefix}sform_submissions {$where_clause}";
$counter_result      = $wpdb->get_var( $wpdb->prepare( $counter_query, $placeholders ) ); // phpcs:ignore
$counter             = 'new' === $entry_status ? $counter_result - 1 : $counter_result;
$placeholders[]      = $entry_date;
$prev_query          = "SELECT id FROM {$wpdb->prefix}sform_submissions {$where_clause} AND date < %s ORDER BY date DESC LIMIT 1";
$prev_id             = $wpdb->get_var( $wpdb->prepare( $prev_query, $placeholders ) ); // phpcs:ignore
$next_query          = "SELECT id FROM {$wpdb->prefix}sform_submissions {$where_clause} AND date > %s ORDER BY date ASC LIMIT 1";
$next_id             = $wpdb->get_var( $wpdb->prepare( $next_query, $placeholders ) ); // phpcs:ignore
$referer             = 'sform-entries' === $query_page ? remove_query_arg( array( 'page', 'form', 'view', 'date', 's', 'orderby', 'order', 'paged', 'id' ) ) : $referer_url;
$prev_url            = esc_url( add_query_arg( 'id', $prev_id ) );
$next_url            = esc_url( add_query_arg( 'id', $next_id ) );
$nav_class           = $counter > 1 ? $color : 'invisible';
$prev_button         = $prev_id ? '<a id="prev-link" href="' . $prev_url . '"><span id="prev" class="paginated"><span class="dashicons dashicons-arrow-left-alt2"></span><span>' . __( 'Prev', 'simpleform-contact-form-submissions' ) . '</span></span></a>' : '';
$next_button         = $next_id ? '<a id="next-link" href="' . $next_url . '"><span id="next" class="paginated"><span class="dashicons dashicons-arrow-right-alt2" style="float: right"></span><span>' . __( 'Next', 'simpleform-contact-form-submissions' ) . '</span></span></a>' : '';
$counter_span        = '<span id="view-counter">' . $counter . '</span>';
$form_name           = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}sform_shortcodes WHERE id = %d", $entry_form ) ); // phpcs:ignore
$referer_form        = $form ? $form_name : 'all forms';
$referer_view        = array(
	''         => __( 'Inbox', 'simpleform-contact-form-submissions' ) . ':',
	'inbox'    => __( 'Inbox', 'simpleform-contact-form-submissions' ) . ':',
	'new'      => __( 'Unread', 'simpleform-contact-form-submissions' ) . ':',
	'answered' => __( 'Answered', 'simpleform-contact-form-submissions' ) . ':',
	'spam'     => __( 'Junk', 'simpleform-contact-form-submissions' ) . ':',
	'trash'    => __( 'Trash', 'simpleform-contact-form-submissions' ) . ':',
);
$entries_view        = $referer_view[ $view_param ];
/* translators: $1$s: View of the table; $2$s: Number of entries found in the view; $3$s: The form where entries were found; */
$navigation_note = sprintf( _n( '%1$s: %2$s entry found in %3$s', '%1$s %2$s entries found in %3$s', $counter ), $entries_view, $counter_span, $referer_form );
$contact_info    = isset( $_GET['info'] ) && 'hidden' === $_GET['info'] ? ' unseen' : ''; // phpcs:ignore
$count_forms     = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sform_shortcodes WHERE status != 'trash'" ); // phpcs:ignore
$form_data_class = $count_forms < 2 ? 'unseen' : '';
$user_type       = 'registered' === $requester_type && 0 !== $requester_id ? __( 'Registered', 'simpleform-contact-form-submissions' ) : __( 'Unregistered', 'simpleform-contact-form-submissions' );
$phone           = ! empty( $entry_phone ) && 'not stored' !== $entry_phone ? $entry_phone : '';
$ip              = ! empty( $entry_ip ) && 'not stored' !== $entry_ip ? $entry_ip : '';
$email           = ! empty( $entry_email ) && 'not stored' !== $entry_email ? $entry_email : '';
$page_user       = '';
$login_data      = '';
$role_class      = '';
$role_name       = '';
$avatar          = false;
if ( 'registered' === $requester_type && 0 !== $requester_id ) {
	$user_info  = get_userdata( $requester_id );
	$page_user  = get_edit_user_link( $requester_id );
	$login_data = false !== $user_info ? $user_info->user_login : '';
	global $wp_roles;
	$user_role  = false !== $user_info ? implode( ', ', $user_info->roles ) : '';
	$role_class = empty( $phone ) && empty( $ip ) && empty( $email ) ? 'last' : '';
	$role_name  = translate_user_role( $wp_roles->roles[ $user_role ]['name'] );
}
if ( is_email( $entry_email ) ) {
	$gravemail = md5( strtolower( trim( $entry_email ) ) );
	$gravsrc   = 'http://www.gravatar.com/avatar/' . $gravemail;
	$gravcheck = 'http://www.gravatar.com/avatar/' . $gravemail . '?d=404';
	$response  = get_headers( $gravcheck );
	$avatar    = false !== $response && strpos( $response[0], '404 Not Found' ) === false ? true : false;
}
$name               = ! empty( $entry_name ) && 'not stored' !== $entry_name && 'anonymous' !== $requester_type ? $entry_name : '';
$lastname           = ! empty( $entry_lastname ) && 'not stored' !== $entry_lastname ? $entry_lastname : '';
$name_line          = empty( $name ) || empty( $lastname ) ? '' : 'topaligned';
$name_class         = empty( $phone ) && empty( $ip ) && empty( $email ) ? 'last ' . $name_line : $name_line;
$lastname_separator = ! empty( $name ) ? '<br>' : '';
$fullname           = ! empty( $name ) || ! empty( $lastname ) ? $name . $lastname_separator . $lastname : __( 'Anonymous', 'simpleform' );
$phone_class        = empty( $ip ) && empty( $email ) ? 'last' : '';
$email_class        = empty( $ip ) ? 'last' : '';
$data_column_class  = isset( $_GET['info'] ) && 'hidden' === $_GET['info'] ? 'fullwidth' : 'first'; // phpcs:ignore
/* translators: at: used to indicate the time */
$at = __( 'at', 'simpleform-contact-form-submissions' );
// Get the site's timezone offset in seconds from UTC.
$timezone_offset = date_offset_get( current_datetime() );
$local_date      = strtotime( $entry_date ) + $timezone_offset;
$date            = date_i18n( strval( get_option( 'date_format' ) ), $local_date );
$time            = date_i18n( strval( get_option( 'time_format' ) ), $local_date );
$data_entry_date = $date . ' ' . $at . ' ' . $time;
$toggle_class    = isset( $_GET['info'] ) && 'hidden' === $_GET['info'] ? '' : ' unseen'; // phpcs:ignore
$subject         = ! empty( $entry_subject ) && 'not stored' !== $entry_subject ? $entry_subject : '';
$editing         = isset( $_GET['editing'] ) && 'hidden' === $_GET['editing'] ? ' collapsed' : ''; // phpcs:ignore
$editing_class   = $editing ? 'closed' : '';
$dashicons_arrow = $editing ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2';
// Update the status of unread entry as soon as the page finishes loading.
if ( 'new' === $entry_status ) {
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}sform_submissions SET status = 'read' WHERE id = %d", $entry_id ) ); // phpcs:ignore
}
$status_class      = $count_forms > 1 && 'trash' !== $entry_status && 'spam' !== $entry_status ? '' : 'last';
$read_selected     = 'read' === $entry_status || 'new' === $entry_status ? 'selected="selected"' : '';
$answered_selected = 'answered' === $entry_status ? 'selected="selected"' : '';
$spam_selected     = 'spam' === $entry_status ? 'selected="selected"' : '';
$trash_selected    = 'trash' === $entry_status ? 'selected="selected"' : '';
/* translators: Prefix for the reply subject */
$re             = __( 'Re: ', 'simpleform-contact-form-submissions' );
$separator      = ! empty( $subject ) ? '?' : '';
$mailto_subject = ! empty( $subject ) ? 'subject=' . $re . str_replace( ' ', '%20', $subject ) : '';
$mailto         = $util->get_sform_option( $entry_form, 'settings', 'mailto', false );
$mailto_class   = 'read' === $entry_status || 'new' === $entry_status ? '' : 'unseen';
$mailto_button  = $mailto && ! empty( $email ) ? '<a id="reply" class="' . esc_attr( $mailto_class ) . '" href="mailto:' . esc_attr( $email ) . esc_attr( $separator ) . esc_attr( $mailto_subject ) . '"><span class="sform reply-message button ' . esc_attr( $color ) . '">' . __( 'Reply', 'simpleform-contact-form-submissions' ) . '</span><span class="dashicons dashicons-external icon-button ' . esc_attr( $color ) . '"></span></a>' : '';
$moving_class   = $count_forms < 2 || 'trash' === $entry_status || 'spam' === $entry_status ? 'unseen' : '';
$forms_data     = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM {$wpdb->prefix}sform_shortcodes WHERE id != %d AND status != 'trash' ORDER BY name ASC", $entry_form ), ARRAY_A ); // phpcs:ignore
$options        = '<option value="">' . __( 'Select a form to move entry to', 'simpleform-contact-form-submissions' ) . '</option>';
foreach ( $forms_data as $form_data ) {
	$id_data   = $form_data['id'];
	$name_data = $form_data['name'];
	$options  .= '<option value="' . $id_data . '">' . $name_data . '</option>';
}
$allowed_tags = $util->sform_allowed_tags();

// Page wrap: opening tag.
$entry_page = '<div id="sform-wrap" class="sform ' . esc_attr( $wrap_class ) . '">';

// Admin notice.
$entry_page .= '<div id="new-release" class="' . esc_attr( $notice_class ) . '">' . wp_kses_post( apply_filters( 'sform_update', $notice ) ) . '&nbsp;</div>';

// Page heading.
$entry_page .= '<div class="full-width-bar ' . esc_attr( $color ) . '"><h1 class="title ' . esc_attr( $color ) . '"><span class="dashicons dashicons-tag responsive"></span>' . __( 'Entry data', 'simpleform-contact-form-submissions' ) . $back_button . '</h1></div>';

// Description page.
$entry_page .= '<div id="page-description"><p>' . esc_html( $description ) . '</p></div>';

// Previous/Next entries navigation.
$entry_page .= '<div id="navigation-buttons" class="' . esc_attr( $nav_class ) . '"><div id="messages-nav">' . $prev_button . $next_button . '</div><div id="messages-info">' . $navigation_note . '&nbsp;</div></div>';

// Form opening tag.
$entry_page .= '<form id="submission-tab" method="post" class="' . esc_attr( $color ) . '">';

// Hidden input.
$entry_page .= '<input type="hidden" id="entry" name="entry" value="' . $entry_id . '">';
$entry_page .= '<input type="hidden" id="entries-counter" name="entries-counter" value="' . $counter . '">';
$entry_page .= '<input type="hidden" id="entry-form" name="entry-form" value="' . $entry_form . '">';
$entry_page .= '<input type="hidden" id="entries-view" name="entries-view" value="' . $view . '">';

// Entry data wrap: opening tag.
$entry_page .= '<div class="submission-data">';

// Contact info data wrap: opening tag.
$entry_page .= '<div id="submitter-data" class="columns-wrap left ' . esc_attr( $color ) . esc_attr( $contact_info ) . '">';

$entry_page .= '<table class="entrie-table"><tbody>';

$entry_page .= '<tr><th class="option first"><span>' . __( 'ID', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext first toggle"># ' . $entry_id . '<span id="toggle-info" data-title="' . esc_attr__( 'Hide', 'simpleform-contact-form-submissions' ) . '" class="dashicons dashicons-arrow-left-alt2 contact-info left"></span></td></tr>';

$entry_page .= '<tr class="' . esc_attr( $form_data_class ) . '"><th class="option"><span>' . __( 'Form', 'simpleform' ) . '</span></th><td class="plaintext"><span id="form_name" class="form-name">' . esc_html( $form_name ) . '</span></td></tr>';

$entry_page .= '<tr><th class="option"><span>' . __( 'User', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext">' . esc_html( $user_type ) . '</td></tr>';

if ( 'registered' === $requester_type && 0 !== $requester_id ) {

	$entry_page .= '<tr><th class="option"><span>' . __( 'Username', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext"><a href="' . esc_attr( $page_user ) . '" target="_blank" class="nodecoration">' . esc_html( $login_data ) . '</a></td></tr>';

	$entry_page .= '<tr><th class="option ' . esc_attr( $role_class ) . '"><span>' . __( 'Role', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext ' . esc_attr( $role_class ) . '">' . esc_html( $role_name ) . '</td></tr>';

}

if ( get_option( 'show_avatars' ) && is_email( $entry_email ) && $avatar ) {

	$entry_page .= '<tr><th class="option"><span>' . __( 'Profile Picture', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext profile">' . get_avatar( esc_attr( $entry_email ), 96, 'mystery' ) . '</td></tr>';

}

$entry_page .= '<tr><th class="option ' . esc_attr( $name_class ) . '"><span>' . __( 'Name', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext ' . esc_attr( $name_class ) . '">' . wp_kses_post( $fullname ) . '</td></tr>';

if ( ! empty( $phone ) ) {

	$entry_page .= '<tr><th class="option ' . esc_attr( $phone_class ) . '"><span>' . __( 'Phone', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext ' . esc_attr( $phone_class ) . '">' . esc_html( $phone ) . '</td></tr>';

}

if ( ! empty( $email ) ) {

	$entry_page .= '<tr><th class="option ' . esc_attr( $email_class ) . '"><span>' . __( 'Email', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext email ' . esc_attr( $email_class ) . '">' . esc_html( $email ) . '</td></tr>';

}

if ( ! empty( $ip ) ) {

	$entry_page .= '<tr><th class="option last"><span>' . __( 'IP', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext last">' . esc_html( $ip ) . '</td></tr>';

}

// Contact info data wrap: closing tab.
$entry_page .= '</tbody></table></div>';

// Message data wrap: opening tag.
$entry_page .= '<div id="message-data-column" class="columns-wrap right ' . esc_attr( $data_column_class ) . '">';

$entry_page .= '<table class="entrie-table"><tbody>';

$entry_page .= '<tr id="submission-id" class="first"><th id="thdate" class="option first"><span>' . __( 'Date', 'simpleform-contact-form-submissions' ) . '</span></th><td id="tddate" class="plaintext first toggle">' . esc_html( $data_entry_date ) . '<span id="toggle-message" data-title="' . esc_attr__( 'Show contact info', 'simpleform-contact-form-submissions' ) . '" class="dashicons dashicons-arrow-right-alt2 contact-info right ' . esc_attr( $toggle_class ) . '"></span></td></tr>';

if ( ! empty( $subject ) ) {

	$entry_page .= '<tr><th class="option"><span>' . __( 'Subject', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext">' . esc_html( stripslashes( $subject ) ) . '</td></tr>';

}

$entry_page .= '<tr><th class="option request"><span>' . __( 'Message', 'simpleform-contact-form-submissions' ) . '</span></th><td class="plaintext request">' . wpautop( stripslashes( $entry_message ) ) . '</td></tr>';

// Message data wrap: closing tag.
$entry_page .= '</tbody></table>';

// Editing options: opening tag.
$entry_page .= '<h2 id="h2-editing" class="options-heading ' . esc_attr( $editing_class ) . '"><span class="editing" data-section="editing">' . __( 'Editing', 'simpleform' ) . '<span class="toggle dashicons editing ' . esc_attr( $dashicons_arrow ) . '"></span></span></h2><div class="section editing ' . esc_attr( $editing ) . '">';

// Editing options wrap: opening tag.
$entry_page .= '<table class="form-table editing"><tbody>';

$entry_page .= '<tr><th id="thstatus" class="option ' . esc_attr( $status_class ) . '"><span>' . __( 'Status', 'simpleform' ) . '</span></th><td id="tdstatus" class="select ' . esc_attr( $status_class ) . '"><select name="message-status" id="message-status" class="sform ' . esc_attr( $color ) . '"><option value="" disabled>' . __( 'Mark as', 'simpleform-contact-form-submissions' ) . '</option><option value="new">' . __( 'unread', 'simpleform-contact-form-submissions' ) . '</option><option value="read" ' . esc_html( $read_selected ) . '>' . __( 'read', 'simpleform-contact-form-submissions' ) . '</option><option value="answered" ' . esc_html( $answered_selected ) . '>' . __( 'answered', 'simpleform-contact-form-submissions' ) . '</option><option value="spam" ' . esc_html( $spam_selected ) . '>' . __( 'junk', 'simpleform-contact-form-submissions' ) . '</option><option value="trash" ' . esc_html( $trash_selected ) . '>' . __( 'trashed', 'simpleform-contact-form-submissions' ) . '</option></select>' . $mailto_button . '</td></tr>';

$entry_page .= '<tr class="trmovable ' . esc_attr( $moving_class ) . '"><th id="thmoving" class="option last"><span>' . __( 'Moving', 'simpleform-contact-form-submissions' ) . '</span></th><td id="tdmoving" class="checkbox-switch last"><div class="switch-box"><label class="switch-input"><input type="checkbox" name="moving" id="moving" class="sform-switch" value="false"><span></span></label><label for="moving" class="switch-label">' . __( 'Allow the entry to be moved to another form', 'simpleform-contact-form-submissions' ) . '</label></div></td></tr>';

$entry_page .= '<tr class="trmoving unseen"><th id="thmoveto" class="option last"><span>' . __( 'Move To', 'simpleform' ) . '</span></th><td id="tdmoveto" class="select last"><select name="moveto" id="moveto" class="sform ' . esc_attr( $color ) . '">' . $options . '</select></td></tr>';

// Editing options wrap: closing tag.
$entry_page .= '</tbody></table>';

// Save changes button.
$entry_page .= '<div id="submit-wrap"><div id="alert-wrap"><noscript><div id="noscript">' . __( 'You need JavaScript enabled to edit entry data. Please activate it. Thanks!', 'simpleform-contact-form-submissions' ) . '</div></noscript><div id="message-wrap" class="message"></div></div><input type="submit" id="edit-entry" name="edit-entry" class="submit-button" value="' . esc_attr__( 'Save Changes', 'simpleform-contact-form-submissions' ) . '">' . wp_nonce_field( 'simpleform_backend_update', 'simpleform_nonce', false, false ) . '</div>';

// Editing options: closing tag.
$entry_page .= '</div>';

// Message data wrap: closing tag.
$entry_page .= '</div>';

// Entry data wrap: closing tag.
$entry_page .= '</div>';

// Form closing tag.
$entry_page .= '</form>';

// Page wrap: closing tag.
$entry_page .= '</div>';

echo wp_kses( $entry_page, $allowed_tags );
