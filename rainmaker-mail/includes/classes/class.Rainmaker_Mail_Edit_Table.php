<?php

class Rainmaker_Mail_Edit_Table {

	/**
	 * The columns used for the edit table
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 */
	private $_columns = array();

	/**
	 * Stores the posts that have been processed previously
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 */
	private $_posts   = array();

	/**
	 * Stores the singleton instance of the `Rainmaker_Mail_Edit_Table` object.
	 *
	 * @var object
	 * @access private
	 * @static
	 */
	private static $_instance;

	/**
	 * Returns the `Rainmaker_Mail_Edit_Table` instance of this class.
	 *
	 * @return object Singleton The `Rainmaker_Mail_Edit_Table` instance.
	 */
	protected static function get_instance() {

		if ( null === static::$_instance ) {
			static::$_instance = new static();
		}

		return static::$_instance;

	}

	/**
	 * Handles custom table output for the edit broadcast `mail` screen..
	 *
	 * @access protected
	 * @return void
	 */
	protected function __construct() {

		$this->_columns = array(
			'recipients'   => __( 'Recipients'  , '' ),
			'openrate'     => __( 'Open Rate'   , '' ),
			'clickrate'    => __( 'Click Rate'  , '' ),
			'unsubscribes' => __( 'Unsubscribes', '' ),
			'columndate'   => __( 'Date'        , '' ),
		);

	}

	/**
	 * Enqueues the script and css files for the formula builder.
	 *
	 * @uses wp_enqueue_script
	 * @uses wp_enqueue_style
	 * @access public
	 * @static
	 * @return void
	 */
	static function enqueue_scripts() {

		wp_enqueue_style(  'rainmaker-mail-broadcast-list-css', RM_MAIL_ASSETS . 'css/rainmaker-email.broadcast.list.css', array( 'magnific-popup-css'              ), RAINMAKER_BUILD_VERSION, 'all' );
		wp_enqueue_script( 'rainmaker-mail-broadcast-list-js' , RM_MAIL_ASSETS . 'js/rainmaker-email.broadcast.list.js'  , array( 'jquery', 'jquery-magnific-popup' ), RAINMAKER_BUILD_VERSION, true  );

		add_action( 'admin_footer', array( 'Rainmaker_Mail_Edit_Table', 'footer' ) );

	}

	/**
	 * Callback on the `manage_mail_posts_columns` filter.
	 *
	 * @access public
	 * @static
	 * @param  array $post_columns
	 * @return array get_instance()->_posts_columns( $post_columns )
	 */
	static function posts_columns( $post_columns ) {

		return static::get_instance()->_posts_columns( $post_columns );

	}

	/**
	 * Modifies the columns in the mail edit lists table..
	 *
	 * @access private
	 * @param  array $post_columns
	 * @return array
	 */
	private function _posts_columns( $post_columns ) {

		$post_columns['title'] = __( 'Email Subject', '' );

		$post_columns = array_slice( $post_columns, 0, 2, true ) + $this->_columns + array_slice( $post_columns, 2, count( $post_columns )-2, true );

		unset( $post_columns['date'] );

		return $post_columns;

	}

	/**
	 * Callback on the `manage_mail_posts_custom_column` action.
	 * Invokes static::get_instance()->_custom_column( $column_id, $post_id )
	 *
	 * @access public
	 * @static
	 * @param string $column_id
	 * @param int $post_id
	 * @return void
	 */
	static function custom_column( $column_id, $post_id ) {

		static::get_instance()->_custom_column( $column_id, $post_id );

	}

	/**
	 * Checks to see if the current column is handled by this object.
	 * Then invokes the appriate column method.
	 *
	 * @access private
	 * @param  string $column_id
	 * @param  int $post_id
	 * @return void
	 */
	private function _custom_column( $column_id, $post_id ) {

		if ( isset( $this->_columns[$column_id] ) ) {

			if ( 'columndate' === $column_id ) {

				$this->$column_id( $post_id );
				return;

			} elseif ( isset( $this->_posts[$post_id] ) ) {
				return;
			}

			$this->_posts[$post_id] = $post_id;

			$this->placeholder( $post_id );

		}

	}

	function placeholder( $post_id ) {
		printf( '<div class="loading-data" data-id="%s"><span class="spinner-inline"></span> %s</div>',
			$post_id,
			__( 'Loading sequence metrics...', '' )
		);
	}

