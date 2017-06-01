jQuery('.recipients').attr('colspan', '4');

jQuery(document).ready(function($) {

	$('.recipients').each( function() {

		var target = this,
			data   = {
			'action': 'rm_broadcast_metrics',
			'post_id': $('.loading-data', this).data('id')
		};

		jQuery.post(ajaxurl, data, function(response) {

			var row = $(target).parent('tr');

			response = $.parseJSON( response )

			$('.recipients', row).html(response.recipients).removeAttr('colspan');
			$('.openrate', row).html(response.openrate).show();
			$('.clickrate', row).html(response.clickrate).show();
			$('.unsubscribes', row).html(response.unsubscribes).show();

		});

	});

	$('.broadcast-preview').magnificPopup({
	  items: {
	      src: '#broadcast_preview',
	      type: 'inline',
	  },
	  mainClass: 'broadcast-preview-popup',
	});

	$('body').on('click', '.broadcast-preview', function() {

		$('#broadcast_preview_window').hide();
		$('.spinner-page').css('display', 'block');

		var data = {
			'action': 'rm_broadcast_preview',
			'post_id': $(this).data('id')
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {

			var iframe = document.getElementById('broadcast_preview_window');
				iframe = (iframe.contentWindow) ? iframe.contentWindow : (iframe.contentDocument.document) ? iframe.contentDocument.document : iframe.contentDocument;
				iframe.document.open();
				iframe.document.write( response );
				iframe.document.close();

			$('#broadcast_preview_window').show();
			$('.spinner-page').css('display','none');

		});

		return false;
	});


});
