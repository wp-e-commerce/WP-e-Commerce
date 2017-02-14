window.WPSC_Purchase_Logs_Admin = window.WPSC_Purchase_Logs_Admin || {};

( function( window, document, $, wpsc, undefined ) {
	'use strict';
	var ENTER = 13;
	var ESCAPE = 27;
	var BR = "\n";
	var $c = {};
	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};

	var admin = {
		blur_timeout : null,
		reset_textbox_width : true,
		$ : $c
	};

	admin.cache = function() {
		$c.body           = $( document.body );
		$c.wrapper        = $( 'table.purchase-logs' );
		$c.details        = $( '.log-details-box' );
		$c.editDetails    = $id( 'edit-shipping-billing' );
		$c.editActions    = $c.editDetails.find( '.wpsc-form-actions' );
		$c.log            = $id( 'wpsc_items_ordered' );
		$c.discount_data  = $id( 'wpsc_discount_data' );
		$c.total_taxes    = $id( 'wpsc_total_taxes' );
		$c.total_shipping = $id( 'wpsc_total_shipping' );
		$c.final_total    = $id( 'wpsc_final_total' );
		$c.spinner        = $c.final_total.find( 'td:last .spinner' );
		$c.billingForm    = $id( 'wpsc-checkout-form-billing' );
		$c.shippingForm   = $id( 'wpsc-checkout-form-shipping' );
		$c.copyForm       = $id( 'wpsc-terms-and-conditions-control' );
		$c.notes          = $id( 'purchlogs_notes' );
	};

	admin.init = function() {
		admin.cache();

		if ( $c.wrapper.length ) {
			$c.wrapper.on( 'change'   , '.wpsc-purchase-log-status'     , admin.event_log_status_change );
			$c.wrapper.on( 'focus'    , '.wpsc-purchase-log-tracking-id', admin.event_tracking_id_focused );
			$c.wrapper.on( 'click'    , '.column-tracking a.add'        , admin.event_button_add_clicked );
			$c.wrapper.on( 'blur'     , '.wpsc-purchase-log-tracking-id', admin.event_tracking_id_blurred );
			$c.wrapper.on( 'click'    , '.column-tracking a.save'       , admin.event_button_save_clicked );
			$c.wrapper.on( 'click'    , '.column-tracking .send-email a', admin.event_button_send_email_clicked );
			$c.wrapper.on( 'keypress' , '.wpsc-purchase-log-tracking-id', admin.event_enter_key_pressed );
			$c.wrapper.on( 'mousedown', '.column-tracking a.save'       , admin.event_disable_textbox_resize );
			$c.wrapper.on( 'focus'    , '.column-tracking a.save'       , admin.event_disable_textbox_resize );
		}

		if ( $c.log.length ) {

			admin.product_search = admin.init_search_view();

			$c.log
				.on( 'click', '.wpsc-remove-item-button', admin.remove_item )
				.on( 'keypress', '.wpsc_item_qty', admin.maybe_update_qty )
				.on( 'change', '.wpsc_item_qty', admin.update_qty )
				.on( 'click', '.wpsc-add-item-button', function() { admin.product_search.trigger( 'open' ); } )
				.on( 'click', '.refund-items', admin.toggleRefundsUI )
				.on( 'click', '.capture-payment', admin.capturePayment )
				.on( 'click', 'button.do-api-refund, button.do-manual-refund', admin.refundItem );
			$c.body.on( 'click', '.ui-find-overlay', function() { admin.product_search.trigger( 'close' ); } );

			$c.editDetails
				.on( 'submit', 'form', admin.handleEditDetails )
				.on( 'click', '.button-secondary', admin.toggleEditDetails );

			$c.details
				.on( 'click', '.edit-log-details', admin.toggleEditDetails );

			$c.notes
				.on( 'submit' , '#note-submit-form'       , admin.addNote )
				.on( 'keydown', '#note-submit-form'       , admin.commandEnterAddNote )
				.on( 'click'  , '.wpsc-remove-note-button', admin.deleteNote );

			window.postboxes.add_postbox_toggles( window.pagenow );

			$c.editActions.prepend( '<button type="button" class="button-secondary">'+ wpsc.strings.cancel_btn +'</button>' );
		}

	};

	admin.toggleRefundsUI = function() {
			$( '.wpsc-refund-ui' ).toggle();
	};

	admin.refundItem = function( evt ) {
		var $button       = $( this );
		var $spinner      = $button.parents( 'tr' ).find('.spinner');
		var api_refund    = $button.is( '.do-api-refund' );
		var refund_string = api_refund ? wpsc.strings.confirm_refund_order : wpsc.strings.confirm_refund_order_manually;

		if ( ! window.confirm( refund_string ) ) {
			return;
		}

		var data = {
			action        : 'purchase_log_refund_items',
			order_id      : wpsc.log_id,
			refund_reason : $( 'input#refund_reason' ).val(),
			refund_amount : $( 'input#refund_amount' ).val(),
			api_refund    : api_refund,
			nonce         : wpsc.purchase_log_refund_items_nonce
		};

		var ajax_callback = function( response ) {
			$spinner.removeClass( 'is-active' );
			if ( ! response.is_successful ) {
				if ( response.error ) {
					window.alert( response.error.messages.join( BR ) );
				}
			} else {

				setTimeout( function() {
					// Re-spinner while we refresh page.
					$spinner.addClass( 'is-active' );
				}, 900 );

				window.location.href = window.location.href;
			}
		};

		$spinner.addClass( 'is-active' );

		$.wpsc_post( data, ajax_callback );
	};

	admin.capturePayment = function( evt ) {
		var $button       = $( this );
		var $spinner      = $button.siblings( '.spinner' );

		var data = {
			action         : 'purchase_log_capture_payment',
			order_id       : wpsc.log_id,
			nonce          : wpsc.purchase_log_capture_payment_nonce
		};

		var ajax_callback = function( response ) {
			$spinner.removeClass( 'is-active' );
			if ( ! response.is_successful ) {
				if ( response.error ) {
					window.alert( response.error.messages.join( BR ) );
				}
			} else {
				setTimeout( function() {
					// Re-spinner while we refresh page.
					$spinner.addClass( 'is-active' );
				}, 900 );

				window.location.href = window.location.href;
			}
		};

		$spinner.addClass( 'is-active' );

		$.wpsc_post( data, ajax_callback );
	};

	admin.event_enter_key_pressed = function(evt) {
		var code = evt.keyCode ? evt.keyCode : evt.which;
		if ( ENTER === code ) {
			$(this).siblings('.save').click();
			evt.preventDefault();
		}
	};

	admin.event_button_send_email_clicked = function() {
		var $this = $(this);

		var post_data = {
			action : 'purchase_log_send_tracking_email',
			log_id : $this.closest('div').data('log-id'),
			nonce  : wpsc.purchase_log_send_tracking_email_nonce
		};

		var ajax_callback = function(response) {
			if (! response.is_successful) {
				window.alert(response.error.messages.join(BR));
				$this.show().siblings('em').remove();
				return;
			}
			$this.siblings('em').addClass('sent').text(wpsc.sent_message);
			$this.remove();
		};

		$this.hide().after('<em>' + wpsc.sending_message + '</em>');
		$.wpsc_post(post_data, ajax_callback);

		return false;
	};

	admin.event_button_save_clicked = function() {
		var $this = $(this);
		var $textbox = $this.siblings('.wpsc-purchase-log-tracking-id');
		var $spinner = $this.siblings('.ajax-feedback');

		var post_data = {
			action : 'purchase_log_save_tracking_id',
			value  : $textbox.val(),
			log_id : $this.parent().data('log-id'),
			nonce  : wpsc.purchase_log_save_tracking_id_nonce
		};

		var ajax_callback = function(response) {
			$spinner.toggleClass('ajax-feedback-active');
			$textbox.blur();
			if (! response.is_successful) {
				window.alert(response.error.messages.join(BR));
				return;
			}
			$this.parent().removeClass('empty');
			admin.reset_tracking_id_width($this.siblings('.wpsc-purchase-log-tracking-id'));
		};

		$this.hide();
		$spinner.toggleClass('ajax-feedback-active');
		$textbox.width(160);

		$.wpsc_post(post_data, ajax_callback);

		return false;
	};

	admin.event_disable_textbox_resize = function() {
		admin.reset_textbox_width = false;
	};

	admin.event_button_add_clicked = function() {
		$(this).siblings('.wpsc-purchase-log-tracking-id').trigger('focus');
		return false;
	};

	admin.reset_tracking_id_width = function($obj) {
		var reset_width = function() {
			if (admin.reset_textbox_width) {
				$obj.siblings('a.save').hide();
				$obj.width('');
				if ($obj.val() === '') {
					$obj.siblings('.add').show();
				}
			}

			admin.reset_textbox_width = true;
		};

		admin.blur_timeout = setTimeout(reset_width, 100);
	};

	admin.event_tracking_id_blurred = function() {
		admin.reset_tracking_id_width( $(this) );
	};

	admin.event_tracking_id_focused = function() {
		var $this = $(this);
		$this.width(128);
		$this.siblings('a.save').show();
		$this.siblings('a.add').hide();
	};

	admin.event_log_status_change = function() {
		var $this = $(this);
		var post_data = {
			nonce      : wpsc.change_purchase_log_status_nonce,
			action     : 'change_purchase_log_status',
			id         : $this.data('log-id'),
			new_status : $this.val(),
			m          : wpsc.current_filter,
			status     : wpsc.current_view,
			paged      : wpsc.current_page,
			_wp_http_referer : window.location.href
		};
		var spinner = $this.siblings('.ajax-feedback');
		spinner.addClass('ajax-feedback-active');
		var ajax_callback = function(response) {
			if (! response.is_successful) {
				window.alert(response.error.messages.join(BR));
				return;
			}
			spinner.removeClass('ajax-feedback-active');
			$('ul.subsubsub').replaceWith(response.obj.views);
			$('.tablenav.top').replaceWith(response.obj.tablenav_top);
			$('.tablenav.bottom').replaceWith(response.obj.tablenav_bottom);
		};

		$.wpsc_post(post_data, ajax_callback);
	};

	admin.remove_item = function() {
		if ( ! window.confirm( wpsc.strings.confirm_delete_item ) ) {
			return;
		}

		var $this = $( this );
		var $row  = $this.parents( '.purchase-log-line-item' );
		var args  = {
			action : 'remove_log_item',
			log_id  : wpsc.log_id,
			item_id : $row.data( 'id' ),
			nonce  : wpsc.remove_log_item_nonce
		};

		var ajax_callback = function(response) {
			if ( ! response.is_successful ) {
				if ( response.error ) {
					window.alert( response.error.messages.join( BR ) );
				}

				return;
			}

			admin.update_totals( response.obj );

			$row.fadeOut( 600, function() {
				$( this ).remove();
			} );
		};

		$c.spinner.addClass( 'is-active' );

		$.wpsc_post( args, ajax_callback );
	};

	admin.maybe_update_qty = function( evt ) {
		var code = evt.keyCode ? evt.keyCode : evt.which;
		if ( ENTER === code ) {
			evt.preventDefault();
			admin.update_qty.call( this, evt );
		}
	};

	admin.update_qty = function( evt ) {
		if ( 'keypress' === evt.type ) {
			admin.update_qty.disable_change = true;
		}

		if ( 'change' === evt.type && true === admin.update_qty.disable_change ) {
			admin.update_qty.disable_change = false;
			return;
		}

		var $this = $( this );
		var $row  = $this.parents( '.purchase-log-line-item' );
		var args  = {
			action  : 'update_log_item_qty',
			log_id  : wpsc.log_id,
			item_id : $row.data( 'id' ),
			qty     : $this.val(),
			nonce   : wpsc.update_log_item_qty_nonce
		};

		if ( 0 === parseInt( args.qty, 10 ) ) {
			return $row.find( '.wpsc-remove-item-button' ).trigger( 'click' );
		}

		var ajax_callback = function(response) {
			if ( ! response.is_successful ) {
				if ( response.error ) {
					window.alert( response.error.messages.join( BR ) );
				}

				return;
			}

			if ( response.obj.final_total ) {
				admin.update_totals( response.obj );
			}
		};

		$c.spinner.addClass( 'is-active' );
		$.wpsc_post( args, ajax_callback );
	};

	admin.update_totals = function( data ) {
		$c.discount_data.find( 'td' ).first().html( data.discount_data );
		$c.discount_data.find( 'td' ).last().html( data.discount );

		if ( $c.total_taxes.length ) {
			$c.total_taxes.find( 'td' ).last().html( data.total_taxes );
		}

		$c.total_shipping.find( 'td' ).last().html( data.total_shipping );
		$c.final_total.find( 'td:last span' ).html( data.final_total );

		$c.spinner.removeClass( 'is-active' );

		$.each( data.quantities, function( id, qty ) {
			qty = parseInt( qty, 10 );
			var $input = $c.log.find( '#purchase-log-item-' + id + ' .wpsc_item_qty' );
			var val = $input.val();
			var $price, $new_price;

			if ( parseInt( val, 10 ) !== parseInt( qty, 10 ) ) {
				$input.val( qty );
			}

			$price = $c.log.find( '#purchase-log-item-' + id + ' .amount .pricedisplay' );
			$new_price = $( data.htmls[ id ] ).find( '.amount .pricedisplay' );
			if ( $price.length && $new_price.length ) {
				$price.text( $new_price.text() );
			}
		} );
	};

	admin.toggleEditDetails = function( evt ) {
		evt.preventDefault();

		var strings = window.WPSC.copyBilling.strings;

		$c.editDetails.slideToggle( 400, function() {
			if ( $( evt.target ).hasClass( 'edit-shipping-details' ) ) {
				$c.billingForm.find( 'h2' ).replaceWith( strings.billing );
				$c.shippingForm.removeClass( 'ui-helper-hidden' );

			} else if ( $c.copyForm.is( ':checked' ) ) {
				$c.billingForm.find( 'h2' ).replaceWith( strings.billing_and_shipping );
				$c.shippingForm.addClass( 'ui-helper-hidden' );
			}
		} );
	};

	admin.handleEditDetails = function( evt ) {
		evt.preventDefault();

		var args = {
			action : 'edit_contact_details',
			log_id : wpsc.log_id,
			nonce  : wpsc.edit_contact_details_nonce,
			fields : $c.editDetails.find( 'form' ).serialize()
		};

		var ajax_callback = function( response ) {
			$c.editActions.find( '.spinner' ).remove();

			if ( ! response.is_successful ) {
				if ( response.error ) {
					window.alert( response.error.messages.join( BR ) );
				}

				return;
			}

			$id( 'wpsc-shipping-details' ).html( response.obj.shipping );
			$id( 'wpsc-billing-details' ).html( response.obj.billing );
			$id( 'wpsc-payment-details' ).html( response.obj.payment );

			// Trigger the edit form to slide closed.
			admin.toggleEditDetails( evt );
		};

		$c.editActions.prepend( '<div class="spinner is-active"></div>' );

		$.wpsc_post( args, ajax_callback );
	};

	admin.commandEnterAddNote = function( evt ) {
		if ( ( evt.metaKey || evt.ctrlKey ) &&  evt.keyCode === ENTER ) {
			admin.addNote( evt );
		}
	};

	admin.addNote = function( evt ) {
		evt.preventDefault();

		$c.notesText = $c.notesText || $id( 'purchlog_notes' );
		var args = {
			action : 'add_note',
			log_id : wpsc.log_id,
			nonce  : wpsc.add_note_nonce,
			note   : $c.notesText.val()
		};

		var ajax_callback = function(response) {
			$c.notes.find( '.spinner' ).removeClass( 'is-active' );

			if ( ! response.is_successful ) {
				if ( response.error ) {
					window.alert( response.error.messages.join( BR ) );
				}

				return;
			}

			$c.notes.find( '.wpsc-notes' ).prepend( response.obj );
			$c.notesText.val( '' );
		};

		$c.notes.find( '.spinner' ).addClass( 'is-active' );

		$.wpsc_post( args, ajax_callback );
	};

	admin.deleteNote = function( evt ) {
		evt.preventDefault();

		if ( ! window.confirm( wpsc.strings.confirm_delete_note ) ) {
			return;
		}

		var $this = $( this );
		var $row  = $this.parents( '.wpsc-note' );
		var args  = {
			action : 'delete_note',
			log_id : wpsc.log_id,
			nonce  : wpsc.delete_note_nonce,
			note   : $row.data( 'id' )
		};

		var ajax_callback = function(response) {
			if ( ! response.is_successful ) {
				if ( response.error ) {
					$this.find( '.spinner' ).remove();
					window.alert( response.error.messages.join( BR ) );
				}

				return;
			}

			$row.slideUp( 600, function() {
				$( this ).remove();
			} );
		};

		$this.prepend( '<div class="spinner is-active"></div>' );

		$.wpsc_post( args, ajax_callback );
	};

	admin.init_search_view = function() {
		var SearchView = window.Backbone.View.extend( {
			el         : '#find-posts',
			overlaySet : false,
			$overlay   : false,
			$checked   : false,
			$table     : false,
			template   : wp.template( 'wpsc-found-product-rows' ),

			events : {
				'keypress .find-box-search :input' : 'maybeStartSearch',
				'keyup #find-posts-input'  : 'escClose',
				'click #find-posts-submit' : 'selectPost',
				'click #find-posts-search' : 'send',
				'click #find-posts-close'  : 'close'
			},

			initialize: function() {
				this.$spinner  = this.$el.find( '.find-box-search .spinner' );
				this.$input    = this.$el.find( '#find-posts-input' );
				this.$response = this.$el.find( '#find-posts-response' );
				this.$overlay  = $( '.ui-find-overlay' );
				this.$table = $( $id( 'tmpl-wpsc-found-products' ).html() );

				this.listenTo( this, 'open', this.open );
				this.listenTo( this, 'close', this.close );
			},

			escClose: function( evt ) {
				var code = evt.keyCode ? evt.keyCode : evt.which;
				if ( ESCAPE === code ) {
					this.close();
				}
			},

			close: function() {
				this.$overlay.hide();
				this.$el.hide();
			},

			open: function() {
				this.$response.html('');

				// WP, why you gotta be like that? (why isn't text in its own dom node?)
				this.$el.show().find( '#find-posts-head' ).html( wpsc.strings.search_head + '<div id="find-posts-close"></div>' );

				this.$input.focus();

				if ( ! this.$overlay.length ) {
					$( 'body' ).append( '<div class="ui-find-overlay"></div>' );
					this.$overlay  = $( '.ui-find-overlay' );
				}

				this.$overlay.show();

				// Pull some results up by default
				this.send();

				return false;
			},

			maybeStartSearch: function( evt ) {
				var code = evt.keyCode ? evt.keyCode : evt.which;
				if ( ENTER === code ) {
					this.send();
					return false;
				}
			},

			send: function() {

				var that = this;
				that.$spinner.addClass( 'is-active' );

				var args  = {
					action  : 'search_products',
					search : that.$input.val(),
					nonce   : wpsc.search_products_nonce
				};

				$.wpsc_post( args )
					.always( function() {

						that.$spinner.removeClass('is-active');

					} ).done( function( response ) {

						if ( ! response.is_successful ) {
							if ( response.error ) {
								that.$response.text( response.error.messages.join( BR ) );
							}
							return;
						}

						that.$table.children( 'tbody' ).html( that.template( { posts : response.obj } ) );
						that.$response.html( that.$table );

					} ).fail( function() {
						that.$response.text( that.errortxt );
					} );
			},

			selectPost: function( evt ) {
				evt.preventDefault();

				this.$checked = $( '#find-posts-response input[type="checkbox"]:checked' );

				var checked = this.$checked.map(function() { return this.value; }).get();

				if ( ! checked.length ) {
					this.close();
					return;
				}

				this.handleSelected( checked );
			},

			handleSelected: function( checked ) {
				var that = this;

				var existing = $c.log.find( '[data-productid]' ).map( function() {
					return $( this ).data( 'productid' );
				} ).get();

				var args = {
					action      : 'add_log_item',
					product_ids : checked,
					existing    : existing,
					log_id      : wpsc.log_id,
					nonce       : wpsc.add_log_item_nonce
				};

				var ajax_callback = function(response) {
					if ( ! response.is_successful ) {
						if ( response.error ) {
							window.alert( response.error.messages.join( BR ) );
						}

						return;
					}

					$c.log.find( '.wpsc_purchaselog_add_product' ).before( response.obj.html );

					admin.update_totals( response.obj );

					that.close();
				};

				$c.spinner.addClass( 'is-active' );

				$.wpsc_post( args, ajax_callback );
			}

		} );

		return new SearchView();
	};


	$.extend( wpsc, admin );

	$( wpsc.init );

} )( window, document, jQuery, window.WPSC_Purchase_Logs_Admin );
