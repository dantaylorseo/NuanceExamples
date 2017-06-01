jQuery(document).ready( function($) {
	loadLists();
	var confirmTipped;

	function loadLists() {

		$(".list-box").fadeOut(1000).remove();

		$('#rainmail-lists-loading').fadeIn(200);

		var data = {
			'action'  : 'load_email_lists',
			'service' : 'rainmail',
		};

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			dataType : 'json',
			method   : 'post',
			success  : function(response, textStatus, jqXHR) {
				//$('#rainmail-lists-container').append( response );
				$('#rainmail-lists-loading').fadeOut(200);
				var count = 1;
				var admin_url = response.admin_url;
				$.each(response.lists, function(key, list) {
					//console.log(list)
					var html = 	'<div class="content-box list-box" id="list-'+ list.id +'">'+
									'<a class="button tool-tip-title mail-delete-list rm-button-icon-delete" href="" data-id="'+ list.id +'">Delete</a>'+
									'<div class="subscribers"><div class="number">'+ list.subscribers +'</div><span>Subscribers <a class="edit-list" href="'+ admin_url +'&list_id='+ list.id +'&tab=subscribers">(manage)</a></span></div>'+
									'<div class="details"><h3><a class="edit-list" href="'+ admin_url +'&list_id='+ list.id +'">'+ list.name +'</a></h3>'+
									'<p>'+ list.description +'</p></div>'+
									'<div class="buttons"><a href="'+ admin_url +'&list_id='+ list.id +'" class="edit-list button button-primary">Edit List</a></div>'+
								'</div>';

					$(html).hide().appendTo('#rainmail-lists-container').delay(count*300).fadeIn(1000);
					count++;
				});
				//console.log(response)
			},
			error    : function(jqXHR, textStatus) {
				$('#rainmail-lists-container').append( '<tr><td>We couldn\'t retrieve your lists. <a href="" class="retryListRefresh">Retry?</a></td></tr>' );
			},
			complete : function(jqXHR, textStatus) {

			}
		});
	}

	$( 'body' ).on( 'click', '.mail-delete-list', function(e) {

		e.preventDefault();

		var deleteButton = $(this);

		var id = $(this).data( 'id' );

		confirmTipped = Tipped.create( deleteButton, '<p class="tool-tip-title">Are you sure you want to delete this list?</p><p class="tool-tip-title">This is a permanent action and cannot be undone.</p><a data-id="'+ id +'" href="#yes" class="button confirm-delete confirm-delete-positive delete-list">Yes</a> <a href="#no" class="button confirm-delete-negative">No</a>', {
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

	})
	.on('click', '.confirm-delete', function(e) {

		e.preventDefault();

		confirmTipped.hide();

		var id = $(this).data( 'id' );

		var data = {
			'action'  : 'edit_optin_list',
			'action2' : 'delete',
			'service' : 'rainmail',
			'data'    : id
		};

		$( '#list-'+id ).fadeOut( 1000 );

		$.ajax( {
			url      : ajaxurl,
			data     : data,
			method   : 'post',
			success  : function(response, textStatus, jqXHR) {
				$( '#list-'+id ).remove();
			},
			error    : function(jqXHR, textStatus) {
				$( '#list-'+id ).fadeIn( 1000 );
				console.log(textStatus);
			},
			complete : function(jqXHR, textStatus) {

			}
		});

	});

	$( '#savelistsettings' ).click(function(e) {

		console.log("clicked save");

		e.preventDefault();
		var thisButton = $(this);
		var buttonText = thisButton.text();

		thisButton.text('Saving...');

		thisButton.prop('disabled', true);

		var id            = $( '#new_id' ).val(),
			name          = $( '#new-name' ).val(),
			desc          = $( '#new-desc' ).val(),
			landingpage   = $( '#sub-landingpage' ).val(),
			newresponder  = $( '#new-responder' ).val(),
			unredirect    = $( '#un-redirect' ).val(),
			rssurl        = $( '#new-rss-url' ).val(),
			triggerid     = $( '#after_unsubscribe_trigger_id' ).val(),
			triggeraction = $( '#after_unsubscribe_trigger_action' ).val(),
			triggerlist   = $( '#after_unsubscribe_trigger_list_id' ).val();
		

		var rss          = 0;
		if( $( '#rainmail-rss-checkbox' ).is(":checked") ){
			rss = 1;
		}

		var data = {
			'action'  : 'edit_optin_list',
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
			},
			'triggers' : {
				'triggerid'       : triggerid,
				'triggeraction'   : triggeraction,
				'triggerlist'     : triggerlist,
			}
		};

		var dataType = 'html';

		if ( id == '' ) {
			dataType = 'json';
		}
		$("#listSaveError").fadeOut(1000).remove();
		$.ajax( {
			url      : ajaxurl,
			data     : data,
			method   : 'post',
			dataType : dataType,
			success  : function(response, textStatus, jqXHR) {
				$.magnificPopup.close();
				if ( id != '' ) {
					updateTemplate(id);
				} else {
					updateTemplate(response.id);
				}
				thisButton.text('Saved!');
				setTimeout(function() {
					thisButton.text(buttonText);
				}, 3000);
			},
			error    : function(jqXHR, textStatus) {
				console.log($('#email-list-navigation').offset().top - 20);
				$('html,body').animate({
					scrollTop: $('#email-list-navigation').offset().top - 20
				}, 'slow');
				$("#list_form").before('<div id="listSaveError" class="error"><p>There was an error saving the list, please try again.</p></div>');
				thisButton.text('Failed!');
				setTimeout(function() {
					thisButton.text(buttonText);
				}, 3000);
			},
			complete : function(jqXHR, textStatus) {
				thisButton.prop('disabled', false);
			}
		});

	});

	$('#email_list_add').live('click', function(e) {
		e.preventDefault();

		var el = $(this);

		$.magnificPopup.open({
			items       : {
				src: '#rainmail-new-list-modal'
			},
			callbacks   : {
				close : function() {

				},
				open  : function() {
					$('#rainmail-new-list-modal input').val('');
					$('#listSaveError').remove();
					$('#rainmail-new-list-modal textarea').val('');
					$('#rainmail-rss-checkbox').prop('checked', false);
					$('.rainmail-rss-feed-url').hide();
					$('#new-rss-url').val('');
					$('#rainmail-new-list-modal option[selected="selected"]').each(
						function() {
							$(this).removeAttr('selected');
						}
					);
					$("#rainmail-new-list-modal select").each(function() {
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

	$( '#manageaddsubmit' ).live( 'click', function(e) {

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

	$("#managecancelsubmit").live("click", function(e) {
		e.preventDefault();
		$.magnificPopup.close();
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

			var is_tinymce_active = false
			is_tinymce_active = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();

			if (is_tinymce_active == null) {
				is_tinymce_active = false
			}

			console.log(is_tinymce_active)

			if(tmceOptIn = tinymce.get('rainmail-optin-content')) {
				var message = tmceOptIn.getContent();
			} else {
				var message = $('#rainmail-optin-content').val();
				//alert( message );
			}
			if (is_tinymce_active == false) {
				var message = $('#rainmail-optin-content').val();
			}

			//alert( message );

			if(!message.match(/\[confirm_link text="([^"]*)"\]/i)) {
				$("#rainmail-optin-editor-loaded").before('<div id="notice" class="error"><p><strong>Alert!</strong> Required shortcode [confirm_text_link="Click here to confirm your subscription."] missing from email content.</p></div>');
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
				console.log("here");
				if(tmceOptIn = tinymce.get('rainmail-optin-content')) {
					console.log("in");
					if(response.message != '') {
						tmceOptIn.setContent(response.message);
					}
				}

				$('#rainmail-optin-content').val(response.message);

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
				//console.log(response)
				//return true;
			},
			error    : function(jqXHR, textStatus) {
				//return false;
			},
			complete : function(jqXHR, textStatus) {

			}
		});

	}

});
