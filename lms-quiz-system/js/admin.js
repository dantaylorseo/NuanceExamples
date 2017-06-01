jQuery(document).ready( function($) {

	$('input[required]').each(function(){
		$(this).removeAttr('required');
		$(this).addClass('required');
	});

	function reorder_questions() {
		var countquestions = 0;

		$.each( $('.lms_quiz_question_box'), function(elem) {
			var questno = countquestions + 1;
			$.each( $(this).find('input'), function(elem2) {
				var name = $(this).attr('name');
				var newname = name.replace( /lms_quiz_question\[([0-9]+)\]/, 'lms_quiz_question['+countquestions+']');
				$(this).attr('name', newname);
			});
			$(this).find('h2 span.lms_quiz_question_no').html(questno);
			countquestions = countquestions + 1;
		});
	}

	function reorder_answers() {
		var countquestions = 0;

		$.each( $('.lms_quiz_answers_table'), function(elem) {
			var questno = countquestions + 1;
			$.each( $(this).find('input'), function(elem2) {
				var name = $(this).attr('name');
				var newname = name.replace( /lms_quiz_question\[([0-9]+)\]/, 'lms_quiz_question['+countquestions+']');
				$(this).attr('name', newname);
			});
			$(this).find('h2 span.lms_quiz_question_no').html(questno);
			countquestions = countquestions + 1;
		});
	}

	var questionbox = '<div class="lms_quiz_question_box">' +
			'<button type="button" class="quiz_move_up">&#9650;</button>' +
			'<button type="button" class="quiz_move_down">&#9660;</button>' +
			'<button class="delete_quiz_question dashicons dashicons-trash">Delete Question</button>' +
			'<div class="lms_quiz_question_box_header">' +
				'<h2>Question <span class="lms_quiz_question_no">1</span> <i class="question_text"></i></h2>' +
			'</div>' +
			'<div class="lms_quiz_question_box_inner">' +
				'<table class="form-table">' +
					'<tr>' +
						'<th>Image</th>' +
						'<td>' +
							'<div id="box3_preview" class="lms_quiz_image_preview">' +
							'<img src="" style="width: 100%; height: auto; display: none;">'+
							'</div>' +
							'<input type="hidden" name="lms_quiz_question[0][image]" value="" id="box3_image">' +
							'<input type="submit" name="upload-box1" id="upload-box1" class="button button-primary upload_media_box" value="Upload" rel="box3">'+
						'</td>'+
					'</tr>' +
					'<tr>' +
						'<th>Question</th>' +
						'<td><input type="text" name="lms_quiz_question[0][question]" required class="regular-text question_input" value="" placeholder="Question"></td>' +
					'</tr>' +
					'<tr>' +
						'<th>Answers</th>' +
						'<td>' +
							'<table class="lms_quiz_answers_table">' +
								'<tr class="lms_quiz_answer_clone">' +
									'<td>' +
										'<input type="text" value="" name="lms_quiz_question[0][answers][0][answer]" required class="answer regular-text" placeholder="Answer">' +
									'</td>' +
									'<td>' +
										'<input type="number" value="" name="lms_quiz_question[0][answers][0][score]" required class="score regular-text" placeholder="Score">' +
									'</td>' +
									'<td>' +
										'<button type="button" class="delete_quiz_answer dashicons dashicons-trash">Delete Answer</button>' +
									'</td>'+
								'</tr>' +
							'</table>' +
							'<button type="button" class="button button-secondary lms_quiz_add_answer">Add Answer</button>' +
						'</td>' +
					'</tr>' +
				'</table>' +
			'</div>' +
		'</div>';
	//var $questionbox      = $('.lms_quiz_question_box:last');
	var $questionboxclone = $($.parseHTML(questionbox));
	var questions = 1;

	var $answer      = $('.lms_quiz_answer_clone:last');
	var $answerclone = $answer.clone();

	$('.lms_quiz_add_question').live( 'click', function(e) {
		e.preventDefault();

		var $newbox = $questionboxclone.clone();

		//console.log( $questionboxclone );

		$newbox.find('input[type=text]').val('');

		var questions = $('.lms_quiz_question_box').length

		$.each( $newbox.find('.upload_media_box'), function( elem ) {
			var name = $(this).attr('rel');
			var newname = name.replace( /box([0-9]+)/, 'box'+questions);
			$(this).attr('rel', newname);
		});

		$.each( $newbox.find('input[type=hidden]'), function( elem ) {
			var name = $(this).attr('id');
			var newname = name.replace( /box([0-9]+)/, 'box'+questions);
			$(this).attr('id', newname);
		});

		$.each( $newbox.find('.lms_quiz_image_preview'), function(elem) {
			var id = $(this).attr('id');
			var newid = id.replace( /box([0-9]+)/, 'box'+questions);
			$(this).attr('id', newid);
		});

		$.each( $newbox.find('input'), function( elem ) {
			var name = $(this).attr('name');
			var newname = name.replace( /lms_quiz_question\[([0-9]+)\]/, 'lms_quiz_question['+questions+']');
			$(this).attr('name', newname);
		});


		questions = parseInt(questions) + 1;

		$newbox.find('h2 span.lms_quiz_question_no').html(questions);
		$('.lms_quiz_question_box_inner').hide();
		$('.lms_quiz_add_question').before($newbox);
		$('.lms_quiz_question_box_inner:last').show();
	});

	$('.lms_quiz_add_answer').live( 'click', function(e) {
		e.preventDefault();
		var $clickedlink = $(this);
		var $linkparent  = $clickedlink.parent();
		var inputs = $linkparent.find('input.answer').length;

		var $newanswer = $linkparent.find('.lms_quiz_answer_clone:last');
		var $newanswerclone = $newanswer.clone();

		$.each( $newanswerclone.find('input'), function( elem ) {

			$(this).val('');

			var name = $(this).attr('name');
			var newname = name.replace( /\[answers\]\[([0-9]+)\]/, function(n) { return n++ });

			var newname = name.replace( /\[answers\]\[\d+\]/, function(attr) {
				return attr.replace(/\d+/, function(val) { return parseInt(inputs) });
			});
			console.log( newname );

			$(this).attr('name', newname);
		});

		$newanswer.after($newanswerclone);
	});

	$('.lms_quiz_question_box_header').live( 'click', function(e) {
		$('.lms_quiz_question_box_inner').hide();
		$(this).parent().find('.lms_quiz_question_box_inner').toggle();
	});

	$('.lms_quiz_question_box_inner').last().show();

	var clicked, imgurl, inputclass, imageclass, file_frame, attachment;

	$('.upload_media_box').live( 'click', function(e) {
		clicked = $(this);
		inputclass = clicked.attr('rel');
		e.preventDefault();
		$('#'+inputclass+'_preview p').remove();
		// If the media frame already exists, reopen it.
		if ( file_frame ) {
		  file_frame.open();
		  return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
		  title: $( this ).data( 'uploader_title' ),
		  button: {
			text: $( this ).data( 'uploader_button_text' ),
		  },
		  multiple: false  // Set to true to allow multiple files to be selected
		});

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {
		  // We set multiple to false so only get one image from the uploader
		  attachment = file_frame.state().get('selection').first().toJSON();
		  if( attachment.type == 'image' ) {
				$('#'+inputclass+'_preview img').attr( 'src', attachment.url ).show();
				$('#'+inputclass+'_image').val( attachment.id );
		  } else {
				$('#'+inputclass+'_preview').append( '<p>The file you uploaded was not an image. Please try again.</p>')
		  }

		});

		// Finally, open the modal
		file_frame.open();
	});

	$('.delete_quiz_image').live( 'click', function(e) {
		e.preventDefault();
		var target = $(this).attr('rel');

		$('#'+target+'_preview img').attr( 'src', 'https://placehold.it/500x250/ffffff/cccccc/?txtsize=60&text=no+image+uploaded' );
		$('#'+target+'_image').val('');

	});

	$('.delete_quiz_question').live( 'click', function(e) {
		e.preventDefault();
		$(this).parent().remove();
		reorder_questions();
	});

	$('.delete_quiz_answer').live( 'click', function(e) {
		e.preventDefault();

		var answers = $(this).parent().parent().parent().find('.answer');
		if( answers.length == 1 ) {
			$(this).parent().parent().find('input').val('');
		} else {
			$(this).parent().parent().remove();
		}


		//$(this).parent().parent().remove();
		//reorder_questions();

	});

	$('.quiz_move_up').live( 'click', function(e) {
		e.preventDefault();

		if( $(this).parent().index() != 0 ) {
			var clone = $(this).parent().clone();

			$(this).parent().prev('.lms_quiz_question_box').before(clone);
			$(this).parent().remove();

			reorder_questions();

			$('.lms_quiz_question_box_inner').hide();
			$(clone).find('.lms_quiz_question_box_inner').toggle();
		}
	});

	$('.quiz_move_down').live( 'click', function(e) {
		e.preventDefault();

		var length = $('.lms_quiz_question_box').length - 1;

		if( $(this).parent().index() != length ) {
			var clone = $(this).parent().clone();

			$(this).parent().next('.lms_quiz_question_box').after(clone);
			$(this).parent().remove();

			reorder_questions();

			$('.lms_quiz_question_box_inner').hide();
			$(clone).find('.lms_quiz_question_box_inner').toggle();
		}
	});

	$('.question_input').live('keyup', function(e) {
		console.log($(this).val());
		$(this).parent().parent().parent().parent().parent().parent().find('.question_text').html(' - '+$(this).val());
		//$(this).parent().parent().parent().parent().parent().parent().css('background-color', 'red');
	});

	var $rule      = $('.lms_quiz_rule_clone:last');
	var $ruleclone = $rule.clone();

	$('.lms_quiz_add_rule').live('click', function(e) {
		var newrule = $ruleclone.clone();
		$('.lms_quiz_rule_clone:last').after(newrule);
		$('.lms_quiz_rule_clone:last').suggest( ajaxurl + '?action=lms_quiz_url_autocomplete' );
	});

	$('.delete_result_rule').live('click', function(e) {
		var rules = $(this).parent().parent().parent().find('.lms_quiz_rule_clone');
		if( rules.length == 1 ) {
			$(this).parent().parent().find('input').val('');
		} else {
			$(this).parent().parent().remove();
		}
	});

	$('.results_page_type').change(function(e) {
		if($(this).val() == 1) {
			$('.lms_quiz_rule_clone input[required]').addClass('required');
		} else {
			$('.lms_quiz_rule_clone input').removeClass('required');
			Tipped.hideAll();
		}
		$('.lms_quiz_rpt').toggleClass('active');
	});


	$( '.quiz-menu-tab-navigation li a' ).each( function(){

			var target     = $( this ).attr( 'href' ),
				is_current = $( this ).parent().hasClass( 'current-menu-tab' );

			if( is_current === false ){
				$( target ).hide();
			}

		});

	$( '.quiz-menu-tab-navigation li a' ).click( function(){
		Tipped.hideAll();
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

		$( target ).show();

		return false;

	});


	var stopSubmission = true;

	$('#publish').click(function(){

		//Remove all tooltips from before
		Tipped.remove('input.required');

		if(stopSubmission == true){

			var missingRequires = false,
				requiredInputs  = $('input.required'),
				requiredCount   = requiredInputs.length;

			//Set up tooltips on empty required fields
			requiredInputs.each(function(){
				if ($(this).val().length === 0){
					var reqField = Tipped.create(this, 'This is a required field.');
					setTimeout(function(){
						reqField.show();
					}, 50);
				}
			});

			//Set up tooltips on empty required fields
			requiredInputs.each(function(){
				if ($(this).val().length === 0){
					var reqField = Tipped.create(this, 'This is a required field.');
					setTimeout(function(){
						reqField.show();
					}, 50);
				}
			});

			//If there are no required fields (simple score) submit the update/quiz
			if(requiredCount === 0){
				stopSubmission = false;
				Tipped.remove('input.required');
				$('#publish').click();
			} else {

				//Check each required field to ensure it is filled in
				requiredInputs.each(function(){

					if ($(this).val().length === 0){
						//If the field is empty, make the tab it is on active
						var tab = $(this).parents('.navClass');
						$('.navClass').hide();
						tab.show();
						//Swap the right tab to be the "current" tab
						$('a[href="#'+tab.attr('id')+'"]').parent().addClass('current-menu-tab');
						$('a[href="#'+tab.attr('id')+'"]').parent().siblings().removeClass('current-menu-tab');
						//Focus on first missing field
						this.focus();
						missingRequires = true;
						return false;
					}

					//If all required fields are filled in, submit the post request (publish/update)
					if (! --requiredCount && ! missingRequires) {
						stopSubmission = false;
						Tipped.remove('input.required');
						$('#publish').click();
					}
				});
			}

			//Keep the form from submitting unless all required fields are filled in
			return false;
		}

	});

	$('#lms_quiz_reports_form').on('submit', function(e) {
		var quiz_type = $('#lms_quiz_report_type').val();

		console.log( quiz_type );
		if ( quiz_type == 0 ) {
			e.preventDefault();
			var quiz_id   = $('#lms_quiz_quiz_id').val();
			location.assign('/admin/edit.php?post_type=lms_quiz_submission&quiz_id='+quiz_id);
		} else if ( quiz_type == 2 ) {
			e.preventDefault();
			var quiz_id   = $('#lms_quiz_quiz_id').val();
			window.open("/admin/admin.php?page=lms-quiz-reports&quiz_id="+quiz_id+"&report_type=2&action=download_csv");
		} else {
			return;
		}

		e.preventDefault();
	});

	// Autocomplete URL
	var availableTags = [
	  "ActionScript",
	  "AppleScript",
	  "Asp",
	  "BASIC",
	  "C",
	  "C++",
	  "Clojure",
	  "COBOL",
	  "ColdFusion",
	  "Erlang",
	  "Fortran",
	  "Groovy",
	  "Haskell",
	  "Java",
	  "JavaScript",
	  "Lisp",
	  "Perl",
	  "PHP",
	  "Python",
	  "Ruby",
	  "Scala",
	  "Scheme"
	];

	$('input.lms_quiz_url_autocomplete').live('focus', function(e) {
		var el = $(this);

	    el.autocomplete({
	        source: function( request, response ) {
				$.ajax({
					url: ajaxurl,
					dataType: "json",
					type: "post",
					data: {
						q: request.term,
						action: 'lms_quiz_url_autocomplete'
					},
					success: function( data ) {
						if( data[0].results == 0 ) {
							el.siblings('.lms_quiz_url_autocomplete_id').val("");
						} else {
							response( data );
						}
					}
				})
			},
			select: function( event, ui ) {
				if( ui.item.results > 0 ) {
					el.val(ui.item.value);
					el.siblings('.lms_quiz_url_autocomplete_id').val(ui.item.ID);
				} else {
					el.siblings('.lms_quiz_url_autocomplete_id').val("");
				}
				return false;
			}
	    });

	});



	$(window).off('beforeunload.edit-post');

});
