<?php
class LMS_Quiz_System {


	private static $instance = null;

	private $admin_icon = '';

	var $textdomain;

	/**
	 * Creates or returns an instance of this class.
	 */
	public static function get_instance() {
		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Construct the class and run actions
	 */
	public function __construct(){

		$this->actions();

	}

	/**
	 * Runs all the actions that set up the functionality of the Quiz System.
	 * Has conditional loading to save on loading times.
	 */
	public function actions() {

		$post_type = ! empty( $_GET['post_type'] ) ? $_GET['post_type']             : '';
		$post_type = ! empty( $_GET['post']      ) ? get_post_type( $_GET['post'] ) : $post_type;

		add_action( 'save_post', array( $this, 'save_quiz_questions_meta' ), 10, 3 );

		if ( in_array( $post_type, array( 'lms_quiz', 'lms_quiz_submission' ) ) || isset ( $_GET['page'] ) && $_GET['page'] == 'lms-quiz-reports' ) {

			add_action( 'admin_init'           , array( 'LMS_Quiz_Reports', 'download_csv'          ) );
			add_action( 'admin_enqueue_scripts', array( $this             , 'admin_enqueue_scripts' ) );
			add_filter( 'parse_query'          , array( $this             , 'submissions_filter'    ) );
			add_action( 'add_meta_boxes'       , array( $this             , 'remove_slug_meta'      ) );

			if ( 'lms_quiz' === $post_type ) {

				add_action( 'edit_form_advanced'               , array( $this, 'quiz_menu'      ) );
				add_action( 'edit_form_advanced'               , array( $this, 'quiz_questions' ) );
				add_action( 'edit_form_advanced'               , array( $this, 'quiz_settings'  ) );

				add_filter( 'post_row_actions'     , array( $this, 'quiz_row_actions' ), 10, 2 );
				add_filter( 'post_updated_messages', 'lms_quiz_updated_messages'               );

			} else {
				add_action( 'add_meta_boxes'       , array( $this, 'submission_meta_boxes' ) );
				add_action( 'restrict_manage_posts', array( $this, 'submissions_table_filtering' ) );
			}

		}



		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_lms_quiz_url_autocomplete', array( $this, 'ajax_page_search' ) );



		// Front end Ajax
		add_action( 'wp_ajax_ajax_lms_quiz_submit'       , array( $this, 'ajax_lms_quiz_submit' ) );
		add_action( 'wp_ajax_nopriv_ajax_lms_quiz_submit', array( $this, 'ajax_lms_quiz_submit' ) );

	}

	/**
	 * Callback on the `admin_enqueue_scripts` filter.
	 * Enqueue admin scripts and styles
	 */
	static public function admin_enqueue_scripts() {
		wp_enqueue_script( 'lms-quiz-admin', LMS_QUIZ_SYSTEM_URL .'/js/admin.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-autocomplete', 'suggest' ), RAINMAKER_BUILD_VERSION, true );

		wp_enqueue_script( 'accesspress-editor', PREMISE_MEMBER_RESOURCES_URL . 'editor.js', array( 'jquery', ), PREMISE_VERSION, true );

		wp_enqueue_style( 'lms-quiz-admin-style', LMS_QUIZ_SYSTEM_URL .'/css/admin.css', array(), RAINMAKER_BUILD_VERSION );
	}


	/**
	 * Callback on the `add_meta_boxes` filter.
	 * Sets up the meta boxes for the view submissions page.
	 */
	static public function submission_meta_boxes() {
		add_meta_box(
			'lms_quiz_submission_meta',
			__( 'Submission Data' ),
			array( self::get_instance(), 'submission_meta_boxes_callback' ),
			'lms_quiz_submission',
			'advanced',
			'core'
		);
	}

