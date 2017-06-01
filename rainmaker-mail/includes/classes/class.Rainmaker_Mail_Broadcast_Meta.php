<?php

class Rainmaker_Mail_Broadcast_Meta {

	/**
	 * Boolean to make the refresh notice only show once.
	 *
	 * (default value: false)
	 *
	 * @var bool
	 * @access private
	 */
	private $_has_refresh_notice = false;

	/**
	 * FeedBlitz lists
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access private
	 */
	private $_lists = array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct(){

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts'  ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_filter( 'enter_title_here'     , array( $this, 'enter_title_here' ) );
		add_action( 'edit_form_top'        , array( $this, 'tab_menu'         ) );
		add_action( 'edit_form_top'        , array( $this, 'send_to'          ) );
		add_action( 'edit_form_top'        , array( $this, 'template'         ) );

		add_action( 'add_meta_boxes'       , array( $this, 'disable_publish'    ) );
		add_filter( 'mce_buttons'          , array( $this, 'mce_buttons'        ) );
		add_filter( 'quicktags_settings'   , array( $this, 'quicktags_settings' ) );

		add_filter( 'screen_options_show_screen', '__return_false' );
		add_filter( 'wp_editor_expand'          , '__return_false' );

		if ( isset( $_GET['post'] ) && get_post_meta( $_GET['post'], '_broadcast_message_ids', true ) ) {
			add_action( 'edit_form_top', array( $this, 'metrics' ) );
			add_action( 'edit_form_top', array( $this, 'sent_content' ) );
		} else {
			add_action( 'edit_form_top', array( $this, 'preview' ) );
		}

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

		wp_enqueue_style(  'rainmaker-mail-broadcast-meta-css', RM_MAIL_ASSETS . 'css/rainmaker-email.broadcast.editor.css', array(          ), '0.0.1', 'all' );
		wp_enqueue_script( 'rainmaker-mail-broadcast-meta-js' , RM_MAIL_ASSETS . 'js/rainmaker-email.broadcast.editor.js'  , array( 'jquery' ), '0.0.1', true  );

	}

	/**
	 * Callback method on the `enter_title_here` filter.
	 * Changes title hint to "Enter subject here" for mail cpt.
	 *
	 * @access public
	 * @static
	 * @param  string $text
	 * @return string
	 */
	static function enter_title_here( $text ) {

		return __( 'Enter subject here', '' );

	}

	/**
	 * Creates custom updated messages for broadcasts.
	 *
	 * @access public
	 * @static
	 * @param mixed $messages
	 * @return void
	 */
	static public function updated_messages ( $messages ) {

		global $post, $post_ID;

		$messages['mail'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Email updated.'        , '' ),
			2  => __( 'Custom field updated.' , '' ),
			3  => __( 'Custom field deleted.' , '' ),
			4  => __( 'Email updated.'        , '' ),
			/* translators: %s: date and time of the revision */
			5  => isset($_GET['revision']) ? sprintf( __( 'Email restored to revision from %s.' , '' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Email Broadcast Sent.', '' ),
			7  => __( 'Email saved.'         , '' ),
			8  => __( 'Email submitted.'     , '' ),
			9  => sprintf( __( 'Broadcast scheduled for: %1$s.', '' ), '<strong>' . date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $post->post_date ) ) . '</strong>' ),
			10 => __( 'Email draft updated.' , '' ),
		);

		return $messages;

	}

