jQuery(document).ready(function($) {

	$('.lms-next-question').click(function(e) {
		e.preventDefault();

		var target = $(this).data('target');
		$('.lms-quiz-question').removeClass('active');

		$(target).addClass('active');
	});

	$('.lms-finish_quiz').click(function(e) {
		e.preventDefault();

		var clicked = $(this);
		var container = clicked.parent().parent();

		var data = $(this).parent().parent().serialize();

		console.log(data);

		$.post(ajax_object.ajax_url, data, function(response) {
			//console.log(response);
			if(response.type == 1) {
				location.assign(response.url);
			} else {
				$('.lms-quiz-question').removeClass('active');
				container.append(response.html);
				if ( $('body').hasClass('admin-bar') ) {
					var quizContainerTop = clicked.parents( '.lms_quiz_form' ).offset().top - 200+ 'px';
				} else {
					var quizContainerTop = clicked.parents( '.lms_quiz_form' ).offset().top - 100+ 'px';
				}

				jQuery( 'html,body' ).animate( { scrollTop: quizContainerTop }, 500 );
			}


		}, 'json');

	});

	$( 'body' ).on( 'click', '.lms-next-question', function() {
		jQuery( this ).parents( '.lms_quiz_form' ).addClass('xxxxxxxxxxxxxxxxx');
		var quizContainerTop = jQuery( this ).parents( '.lms_quiz_form' ).offset().top - 100+ 'px';
		console.log( quizContainerTop );
		jQuery( 'html,body' ).animate( { scrollTop: quizContainerTop }, 500 );

	} );

});