	/**
	 * Displays the submissions meta box defined at submission_meta_boxes()
	 * @param  object $post The post object from WordPress
	 */
	static public function submission_meta_boxes_callback( $post ) {
		$data = get_post_meta( $post->ID, '_lms_quiz_submission_data', true );
		$user_info = get_userdata( $data['user'] );
	?>
		<table class="form-table">
			<tr>
				<th>Quiz</th>
				<td>
					<a href="<?php echo admin_url( '/post.php?post='.$data['quiz'].'&action=edit' ); ?>">
						<?php echo get_the_title( $data['quiz'] ); ?> (<?php echo $data['quiz']; ?>)
					</a>
				</td>
			</tr>
			<?php if ( $data['user'] != 0 ) : ?>
				<tr>
					<th>User</th>
					<td>
						<a href="<?php echo admin_url( '/user-edit.php?user_id='.$data['user'] ); ?>"><?php echo $user_info->user_login; ?> (<?php echo $data['user']; ?>)</a>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th>User IP</th>
				<td>
					<?php echo $data['ip']; ?>
				</td>
			</tr>
			<tr>
				<th>Email</th>
				<td>
					<?php
						if ( $data['user'] != 0 ) {
							echo $user_info->user_email;
						 } else {
							echo ( $data['email'] != '' ? $data['email'] : 'Not set' );
						 }
					 ?>
				</td>
			</tr>
			<tr>
				<th>Total Score</th>
				<td>
					<?php echo $data['score']; ?>
				</td>
			</tr>
		</table>

		<div class="lms_quiz_individual_answers">
			<h2>Answers</h2>
			<table class="form-table">
				<tr>
					<th>Question</th>
					<th>Answer</th>
					<th>Score</th>
				</tr>
					<?php
						for ( $i = 0; $i<count( $data['answers'] ); $i++ ) {
							$q = $i + 1;
							echo '
							<tr>
								<td>'.$data['answers'][$i]['question'].'</td>
								<td>'.$data['answers'][$i]['answer'].'</td>
								<td>'.$data['answers'][$i]['score'].'</td>
							</tr>';
						}
					?>
				<tr>
					<th colspan="2">Total</th>
					<td><?php echo $data['score']; ?></td>
				</tr>
			</table>
		</div>
	<?php
	}


	static function quiz_menu() {

		$menu_items = array(
			'questions'         => __( 'Quiz Questions', '' ),
			'settings'          => __( 'Quiz Settings', '' ),
		);

		$current = ' current-menu-tab';

?>
		<nav id="quiz-menu-navigation" class="rm-tabbed-menu">
			<ul class="quiz-menu-tab-navigation page-tab-navigation page-main-menu menu">
				<?php

		foreach ( $menu_items as $id => $name ) {

			printf(
				'<li class="menu-tab%s"><a href="#%s">%s</a></li>%s',
				$current,
				$id,
				$name,
				"\r\n"
			);

			$current = ' ';

		}

?>
			</ul>
		</nav>
		<?php

	}