	/**
	 * Callback on the `add_meta_boxes` action.
	 * Removes the default submitdiv metabox.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static public function disable_publish() {

		remove_meta_box( 'submitdiv', null, 'side' );

	}

	/**
	 * Creates the broacast editor menu.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function tab_menu() {

		$menu_items = array(
			'metrics'               => __( 'Broadcast Metrics'      , '' ),
			'send_email_to'         => __( 'Sender &amp; Recipients', '' ),
			'select_email_template' => __( 'Template'               , '' ),
			'poststuff'             => __( 'Content'                , '' ),
			'sent_content'          => __( 'Content'                , '' ),
			'preview_email'         => __( 'Test &amp; Schedule'    , '' ),
		);

		if ( isset( $_GET['post'] ) && get_post_meta( $_GET['post'], '_broadcast_message_ids', true ) ) {
			unset( $menu_items['preview_email'] );
			unset( $menu_items['select_email_template'] );
			unset( $menu_items['poststuff'] );
		} else {
			unset( $menu_items['metrics'] );
			unset( $menu_items['sent_content'] );
		}

		$current = ( isset( $_GET['tab'] ) && isset( $menu_items[$_GET['tab']] ) ) ? '' : ' current-menu-tab';

?>
		<nav id="mail-campaign-navigation" class="rm-tabbed-menu">
			<ul class="mail-campaign-tab-navigation page-tab-navigation page-main-menu menu">
				<?php

		foreach ( $menu_items as $id => $name ) {

			if ( isset( $_GET['tab'] ) && $id == $_GET['tab'] ) {
				$current = ' current-menu-tab';
			}

			printf(
				'<li class="menu-tab%s"><a href="#%s">%s</a></li>%s',
				$current,
				$id,
				$name,
				"\r\n"
			);

			$current = '';

		}

?>
			</ul>
<?php
		if ( ! ( isset( $_GET['post'] ) && get_post_meta( $_GET['post'], '_broadcast_message_ids', true ) ) ) :
?>
			<input type="submit" name="save" id="menu-save-post" value="<?php _e( 'Save', '' ); ?>" class="button button-primary">
<?php endif; ?>
		</nav>
		<?php

	}

	/**
	 * Creates the Recipient meta options recipient output.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function send_to() {
		$hidden   = ( isset( $_GET['post'] ) && get_post_meta( $_GET['post'], '_broadcast_message_ids', true ) ) ? ' class="hidden" ' : '';
		$readonly = ( isset( $_GET['post'] ) && get_post_meta( $_GET['post'], '_broadcast_message_ids', true ) ) ? ' disabled ' : '';
?>
		<input type="hidden" name="_broadcast_editor_nonce" id="broadcast_editor_nonce" value="<?php echo wp_create_nonce( RM_MAIL_DIR ); ?>" />
		<div id="send_email_to"<?php echo $hidden; ?>>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label for="sender_name"><?php _e( 'Sender Name', '' ); ?></label></th>
						<td>
							<input type="text" id="sender_name" name="_sender_name" value="<?php genesis_custom_field( '_sender_name' ); ?>" style="min-width: 50%;" <?php echo $readonly; ?> />
							<p class="description"><?php _e( 'Leave empty to use list default.', '' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="sender_email"><?php _e( 'Sender Email', '' ); ?></label></th>
						<td>
							<input type="text" id="sender_email" name="_sender_email" value="<?php genesis_custom_field( '_sender_email' ); ?>" style="min-width: 50%;" <?php echo $readonly; ?> />
							<p class="description"><?php _e( 'Leave empty to use list default.', '' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Recipients', '' ); ?></th>
						<td>
							<div class="email-recipients-container">
								<h4><?php _e( 'Send to', '' ); ?></h4>
								<ul class="email-recipients recipients-list">
								<?php

		$lists = Rainmaker_Opt_In_Gateway_FeedBlitz::get_lists();

		$current_lists = isset( $_GET['post'] ) ? (array) get_post_meta( $_GET['post'], '_email_recipient_list', true ) : array();

		$first_list_class = ' class="first_list" '; //used for acceptance tests.

		foreach ( $lists as $label => $sections ) {

			printf( '<h5>%s</h5>', $label );

			foreach ( $sections as $id => $name ) {

				$checked = ! empty( $current_lists[$id] ) ? 'checked="checked" ' : '';

				printf(
					'<li class="email-recipient recipients-list-required">
						<input type="checkbox" %4$s name="_email_recipient_list[%2$s]" id="email_recipient_list_%2$s" value="1" %3$s/>
						<label for="email_recipient_list_%2$s"%5$s>%1$s</label>
					</li>',
					$name,
					$id,
					$checked,
					$readonly,
					$first_list_class
				);

				$first_list_class = '';

			}

		}

?>
								</ul>
							</div>
							<div class="email-recipients-container">
								<h4><?php _e( 'List Suppression', '' ); ?></h4>
								<ul class="email-recipients email-recipients-supression">
								<?php

		$current_lists = isset( $_GET['post'] ) ? (array) get_post_meta( $_GET['post'], '_email_suppression_list', true ) : array();

		foreach ( $lists as $label => $sections ) {

			printf( '<h5>%s</h5>', $label );

			foreach ( $sections as $id => $name ) {

				$checked = ! empty( $current_lists[$id] ) ? 'checked="checked" ' : '';

				printf(
					'<li class="email-recipient email-recipient-supression">
						<input type="checkbox" %4$s name="_email_suppression_list[%2$s]" id="email_suppression_list_%2$s" value="1" %3$s/>
						<label for="email_suppression_list_%2$s">%1$s</label>
					</li>',
					$name,
					$id,
					$checked,
					$readonly
				);

			}

		}

?>
								</ul>
							</div>
							<div class="clear"></div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Custom Fields', '' ); ?></th>
						<td>
							<div class="email-recipients-container">
								<h4><?php _e( 'Tagged with:', '' ); ?></h4>
								<ul class="email-recipients">
								<?php

		$fields = Rainmaker_Opt_In_Gateway_FeedBlitz::get_remote_fields();

		asort( $fields );

		$current_fields = isset( $_GET['post'] ) ? (array) get_post_meta( $_GET['post'], '_email_recipient_tagged_with', true ) : array();

		foreach ( $fields as $field ) {

			$checked = ! empty( $current_fields[esc_attr( $field )] ) ? 'checked="checked" ' : '';

			printf(
				'<li class="email-recipient">
					<input type="checkbox" %4$s name="_email_recipient_tagged_with[%2$s]" id="_email_recipient_tagged_with_%5$s" value="1" %3$s/>
					<label for="_email_recipient_tagged_with_%5$s">%1$s</label>
				</li>',
				$field,
				esc_attr( $field ),
				$checked,
				$readonly,
				sanitize_title( $field )
			);

		}

?>
								</ul>
							</div>
							<div class="email-recipients-container">
								<h4><?php _e( 'Does not have tag:', '' ); ?></h4>
								<ul class="email-recipients email-recipients-supression">
								<?php

		$current_fields = isset( $_GET['post'] ) ? (array) get_post_meta( $_GET['post'], '_email_recipient_not_tagged', true ) : array();

		foreach ( $fields as $field ) {

			$checked = ! empty( $current_fields[esc_attr( $field )] ) ? 'checked="checked" ' : '';

			printf(
				'<li class="email-recipient email-recipient-supression">
											<input type="checkbox" %4$s name="_email_recipient_not_tagged[%2$s]" id="_email_recipient_not_tagged_%5$s" value="1" %3$s/>
											<label for="_email_recipient_not_tagged_%5$s">%1$s</label>
										</li>',
				$field,
				esc_attr( $field ),
				$checked,
				$readonly,
				sanitize_title( $field )
			);

		}

?>
								</ul>
							</div>
							<div class="clear"></div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php
	}

	/**
	 * Creates the Recipient meta options template output.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function template() {
		$hidden = ( isset( $_GET['post'] ) && get_post_meta( $_GET['post'], '_broadcast_message_ids', true ) ) ? ' class="hidden" ' : '';
?>
		<div id="select_email_template"<?php echo $hidden; ?>>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><label><?php _e( 'Select template', '' ); ?></label></th>
						<td>
							<ul class="email-template-options">

								<?php

		$url = add_query_arg( array( 'tab' => 'email', 'saved' => 'true' ), menu_page_url( 'universal-settings', 0 ) );

		$templates = array(
			''        => sprintf( __( 'Default<small>(%s - <a href="%s" target="_blank">change</a>)</small>', '' ), Rainmaker_Mail_Template_Option::get( true ), $url ),
			'plain'   => __( 'Plain Text', '' ),
			'basic'   => __( 'Basic'     , '' ),
			'sidebar' => __( 'Sidebar'   , '' ),
			'custom'  => __( 'Custom'    , '' ),
		);

		$custom = rm_lp_get_option( 'custom_email_template' );

		if ( empty( $custom ) || $custom == trim( str_replace( '    ', '	', file_get_contents( sprintf( '%sassets/templates/basic.html', RM_MAIL_DIR ), 'r' ) ) ) ) {
			unset( $templates['custom'] );
		}

		$current = isset( $_GET['post'] ) ? get_post_meta( $_GET['post'], '_email_template', true ) : '';

		foreach ( $templates as $id => $name ) {

			$default = $id ? sprintf( '<input type="hidden" id="%1$s" name="%1$ss" value="%2$s" />', 'default_email_template', Rainmaker_Mail_Template_Option::get( true ) ) : '';

			$image   = sprintf( '<img src="%simages/%s.png" alt="%s" />', RM_MAIL_ASSETS, ( $id ? $id : 'default' ), $id ? $name : Rainmaker_Mail_Template_Option::get( true ) );

			printf(
				'<li class="email-template-option"><label for="email-template-%1$s"><input type="radio" id="email-template-%1$s" name="_email_template" value="%1$s" %4$s/>%2$s%3$s</label>%5$s</li>',
				$id,
				$image,
				$name,
				checked( $id, $current, false ),
				$default
			);

		}
?>

							</ul>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php
	}

	/**
	 * Creates the Recipient meta options preview output.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function preview() {
?>

		<div id="preview_email" class="clearfix rm-post-body">
			<div class="rm-sidebar">
				<?php Rainmaker_Mail_Broadcast_Meta::post_submit_meta_box(); ?>
			</div>
			<div class="rm-content">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label><?php _e( 'Test Recipient', '' ); ?></label></th>
							<td>
								<input type="text" id="test_email_recipient" name="test_email_recipient" value="" />
								<div class="email-test">
									<a class="button button-primary button-test-email" href="#"><?php _e( 'Send Test', '' ); ?></a>
									<span class="spinner"></span>
								</div>
								<div class="description">
									<?php

		_e( 'Separate multiple email addresses with commas', '' );

		rainmaker_tooltip( sprintf( '<p class="description">%s</p>', __( 'Standard CAN-SPAM footer content will be included when test emails are sent to an email address added to one of your lists. If the email address is not on a list, the standard footer will not be included and your chosen sender name and reply email address may not be used.', '' ) ) );
?>
								</div>
								<div class="hidden preview-success"><?php Rainmaker_Markup::formated_message( __( 'Congratulations, the email was sent.', '' ) . rainmaker_get_tooltip( sprintf( '<span class="description">%s</span>', __( 'Occasionally, it can take test emails a few minutes to show up in your inbox. If you don\'t see the test email in your inbox after a few minutes, be sure to check your Spam, Junk or Trash folders, as filters in your email program can sometimes prevent broadcast emails from reaching your inbox.', '' ) ) ) ); ?></div>
								<div class="hidden preview-failure"><?php Rainmaker_Markup::formated_message( __( 'Sorry, there was a problem sending the test. Please try again.', '' ), 'warning' ); ?></div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label><?php _e( 'Subject', '' ); ?></label></th>
							<td>
								<h2 id="email-subject-preview">
								</h2>
							</td>
						</tr>
					</tbody>
				</table>
				<iframe id="broadcast_preview_window"></iframe>
			</div>
		</div>

		<?php
	}

	/**
	 * Creates the Recipient meta options sent content output.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function sent_content() {
?>

		<div id="sent_content" class="clearfix rm-post-body">
			<div class="rm-sidebar">
				<?php Rainmaker_Mail_Broadcast_Meta::post_submit_meta_box(); ?>
			</div>
			<div class="rm-content">
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label><?php _e( 'Subject', '' ); ?></label></th>
							<td>
								<h2 id="email-subject-preview"><?php the_title(); ?></h2>
							</td>
						</tr>
					</tbody>
				</table>
				<div>
				<?php
		$content_post = get_post( $_GET['post'] );
		$content = $content_post->post_content;
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		echo $content;
?>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Display post submit form fields.
	 *
	 * @since 2.7.0
	 *
	 * @global string $action
	 *
	 * @param object $post
	 */
	static function post_submit_meta_box() {
		global $action, $post;

		$message_ids = get_post_meta( $post->ID, '_broadcast_message_ids', true );

		$post_type = $post->post_type;
		$post_type_object = get_post_type_object($post_type);
		$can_publish = current_user_can($post_type_object->cap->publish_posts);

		$args = null;
		if ( post_type_supports($post_type, 'revisions') && 'auto-draft' != $post->post_status ) {
			$revisions = wp_get_post_revisions( $post_ID );

			// We should aim to show the revisions metabox only when there are revisions.
			if ( count( $revisions ) > 1 ) {
				reset( $revisions ); // Reset pointer for key()
				$args = array( 'revisions_count' => count( $revisions ), 'revision_id' => key( $revisions ) );
			}
		}
?>
		<div id="submitdiv" class="postbox">
		<div class="inside">

		<div class="submitbox" id="submitpost">

		<div id="minor-publishing">

		<?php // Hidden submit button early on so that the browser chooses the right button when form is submitted with Return key ?>
		<div style="display:none;">
		<?php submit_button( __( 'Save' ), 'button', 'save' ); ?>
		</div>

		<div id="misc-publishing-actions">

		<div class="misc-pub-section misc-pub-post-status"><label for="post_status"><?php _e('Status:') ?></label>
		<span id="post-status-display-fixed">
		<?php
		switch ( $post->post_status ) {

		case 'private':
			_e('Privately Published');
			break;

		case 'publish':
			_e('Broadcast Sent');
			break;

		case 'future':
			_e('Scheduled');
			break;

		case 'pending':
			_e('Pending Review');
			break;

		case 'draft':
		case 'auto-draft':
			_e('Draft');
			break;

		}
?>
		</span>
		</div><!-- .misc-pub-section -->

		<?php
		/* translators: Publish box date format, see http://php.net/date */
		$datef = __( 'M j, Y @ H:i' );
		if ( 0 != $post->ID ) {
			if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
				$stamp = __('Scheduled for: <b>%1$s</b>');
			} elseif ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
				$stamp = __('Broadcast on: <b>%1$s</b>');
			} elseif ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
				$stamp = __('Schedule for: <b>%1$s</b>');
				$date = date_i18n( $datef, strtotime( current_time('mysql') ) + ( 60 * 60 * 12 ) ); //default is to schedule 12 hours out.
			} elseif ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
				$stamp = __('Schedule for: <b>%1$s</b>');
			} else { // draft, 1 or more saves, date specified
				$stamp = __('Broadcast on: <b>%1$s</b>');
			}
			$date = empty( $date ) ? date_i18n( $datef, strtotime( $post->post_date ) ) : $date;
		} else { // draft (no saves, and thus no date specified)
			$stamp = __('Schedule for: <b>%1$s</b>');
			$date = date_i18n( $datef, strtotime( current_time('mysql') ) + ( 60 * 60 * 12 ) ); //default is to schedule 12 hours out.
		}

		if ( ! empty( $args['args']['revisions_count'] ) ) :
			$revisions_to_keep = wp_revisions_to_keep( $post );
