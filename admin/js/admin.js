/**
 * JavaScript code delegated to the backend functionality of the plugin.
 *
 * @package SimpleForm Contact Form Submissions
 * @subpackage SimpleForm Contact Form Submissions/admin
 */

(function( $ ) {
	'use strict';

	$( window ).on(
		'load',
		function() {

			var toggle       = document.getElementsByClassName( 'contact-info' );
			var toggleLength = toggle.length;

			for ( var i = 0; i < toggleLength; i++ ) {
				toggle[i].addEventListener(
					'click',
					function() {
						document.getElementById( 'message-wrap' ).classList.remove( 'seen' );
						var info = document.getElementById( 'submitter-data' );
						info.classList.toggle( 'unseen' );
						document.getElementById( 'toggle-message' ).classList.toggle( 'unseen' );
						document.getElementById( 'message-data-column' ).classList.toggle( 'fullwidth' );
						var prev_link = document.getElementById( 'prev-link' );
						var next_link = document.getElementById( 'next-link' );
						if ( info.classList.contains( 'unseen' ) ) {
							if ( prev_link ) {
								var href_prev  = prev_link.getAttribute( 'href' );
								let prevParams = new URLSearchParams( href_prev );
								if ( prevParams.has( 'info' ) === false ) {
									var new_href_prev = href_prev + '&info=hidden';
									prev_link.setAttribute( 'href', new_href_prev );
								}
							}
							if ( next_link ) {
								var href_next  = next_link.getAttribute( 'href' );
								let nextParams = new URLSearchParams( href_next );
								if ( nextParams.has( 'info' ) === false ) {
									var new_href_next = href_next + '&info=hidden';
									next_link.setAttribute( 'href', new_href_next );
								}
							}
						} else {
							if ( prev_link ) {
								var href_prev  = prev_link.getAttribute( 'href' );
								let prevParams = new URLSearchParams( href_prev );
								if ( prevParams.has( 'info' ) === true ) {
									var new_href_prev = href_prev.replace( '&info=hidden', '' );
									prev_link.setAttribute( 'href', new_href_prev );
								}
							}
							if ( next_link ) {
								var href_next  = next_link.getAttribute( 'href' );
								let nextParams = new URLSearchParams( href_next );
								if ( nextParams.has( 'info' ) === true ) {
									var new_href_next = href_next.replace( '&info=hidden', '' );
									next_link.setAttribute( 'href', new_href_next );
								}
							}
						}
					}
				);
			}

			$( '#message-status' ).on(
				'change',
				function(e) {
					var option = $( this ).val();
					if ( option != 'spam' && option != 'trash' ) {
						$( '#reply' ).addClass( 'unseen' );
						$( '.trmovable' ).removeClass( 'unseen' );
						$( '#thstatus, #tdstatus' ).removeClass( 'last' );
						if ( $( '#moving' ).prop( 'checked' ) == true ) {
							$( '.trmoving' ).removeClass( 'unseen' );
							$( '#thmoveto, #tdmoveto' ).addClass( 'last' );
						} else {
							$( '#thmoving, #tdmoving' ).addClass( 'last' );
						}
						if ( option == 'read' || option == 'new' ) {
							$( '#reply' ).removeClass( 'unseen' );
							$( '.reply-message.button' ).removeClass( 'unseen' );
						} else {
							$( '#reply' ).addClass( 'unseen' );
							$( '.reply-message.button' ).addClass( 'unseen' );
						}
					} else {
						$( '.trmovable, .trmoving, .reply-message.button' ).addClass( 'unseen' );
						$( '#thstatus, #tdstatus' ).addClass( 'last' );
						$( '#thmoving, #tdmoving, #thmoveto, #tdmoveto' ).removeClass( 'last' );
					}
				}
			);

			$( '#moving' ).on(
				'click',
				function() {
					if ( $( this ).prop( 'checked' ) == true ) {
						$( this ).val( 'true' );
						$( '.trmoving' ).removeClass( 'unseen' );
						$( '#thmoving, #tdmoving' ).removeClass( 'last' );
						$( '#thmoveto, #tdmoveto' ).addClass( 'last' );
					} else {
						$( this ).val( 'false' );
						$( '.trmoving' ).addClass( 'unseen' );
						$( '#moveto' ).val( '' );
						$( '#thmoving, #tdmoving' ).addClass( 'last' );
						$( '#thmoveto, #tdmoveto' ).removeClass( 'last' );
					}
				}
			);

			// Switch the column with row actions.
			$( '.hide-column-tog' ).on(
				'click',
				function(e) {
					var column = $( this ).val();
					if ( column === 'id' ) {
						if ( $( this ).prop( 'checked' ) == true ) {
							$( '#id-actions' ).removeClass( 'hidden' );
							$( '#subject-actions' ).addClass( 'hidden' );
							$( '.column-id' ).addClass( 'column-primary' );
							$( '.column-subject' ).removeClass( 'column-primary' );
						} else {
							if ( $( '#subject-hide' ).length > 0 && $( '#subject-hide' ).prop( 'checked' ) == true ) {
								$( '#subject-actions' ).removeClass( 'hidden' );
								$( '#id-actions' ).addClass( 'hidden' );
								$( '.column-id' ).removeClass( 'column-primary' );
								$( '.column-subject' ).addClass( 'column-primary' );
							}
						}
					}
					if ( column === 'subject' ) {
						if ( $( this ).prop( 'checked' ) == true ) {
							if ( ( $( '#id-hide' ).length > 0 && $( '#id-hide' ).prop( 'checked' ) == false ) || $( '#id-hide' ).length == 0 ) {
								$( '#subject-actions' ).removeClass( 'hidden' );
								$( '.column-id' ).removeClass( 'column-primary' );
								$( '.column-subject' ).addClass( 'column-primary' );
							}
						} else {
							$( '#subject-actions' ).addClass( 'hidden' );
							$( '.column-id' ).addClass( 'column-primary' );
							$( '.column-subject' ).removeClass( 'column-primary' );
						}
					}
					// Display error if no column has row actions.
					if ( $( '#id-hide' ).length > 0 && $( '#id-hide' ).prop( 'checked' ) == false && $( '#subject-hide' ).length == 0 ) {
						$( '.submission-notice' ).html( sform_submissions_object.id_notice );
					} else if ( $( '#subject-hide' ).length > 0 && $( '#subject-hide' ).prop( 'checked' ) == false && $( '#id-hide' ).length == 0 ) {
						$( '.submission-notice' ).html( sform_submissions_object.subject_notice );
					} else if ( $( '#id-hide' ).length > 0 && $( '#id-hide' ).prop( 'checked' ) == false && $( '#subject-hide' ).length > 0 && $( '#subject-hide' ).prop( 'checked' ) == false ) {
						$( '.submission-notice' ).html( sform_submissions_object.combo_notice );
					} else {
						$( '.submission-notice' ).html( '' );
					}
				}
			);

			$( '#edit-entry' ).on(
				'click',
				function(e) {
					$( '.message' ).removeClass( 'error success unchanged' );
					$( '#message-wrap' ).addClass( 'seen' );
					$( '.message' ).text( sform_submissions_object.saving );
					var formData = $( 'form#submission-tab' ).serialize();
					var formname = $( '#form_name' ).text()
					var formname = $( '#moveto option:selected' ).text();
					var status   = $( '#message-status' ).val();
					var newform  = $( '#moveto' ).val();
					$.ajax(
						{
							type: 'POST',
							dataType: 'json',
							url: sform_submissions_object.ajaxurl,
							data: formData + '&action=edit_entry',
							success: function( data ){
								var error          = data['error'];
								var update         = data['update'];
								var current_form   = data['current_form'];
								var current_status = data['current_status'];
								var entries        = data['entries'];
								var options        = data['options'];
								var message        = data['message'];
								if ( error === true ) {
									$( '.message' ).addClass( 'error' );
									$( '.message' ).html( data.message );
								}
								if ( error === false ) {
									if ( update === false ) {
										$( '.message' ).addClass( 'unchanged' );
									} else {
										$( '#view-counter' ).text( entries );
										if ( status !== current_status ) {
											if ( status == 'new' ) {
												var unread = parseInt( $( '#unread-messages' ).text(), 10 );
												if ( isNaN( unread ) ) {
													$( '#unread-messages' ).html( '<span class="sform awaiting-mod">1</span>' );
												} else {
													var number = ++unread;
													$( '.sform.awaiting-mod' ).text( number );
												}
											} else {
												var unread = parseInt( $( '#unread-messages' ).text(), 10 );
												if ( ! isNaN( unread ) && current_status == 'new' ) {
													if ( unread > '1' ) {
														var number = --unread;
														$( '.sform.awaiting-mod' ).text( number );
													} else {
														$( '#unread-messages' ).html( '' );
													}
												}
											}
										}
										$( '.message' ).addClass( 'success' );
										$( '.form-name' ).html( data.current_form );
										$( '#moveto' ).html( data.options );
										$( '#moving' ).prop( 'checked', false );
										$( '.trmoving' ).addClass( 'unseen' );
										$( '#thmoving, #tdmoving' ).addClass( 'last' );
									}
									$( '.message' ).html( data.message );
								}
							},
							error: function( data ) {
								$( '.message' ).html( 'AJAX call failed' );
							}
						}
					);
					e.preventDefault();
					return false;
				}
			);

			$( document ).on(
				'input',
				function() {
					$( '#message-wrap' ).removeClass( 'seen' );
				}
			);

			$( '#storing' ).on(
				'click',
				function() {
					if ( $( this ).prop( 'checked' ) == true) {
						$( '.trstoring' ).removeClass( 'unseen' );
						$( '#storing-description' ).html( sform_submissions_object.disable );
						if ( $( '#counter' ).prop( 'checked' ) == true ) {
							$( '.sform.awaiting-mod' ).removeClass( 'unseen' );
						}
					} else {
						$( '.trstoring' ).addClass( 'unseen' );
						$( '#storing-description' ).html( sform_submissions_object.enable );
						$( '.sform.awaiting-mod' ).addClass( 'unseen' );
					}
				}
			);

			$( '#counter' ).on(
				'click',
				function() {
					if ( $( this ).prop( 'checked' ) == true) {
						$( '.sform.awaiting-mod' ).removeClass( 'unseen' );
					} else {
						$( '.sform.awaiting-mod' ).addClass( 'unseen' );
					}
				}
			);

			// Prevent duplicate queries by appending the selector to move entries in the bottom bulk actions.
			if ( document.getElementById( 'moveto' ) != null && document.getElementById( 'bulk-action-selector-bottom' ) != null ) {
				const node         = document.getElementById( 'moveto' );
				const clone        = node.cloneNode( true );
				var bottomSelector = document.getElementById( 'bulk-action-selector-bottom' );
				bottomSelector.parentNode.insertBefore( clone, bottomSelector.nextSibling );
			}

			$( '#bulk-action-selector-top, #bulk-action-selector-bottom' ).on(
				'change',
				function() {
					var selectbulk = $( this ).val();
					if ( selectbulk === 'bulk-move' ) {
						$( '.moveto' ).removeClass( 'unseen' );
					} else {
						$( '.moveto' ).addClass( 'unseen' );
						$( '.moveto' ).val( '' );
						$( '#move2' ).val( '' );
					}
				}
			);

			$( '.moveto' ).on(
				'change',
				function() {
					var form = $( this ).val();
					$( '#move2' ).val( form );
					$( '.moveto' ).val( form );
				}
			);

			$( 'span.editing' ).on(
				'click',
				function() {
					var section   = $( this ).attr( 'data-section' );
					var prev_link = document.getElementById( 'prev-link' );
					var next_link = document.getElementById( 'next-link' );
					$( '.section.' + section ).toggleClass( 'collapsed' );
					if ( ! $( '.section.' + section ).hasClass( 'collapsed' ) ) {
						$( 'span.toggle.' + section ).removeClass( 'dashicons-arrow-down-alt2' );
						$( 'span.toggle.' + section ).addClass( 'dashicons-arrow-up-alt2' );
						$( '#h2-' + section ).removeClass( 'closed' );
						if ( prev_link ) {
							var href_prev  = prev_link.getAttribute( 'href' );
							let prevParams = new URLSearchParams( href_prev );
							if ( prevParams.has( 'editing' ) === true ) {
								var new_href_prev = href_prev.replace( '&editing=hidden', '' );
								prev_link.setAttribute( 'href', new_href_prev );
							}
						}
						if ( next_link ) {
							var href_next  = next_link.getAttribute( 'href' );
							let nextParams = new URLSearchParams( href_next );
							if ( nextParams.has( 'editing' ) === true ) {
								var new_href_next = href_next.replace( '&editing=hidden', '' );
								next_link.setAttribute( 'href', new_href_next );
							}
						}
					} else {
						$( 'span.toggle.' + section ).removeClass( 'dashicons-arrow-up-alt2' );
						$( 'span.toggle.' + section ).addClass( 'dashicons-arrow-down-alt2' );
						$( '#h2-' + section ).addClass( 'closed' );
						if ( prev_link ) {
							var href_prev  = prev_link.getAttribute( 'href' );
							let prevParams = new URLSearchParams( href_prev );
							if ( prevParams.has( 'editing' ) === false ) {
								var new_href_prev = href_prev + '&editing=hidden';
								prev_link.setAttribute( 'href', new_href_prev );
							}
						}
						if ( next_link ) {
							var href_next  = next_link.getAttribute( 'href' );
							let nextParams = new URLSearchParams( href_next );
							if ( nextParams.has( 'editing' ) === false ) {
								var new_href_next = href_next + '&editing=hidden';
								next_link.setAttribute( 'href', new_href_next );
							}
						}
					}
				}
			);

		}
	);

})( jQuery );