	static function get_metrics() {

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : '';

		if ( ! static::get_instance()->setup_data( $post_id ) ) {
			
			$metrics = array(
				'recipients'   => static::get_instance()->_default( $post_id ),
				'openrate'     => static::get_instance()->_default( $post_id ),
				'clickrate'    => static::get_instance()->_default( $post_id ),
				'unsubscribes' => static::get_instance()->_default( $post_id ),
			);
			
		} else {

			$metrics = array(
				'recipients'   => static::get_instance()->recipients(   $post_id ),
				'openrate'     => static::get_instance()->openrate(     $post_id ),
				'clickrate'    => static::get_instance()->clickrate(    $post_id ),
				'unsubscribes' => static::get_instance()->unsubscribes( $post_id ),
			);
			
		}
		
		//var_dump( $metrics );
		
		echo json_encode( $metrics );

		die();

	}

	/**
	 * Sets up the metrics data for the broadcast.
	 *
	 * @access public
	 * @param  int $post_id
	 * @return boolean
	 */
	function setup_data( $post_id ) {

		if ( isset( $this->_posts[$post_id] ) ) {
			if ( 0 > $this->_posts[$post_id] ) {
				$this->_default(); //no metrics available
				return false;
			}
			return true;
		}

		$mailings    = get_post_meta( $post_id, '_broadcast_message_ids', true );
		$has_metrics = false;

		if ( $mailings ) {

			$mailing_metrics = array(
				'recipients'   => 0,
				'openrate'     => 0,
				'clickrate'    => 0,
				'unsubscribes' => 0,
			);

			foreach ( $mailings as $list => $mailing ) {

				if ( empty( $mailing ) ) {
					continue;
				}

				$list_metrics = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_metrics( $list, $mailing );

				if ( empty( $list_metrics ) ) {
					continue;
				}

				if ( empty( $list_metrics['sent'] ) ) {
					continue;
				}

				$mailing_metrics['recipients']   += $list_metrics['sent'];
				$mailing_metrics['openrate']     += $list_metrics['opens'];
				$mailing_metrics['clickrate']    += $list_metrics['clicks'];
				$mailing_metrics['unsubscribes'] += $list_metrics['unsubscribes'];

				$has_metrics = true;

			}

		}

		if ( $has_metrics ) {
			$this->_posts[$post_id] = $mailing_metrics;
			return true;
		} else {
			$this->_posts[$post_id] = -1;
		}

		return false;

	}

	/**
	 * Outputs the number_format value for the number of mailings sent.
	 *
	 * @access public
	 * @param  int $post_id
	 * @return void
	 */
	function recipients( $post_id ) {
		return number_format( $this->_posts[$post_id]['recipients'] );
	}

	/**
	 * Outputs the percentage of the mailings opened.
	 *
	 * @access public
	 * @param  int $post_id
	 * @return void
	 */
	function openrate( $post_id ) {
		return sprintf( '%d%%', ( $this->_posts[$post_id]['openrate'] / $this->_posts[$post_id]['recipients'] ) * 100 );
	}

	/**
	 * Outputs percentage of the mailing links clicked.
	 *
	 * @access public
	 * @param  int $post_id
	 * @return void
	 */
	function clickrate( $post_id ) {
		return sprintf( '%d%%', ( $this->_posts[$post_id]['clickrate'] / $this->_posts[$post_id]['recipients'] ) * 100 );
	}

	/**
	 * Outputs the number_format value for the number of unsubscribes.
	 *
	 * @access public
	 * @param  int $post_id
	 * @return void
	 */
	function unsubscribes( $post_id ) {
		return number_format( $this->_posts[$post_id]['unsubscribes'] );
	}

	private function _default() {
		return '<span class="no-data">&mdash;</span>';
	}

