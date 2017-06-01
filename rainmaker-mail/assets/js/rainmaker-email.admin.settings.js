var imageTarget   = '',
	previewTarget = '';

function loadAddress() {
	jQuery('#rainmail_current_footer_address').html('<div class="spinner-page" style="display:block;width:190px">Loading current address...</div>');

	var data = {
		'action'  : 'rainmail_ajax_get_address',
		'service' : 'rainmail',
	};

	jQuery.ajax( {
		url      : ajaxurl,
		data     : data,
		dataType : 'html',
		method   : 'post',
		success  : function(response, textStatus, jqXHR) {
			jQuery('#rainmail_current_footer_address').html( response );

		},
		error    : function(jqXHR, textStatus) {
			jQuery('#rainmail_current_footer_address').html( 'There was an error retrieving the address. <a href="#" class="rainmail_reload_address">Retry?</a>' );
		},
		complete : function(jqXHR, textStatus) {

		}
	});

}

jQuery(document).ready( function($){

	$('.email-sidebar-content, .email-custom-template').hide();

	if( $('#email-template-sidebar:checked, #email-template-custom:checked').length > 0 ) {

		$('.email-sidebar-content').show();

	}

	if( $('#email-template-custom:checked').length > 0 ) {

		$('.email-custom-template').show();

	}

	$( '.email-template-option input' ).change( function() {

		if (this.checked && this.value != 'basic') {
			$('.email-sidebar-content').fadeIn();
		} else {
			$('.email-sidebar-content').fadeOut();
		}

		if (this.checked && this.value == 'custom') {
			$('.email-custom-template').fadeIn();
		} else {
			$('.email-custom-template').fadeOut();
		}

	});

	function has_content_tag() {

		var content = $('#custom_email_template').val();
			index   = content.indexOf('{{content}}');

		if ( ! content ) {
			return true; //allow no content to pass as it will reset the custom template
		}

			$('#custom_email_template').val(content.replace(/    /g, '	').replace(/  /g, ''));

		if( content.indexOf('{{content}}') == -1 ) {

			alert( 'Oops, looks like you didn\'t include {{content}} in your custom email template. This is required and your template will not work right without it. Please update your template to include that field.' );

			return false;

		} else {
			return true;
		}


	}

	$('body').on( 'blur', '#custom_email_template',  function(){

		has_content_tag();

	});

	$('body').on( 'click', '#submit', function(){

		if ( ! has_content_tag() ) {
			return false;
		}

	});

	//header image
	$('.button-upload-image').click(function() {

		imageTarget   = $(this).attr('href');
		previewTarget = $(this).data('preview');

		var image = wp.media({
			title: 'Upload Image',
			multiple: false,
			library: {
				type: 'image'
			},
			button: {
				text: 'Use selected image'
			}
		}).open()
		.on('select', function(e){

			// This will return the selected image from the Media Uploader, the result is an object
			var uploaded_image = image.state().get('selection').first();
			// We convert uploaded_image to a JSON object to make accessing it easier
			var image_url = uploaded_image.toJSON().url;
			// Let's assign the url value to the input field
			$(imageTarget).val(image_url);
			$(previewTarget).attr( 'src', image_url);

		});

		return false;

	});

	$('body').on( "focusout", '#email_template_logo', function(){

		image_url = ( $(this).val().length > 0 ) ? $(this).val() : $('#email_template_logo_default').val();

		$('#email_template_logo_preview').attr('src', image_url );

	});

	$('body').on( 'click', '.button-remove-image', function() {

		imageTarget   = $(this).attr('href');
		previewTarget = $(this).data('preview');

		$(imageTarget).val('');
		$(previewTarget).attr( 'src', $('#email_template_logo_default').val());

		return false;
	});

	// Lists Scripts

	Tipped.delegate('.tool-tip-title', {
		skin: 'rainmaker'
	});

	loadAddress();

	function loadLists() {

		$('#rainmail-edit-lists').html(`
			<table class="rm-small-table" id="rainmail-edit-lists">
				<tr><td><span class="spinner"> Loading lists...</span></td></tr>
			</table>`);

		$("#rainmail-edit-lists .spinner").css('float', 'left').css('width', 'auto').css('padding-left', '30px').show();


		var data = {
			'action'  : 'reload_edit_list',
			'service' : 'rainmail',
		};

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			dataType : 'html',
			method   : 'post',
			success  : function(response, textStatus, jqXHR) {
				$('#rainmail-edit-lists').html( response );
				$('#lists_loading').hide();
			},
			error    : function(jqXHR, textStatus) {
				$('#rainmail-edit-lists').html( '<tr><td>We couldn\'t retrieve your lists. <a href="" class="retryListRefresh">Retry?</a></td></tr>' );
			},
			complete : function(jqXHR, textStatus) {

			}
		});
	}



	$(".retryListRefresh").live("click", function(e) {
		e.preventDefault();
		loadLists();
	});

	$("#cancelsubmit").live("click", function(e) {
		e.preventDefault();
		$.magnificPopup.close();
	});

	$('.email_list_refresh_template').live('click', function(e) {
		e.preventDefault();

		var clicked = $(this);
		var listid = $(this).data('id');

		clicked.prop('disabled', true).text("Loading...").addClass('button-loading');

		Tipped.hideAll();

		var data = {
			'action'  : 'update_master_template',
			'id'      : listid

		}

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			dataType : 'html',
			method   : 'post',
			success  : function(response, textStatus, jqXHR) {
				console.log(response)
				clicked.removeClass('button-loading').addClass('button-success');
				setTimeout(function() {
					clicked.removeClass('button-success').text("Reload").prop('disabled', false);
				}, 5000);
			},
			error    : function(jqXHR, textStatus) {
				clicked.removeClass('button-loading').addClass('button-fail');
				setTimeout(function() {
					clicked.removeClass('button-fail').text("Reload").prop('disabled', false);
				}, 5000);
			},
			complete : function(jqXHR, textStatus) {

			}
		});

		console.log("Clicked refresh");
	});

	$('.email_list_edit').live('click', function(e) {
		e.preventDefault();
		var el = $(this);

		$.magnificPopup.open({
			items: {
				src: '#rainmail-new-list'
			},
			callbacks: {
				close: function() {
					$('#edit_list_title').html("Add New List");
					$('#addsubmit').text("Add List");
				},
				open: function() {
					$('#listSaveError').remove();
					$('#edit_list_title').html("Edit List");
					$('#addsubmit').text("Edit List");
					$('#new_id').val(el.data('id'));
					$('#new-name').val(el.data('name'));
					$('#new-desc').val(el.data('description'));
					$('#new-responder [value="'+ el.data('autoresponderid') +'"]').attr('selected', 'selected');
					$('#sub-landingpage [value="'+ el.data('landingpage') +'"]').attr('selected', 'selected');
					$('#un-redirect [value="'+ el.data('unsubscriberedirecturl') +'"]').attr('selected', 'selected');
					console.log(el.data('turbo'));
					console.log(el.data('link'));
					if ( el.data('turbo') == '-10' ) {
						$('#rainmail-rss-checkbox').prop('checked', true);
						$('.rainmail-rss-feed-url').show();
						$('#new-rss-url').val(el.data('link'));
					} else {
						$('#rainmail-rss-checkbox').prop('checked', false);
						$('.rainmail-rss-feed-url').hide();
						$('#new-rss-url').val('');
					}

				}
			},
			type: 'inline',
			preloader: true,
			modal: true
		}, 0);

	});

	$('#email_list_add').live('click', function(e) {
		e.preventDefault();

		var el = $(this);

		$.magnificPopup.open({
			items       : {
				src: '#rainmail-new-list'
			},
			callbacks   : {
				close : function() {

				},
				open  : function() {
					$('#rainmail-new-list input').val('');
					$('#listSaveError').remove();
					$('#rainmail-new-list textarea').val('');
					$('#rainmail-rss-checkbox').prop('checked', false);
					$('.rainmail-rss-feed-url').hide();
					$('#new-rss-url').val('');
					$('#rainmail-new-list option[selected="selected"]').each(
						function() {
							$(this).removeAttr('selected');
						}
					);
					$("#rainmail-new-list select").each(function() {
						$(this).find("option:first").attr('selected','selected');
					});
					//$("#rainmail-new-list option:first").attr('selected','selected');

				}
			},
			type      : 'inline',
			modal     : true,
			preloader : true,
		}, 0);

	});



	$( '#addsubmit' ).live( 'click', function(e) {

		e.preventDefault();
		var thisButton = $(this);
		var buttonText = thisButton.text();

		thisButton.text('Saving...');

		thisButton.prop('disabled', true);

		var id           = $( '#new_id' ).val();
		var name         = $( '#new-name' ).val();
		var desc         = $( '#new-desc' ).val();
		var landingpage  = $( '#sub-landingpage' ).val();
		var newresponder = $( '#new-responder' ).val();
		var unredirect   = $( '#un-redirect' ).val();
		var rssurl       = $( '#new-rss-url' ).val();

		var rss          = 0;
		if( $( '#rainmail-rss-checkbox' ).is(":checked") ){
			rss = 1;
		}

		var data = {
			'action'  : 'edit_optin_list',
			'service' : 'rainmail',
			'data'    : {
				'id'              : id,
				'name'            : name,
				'description'     : desc,
				'rss'             : rss,
				'rss-url'         : rssurl,
				'sub-landingpage' : landingpage,
				'new-responder'   : newresponder,
				'un-redirect'     : unredirect,
				'json'            : 1
			}
		};

		var dataType = 'html';

		if ( id == '' ) {
			dataType = 'json';
		}

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			method   : 'post',
			dataType : dataType,
			success  : function(response, textStatus, jqXHR) {
				$.magnificPopup.close();
				loadLists();
				if ( id != '' ) {
					updateTemplate(id);
				} else {
					updateTemplate(response.id);
				}

			},
			error    : function(jqXHR, textStatus) {
				$('.mfp-content').animate({
					scrollTop: $('#rainmail-new-list').offset().top - 20
				}, 'slow');
				$("#edit_list_title").after('<div id="listSaveError" class="error"><p>There was an error saving the list, please try again.</p></div>');
				console.log(textStatus);
			},
			complete : function(jqXHR, textStatus) {
				thisButton.prop('disabled', false);
				thisButton.text(buttonText);
			}
		});

	});

	function updateTemplate(id) {

		var data = {
			'action'  : 'update_master_template',
			'id'      : id

		}

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			dataType : 'html',
			method   : 'post',
			success  : function(response, textStatus, jqXHR) {
				console.log(response)
				//return true;
			},
			error    : function(jqXHR, textStatus) {
				//return false;
			},
			complete : function(jqXHR, textStatus) {

			}
		});

	}

	$( '.rm-close' ).live( 'click', function(e) {

		e.preventDefault();

		$( this ).parent().fadeOut( 'fast' );

	});

	$('#rainmail-rss-checkbox').click(function(e) {
		if($(this).is(":checked")) {
			$('.rainmail-rss-feed-url').show();
		} else {
			$('.rainmail-rss-feed-url').hide();
		}
	});

	$('body').on( 'click', '.close-box, .editor-overlay, #rainmail-optin-cancel', function(e){
		e.preventDefault();
		hideOptInPopupEditor();
	})
	.on('click', '.rainmail-edit-optin', function(e) {
		e.preventDefault();
		var id = $(this).data( 'id' )
		$('.error').remove();
		showOptInPopupEditor(id);
	}).on('click', '#rainmail-optin-default', function(e) {
		if($(this).is(":checked")) {
			$('#rainmail-optin-editor-inner').hide();
		} else {
			$('#rainmail-optin-editor-inner').show();
		}
	}).on('click', '#rainmail-optin-save', function(e) {
		e.preventDefault();
		var el = $(this);
		saveOptInEmail(el);
	});

	function saveOptInEmail(el) {

		var data,
			message;

		el.attr('disabled', 'disabled').text('Saving...');

		$('.error').remove();

		if($('#rainmail-optin-default').is(":checked")) {
			data = {
				id:      $('#rainmail-optin-id').val(),
				subject: '',
				message: '',
				action:  'update_optin_email_template'
			}
		} else {



			if(tmceOptIn = tinymce.get('rainmail-optin-content')) {
				var message = tmceOptIn.getContent();
			} else {
				var message = $('#rainmail-optin-content').val();
				//alert( message );
			}

			//alert( message );

			if(!message.match(/\[confirm_link text="([^"]*)"\]/i)) {
				$("#rainmail-optin-editor-loaded").before('<div id="notice" class="error"><p><strong>Alert!</strong> Required shortcode [confirm_link text="Click here to confirm your subscription."] missing from email content.</div>');
				$('#rainmail-optin-editor').animate({
			        scrollTop: 0
			    }, 'slow');
			    el.removeAttr('disabled').text('Save');
			    return false;
			}

			data = {
				id:      $('#rainmail-optin-id').val(),
				subject: $('#rainmail-optin-subject').val(),
				message: message,
				action:  'update_optin_email_template'
			}
		}

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			dataType : 'json',
			method   : 'post',
			success  : function(response, textStatus, jqXHR) {
				//console.log(response);
				if ( typeof response.rsp !== "undefined" && typeof response.rsp.err !== "undefined") {
					console.log('Error');
					console.log( response.rsp.err["@attributes"].msg );
					if( response.rsp.err["@attributes"].code == "-1" ) {
						var message = "Email subject and message text cannot be empty.";
					} else {
						var message = response.rsp.err["@attributes"].msg;
					}
					message = message.replace(/\(&lt;\$BlogSubLink\$&gt;\)/gi, '[confirm_link text="Anchor text"]');
					console.log(message);
					$("#rainmail-optin-editor-loaded").before('<div id="notice" class="error"><p><strong>Alert! </strong>'+ message +'</p></div>');
					$('#rainmail-optin-editor').animate({
				        scrollTop: 0
				    }, 'slow');
				} else {
					hideOptInPopupEditor();
				}
			},
			error    : function(jqXHR, textStatus) {
				$("#rainmail-optin-editor-loaded").before('<div id="notice" class="error"><p><strong>Alert! </strong>There was an issue whilst saving. Please try again.</p></div>');
				$('#rainmail-optin-editor').animate({
			        scrollTop: 0
			    }, 'slow');
			},
			complete : function(jqXHR, textStatus) {
				el.removeAttr('disabled').text('Save');
			}
		});
	}

	function showOptInPopupEditor(id) {

		$('#rainmail-optin-save').removeAttr('disabled').text('Save');

		$('#rainmail-optin-editor, .editor-overlay').show();
		$('#rainmail-optin-editor-loading').show();
		$('#rainmail-optin-editor-loaded').hide();
		$('#rainmail-optin-editor-inner').hide();
		$('#rainmail-optin-default').attr('checked', true);
		$('#rainmail-optin-id').val(id);
		$('#rainmail-optin-editor div[aria-label="Fullscreen"]').hide();

		$('#rainmail-optin-content').val('');
		if(tmceOptIn = tinymce.get('rainmail-optin-content')) {
			tmceOptIn.setContent('');
		}

		var tmceOptIn,
			data = {
				'action'  : 'get_optin_email',
				'id'      : id
			};

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			dataType : 'json',
			method   : 'post',
			success  : function(response, textStatus, jqXHR) {
				$('#rainmail-optin-editor-loading').hide();
				$('#rainmail-optin-editor-loaded').show();
				$('#rainmail-optin-subject').val(response.title);
				$('#rainmail-optin-content').text(response.message);
				if(tmceOptIn = tinymce.get('rainmail-optin-content')) {
					if(response.message != '') {
						tmceOptIn.setContent(response.message);
					}
				}
				if(response.subject != '' && response.message != '') {
					$('#rainmail-optin-editor-inner').show();
					$('#rainmail-optin-default').attr('checked', false);
				}
			},
			error    : function(jqXHR, textStatus) {
				$('#rainmail-optin-editor-loading').html("Error loading the lists opt-in conformation email. Please try again.");
			},
			complete : function(jqXHR, textStatus) {

			}
		});
	}

	function hideOptInPopupEditor() {
		$('.editor-popup').hide();
		$('.editor-overlay').fadeOut('400');
	}


	$( '.email_list_delete' ).live( 'click', function(){

		var deleteButton = $(this),
			Input        = $(this).attr('href'),
			confirmTipped;

		var id = $(this).data( 'id' );

		confirmTipped = Tipped.create( deleteButton, '<p class="tool-tip-title">Are you sure you want to delete this list?</p><p class="tool-tip-title">This is a permanent action and cannot be undone.</p><a href="#yes" class="button confirm-delete confirm-delete-positive delete-list">Yes</a> <a href="#no" class="button confirm-delete confirm-delete-negative">No</a>', {
			skin: 'light',
			size: 'small',
			close: true,
			hideOn: false,
			hideOnClickOutside: true,
			hideOthers: true,
			showOn: false,
		});

		setTimeout(function(){
			confirmTipped.show();
		}, 100 );

		$('body').on('click', '.confirm-delete', function(){

			confirmTipped.hide();

			if ( $(this).hasClass('delete-list') ) {

				var data = {
					'action'  : 'edit_optin_list',
					'action2' : 'delete',
					'service' : 'rainmail',
					'data'    : id
				};

				$( 'tr#post-'+id ).fadeOut( 1000 );

				$.ajax( {
					url      : ajaxurl,
					data     : data,
					method   : 'post',
					success  : function(response, textStatus, jqXHR) {
						$( 'tr#post-'+id ).remove();
					},
					error    : function(jqXHR, textStatus) {
						console.log( textStatus );
						$( 'tr#post-'+id ).fadeIn( 1000 );
					},
					complete : function(jqXHR, textStatus) {

					}
				});

			}

			return false;

		});

		return false;

	});

	$('body').on('click', '#edit-rainmail-address', function(e) {
		e.preventDefault();
		$('#rainmail-edit-address .loading').show();
		$('#rainmail-edit-address .loaded').hide();
		$.magnificPopup.open({
			items       : {
				src: '#rainmail-edit-address'
			},
			callbacks   : {
				close : function() {

				},
				open  : function() {

					$('#rainmail-edit-address .error').remove();

					var data = {
						'action'  : 'get_rainmail_address',
					};

					$.ajax( {
						url      : ajaxurl,
						data     : data,
						method   : 'post',
						dataType : 'json',
						success  : function(response, textStatus, jqXHR) {
							console.log(response.profile.contact);
							var contact = response.profile.contact;
							$('#rainmail_address_company').val(contact.Company);
							$('#rainmail_address_name').val(contact.Name);
							$('#rainmail_address_street').val(contact.Street1);
							$('#rainmail_address_street2').val(contact.Street2);
							$('#rainmail_address_city').val(contact.City);
							$('#rainmail_address_country').val(contact.Country);
							$('#rainmail_address_zip').val(contact.ZipCode);
							$('#rainmail_address_state').val(contact.State);
							$('#rainmail_address_tel').val(contact.Phone);
							$('#rainmail_address_email').val(contact.Email);
							$('#rainmail_address_tag').val(response.tagline);
							$('#rainmail-edit-address .loading').hide();
							$('#rainmail-edit-address .loaded').show();
						},
						error    : function(jqXHR, textStatus) {
							//console.log( textStatus );
						},
						complete : function(jqXHR, textStatus) {

						}
					});
				}
			},
			type      : 'inline',
			modal     : true,
			preloader : true,
		}, 0);
	})
	.on('click', '#saveRMailAddress', function(e) {
		e.preventDefault();
		el = $(this);
		$('#rainmail-edit-address .error').remove();
		el.attr('disabled', 'disabled').text("Saving...");

		var data = {
			action:  'save_rainmail_address',
			company: $('#rainmail_address_company').val(),
			name:    $('#rainmail_address_name').val(),
			street:  $('#rainmail_address_street').val(),
			street2: $('#rainmail_address_street2').val(),
			city:    $('#rainmail_address_city').val(),
			country: $('#rainmail_address_country').val(),
			zip:     $('#rainmail_address_zip').val(),
			state:   $('#rainmail_address_state').val(),
			tel:     $('#rainmail_address_tel').val(),
			email:   $('#rainmail_address_email').val(),
			tag:     $('#rainmail_address_tag').val()
		}

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			method   : 'post',
			dataType : 'json',
			success  : function(response, textStatus, jqXHR) {
				console.log(response);
				if(response.rsp.err) {
					$('.mfp-content').animate({
						scrollTop:  0
					}, 'slow');
					$('#rainmail-edit-address .description:first').after('<div class="error"><p><strong>Alert: </strong>'+response.rsp.err['@attributes'].msg+'</p></div>');

				} else {
					loadAddress();
					$.magnificPopup.close();
				}
			},
			error    : function(jqXHR, textStatus) {
				//console.log( textStatus );
			},
			complete : function(jqXHR, textStatus) {
				el.removeAttr('disabled').text("Save");
				loadAddress();
			}
		});
	})
	.on('click', '.rainmail_reload_address', function(e) {
		e.preventDefault();
		loadAddress();
	});

	//template preview
	$('.rm-button-template-preview').magnificPopup({
	  items: {
	      src: '#rainmail_template_preview',
	      type: 'inline',
	  },
	  mainClass: 'template-preview-popup',
	  closeOnBgClick: false,
	});

	$('body').on( 'click', '.rm-button-template-preview', function() {

		$('#rainmail_template_preview .spinner-page').css('display', 'block');
		$('#rainmail_template_preview .preview').hide();

		var template    = $(this).data('template'),
			sidebar     = $('#email_template_sidebar').val(),
			logo        = $('#email_template_logo').val(),
			headerAlt   = $('#email_template_header_text').val(),
			headerRight = $('#email_template_header_right_text').val();

		var data = {
			'action': 'rm_rainmail_template_preview',
			'template': template,
			'sidebar': sidebar,
			'logo': logo,
			'headerAlt': headerAlt,
			'headerRight': headerRight,
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {

			var iframe = document.getElementById('rainmail_template_preview_window');
				iframe = (iframe.contentWindow) ? iframe.contentWindow : (iframe.contentDocument.document) ? iframe.contentDocument.document : iframe.contentDocument;
				iframe.document.open();
				iframe.document.write( response );
				iframe.document.close();

			$('#rainmail_template_preview .preview').show();
			$('#rainmail_template_preview .spinner-page').css('display','none');

		});

		return false;

	});

	//custom template editor
	$('.rm-button-template-edit').magnificPopup({
	  items: {
	      src: '#rainmail_custom_template_editor',
	      type: 'inline',
	  },
	  mainClass: 'custom-template-editor-popup',
	  closeOnBgClick: false,
	});

	$('body').on( 'click', '.rm-button-template-edit', function() {
		return false;
	});

	//sidebar editor
	$('body').on( 'click', '.rm-template-sidebar-editor', function() {

		$( $(this).attr('href') + ', .editor-overlay' ).show();

		return false;
	});

	//close popup
	$('body').on('click', '.button-close-popup', function(){
		$.magnificPopup.close();

		hideOptInPopupEditor();

		return false;
	});

});