?>
		<div class="misc-pub-section misc-pub-revisions">
		<?php
		if ( $revisions_to_keep > 0 && $revisions_to_keep <= $args['args']['revisions_count'] ) {
			echo '<span title="' . esc_attr( sprintf( __( 'Your site is configured to keep only the last %s revisions.' ),
					number_format_i18n( $revisions_to_keep ) ) ) . '">';
			printf( __( 'Revisions: %s' ), '<b>' . number_format_i18n( $args['args']['revisions_count'] ) . '+</b>' );
			echo '</span>';
		} else {
			printf( __( 'Revisions: %s' ), '<b>' . number_format_i18n( $args['args']['revisions_count'] ) . '</b>' );
		}
?>
			<a class="hide-if-no-js" href="<?php echo esc_url( get_edit_post_link( $args['args']['revision_id'] ) ); ?>"><span aria-hidden="true"><?php _ex( 'Browse', 'revisions' ); ?></span> <span class="screen-reader-text"><?php _e( 'Browse revisions' ); ?></span></a>
		</div>
		<?php endif;

		if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>
		<div class="misc-pub-section curtime misc-pub-curtime">
			<span id="timestamp">
			<?php printf($stamp, $date); ?></span>
			<?php if ( ! $message_ids ) : ?>
			<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
			<fieldset id="timestampdiv" class="hide-if-js">
			<legend class="screen-reader-text"><?php _e( 'Date and time' ); ?></legend>
			<?php static::touch_time( ( $action === 'edit' ), 1 ); ?>
			</fieldset>
			<?php endif; ?>
		</div><?php // /misc-pub-section ?>
		<?php endif; ?>

		<?php
		/**
		 * Fires after the post time/date setting in the Publish meta box.
		 *
		 * @since 2.9.0
		 */
		do_action( 'post_submitbox_misc_actions' );
