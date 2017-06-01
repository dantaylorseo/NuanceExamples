<?php

class LMS_Quiz_Reports {

	/**
	 * Callback for `add_object_page()` from LMS_Quiz_Admin_Menu
	 * Echos the HTML for the reports page.
	 */
	public function quiz_reports_page() {
		$quizzes = LMS_Quiz_System::get_all_quizzes();
		$report_types = array(
			'0' => 'Individual Entries',
			'1' => 'Aggregate Results',
			'2' => 'Export CSV'
		);
	?>
		<div class="wrap">
			<h2><?php _e('Quiz Reports') ?></h2>
			<form method="GET" id="lms_quiz_reports_form" method="<?php echo admin_url( '/admin.php?page=lms-quiz-reports' ); ?>">
				<input type="hidden" name="page" value="lms-quiz-reports">
				<table class="form-table">
					<tr>
						<th>Quiz</th>
						<td>
							<?php
								$quizargs = array(
									'data'    => $quizzes,
									'initial' => 'Select quiz',
									'name'    => 'quiz_id',
									'id'      => 'lms_quiz_quiz_id'
								);
								if ( isset ( $_GET['quiz_id'] ) ) {
									$quizargs['selected'] =  $_GET['quiz_id'];
								}
								array_to_select( $quizargs );
							?>
						</td>
					</tr>
					<tr>
						<th>Report Type</th>
						<td>
							<?php
								$reportargs = array(
									'data'    => $report_types,
									'initial' => 'Select report type',
									'name'    => 'report_type',
									'id'      => 'lms_quiz_report_type'
								);
								if ( isset ( $_GET['report_type'] ) ) {
									$reportargs['selected'] =  $_GET['report_type'];
								}
								array_to_select( $reportargs );
							?>
						</td>
					</tr>
				</table>
				<p>
					<button type="submit" class="button-primary" id="report-button">View Report</button>
				</p>
			</form>
		<?php
			if ( isset ( $_GET['quiz_id'] ) && isset( $_GET['report_type'] ) ) {

				switch ( $_GET['report_type'] ) {
					case 1 :
						self::aggregate_report( $_GET['quiz_id'] );
						break;
				}

			}
		?>
		</div>
	<?php
	}

	/**
	 * Provdies the output for the aggregate report
	 * @param  int $quiz_id The ID of the quiz
	 */
	private static function aggregate_report( $quiz_id ) {
		$args = array(
			'post_type'  => 'lms_quiz_submission',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'field'   => '_lms_quiz_quiz_id',
					'value'   => $_GET['quiz_id'],
					'compare' => '=',
					'type'    => 'NUMERIC'
				)
			)
		);
		$results = new WP_Query( $args );
		//print_var( $results );
		$questions = get_post_meta( $quiz_id, '_lms_quiz_questions', true );
		$newquestions = array();
		foreach ( $questions as $qkey=>$qvalue ) {
			foreach ( $qvalue['answers'] as $key=>$value ) {
				$questions[$qkey]['answers'][$key]['count'] = 0;
			}
		}

		foreach ( $results->posts as $result ) {
			$meta = get_post_meta( $result->ID, '_lms_quiz_submission_data', true );
			foreach ( $meta['answers'] as $key=>$value ) {
				//print_var( $answer );
				$answer = $meta['answers'][$key]['answer_no'];
				$questions[$key]['answers'][$answer]['count'] ++;
			}
		}
	?>
		<h2 class="nav-tab-wrapper">
			<span class="nav-tab nav-tab-active ">Aggregate Results</span>
		</h2>
		<h3 class="lms_quiz_total_entries">Total Entries: <strong><?php echo $results->found_posts; ?></strong></h3>
		<?php foreach ( $questions as $key=>$question ) { ?>
		<div class="lms_quiz_reports_question">
			<h3><em>Question <?php echo $key+1; ?>:</em> <?php echo stripslashes( $question['question'] ); ?></h3>
			<table class="lms_quiz_reports_table">
				<tr>
					<th class="lms_quiz_agg_1"></th><th class="lms_quiz_agg_2">Answers</th><th class="lms_quiz_agg_3">Percentage</th><th class="lms_quiz_agg_4"></th>
				</tr>
				<?php foreach( $question['answers'] as $answer ) { ?>
					<tr>
						<td class="lms_quiz_agg_1"><?php echo stripslashes( $answer['answer'] ); ?></td>
						<td class="lms_quiz_agg_2"><?php echo $answer['count']; ?></td>
						<td class="lms_quiz_agg_3"><span class="lms_quiz_score"><?php echo number_format( ( $answer['count'] / $results->found_posts ) * 100, 1 ); ?>%</span></td>
						<td class="lms_quiz_agg_4"><span class="lms_quiz_score_bar" style="width: <?php echo ( ( $answer['count'] / $results->found_posts ) *100 ); ?>%;"></span></td>
					</tr>
				<?php } ?>
			</table>
		</div>
		<?php } ?>

	<?php
	}

	/**
	 * Pulls together the submission data
	 * @param  int   $quiz_id The ID of the quiz
	 * @return array $csv     An array of submission data formatted for CSV     
	 */
	public static function export_csv( $quiz_id ) {
		$args = array(
			'post_type'  => 'lms_quiz_submission',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'field'   => '_lms_quiz_quiz_id',
					'value'   => $_GET['quiz_id'],
					'compare' => '=',
					'type'    => 'NUMERIC'
				)
			)
		);
		$results = new WP_Query( $args );

		$headers = array(
			'User',
			'Email',
			'IP',
			'Total Score'
		);

		$questions = get_post_meta( $quiz_id, '_lms_quiz_questions', true );
		$newquestions = array();
		foreach ( $questions as $question ) {
			array_push( $headers, 'Q: '.$question['question'] );
		}
		array_push( $headers, 'Link to submission' );
		array_push( $headers, 'Date' );


		$csv = array();
		$csv[] = $headers;

		foreach ( $results->posts as $result ) {
			
			$meta = get_post_meta( $result->ID, '_lms_quiz_submission_data', true );
			$user_info = get_userdata( $meta['user'] );

			if ( $meta['user'] != 0 ) {
				$email = $user_info->user_email;
			 } else {
				$email = ( $meta['email'] != '' ? $meta['email'] : 'Not set' );
			 }
			$temp = array(
				$user_info->user_login,
				$email,
				$meta['ip'],
				$meta['score']
			);

			foreach ( $meta['answers'] as $answer ) {
				array_push( $temp, $answer['answer'] );
			}
			
			array_push( $temp, admin_url( '/post.php?post='.$result->ID.'&action=edit' ) );
			array_push( $temp, $result->post_date );
			array_push( $csv, $temp );

		}

		return $csv;
	}

	/**
	 * Outputs submisison data to a CSV and downloads the file.
	 * @uses   export_csv()
	 * @return void    
	 */
	public static function download_csv() {

		if ( isset ( $_GET['quiz_id'] ) && isset( $_GET['report_type'] ) && ( isset( $_GET['action'] ) && $_GET['action'] == 'download_csv' ) ) {

			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename='.$_GET['quiz_id'].'-submissions.csv');

			$output = fopen('php://output', 'w');

			$csv = self::export_csv( $_GET['quiz_id'] );

			foreach ($csv AS $values){
				fputcsv($output, $values);
			}

			exit;
		}

	}
}