	/**
	 * Displays the quiz questions meta box defined at quiz_meta_boxes()
	 * @param  object $post The post object from WordPress
	 */
	static public function quiz_settings( $post ) {
		$data = get_post_meta( $post->ID, '_lms_quiz_settings', true );
		if ( isset( $data['results_page'] ) && $data['results_page'] ) {
			$results_page_content = stripslashes( $data['results_page'] );
		} else {
			$results_page_content = "You scored {score} out of {total}";
		}

		if ( isset( $data['public'] ) ) {
			$public = $data['public'];
		} else {
			$public = 0;
		}

		if ( isset( $data['resubmission'] ) ) {
			$resubmission = $data['resubmission'];
		} else {
			$resubmission = 0;
		}

	?>
	<div id="settings" class="navClass">
		<p class="description">With this option you can select between showing a simple results message on screen at the end of your quiz, or setting rules for where a user should be taken depending on their scores. For example, if someone scores between 51 and 100 you can take them to a "pass" page, then send those who score 50 and below to your "fail" page.</p>
		<table class="form-table">
			<tr>
				<th>Display Results</th>
				<td>
					<p><input type="radio" <?php  echo ( isset( $data['results_type'] ) ? checked( $data['results_type'], 2, false ) : 'checked' ); ?> class="results_page_type" id="lms_quiz_settings_simple_template" name="lms_quiz_settings[results_type]" value="2"> <label for="lms_quiz_settings_simple_template">Simple Results Message</label></p>
					<p><input type="radio" <?php if ( isset( $data['results_type'] ) ) checked( $data['results_type'], 1 ); ?> class="results_page_type" id="lms_quiz_settings_rule_based" name="lms_quiz_settings[results_type]" value="1"> <label for="lms_quiz_settings_rule_based">Rule Based</label></p>
				</td>
			<tr class="lms_quiz_rpt
				<?php  if ( isset( $data['results_type'] ) && $data['results_type'] == 1  ) {
					echo 'active';
				}  ?>">
				<th>Results Pages Rules</th>
				<td>
					<table class="lms_results_page_rules_table">

						<?php
							if ( isset( $data['results_rules'] ) && $data['results_rules'][0]['from'] != '' ) :
								foreach ( $data['results_rules'] as $rule ) :
						?>
									<tr class="lms_quiz_rule_clone">
										<td><label>From Score</label> <input <?php echo ( isset( $data['results_type'] ) && $data['results_type'] == 1 ? 'required' : '' ); ?> name="results_rules[from][]" type="number" placeholder="0" value="<?php echo $rule['from']; ?>"></td>
										<td><label>To Score</label> <input <?php echo ( isset( $data['results_type'] ) && $data['results_type'] == 1 ? 'required' : '' ); ?> name="results_rules[to][]" type="number" placeholder="0" value="<?php echo $rule['to']; ?>"></td>
										<td><label>URL/Page</label>  <input <?php echo ( isset( $data['results_type'] ) && $data['results_type'] == 1 ? 'required' : '' ); ?> name="results_rules[url][]" type="text" class="lms_quiz_url_autocomplete" placeholder="http://" value="<?php echo $rule['url']; ?>">
											<input type="hidden" name="results_rules[page][]" class="lms_quiz_url_autocomplete_id"></td>
										<td><button type="button" class="dashicons dashicons-trash delete_result_rule">Delete Rule</button></td>
									</tr>
						<?php
								endforeach;
							else:
						?>
						<tr class="lms_quiz_rule_clone">
							<td><label class="show-on-mobile"><strong>From Score</strong></label> <input <?php echo ( isset( $data['results_type'] ) && $data['results_type'] == 1 ? 'required' : '' ); ?> name="results_rules[from][]" type="number" placeholder="0"></td>
							<td><label class="show-on-mobile"><strong>To Score</strong></label> <input <?php echo ( isset( $data['results_type'] ) && $data['results_type'] == 1 ? 'required' : '' ); ?> name="results_rules[to][]" type="number" placeholder="0"></td>
							<td><label class="show-on-mobile"><strong>URL/Page</strong></label> <input <?php echo ( isset( $data['results_type'] ) && $data['results_type'] == 1 ? 'required' : '' ); ?> class="lms_quiz_url_autocomplete" name="results_rules[url][]" type="text" placeholder="http://">
							<input type="hidden" name="results_rules[page][]" class="lms_quiz_url_autocomplete_id"></td>
							<td><button type="button" class="dashicons dashicons-trash delete_result_rule">Delete Rule</button></td>
						</tr>
						<?php
							endif;
						?>

					</table>
					<button type="button" class="button button-secondary lms_quiz_add_rule">Add Rule</button>
					<p class="description">The submission ID will be added to the URL string automatically</p>
				</td>
			</tr>
			<tr class="lms_quiz_rpt <?php  if ( ( isset( $data['results_type'] ) && $data['results_type'] == 2  ) || ! isset( $data['results_type'] ) ) {
					echo 'active';
				}  ?>">
				<th>Results Page Template</th>
				<td class="form-field">
					<textarea name="lms_quiz_settings[results_page]" rows="10" width="100%"><?php echo $results_page_content; ?></textarea>
					<p class="description">Tags you can use: {score}, {total}, {quiz_link}</p>
				</td>
			</tr>
		</table>
		</div>
	<?php
	}

	/**
	 * Displays the quiz settings meta box defined at quiz_meta_boxes()
	 * @param  object $post The post object from WordPress
	 */
	static public function quiz_questions( $post ) {
		wp_nonce_field( 'quiz_meta_data', 'quiz_meta_nonce' );
		$data = get_post_meta( $post->ID, '_lms_quiz_questions', true );
		wp_enqueue_media();
		$questions = 0;
		$shortcode = htmlspecialchars('[quiz id="'.get_the_ID().'"]');
		echo '<div class="lms_quiz_shortcode_box">
			<p>You can embed this quiz on any area of your site that accepts a shortcode: posts, pages, landing pages, content areas, etc.</p>
			<input type="text" class="large-text" value="'.$shortcode.'">
		</div>';
		echo '<div>';
?>
	<div id="questions" class="navClass">
<?php
		if ( isset( $data[0] ) ) {

		foreach( $data as $question ) {
			$answers = 0;
	?>

		<div class="lms_quiz_question_box">
			<button type="button" class="quiz_move_up dashicons dashicons-arrow-up-alt2">Move up</button>
			<button type="button" class="quiz_move_down dashicons dashicons-arrow-down-alt2">Move down</button>
			<button class="delete_quiz_question dashicons dashicons-trash">Delete Question</button>

			<div class="lms_quiz_question_box_header">
				<h2>Question <span class="lms_quiz_question_no"><?php echo $questions + 1; ?></span><i class="question_text"> - <?php echo stripslashes( $question['question'] ); ?></i></h2>
			</div>
			<div class="lms_quiz_question_box_inner">
				<table class="form-table">
					<tr>
						<th>Image</th>
						<td>
							<div id="box<?php echo $questions; ?>_preview" class="lms_quiz_image_preview">
							<?php

								if ( isset( $question['image'] ) && $question['image'] != '' ) {
									$image      = wp_get_attachment_image_src( $question['image'], 'full' );
									$preview    = $image[0];
									$imgsrc     = $question['image'];
								} else {
									$preview = '';
									$imgsrc = '';
								}
							?>

							<?php if ( $preview != '' ) { ?><img src="<?php echo $preview; ?>" style="width: 100%; height: auto;"><?php } ?>
						</div>
						<input type="hidden" name="lms_quiz_question[<?php echo $questions; ?>][image]'; ?>" value="<?php echo $imgsrc; ?>" id="box<?php echo $questions; ?>_image">
						<?php
							if ( $imgsrc == '' ) {
								submit_button( 'Upload', 'primary upload_media_box', 'upload-box1', false, array( 'rel' => 'box'.$questions ) );
							} else {
								submit_button( 'Change', 'primary upload_media_box', 'upload-box'.$questions, false, array( 'rel' => 'box'.$questions ) );
								submit_button( 'Delete', 'delete delete_quiz_image', 'delete-box'.$questions, false, array( 'rel' => 'box'.$questions ) );
							}

						?>
						<p class="description">The file you upload should either be a JPEG, PNG or GIF file (.jpg, .jpeg, .png, .gif).</p>
						</td>
					</tr>
					<tr>
						<th>Question</th>
						<td><input type="text" name="lms_quiz_question[<?php echo $questions; ?>][question]" required class="regular-text question_input" value="<?php echo stripslashes( $question['question'] ); ?>" placeholder="Question"></td>
					</tr>
					<tr>
						<th>Answers</th>
						<td>
							<table class="lms_quiz_answers_table">
							<?php foreach( $question['answers'] as $answer ) { ?>
								<tr class="lms_quiz_answer_clone">
									<td>
										<input type="text" value="<?php echo stripslashes( $answer['answer'] ); ?>" name="lms_quiz_question[<?php echo $questions; ?>][answers][<?php echo $answers; ?>][answer]" required class="answer regular-text" placeholder="Answer">
									</td>
									<td>
										<input type="number" value="<?php echo $answer['score']; ?>" name="lms_quiz_question[<?php echo $questions; ?>][answers][<?php echo $answers; ?>][score]" required class="score regular-text" placeholder="Score">
									</td>
									<td>
										<button type="button" class="delete_quiz_answer dashicons dashicons-trash">Delete Answer</button>
									</td>
								</tr>
							<?php
								$answers ++;
								} ?>
							</table>
							<button type="button" class="button button-secondary lms_quiz_add_answer">Add Answer</button>
						</td>
					</tr>
				</table>
			</div>
		</div>
	<?php $questions++; } } else { ?>
		<div class="lms_quiz_question_box">
			<button type="button" class="quiz_move_up dashicons dashicons-arrow-up-alt2">Move up</button>
			<button type="button" class="quiz_move_down dashicons dashicons-arrow-down-alt2">Move down</button>
			<button class="delete_quiz_question dashicons dashicons-trash">Delete Question</button>
			<div class="lms_quiz_question_box_header">
				<h2>Question <span class="lms_quiz_question_no">1</span> <i class="question_text"></i></h2>
			</div>
			<div class="lms_quiz_question_box_inner">
				<table class="form-table">
					<tr>
						<th>Image</th>
						<td>
							<div id="box<?php echo $questions; ?>_preview" class="lms_quiz_image_preview">
							<?php

								$preview = '';
								$imgsrc = '';

							?>

							<img src="<?php echo $preview; ?>" style="width: 100%; height: auto; <?php echo ( $preview == '' ? ' display: none; ' : '' ); ?> ">
						</div>
						<input type="hidden" name="lms_quiz_question[<?php echo $questions; ?>][image]'; ?>" value="<?php echo $imgsrc; ?>" id="box<?php echo $questions; ?>_image">
						<?php
							if ( $imgsrc == '' ) {
								submit_button( 'Upload', 'primary upload_media_box', 'upload-box1', false, array( 'rel' => 'box'.$questions ) );
							} else {
								submit_button( 'Change', 'primary upload_media_box', 'upload-box'.$questions, false, array( 'rel' => 'box'.$questions ) );
								submit_button( 'Delete', 'delete delete_quiz_image', 'delete-box'.$questions, false, array( 'rel' => 'box'.$questions ) );
							}

						?>
							<p class="description">The file you upload should either be a JPEG, PNG or GIF file (.jpg, .jpeg, .png, .gif).</p>
						</td>
					</tr>
					<tr>
						<th>Question</th>
						<td><input type="text" name="lms_quiz_question[<?php echo $questions; ?>][question]" required class="regular-text question_input" value="" placeholder="Question"></td>
					</tr>
					<tr>
						<th>Answers</th>
						<td>
							<table class="lms_quiz_answers_table">

								<tr class="lms_quiz_answer_clone">
									<td>
										<input type="text" value="" name="lms_quiz_question[<?php echo $questions; ?>][answers][0][answer]" required class="answer regular-text" placeholder="Answer">
									</td>
									<td>
										<input type="number" value="" name="lms_quiz_question[<?php echo $questions; ?>][answers][0][score]" required class="score regular-text" placeholder="Score">
									</td>
									<td>
										<button type="button" class="delete_quiz_answer dashicons dashicons-trash">Delete Answer</button>
									</td>
								</tr>

							</table>
							<button type="button" class="button button-secondary lms_quiz_add_answer">Add Answer</button>
						</td>
					</tr>
				</table>
			</div>
		</div>
	<?php } ?>

		<button type="button" class="button button-secondary lms_quiz_add_question">Add Question</button>
		</div>
		</div>
	<?php
	}

	/**
	 * Validates and sets a variable before input to database
	 * @param  mixed &$arr_r
	 */
	static private function validate_questions( &$arr_r ) {
		foreach ( $arr_r as &$val ) {
			if ( is_array( $val ) ) {
				self::validate_questions( $val );
			} else {
				$val = addslashes( $val );
				$val = htmlspecialchars( $val );
			}
			unset( $val );
		}
	}

	/**
	 * Callback on the `save_post` filter.
	 * Saves the data in the quiz questions and settings meta boxes
	 * @param  int     $post_id  The post ID that the data is to be saved to
	 * @param  object  $post     The WordPress post object
	 * @param  bool    $update   Whether this is an existing post being updated or not.
	 * @return int               the post ID
	 */
	static public function save_quiz_questions_meta( $post_id, $post, $update ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		if ( 'auto-draft' == $post->post_status ) {
			return $post_id;
		}

		if ( isset( $_POST['post_type'] ) && 'lms_quiz' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;

		} else {
			return $post_id;
		}

		$questions = $_POST['lms_quiz_question'];

		self::validate_questions($questions);

		if ( isset( $_POST['lms_quiz_question'] ) ) {
			update_post_meta( $post_id, '_lms_quiz_questions', $questions );
		}

		if ( isset( $_POST['results_rules'] ) && $_POST['results_rules']['from'][0] != '' ) {
			$count = count( $_POST['results_rules']['from'] );
			$rules = array();

			for( $i = 0; $i < $count; $i++ ) {
				$temp = array( 'from' => $_POST['results_rules']['from'][$i], 'to' => $_POST['results_rules']['to'][$i], 'url' => $_POST['results_rules']['url'][$i] );

				if( ! empty( $_POST['results_rules']['page'][$i] ) ){
					$temp['page'] = $_POST['results_rules']['page'][$i];
				} else {
					$temp['page'] = '';
				}

				$rules[] = $temp;
			}

		}



		if ( isset( $_POST['lms_quiz_settings'] ) ) {

			$settings = $_POST['lms_quiz_settings'];

			if ( isset( $rules ) ) {
				$settings['results_rules'] = $rules;
			}

			update_post_meta( $post_id, '_lms_quiz_settings', $settings );
		}


	}

	/**
	 * Callback on the `wp_ajax_ajax_lms_quiz_submit` filter.
	 * Callback on the `wp_ajax_nopriv_ajax_lms_quiz_submit` filter.
	 * Front end ajax submission of quiz form
	 * Outputs JSON encoded array
	 */
	static public function ajax_lms_quiz_submit() {
		$data     = get_post_meta( $_POST['quiz-id'], '_lms_quiz_questions', true );
		$settings = get_post_meta( $_POST['quiz-id'], '_lms_quiz_settings', true );

		$score = 0;
		$total = 0;

		$submission_answers = array();

		foreach ( $_POST['lms_quiz_answer']['question'] as $key=>$value ) {
			$score = $score + $data[$key]['answers'][$value]['score'];
			$max = 0;

			$submission_answers[$key] = array(
				'question'  => $data[$key]['question'],
				'answer_no' => $value,
				'answer'    => $data[$key]['answers'][$value]['answer'],
				'score'     => $data[$key]['answers'][$value]['score']
			);

			foreach ( $data[$key]['answers'] as $score1 ) {
				if ( $score1['score'] > $max ) {
					$max = $score1['score'];
				}
			}
			$total = $total + $max;
		}
		
		$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) && strlen( $_SERVER['REMOTE_ADDR'] ) > 6 ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';      
		$ip_address = function_exists( 'nginx_cache_real_user_ip' )                             ? nginx_cache_real_user_ip() : $ip_address;

		$submission_data = array (
			'user'      => get_current_user_id( ), //returns 0 when not logged in
			'answers'   => $submission_answers,
			'ip'        => $ip_address,
			'email'     => ( isset( $_POST['email'] ) ? $_POST['email'] : '' ),
			'quiz'      => $_POST['quiz-id'],
			'score'     => $score
		);

		$submission_id = self::add_submission( $submission_data );

		$output = array( 'status' => 'ok', 'type' => $settings['results_type'], 'submission' => $submission_id );
		if ( $settings['results_type'] == 1 ) {

			foreach ( $settings['results_rules'] as $rule ) {
				if ( $score >= $rule['from'] && $score <= $rule['to'] ) {
					if ( ! empty( $rule['page'] ) ) {
						$url = get_the_permalink( $rule['page'] );
					} else {
						$url = $rule['url'];
					}
					break;
				}
			}

			$output['url'] = $url;

		} else {

			$results_page_content = stripslashes( $settings['results_page'] );
			$results_page_content = str_replace( '{score}', $score, $results_page_content );
			$results_page_content = str_replace( '{total}', $total, $results_page_content );
			$results_page_content = str_replace( '{quiz_link}', site_url( $_POST['_wp_http_referer'] ), $results_page_content );

			$html  = '<div class="lms-quiz-question active">';
			$html .= wpautop($results_page_content);
			$html .= '</div>';

			$output['html'] = $html;

		}

		echo json_encode( $output );
		wp_die();

	}

	/**
	 * Adds a submission to the database
	 * @param  array $data An array of the data to be saved.
	 * @return int   $post_id
	 */
	static private function add_submission( $data ) {

		$title = array( get_the_title( $data['quiz'] ) );

		$email = ! empty( $data['email'] ) ? $data['email'] : '';
		$email = empty( $email ) && ! empty( $data['user'] ) ? get_userdata( $data['user'] )->user_email : '';

		if ( $email ) {
			$title[] = $email;
		}

		$title[] = date( 'Y/m/d H:i:s' );

		$title = implode( ' - ', $title );

		$post_data = array(
		  'post_title'      => $title,
		  'post_status'     => 'publish',
		  'post_type'       => 'lms_quiz_submission',
		  'post_author'     => $data['user'],
		);

		$post_id = wp_insert_post( $post_data );

		if ( $post_id != 0 ) {

			update_post_meta( $post_id, '_lms_quiz_submission_data', $data );
			update_post_meta( $post_id, '_lms_quiz_quiz_id', $data['quiz'] );

		}

		return $post_id;

	}

	/**
	 * Callback on the `post_row_actions` filter.
	 * Adds a view submissions link to the quiz quick links section
	 * @param  arrray  $actions
	 * @param  WP_Post $post    WordPress object of post
	 * @return array            Array of actions with the new action
	 */
	public static function quiz_row_actions( $actions, WP_Post $post ) {

		if ( $post->post_type != 'lms_quiz' ) {
			return $actions;
		}

		$actions['lms_quiz_links'] = '<a href="'. admin_url( '/edit.php?post_type=lms_quiz_submission&quiz_id='. $post->ID ) .'">View Reports</a>';
		return $actions;

	}

	/**
	 * Callback on the `parse_query` filter.
	 * Filter submissions table to show only from selected quiz
	 * @param  object $query WordPress query object
	 */
	public static function submissions_filter( $query ) {

		 if ( is_admin() && $query->query['post_type'] == 'lms_quiz_submission' ) {

			$qv = &$query->query_vars;
			$qv['meta_query'] = array();

			if ( ! empty( $_GET['quiz_id'] ) && $_GET['quiz_id'] != -1 ) {

				$qv['meta_query'][] = array(
					'field'   => '_lms_quiz_quiz_id',
					'value'   => $_GET['quiz_id'],
					'compare' => '=',
					'type'    => 'NUMERIC'
				  );

			}

		 }

	}

	/**
	 * Gets all quizzes into an array of ID => Title
	 * @return array An array of quizzes
	 */
	public static function get_all_quizzes() {

		$quizzes = array();

		$query = new WP_Query(
			array(
				'post_type'      => 'lms_quiz',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$quizzes[get_the_ID()] = get_the_title();
			}
		}

		wp_reset_postdata();
		return $quizzes;

	}

	/**
	 * Callback on the `restrict_manage_posts` filter.
	 * Shows the select box for filtering by quiz on submissions table
	 * @uses   self::get_all_quizzes()
	 */
	public static function submissions_table_filtering() {

		$quizzes = self::get_all_quizzes();
		echo '<select class="postform" name="quiz_id">';
		echo '<option value="-1">' . __( 'Show quizzes' ) . '</option>';
		foreach ( $quizzes as $key=>$value ) {

			$selected = ( ! empty( $_GET['quiz_id'] ) && $_GET['quiz_id'] == $key ) ? 'selected="selected"' : '';
			echo '<option value="'.$key,'" '.$selected.'>' . $value . '</option>';

		}
		echo '</select>';

	}

	/**
	 * Callback on the `add_meta_boxes` filter.
	 * Removes slug from post page and page options.
	 */
	public static function remove_slug_meta() {
		remove_meta_box( 'slugdiv', 'lms_quiz'           , 'normal' );
		remove_meta_box( 'slugdiv', 'lms_quiz_submission', 'normal' );
	}

	/**
	 *  Callback for ``
	 *  Provides json encoded output for ajax page search on url entry.
	 *
	 */

	public static function ajax_page_search() {
		$q = $_REQUEST['q'];
		global $wpdb;

		$array = array();

	    $search = like_escape( $q );

	    $query = 'SELECT ID,post_title FROM ' . $wpdb->posts . '
	        WHERE post_title LIKE \'' . $search . '%\'
	        AND post_type = \'page\'
	        AND post_status = \'publish\'
	        ORDER BY post_title ASC';

	    $results = $wpdb->get_results($query);

	    if( $wpdb->num_rows > 0 ) {
		    foreach ( $results as $row ) {
		        $post_title = $row->post_title;
		        $id = $row->ID;

		        //echo $post_title."\n";

		        //$meta = get_post_meta($id, 'YOUR_METANAME', TRUE);
		        $array[] = array(
		        	'results' => $wpdb->num_rows,
		        	'ID'      => $id,
		        	'value'   => $post_title
		        );

		    }
		} else {
			$array[] = array(
				'results' => $wpdb->num_rows,
	        	'ID'      => '',
	        	'value'   => 'No results found.'
	        );
		}
	    echo json_encode( $array );
	    die();
	}

}
$lms_quiz_system = new LMS_Quiz_System();