	/**
	 * Handles the post date column output.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @global string $mode
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
	public function columndate( $post_id ) {

		$post = get_post( $post_id );

		global $mode;

		if ( '0000-00-00 00:00:00' === $post->post_date ) {
			$t_time = $h_time = __( 'Not Sent' );
			$time_diff = 0;
		} else {
			$t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
			$m_time = $post->post_date;
			$time = get_post_time( 'G', true, $post );

			$time_diff = time() - $time;

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
			} else {
				$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
			}
		}

		if ( 'publish' === $post->post_status ) {
			_e( 'Sent' );
		} elseif ( 'future' === $post->post_status ) {
			if ( $time_diff > 0 ) {
				echo '<strong class="error-message">' . __( 'Missed schedule' ) . '</strong>';
			} else {
				_e( 'Scheduled' );
			}
		} else {
			_e( 'Last Modified' );
		}
		echo '<br />';
		if ( 'excerpt' === $mode ) {
			/**
			 * Filter the published time of the post.
			 *
			 * If `$mode` equals 'excerpt', the published time and date are both displayed.
			 * If `$mode` equals 'list' (default), the publish date is displayed, with the
			 * time and date together available as an abbreviation definition.
			 *
			 * @since 2.5.1
			 *
			 * @param string  $t_time      The published time.
			 * @param WP_Post $post        Post object.
			 * @param string  $column_name The column name.
			 * @param string  $mode        The list display mode ('excerpt' or 'list').
			 */
			echo apply_filters( 'post_date_column_time', $t_time, $post, 'date', $mode );
		} else {

			/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, 'date', $mode ) . '</abbr>';
		}
	}

	/**
	 * Callback for WordPress 'page_row_actions' action.
	 *
	 * Add the 'Duplicate' ability to the mail post type.
	 *
	 * @since 2.4.2
	 *
	 * @param  array  $actions An associative array of actions.
	 * @param  object $post    The current post object.
	 * @return array           An associative array of actions.
	 */
	static function page_row_actions( $actions, $post ) {

		if ( isset( $_GET['post_status'] ) && 'trash' == $_GET['post_status'] ) {
			return $actions;//don't alter the trash post actions
		}

		$edit_posts_link = get_edit_post_link( $post->ID, '' );
		$view_posts_link = add_query_arg( 'tab', 'preview_email', $edit_posts_link );

		if ( 'publish' == get_post_status( $post->ID ) ) {

			$trash = $actions['trash'];

			$actions = array(
				'edit'  => sprintf( '<a href="%s" title="%s">%s</a>'             , $edit_posts_link,            __( 'View Broadcast Metrics', '' ), __( 'Metrics', '' ) ),
				'view'  => sprintf( '<a href="%s" data-id="%s" title="%s" class="broadcast-preview">%s</a>', $view_posts_link, $post->ID, __( 'View'                  , '' ), __( 'View'   , '' ) ),
				'trash' => $trash,
			);

		} else {

			$trash = $actions['trash'];
			$edit  = $actions['edit'];

			$actions = array(
				'edit'  => $edit,
				'view'  => sprintf( '<a href="%s" data-id="%s" title="%s" class="broadcast-preview">%s</a>', $view_posts_link, $post->ID, __( 'Preview', '' ), __( 'Preview', '' ) ),
				'trash' => $trash,
			);

		}

		$args = array( 'premise_copy_post' => $post->ID );
		$url  = add_query_arg( $args, admin_url() );
		$link = sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( $url ),
			esc_attr__( 'Duplicate', 'premise' ),
			esc_html__( 'Duplicate', 'premise' )
		);

		$actions["{$post->post_type}_copy"] = $link;

		return $actions;

	}

	/**
	 * Callback on the `wp_ajax_rm_broadcast_preview` hook.
	 * Outputs the email content as a preview.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function preview() {

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : '';

		if ( empty( $post_id ) ) {
			die( 'Error: Broadcast Not Found.' );
		}

		$post = get_post( $post_id );

		if ( empty( $post ) || is_wp_error( $post ) || empty( $post->post_content ) ) {
			die( 'Error: Broadcast Content Not Found.' );
		}

		$template = get_post_meta( $post_id, '_email_template', true );

		if ( empty( $template ) ) {
			$template = Rainmaker_Mail_Template_Option::get();
		}

		$content = Rainmaker_Mail_Build_Content::get_instance()->get_mail_content( $post->post_content, array( 'template' => $template, 'do_shortcodes' => false, ) );

		die( $content );

	}

	/**
	 * Callback on the `admin_footer` hook.
	 * Outputs the #subscriber_editor box.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function footer() {

?>
		<div id="broadcast_preview" class="white-popup mfp-hide">

			<iframe id="broadcast_preview_window"></iframe>

		</div>
		<?php

	}

}
