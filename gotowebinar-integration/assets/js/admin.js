jQuery(document).ready(function($) {
	$('#insert_gtw_form_link').magnificPopup({
	  items: {
		src: '#insert_gtw_form',
		type: 'inline'
		},
		callbacks: {
			open: function() {

				var data = {
					'action' : 'gtw_get_webinars'
				}

				jQuery.post(ajaxurl, data, function(response) {
					$("#webinar_key").html(response);
				}, 'html');

			},
			close: function() {
				$('#gtw_insert_form').trigger('reset');
			}
		}
	});

	$('body').on('submit', '#gtw_insert_form', function(e) {
		e.preventDefault();
		var webinar = $("#webinar_key").val();
		var button  = $("#button_text").val();
		var page    = $("#thank_page").val();
		var auto   = $('#gtw_register_check');
		var check   = $('#gtw_redirect_check');
		//alert(product);
		var shortcode = '[webinar key="'+webinar+'"';
		if(button != '') {
			shortcode += ' button="'+button+'"';
		}
		if(check.is(":checked")) {
			shortcode += ' page="'+page+'"';
		}
		if(auto.is(":checked")) {
			shortcode += ' redirect="true"';
		}
		shortcode += ']';

		var is_tinymce_active = false
		is_tinymce_active = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden();

		if (is_tinymce_active == null) {
			is_tinymce_active = false
		}
		if (is_tinymce_active != false) {
			tinymce.activeEditor.execCommand('mceInsertRawHTML', false, shortcode);
		} else {
			QTags.insertContent(shortcode);
		}
		$.magnificPopup.close()
	})
	.on('change', '#gtw_redirect_check', function(e) {
		if($(this).is(":checked")) {
			$("#gtw_redirect_row").css('visibility', 'visible');
		} else {
			$("#gtw_redirect_row").css('visibility', 'hidden');
		}
	});

});