?>
		</div>
		<div class="clear"></div>
		</div>

		<div id="major-publishing-actions">
		<?php
		/**
		 * Fires at the beginning of the publishing actions section of the Publish meta box.
		 *
		 * @since 2.7.0
		 */
		do_action( 'post_submitbox_start' );
?>
		<div id="delete-action">
		<?php
		if ( current_user_can( "delete_post", $post->ID ) ) {
			if ( ! EMPTY_TRASH_DAYS )
				$delete_text = __('Delete Permanently');
			else
				$delete_text = __('Move to Trash');
?>
		<a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a><?php
		} ?>
		</div>

		<div id="publishing-action">
		<span class="spinner"></span>
		<input type="submit" name="" id="sending" class="button button-large button-secondary hidden" value="Sending …" disabled="disabled">

		<?php if ( ! exceeds_subscriber_limit() && ! $message_ids ) : ?>

			<?php if ( ! in_array( $post->post_status, array( 'publish', 'future', 'private' ) ) || 0 == $post->ID ) : ?>
				<?php if ( $can_publish ) : ?>
					<?php if ( isset( $_GET['post'] ) && 0 != $post->ID && ! empty( $post->post_date_gmt ) && time() > strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Schedule Broadcast') ?>" />
						<?php submit_button( __( 'Send Broadcast Immediately' ), 'primary button-large', 'publish', false ); ?>
					<?php else : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Send Broadcast Immediately') ?>" />
						<?php submit_button( __( 'Schedule Broadcast' ), 'primary button-large', 'publish', false ); ?>
					<?php endif; ?>
				<?php else : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>" />
					<?php submit_button( __( 'Submit for Review' ), 'primary button-large', 'publish', false ); ?>
				<?php endif; ?>
			<?php else : ?>
					<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
					<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update' ) ?>" />
			<?php endif; ?>

		<?php endif; ?>
		</div>
		<div class="clear"></div>
		</div>

		</div><!-- end #submitpost -->

		</div><!-- end .inside -->
		</div><!-- end #submitdiv-->

		<?php
	}

	/**
	 * Print out HTML form date elements for editing post or comment publish date.
	 *
	 * @since 0.71
	 *
	 * @global WP_Locale $wp_locale
	 * @global object    $comment
	 *
	 * @param int|bool $edit      Accepts 1|true for editing the date, 0|false for adding the date.
	 * @param int|bool $for_post  Accepts 1|true for applying the date to a post, 0|false for a comment.
	 * @param int      $tab_index The tabindex attribute to add. Default 0.
	 * @param int|bool $multi     Optional. Whether the additional fields and buttons should be added.
	 *                            Default 0|false.
	 */
	static function touch_time( $edit = 1, $for_post = 1, $tab_index = 0, $multi = 0 ) {

		global $wp_locale, $comment;
		$post = get_post();

		$edit = in_array( $post->post_status, array( 'future', 'publish' ) );

		$tab_index_attribute = '';

		if ( (int) $tab_index > 0 ) {
			$tab_index_attribute = " tabindex=\"$tab_index\"";
		}

		$cur_time  = current_time('timestamp');
		$time_adj  = $cur_time + DAY_IN_SECONDS/2; //default is to schedule 12 hours from now.
		$post_date = $for_post ? $post->post_date : $comment->comment_date;

		$jj = ( $edit ) ? mysql2date( 'd', $post_date, false ) : gmdate( 'd', $time_adj );
		$mm = ( $edit ) ? mysql2date( 'm', $post_date, false ) : gmdate( 'm', $time_adj );
		$aa = ( $edit ) ? mysql2date( 'Y', $post_date, false ) : gmdate( 'Y', $time_adj );
		$hh = ( $edit ) ? mysql2date( 'H', $post_date, false ) : gmdate( 'H', $time_adj );
		$mn = ( $edit ) ? mysql2date( 'i', $post_date, false ) : gmdate( 'i', $time_adj );
		$ss = ( $edit ) ? mysql2date( 's', $post_date, false ) : gmdate( 's', $time_adj );

		$cur_jj = gmdate( 'd', $cur_time );
		$cur_mm = gmdate( 'm', $cur_time );
		$cur_aa = gmdate( 'Y', $cur_time );
		$cur_hh = gmdate( 'H', $cur_time );
		$cur_mn = gmdate( 'i', $cur_time );

		$month = '<label><span class="screen-reader-text">' . __( 'Month' ) . '</span><select ' . ( $multi ? '' : 'id="mm" ' ) . 'name="mm"' . $tab_index_attribute . ">\n";
		for ( $i = 1; $i < 13; $i = $i +1 ) {
			$monthnum = zeroise($i, 2);
			$monthtext = $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) );
			$month .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected( $monthnum, $mm, false ) . '>';
			/* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
			$month .= sprintf( __( '%1$s-%2$s' ), $monthnum, $monthtext ) . "</option>\n";
		}
		$month .= '</select></label>';

		$day = '<label><span class="screen-reader-text">' . __( 'Day' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="jj" ' ) . 'name="jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
		$year = '<label><span class="screen-reader-text">' . __( 'Year' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="aa" ' ) . 'name="aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" /></label>';
		$hour = '<label><span class="screen-reader-text">' . __( 'Hour' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="hh" ' ) . 'name="hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
		$minute = '<label><span class="screen-reader-text">' . __( 'Minute' ) . '</span><input type="text" ' . ( $multi ? '' : 'id="mn" ' ) . 'name="mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';

		echo '<div class="timestamp-wrap">';

		/* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
		printf( __( '%1$s %2$s, %3$s @ %4$s:%5$s' ), $month, $day, $year, $hour, $minute );

		echo '</div><input type="hidden" id="ss" name="ss" value="' . $ss . '" />';

		if ( $multi ) return;

		echo "\n\n";

		if ( empty( $_GET['post'] ) ) {
			$map = array(
				'mm' => array( $cur_mm, $cur_mm ),
				'jj' => array( $cur_jj, $cur_jj ),
				'aa' => array( $cur_aa, $cur_aa ),
				'hh' => array( $cur_hh, $cur_hh ),
				'mn' => array( $cur_mn, $cur_mn ),
			);
		} else {
			$map = array(
				'mm' => array( $mm, $cur_mm ),
				'jj' => array( $jj, $cur_jj ),
				'aa' => array( $aa, $cur_aa ),
				'hh' => array( $hh, $cur_hh ),
				'mn' => array( $mn, $cur_mn ),
			);
		}

		foreach ( $map as $timeunit => $value ) {
			list( $unit, $curr ) = $value;

			echo '<input type="hidden" id="hidden_' . $timeunit . '" name="hidden_' . $timeunit . '" value="' . $unit . '" />' . "\n";
			$cur_timeunit = 'cur_' . $timeunit;
			echo '<input type="hidden" id="' . $cur_timeunit . '" name="' . $cur_timeunit . '" value="' . $curr . '" />' . "\n";
		}
?>

	<p>
	<a href="#edit_timestamp" class="save-timestamp hide-if-no-js button"><?php _e('OK'); ?></a>
	<a href="#edit_timestamp" class="cancel-timestamp hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
	</p>
	<?php
	}

	/**
	 * Outputs the metrics HTML.
	 *
	 * @access public
	 * @return void
	 */
	function metrics() {

		$mailings    = get_post_meta( $_GET['post'], '_broadcast_message_ids', true );
		$has_metrics = false;
?>
		<div id="metrics" class="">
			<p><strong><?php _e( 'Subject: ', '' ); ?></strong> <?php printf( '%s', get_the_title( '', $_GET['post'] ) ); ?></p>
			<p><strong><?php _e( 'Broadcast On: ', '' ); ?></strong> <?php printf( '%s %s', get_the_date( '', $_GET['post'] ), get_the_time( '', $_GET['post'] ) ); ?></p>
			<?php foreach ( $mailings as $list => $mailing ) : ?>
				<?php if ( empty( $mailing ) && ! ( $mailing = $this->get_mailing_id( $list ) ) ) { continue; } ?>
				<h3><strong><?php _e( 'List: ', '' ); ?></strong><?php $this->list_title( $list ); ?></h3>
				<?php $this->mailing_metrics( $list, $mailing ); ?>
				<?php $has_metrics = true; ?>
			<?php endforeach; ?>
			<?php if ( ! $has_metrics ) : ?>
			<div class="rm-action-message">
				<h3><?php _e( 'This broadcast pre-dates the metrics feature so there are no statistics to display.', '' ); ?></h3>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Gets the mainlist ID from the list.
	 * Currently doesn't work so just returns false.
	 *
	 * @access public
	 * @param  id $list
	 * @return void
	 */
	function get_mailing_id( $list ) {
		return false;
	}

	/**
	 * Outputs the list title from the list ID.
	 *
	 * @access public
	 * @param  int $list
	 * @return void
	 */
	function list_title( $list ) {
		if ( empty( $this->_lists ) ) {
			$lists = Rainmaker_Opt_In_Gateway_FeedBlitz::get_lists();
			foreach ( $lists as $list_set ) {
				$this->_lists += $list_set;
			}
		}

		if ( isset( $this->_lists[$list] ) ) {
			echo esc_html( $this->_lists[$list] );
		}

	}

	/**
	 * Outputs the metrics for a specific list and mailing.
	 *
	 * @access public
	 * @param  int $list
	 * @param  int $mailing
	 * @return void
	 */
	function mailing_metrics( $list, $mailing ) {
		$metrics = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_metrics( $list, $mailing );
		if ( empty( $metrics ) ) {
			if ( empty( $this->_has_refresh_notice ) ) {
				$this->_has_refresh_notice = true;
				Rainmaker_Markup::formated_message( sprintf( __( '%sRefresh%s this page to see the latest metrics.', '' ), sprintf( '<a href="%s">',  get_edit_post_link( $_GET['post'], '' ) ), '</a>' ), 'notice notice-warning' );
			}
			return;
		}

		if ( isset( $_REQUEST['message'] ) && 6 == $_REQUEST['message'] && empty( $this->_has_refresh_notice ) ) { //Refresh this page to see the latest metrics.
			$this->_has_refresh_notice = true;
			Rainmaker_Markup::formated_message( sprintf( __( '%sRefresh%s this page to see the latest metrics.', '' ), sprintf( '<a href="%s">',  get_edit_post_link( $_GET['post'], '' ) ), '</a>' ), 'notice notice-warning' );
		}

?>
		<div class="statbox_group">

			<span class="statbox_item sent">
				<span class="statbox_item_label"><?php _e( 'Sent', '' ); ?></span>
				<span class="statbox_item_value"><?php echo $metrics['sent']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['sent']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item opens">
				<span class="statbox_item_label"><?php _e( 'Opens', '' ); ?></span>
				<span class="statbox_item_value"><?php echo $metrics['opens']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['opens']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item uniqueopens">
				<span class="statbox_item_label"><?php _e( 'Unique Opens', '' ); ?></span>
				<span class="statbox_item_value"><?php echo $metrics['uniqueopens']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['uniqueopens']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item clicks">
				<span class="statbox_item_label"><?php _e( 'Clicks', '' ); ?></span>
				<span class="statbox_item_value"><?php echo $metrics['clicks']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['clicks']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item uniqueclicks">
				<span class="statbox_item_label"><?php _e( 'Unique Clicks', '' ); ?></span>
				<span class="statbox_item_value"><?php echo $metrics['uniqueclicks']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['uniqueclicks']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

		</div>

		<div class="statbox_group">

			<span class="statbox_item unsubscribes">
				<span class="statbox_item_label"><?php _e( 'Unsubscribes', '' ); ?></span>
				<span class="statbox_item_value stat_red"><?php echo $metrics['unsubscribes']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['unsubscribes']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item softbounces">
				<span class="statbox_item_label"><?php _e( 'Soft Bounces', '' ); ?></span>
				<span class="statbox_item_value stat_red"><?php echo $metrics['softbounces']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['softbounces']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item hardbounces">
				<span class="statbox_item_label"><?php _e( 'Hard Bounces', '' ); ?></span>
				<span class="statbox_item_value stat_red"><?php echo $metrics['hardbounces']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['hardbounces']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item complaints">
				<span class="statbox_item_label"><?php _e( 'Complaints', '' ); ?></span>
				<span class="statbox_item_value stat_red"><?php echo $metrics['complaints']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['complaints']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

			<span class="statbox_item forwards">
				<span class="statbox_item_label"><?php _e( 'Forwards', '' ); ?></span>
				<span class="statbox_item_value"><?php echo $metrics['forwards']; ?></span>
				<span class="statbox_item_diff"><?php printf( '%s%%', ( empty( $metrics['sent'] ) ? 0 : ( $metrics['forwards']/$metrics['sent'] ) * 100 ) ); ?></span>
			</span>

		</div>
		<?php

	}

	/**
	 * Callback on the `mce_buttons` filter.
	 * Removes the wp_more and fullscreen buttons.
	 *
	 * @access public
	 * @param  array $buttons
	 * @return array
	 */
	function mce_buttons( $buttons ) {

		$remove = array( 'wp_more' );

		return array_diff( $buttons, $remove );

	}

	/**
	 * Callback on the `quicktags_settings` filter.
	 * Removes the more button.
	 *
	 * @access public
	 * @param  array $settings
	 * @return array
	 */
	function quicktags_settings( $settings ) {

		$remove = array( 'more' );

		$settings['buttons'] = implode( ',', array_diff( explode( ',',$settings['buttons'] ), $remove ) );

		return $settings;

	}

}
