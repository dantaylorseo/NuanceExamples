jQuery(document).ready(function($) {
	
	//tab navigation	
	$( '.mail-campaign-tab-navigation li a' ).each( function(){
			
			var target     = $( this ).attr( 'href' ),
				is_current = $( this ).parent().hasClass( 'current-menu-tab' );
			
			if( is_current === false ){
				$( target ).hide();
			}
			
		});
	
	$( '.mail-campaign-tab-navigation li a' ).click( function(){
	
		var is_current = $( this ).parent().hasClass( 'current-menu-tab' );
		
		if( is_current ){
			return false;
		}
	
		$( this ).parent().siblings().each( function(){
		
			$( this ).removeClass( 'current-menu-tab' );
			
			var target = $( 'a', this ).attr( 'href' );
			
			$( target ).hide();
			
		});
		
		$( this ).parent().addClass( 'current-menu-tab' );
			
		var target = $( this ).attr( 'href' );
		
		if( '#preview_email' == target ) {
			
			$( '#email-subject-preview' ).text( $('#title').val() );
			broadcast_preview( 'display' );
			
		} else if( '#send_email_to' == target && $(this).parent().hasClass('list-alert') ) {
			$(this).parent().removeClass('list-alert');
		}
		
		$( target ).show();
		
		return false;
		
	});
	
	$('.button-test-email').click( function(){
		
		//prevent multiple clicks while AJAX is processing
		if( $('.email-test .spinner').is(':visible') ){
			return false;
		}
		
		$('.preview-success, .preview-failure').fadeOut();
		$('.email-test .spinner').css('display', 'inline-block');
		
		broadcast_preview( 'email' );
		
		return false;
		
	});
	
	function broadcast_preview( type ) {
		
		var editor, lists;
		
		if (typeof tinyMCE == "object" && tinyMCE !== null) {
			editor = tinyMCE.EditorManager.get('content');
		}

		if (typeof editor == "undefined" || editor == null || editor.isHidden()) {
			var content = jQuery('#content').val();
		}
		else {
			var content = editor.getContent();
		}

		lists = getLists();
		
		var data = {
			'action': 'mail_preview',
			'type': type,
			'post_id': $('#post_ID').val(),
			'template': $('input[name=_email_template]:checked').val(),
			'sender_name': $('#sender_name').val(),
			'sender_email': $('#sender_email').val(),
			'lists': JSON.stringify(lists),
			'recipients': $('#test_email_recipient').val(),
			'subject': $('#title').val(),
			'message': content
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {

			if( 'display' === type ) {
				var iframe = document.getElementById('broadcast_preview_window');
				iframe = (iframe.contentWindow) ? iframe.contentWindow : (iframe.contentDocument.document) ? iframe.contentDocument.document : iframe.contentDocument;
				iframe.document.open();
				iframe.document.write( response );
				iframe.document.close();
			} else if( 'email' === type ) {
				
				$('.email-test .spinner').css('display', 'none');
				
				if( response.indexOf("error") > -1 ) {
					$('.preview-failure').fadeIn();
				} else {
					$('.preview-success').fadeIn();
				}
				
			}
			
		});
		
	}
	
	function getLists() {
		
		var selected = $('.email-recipient input:checked'),
			lists    = new Array();
			
		if ( selected.length > 0 ) {
			selected.each( function(){
				
				lists[lists.length] = $(this).attr('id').replace( 'email_recipient_list_', '' );
				
			});
		}
		
		return lists;
		
	}
	
	$('body').on('click', '.save-timestamp', function(){
		
		setTimeout(
			function() {
				var buttonText = $('#publish').val();
				
				switch( buttonText ) {
					 case 'Schedule':
					 	$('#publish').val('Schedule Broadcast');
					 	break;
					 case 'Publish':
					 	$('#publish').val('Send Broadcast Immediately');
					 	break;
				}
				
		}, 5 );
		
	});
	
	var preventSubmit = true;
	
	//ensure there is a selected list before scheduling/publishing
	$('body').on('click', '#publish', function(){

		Tipped.remove('.recipients-list');
		Tipped.remove('#title');
		
		if ( 'Send Broadcast Immediately' != $(this).val() ) {
			preventSubmit = false;
		}

		if( ! $('.recipients-list-required input:checked').length > 0 ) {
			
			$('a[href="#send_email_to"]').trigger('click');
			
			var reqList = Tipped.create( $('.recipients-list'), 'Please select a recipient first.');
			setTimeout(function(){
				reqList.show();
			}, 50);
			
			return false;
			
		}

		if( $('#title').val() == '' ) {
				
			$('a[href="#poststuff"]').trigger('click');

			var reqSubject = Tipped.create('#title', 'The broadcast subject is required.');
			setTimeout(function(){
				reqSubject.show();
			}, 50);

			$( '#title' ).focus();

			return false;

		}
		
		var editor;
		
		if (typeof tinyMCE == "object" && tinyMCE !== null) {
			editor = tinyMCE.EditorManager.get('content');
		}

		if (typeof editor == "undefined" || editor == null || editor.isHidden()) {
			var content = jQuery('#content').val();
		}
		else {
			var content = editor.getContent();
		}
		
		if ( content == '' ) {
			$('a[href="#poststuff"]').trigger('click');

			var reqSubject = Tipped.create('#postdivrich', 'The broadcast content is required.');
			setTimeout(function(){
				reqSubject.show();
			}, 50);

			$( '#title' ).focus();

			return false;
		}
		
		if ( preventSubmit ) {
		
			if ( 'Send Broadcast Immediately' == $(this).val() ) {
				$('#sending').show();
				preventSubmit = false;
				$(this).hide();
				
				var submitButton = $(this);
				setTimeout( function(){ submitButton.click() }, 100 );
			}
			
			return false;
		
		}
		
	});
	
	$(document).on("submit", "#post", function(event){
		$(window).off( 'beforeunload.edit-post' );
	});

});
    
