<?php

/**
 * Handles the manage lists and edit lists pages
 */
class Rainmaker_Mail_Manage_Lists {

	/**
	 * Stores the singleton instance of the `Rainmaker_Mail_Settings` object.
	 *
	 * @var object
	 * @access private
	 * @static
	 */
	private static $_instance;

	/**
	 * Returns the `Rainmaker_Mail_Manage_Lists` instance of this class.
	 * This is not a singleton but a helper to get the instance used for the page.
	 *
	 * @return object The `Rainmaker_Mail_Manage_Lists` instance.
	 */
	protected static function get_instance() {

		if ( null === static::$_instance ) {
			static::$_instance = new static();
		}

		return static::$_instance;

	}

	/**
	 * Callback on the admin_enqueue_scripts action.
	 * Enqueues scripts and styles for the manage lists page.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	static function enqueue_scripts() {

		wp_enqueue_style(  'rainmaker-mail-manage-lists-css', RM_MAIL_ASSETS       . 'css/rainmaker-email.admin.manage-lists.css', array( 'magnific-popup'                  ), RAINMAKER_BUILD_VERSION, 'all' );
		wp_enqueue_script( 'rainmaker-mail-manage-lists-js' , RM_MAIL_ASSETS       . 'js/rainmaker-email.admin.manage-lists.js'  , array( 'jquery', 'magnific-popup'        ), RAINMAKER_BUILD_VERSION, false );
		wp_enqueue_style(  'rainmaker-subscriber-meta-css'  , RM_SUBSCRIBER_ASSETS . 'css/rainmaker-email.subscriber.editor.css' , array( 'magnific-popup-css'              ), RAINMAKER_BUILD_VERSION, 'all' );
		wp_enqueue_script( 'rainmaker-subscriber-meta-js'   , RM_SUBSCRIBER_ASSETS . 'js/rainmaker-email.subscriber.editor.js'   , array( 'jquery', 'jquery-magnific-popup' ), RAINMAKER_BUILD_VERSION, true  );

		wp_enqueue_media();

	}

	/**
	 *
	 * Callback for add_menu_page()
	 *
	 * Outputs manage lists page
	 *
	 * @return Void
	 */
	public static function manage_lists_page() {
?>
		<div class="wrap">
			<h1><?php _e( "Email Lists", '' ); ?></h1>


			<div id="mail-lists-select">
				<div class="content-boxes-wrap" id="rainmail-lists-container">

					<div class="add-new-list content-box" id="rainmail-new-list">
						<span class="dashicons dashicons-email"></span>
						<div class="details">
							<h3><?php _e( 'Add New List', '' ); ?></h3>
						</div>
						<div class="buttons">
							<button class="button-primary" id="email_list_add"><?php _e( 'Start Here', '' ); ?></button>
						</div>
					</div>
					<div class="content-box" id="rainmail-lists-loading">
						<div class="spinner-page" style="display: block;"><?php _e( 'Loading lists...', '' ); ?></div>
					</div>

				</div>
			</div>
		</div>

		<div id="rainmail-new-list-modal" class="white-popup mfp-hide mfp-close-btn-in">
			<button title="Close (Esc)" type="button" class="mfp-close">×</button>
			<h2 class="mfp-title" id="edit_list_title"><?php _e( 'Add New list' ); ?></h2>
			<table class="table form-table" id="list_form">
				<input id="new_id" value="" type="hidden">
				<tr>
					<th><?php _e( 'List Name', '' )?></th>
					<td><input type="text" class="large-text" id="new-name" placeholder="List name"><p class=description><?php _e( 'Give your list a unique, specific name.', '' ); ?></p></td>
				</tr>
				<tr>
					<th><?php _e( 'Description' ); ?></th>
					<td><textarea rows="3" class="large-text" id="new-desc" placeholder="List description"></textarea></td>
				</tr>
				<tr>
					<th><?php _e( 'Blog Broadcast', '' ); ?></th>
					<td>
						<p>
							<input type="checkbox" id="rainmail-rss-checkbox" value="1">
							<label><?php _e( 'Turn on RSS feed for this list', '' ); ?></label>
						</p>
						<div class="rainmail-rss-feed-url">
							<input type="text" class="large-text" id="new-rss-url" placeholder="Enter your RSS feed URL">
							<p class="description"><?php _e( 'Leave empty to use your default RSS feed.', '' ); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<th colspan="2"><h3><?php _e( 'After subscribe:', '' ); ?></h3></th>
				</tr>
				<tr>
					<th><?php _e( 'Redirect to', '' ); ?></th>
					<td><?php rm_dropdown_pages( array( 'id' => 'sub-landingpage', 'show_option_none' => __( 'Select a page...', '' ) ) ); ?> </td>
				</tr>
				<tr>
					<th><?php _e( 'Autoresponder', '' ); ?></th>
					<td>
						<select id="new-responder" class="large-text">
							<option value=""><?php _e( 'No Autoresponder', '' ); ?></option>
			<?php
		$gateway    = new Rainmaker_Opt_In_Gateway_FeedBlitz;
		$responders = $gateway->get_autoresponders();
		foreach ( $responders as $id => $name ) : ?>
							<option value="<?php echo $id; ?>"><?php echo $name; ?></option>
			<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th colspan="2"><h3><?php _e( 'After unsubscribe:', '' ); ?></h3></th>
				</tr>
				<tr>
					<th><?php _e( 'Redirect to' ); ?></th>
					<td><?php rm_dropdown_pages( array( 'id' => 'un-redirect', 'show_option_none' => __( 'Select a page...', '' ) ) ); ?> </td>
				</tr>
				<?php static::get_instance()->after_unsubscribe_automation(); ?>

			</table>

			<button type="button" class="button-primary" id="manageaddsubmit"  ><?php _e( 'Add List', '' ); ?></button>
			<button type="button" class="button"        id="managecancelsubmit"><?php _e( 'Cancel'  , '' ); ?></button>

		</div>
<?php
	}

	function after_unsubscribe_automation() {

		$trigger = $this->get_trigger();
		$lists   = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_lists();

?>
		<tr>
			<th>
				<select name="after_unsubscribe_trigger[action]" id="after_unsubscribe_trigger_action">
					<option value="SubscribeTo"     <?php echo 'SubscribeTo'     == $trigger['action'] ? 'selected' : ''; ?>><?php _e( 'Subscribe To'    , '' ); ?></option>
					<option value="UnsubscribeFrom" <?php echo 'UnsubscribeFrom' == $trigger['action'] ? 'selected' : ''; ?>><?php _e( 'Unsubscribe From', '' ); ?></option>
				</select>
				<input type="hidden" name="after_unsubscribe_trigger[trigger_id]" id="after_unsubscribe_trigger_id" value="<?php echo $trigger['trigger_id']; ?>" />
			</th>
			<td>
				<?php $current_list_id = isset( $_REQUEST['list_id'] ) ? $_REQUEST['list_id'] : ''; ?>
				<select name="after_unsubscribe_trigger[list_id]" id="after_unsubscribe_trigger_list_id">
					<option value=""><?php _e( 'Select List', '' ); ?></option>
					<?php foreach ( $lists as $opt_group => $group_lists ) : ?>
					<optgroup label="<?php echo $opt_group; ?>">
						<?php foreach ( $group_lists as $list_id => $list_name ) : ?>
						<?php if ( $list_id == $current_list_id ) { continue; } ?>
						<option value="<?php echo $list_id; ?>" <?php echo $list_id  == $trigger['list_id'] ? 'selected' : ''; ?>><?php echo $list_name; ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	function get_trigger() {
		$list_id = isset( $_REQUEST['list_id'] ) ? $_REQUEST['list_id'] : '';

		$trigger = array(
			'trigger_id' => '',
			'action'     => '',
			'list_id'    => '',
		);

		if ( $list_id ) {

			$triggers = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_triggers( $list_id );

			if ( $triggers && isset( $triggers['trigger'] ) && isset( $triggers['trigger']['id'] ) ) {
				$trigger = array(
					'trigger_id' => $triggers['trigger']['id'],
					'action'     => $triggers['trigger']['action'],
					'list_id'    => $triggers['trigger']['listid'],
				);
			} elseif ( $triggers && isset( $triggers['trigger'] ) ) {
				foreach ( $triggers['trigger'] as $trigger ) {

					if ( 'Unsubscribe' !== $trigger['event'] ) {
						continue;
					}

					$trigger = array(
						'trigger_id' => $trigger['id'],
						'action'     => $trigger['action'],
						'list_id'    => $trigger['listid'],
					);
				}
			}

		}

		return $trigger;

	}

	/**
	 *
	 * Callback for add_menu_page()
	 *
	 * Outputs edit list page
	 *
	 * @return Void
	 */
	public static function edit_list_page() {
		$menu_items = array(
			'overview'          => __( 'Overview'   , '' ),
			'subscribers'       => __( 'Subscribers', '' ),
			'mailings'          => __( 'Mailings'   , '' ),
			'settings'          => __( 'Settings'   , '' ),
		);

		$list = Rainmaker_Opt_In_Gateway_Loader::rm_manage_lists_get_lists( $_REQUEST['list_id'], true );

?>
	<div class="wrap">
		<h1>
			<?php _e( "Edit List", '' ); ?>

			<a href="<?php echo admin_url( 'admin.php?page=rainmail-manage-lists' ); ?>" class="view-all-h2" data-href=""><?php _e( 'View All', '' ); ?></a>
		</h1>
		<div class="page-subtitle"><?php echo $list['name']; ?></div>
		<nav id="email-list-navigation" class="rm-tabbed-menu">
			<ul class="email-list-tab-navigation page-tab-navigation page-main-menu menu">
				<?php

		foreach ( $menu_items as $id => $name ) {

			if ( ( empty( $_REQUEST['tab'] ) && $id == 'overview' ) || ( ! empty( $_REQUEST['tab'] ) && $_REQUEST['tab'] == $id ) ) {
				$current = ' current-menu-tab';
			} else {
				$current = '';
			}

			printf(
				'<li class="menu-tab%s"><a href="%s">%s</a></li>%s',
				$current,
				add_query_arg( 'tab', $id, $_SERVER['REQUEST_URI'] ),
				$name,
				"\r\n"
			);

		}

?>
			</ul>
		</nav>
<?php
		if ( empty( $_REQUEST['tab'] ) || $_REQUEST['tab'] == 'overview' )  {
			self::overview_tab();
		} elseif ( $_REQUEST['tab'] == 'subscribers' ) {
			self::subscribers_tab();
		} elseif ( $_REQUEST['tab'] == 'mailings' ) {
			self::mailings_tab();
		} elseif ( $_REQUEST['tab'] == 'settings' ) {
			self::settings_tab();
		}
?>

	</div>
	<div id="subscriber_editor" class="white-popup mfp-hide">

			<h2 class="mfp-title">
				<span class="edit-subscriber"><?php _e( 'Edit Subscriber', '' ); ?></span>
				<span class="add-subscriber" ><?php _e( 'Add Subscriber' , '' ); ?></span>
			</h2>

			<span class="loader spinner-page"></span>
			<div class="subscriber">


			</div>
			<a href="#" class="button button-primary button-save-subscriber edit-subscriber">
				<span class="edit-subscriber"><?php _e( 'Update Subscriber' , '' ); ?></span>
			</a>

			<a href="#" class="button button-primary button-add-subscriber add-subscriber">
				<span class="add-subscriber" ><?php _e( 'Add New Subscriber', '' ); ?></span>
			</a>

			<button type="button" class="button button-cancel"><?php _e( 'Cancel', '' ); ?></button>

		</div>
<?php
	}

	/**
	 *
	 * Callback for edit_list_page
	 *
	 * Outputs overview tab
	 *
	 * @return Void
	 */
	private function overview_tab() {
		$args  = array(
			'post_type'    => 'subscribers',
			'meta_key'     => '_rm_subscriber_lists',
			'meta_value'   => $_REQUEST['list_id'],
			'meta_compare' => 'LIKE',
			'limit'        => 10,
			'order'        => 'DESC',
			'orderby'      => 'date',
		);
		$query = new WP_Query( $args );

		$subscribers = array();

		if ( $query->have_posts() ) {

			while ( $query->have_posts() ) {
				$query->the_post();

				$meta  = get_post_meta( $query->post->ID );
				$lists = get_post_meta( $query->post->ID, '_rm_subscriber_lists', true );
				//print_r($lists);
				$name  = '';

				$name .=  ! empty( $meta['firstname'] ) ?      $meta['firstname'][0] : '';
				$name .=  ! empty( $meta['lastname']  ) ? ' '. $meta['lastname'][0]  : '';

				$temp = array(
					'id'     => $query->post->ID,
					'email'  => get_the_title(),
					'name'  => ! empty( $name ) ? $name : '-',
					'date'   => date_i18n( get_option( 'date_format' ), strtotime( $lists[$_REQUEST['list_id']]['date'] ) ),
					'status' => ! empty( $lists[$_REQUEST['list_id']]['status'] ) ? $lists[$_REQUEST['list_id']]['status'] : '-'
				);

				$subscribers[] = $temp;

			}
			wp_reset_postdata();

		}

		$args  = array(
			'post_type'    => 'mail',
			'meta_key'     => '_email_recipient_list',
			'meta_value'   => $_REQUEST['list_id'],
			'meta_compare' => 'LIKE',
			'limit'        => 10
		);
		$query = new WP_Query( $args );

		$mailings = array();

		if ( $query->have_posts() ) {

			while ( $query->have_posts() ) {
				$query->the_post();


				$meta  = get_post_meta( $query->post->ID );
				$lists = get_post_meta( $query->post->ID, '_broadcast_message_ids', true );

				$list_metrics = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_metrics( $_REQUEST['list_id'], $lists[$_REQUEST['list_id']] );

				if ( 'future' == $query->post->post_status ) { // scheduled for publishing at a future date
					$stamp = __( '<span class="warning">Scheduled for:</span> %1$s', '' );
				} elseif ( 'publish' == $query->post->post_status || 'private' == $query->post->post_status ) { // already published
					$stamp = __( '<span class="success">Broadcast on:</span> %1$s', '' );
				} elseif ( 'draft' == $query->post->post_status ) { // already published
					$stamp = __( '<span class="warning">Draft:</span> %1$s', '' );
				} else { // draft, 1 or more saves, date specified
					$stamp = __( '<span class="success">Broadcast on:</span> %1$s', '' );
				}

				$datef = __( 'M j, Y @ H:i' );
				$date  = date_i18n( $datef, strtotime( $query->post->post_date ) );

				$mailings[strtotime($query->post->post_date)] = array(
					'id'      => $query->post->ID,
					'subject' => get_the_title(),
					'sent'    => $list_metrics['sent'],
					'opens'   => empty( $list_metrics['sent'] ) ? 0 : number_format( ( $list_metrics['opens' ] / $list_metrics['sent'] ) * 100, 0 ),
					'clicks'  => empty( $list_metrics['sent'] ) ? 0 : number_format( ( $list_metrics['clicks'] / $list_metrics['sent'] ) * 100, 0 ),
					'status'  => sprintf( $stamp, $date )
				);

			}
			krsort( $mailings );
			wp_reset_postdata();

			//$list_metrics = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_metrics( $list, $mailing );

		}
?>
		<h3 class="form-heading"><?php _e( 'Recent Subscribers' ); ?> <small><a href="<?php echo admin_url('/admin.php?page=rainmail-edit-list&list_id='.$_REQUEST['list_id'].'&tab=subscribers'); ?>">(<?php _e( 'see all subscribers', '' ); ?>)</a></small></h3>
	<?php if ( empty( $subscribers ) ) : ?>
		<div class="rm-action-message">
			<span class="dashicons dashicons-groups"></span>
            <h3><?php _e( 'This list doesn\'t have any subscribers yet.', '' ); ?></h3>
			<a href="/admin/admin.php?page=rm_kb&space_id=5088&manual_id=57986&lesson_id=544767" class="button-primary"><?php _e( 'Build Your List', '' ); ?></a>
        </div>
    <?php else: ?>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th class="manage-column column-avatar"      >                                       </th>
					<th class="manage-column column-title"       ><?php _e( 'Email Address'    , '' ); ?></th>
					<th class="manage-column column-name"        ><?php _e( 'Name'             , '' ); ?></th>
					<th class="manage-column column-subscription"><?php _e( 'Subscription Date', '' ); ?></th>
					<th class="manage-column column-status"      ><?php _e( 'Status'           , '' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $subscribers as $subscriber ) : ?>
				<tr>
					<td class="manage-column column-avatar"><?php echo get_avatar( $subscriber['email'], 30 ); ?></td>
					<td class="manage-column column-title">
						<a class="row-title" title="Edit “<?php _e( $subscriber['email'] ); ?>”" data-href="<?php echo admin_url('/post.php?post='.$subscriber['id'].'&amp;action=edit'); ?>"><?php _e( $subscriber['email'] ); ?></a>
					</td>
					<td class="manage-column column-name"><?php _e( $subscriber['name'] ); ?></td>
					<td class="manage-column column-subdate"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $subscriber['date'] ) ); ?></td>
					<td class="manage-column column-status"><span class="success"><?php _e( $subscriber['status'] ); ?></span></td>
				</tr>
				<?php endforeach; ?>

			</tbody>
		</table>
		<?php endif; ?>

		<h3 class="form-heading"><?php _e( 'Recent Mailings' ); ?> <small><a href="<?php echo admin_url('/admin.php?page=rainmail-edit-list&list_id='.$_REQUEST['list_id'].'&tab=mailings'); ?>">(<?php _e( 'see all mailings' ); ?>)</a></small></h3>

	<?php if ( empty( $mailings ) ) : ?>
        <div class="rm-action-message">
			<span class="dashicons dashicons-email"></span>
            <h3><?php _e( 'This list doesn\'t have any mailings yet.' ); ?></h3>
			<a href="/admin/post-new.php?post_type=mail" class="button-primary"><?php _e( 'Send Your First Broadcast', '' ); ?></a>
        </div>
    <?php else : ?>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th class="manage-column column-title"     ><?php _e( 'Subject'   , '' ); ?></th>
					<th class="manage-column column-recipients"><?php _e( 'Recipients', '' ); ?></th>
					<th class="manage-column column-openrate"  ><?php _e( 'Open Rate' , '' ); ?></th>
					<th class="manage-column column-clickrate" ><?php _e( 'Click Rate', '' ); ?></th>
					<th class="manage-column column-status"    ><?php _e( 'Status'    , '' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $mailings as $mailing ) : ?>
				<tr>
					<td class="manage-column column-title"><strong><a href="<?php echo admin_url('/post.php?post='.$mailing['id'].'&action=edit'); ?>"><?php echo $mailing['subject']; ?></a></strong></td>
					<td class="manage-column column-recipients"><?php echo number_format( $mailing['sent'], 0 ); ?></td>
					<td class="manage-column column-openrate"><?php echo $mailing['opens']; ?>%</td>
					<td class="manage-column column-clickrate"><?php echo $mailing['clicks']; ?>%</td>
					<td class="manage-column column-status"><?php echo $mailing['status']; ?></td>
				</tr>
				<?php endforeach; ?>
	<?php endif; ?>
			</tbody>
		</table>
<?php
	}

	/**
	 *
	 * Callback for edit_list_page
	 *
	 * Outputs settings tab
	 *
	 * @return Void
	 */
	static function settings_tab() {
		$list = Rainmaker_Opt_In_Gateway_Loader::rm_manage_lists_get_lists( $_REQUEST['list_id'], true );
		//echo '<pre>'.print_r($list, true).'</pre>';
?>
		<table class="table form-table" id="list_form">
			<input id="new_id" value="<?php echo $_REQUEST['list_id']; ?>" type="hidden">
			<tr>
				<th><?php _e( 'List Name', '' ); ?></th>
				<td><input type="text" class="large-text" id="new-name" placeholder="List name" value="<?php echo ! empty( $list['name'] ) ? $list['name'] : '' ;?> "><p class=description><?php _e( 'Give your list a unique, specific name.', '' ); ?></p></td>

			</tr>
			<tr>
				<th><?php _e( 'Description', '' ); ?></th>
				<td><textarea rows="3" class="large-text" id="new-desc" placeholder="List description"><?php echo ! empty( $list['description'] ) ? $list['description'] : '' ;?></textarea></td>
			</tr>
			<tr>
				<th><?php _e( 'Blog Broadcast', '' ); ?></th>
				<td>
					<p>
						<input type="checkbox" id="rainmail-rss-checkbox" <?php checked( $list['turbo'], '-10' ); ?> value="1">
						<label><?php _e( 'Turn on RSS feed for this list', '' ); ?></label>
					</p>
					<div class="rainmail-rss-feed-url" <?php if ( empty( $list['turbo'] ) || $list['turbo'] != '-10' ) echo 'style="display: none;" '; ?>>
						<input type="text" class="large-text" id="new-rss-url" placeholder="<?php _e( 'Enter your RSS feed URL', '' ); ?>" value="<?php echo ! empty( $list['link'] ) ? $list['link'] : '' ;?> ">
						<p class="description"><?php _e( 'Leave empty to use your default RSS feed.', '' ); ?></p>
					</div>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Personalization & Email Branding', '' ); ?></th>
				<td>
					<p><strong><?php _e( 'Opt-in confirmation email', '' ); ?></strong></p>
					<p>
						<a href="#" class="rainmail-edit-optin button button-primary" data-id="<?php echo $_REQUEST['list_id']; ?>"><?php _e( 'Edit Email', '' ); ?></a>
					</p>
				</td>
			</tr>
			<tr>
				<th colspan="2"><h3><?php _e( 'After subscribe:', '' ); ?></h3></th>
			</tr>
			<tr>
				<th><?php _e( 'Redirect to', '' ); ?></th>
				<td><?php rm_dropdown_pages( array( 'id' => 'sub-landingpage','show_option_none' => __( 'Select a page...', '' ), 'selected' => $list['landingpage'] ) ); ?> </td>
			</tr>
			<tr>
				<th><?php _e( 'Autoresponder', '' ); ?></th>
				<td>
					<select id="new-responder" class="large-text">
						<option value=""><?php _e( 'No Autoresponder', '' ); ?></option>
		<?php
		$responders = Rainmaker_Opt_In_Gateway_Loader::get_autoresponders();
		foreach ( $responders as $id=>$name ) :
?>
						<option <?php echo $id == $list['autoresponderid'] ? 'selected' : ''; ?> value="<?php echo $id; ?>"><?php echo $name; ?></option>
		<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th colspan="2"><h3><?php _e( 'After unsubscribe:', '' ); ?></h3></th>
			</tr>
			<tr>
				<th><?php _e( 'Redirect to', '' ); ?></th>
				<td><?php rm_dropdown_pages( array( 'id' => 'un-redirect', 'show_option_none' => __( 'Select a page...', '' ), 'selected' => $list['unsubscriberedirecturl'] ) ); ?> </td>
			</tr>
			<?php static::get_instance()->after_unsubscribe_automation(); ?>


		</table>
		<button type="button" id="savelistsettings" class="button button-primary"><?php _e( 'Save List', '' ); ?></button>

		<div id="rainmail-optin-editor" class="editor-popup hidden">
			<div class="wrap">
				<a class="close-box dashicons dashicons-no" href="#"><?php _e( 'Close', '' ); ?></a>
				<h2><?php _e( 'Edit Opt-in Confirmation Email', '' ); ?></h2>
				<div id="rainmail-optin-editor-loading">
					<div class="spinner-page"><?php _e( 'Loading...', '' ); ?></div>
				</div>
				<div id="rainmail-optin-editor-loaded">
					<div id="rainmail-optin-default-option">
						<p>
							<input type="checkbox" id="rainmail-optin-default" name="rainmail-optin-default" checked value="1">
							<label for="rainmail-optin-default"><?php _e( 'Use the default opt-in confirmation email?', '' ); ?></label>
						</p>
					</div>
					<div id="rainmail-optin-editor-inner">
						<p><label><strong><?php _e( 'Email subject (required):', '' ); ?></strong></label></p>
						<p><input type="text" class="large-text" id="rainmail-optin-subject" placeholder="<?php _e( 'Email subject', '' ); ?>" value=""></p>
						<p><label><strong><?php _e( 'Content:', '' ); ?></strong></label></p>
						<p class="description"><?php _e( 'Required Shortcode: [confirm_link text="Click here to confirm your subscription."] - this shortcode must be included in your confirmation email. It will populate the link users click to activate their subscription. You can replace "Click here to confirm your subscription." with your desired link text.', '' ); ?></p>
						<p class="description"><?php _e( 'Optional Shortcode: [recipient_email] - this shortcode will display the user\'s email address.', '' ); ?></p>

						<?php
		$options = array(
			'tabfocus_elements' => 'insert-media-button',
			'tinymce' => array(
				'resize'                 => true,
				'add_unload_trigger'     => false,
				'theme_advanced_disable' => 'fullscreen'
			),
		);
		wp_editor( '', 'rainmail-optin-content', $options );
?>
					</div>
					<input type="hidden" value="" id="rainmail-optin-id">
					<a href="#" class="button button-primary"   id="rainmail-optin-save"  ><?php _e('Save'  , ''); ?></a>
					<a href="#" class="button button-secondary" id="rainmail-optin-cancel"><?php _e('Cancel', ''); ?></a>
				</div>
			</div>
		</div>
		<div class="editor-overlay hidden"></div>

<?php
	}

	/**
	 *
	 * Callback for edit_list_page
	 *
	 * Outputs subscribers tab
	 *
	 * @return Void
	 */
	private function subscribers_tab() {

		$items   = 0;
		$paged   = ! empty( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;
		$perpage = 10;

		$args  = array(
			'post_type'      => 'subscribers',
			'meta_key'       => '_rm_subscriber_lists',
			'meta_value'     => $_REQUEST['list_id'],
			'meta_compare'   => 'LIKE',
			'posts_per_page' => $perpage,
			'paged'          => $paged,
			'order'          => 'DESC',
			'orderby'        => 'date',
		);

		$query       = new WP_Query( $args );
		$subscribers = array();

		if ( $query->have_posts() ) {

			$items = $query->found_posts;

			while ( $query->have_posts() ) {
				$query->the_post();

				$meta  = get_post_meta( $query->post->ID );
				$lists = get_post_meta( $query->post->ID, '_rm_subscriber_lists', true );

				$name  = '';

				$name .=  ! empty( $meta['firstname'] ) ?      $meta['firstname'][0] : '';
				$name .=  ! empty( $meta['lastname'] )  ? ' '. $meta['lastname' ][0] : '';

				$temp = array(
					'id'     => $query->post->ID,
					'email'  => get_the_title(),
					'name'  => ! empty( $name ) ? $name : '-',
					'date'   => date_i18n( get_option( 'date_format' ), strtotime( $lists[$_REQUEST['list_id']]['date'] ) ),
					'status' => ! empty( $lists[$_REQUEST['list_id']]['status'] ) ? $lists[$_REQUEST['list_id']]['status'] : '-'
				);

				$subscribers[] = $temp;

			}
			wp_reset_postdata();

		}

?>
	<?php if ( empty( $subscribers ) ) : ?>
		<div class="rm-action-message">
			<span class="dashicons dashicons-groups"></span>
            <h3><?php _e( 'This list doesn\'t have any subscribers yet.' ); ?></h3>
			<a href="/admin/admin.php?page=rm_kb&space_id=5088&manual_id=57986&lesson_id=544767" class="button-primary"><?php _e( 'Build Your List', '' ); ?></a>
        </div>
    <?php else : ?>
		<?php Rainmaker_Markup::rm_pagination( $items, $paged, $perpage ); ?>
		<table id="subscribers_list" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th class="manage-column column-avatar"      >                                       </th>
					<th class="manage-column column-title"       ><?php _e( 'Email Address'    , '' ); ?></th>
					<th class="manage-column column-name"        ><?php _e( 'Name'             , '' ); ?></th>
					<th class="manage-column column-subscription"><?php _e( 'Subscription Date', '' ); ?></th>
					<th class="manage-column column-status"      ><?php _e( 'Status'           , '' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $subscribers as $subscriber ) : ?>
				<tr>
					<td class="manage-column column-avatar"><?php echo get_avatar( $subscriber['email'], 30 ); ?></td>
					<td class="manage-column column-title">
						<a class="row-title" title="<?php _e( 'Edit', '' ); ?> “<?php echo $subscriber['email']; ?>”" data-href="<?php echo admin_url( '/post.php?post=' . $subscriber['id'].'&amp;action=edit' ); ?>"><?php echo $subscriber['email']; ?></a>
					</td>
					<td class="manage-column column-name"><?php echo $subscriber['name']; ?></td>
					<td class="manage-column column-subscription"><?php echo $subscriber['date']; ?></td>
					<td class="manage-column column-status"><span class="success"><?php echo $subscriber['status']; ?></span></td>
				</tr>
				<?php endforeach;
?>

			</tbody>
		</table>
		<?php Rainmaker_Markup::rm_pagination( $items, $paged, $perpage, true ); ?>
	<?php endif; ?>
<?php
	}

	/**
	 *
	 * Callback for edit_list_page
	 *
	 * Outputs mailings tab
	 *
	 * @return Void
	 */
	private function mailings_tab() {

		$items   = 0;
		$paged   = ! empty( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;
		$perpage = 10;

		$args    = array(
			'post_type'      => 'mail',
			'meta_key'       => '_email_recipient_list',
			'meta_value'     => $_REQUEST['list_id'],
			'meta_compare'   => 'LIKE',
			'posts_per_page' => $perpage,
			'paged'          => $paged
		);

		$query    = new WP_Query( $args );
		$mailings = array();

		if ( $query->have_posts() ) {

			$items = $query->found_posts;

			while ( $query->have_posts() ) {

				$query->the_post();

				$meta  = get_post_meta( $query->post->ID );
				$lists = get_post_meta( $query->post->ID, '_broadcast_message_ids', true );

				$list_metrics = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_metrics( $_REQUEST['list_id'], $lists[$_REQUEST['list_id']] );

				if ( 'future' == $query->post->post_status ) { // scheduled for publishing at a future date
					$stamp = __( '<span class="warning">Scheduled for:</span> %1$s', '' );
				} elseif ( 'publish' == $query->post->post_status || 'private' == $query->post->post_status ) { // already published
					$stamp = __( '<span class="success">Broadcast on:</span> %1$s', '' );
				} elseif ( 'draft' == $query->post->post_status ) { // already published
					$stamp = __( '<span class="warning">Draft:</span> %1$s', '' );
				} else { // draft, 1 or more saves, date specified
					$stamp = __( '<span class="success">Broadcast on:</span> %1$s', '' );
				}

				$datef = __( 'M j, Y @ H:i' );
				$date  = date_i18n( $datef, strtotime( $query->post->post_date ) );

				$mailings[strtotime( $query->post->post_date )] = array(
					'id'      => $query->post->ID,
					'subject' => get_the_title(),
					'sent'    => $list_metrics['sent'],
					'opens'   => empty( $list_metrics['sent'] ) ? 0 : number_format( ( $list_metrics['opens' ] / $list_metrics['sent'] ) * 100, 0 ),
					'clicks'  => empty( $list_metrics['sent'] ) ? 0 : number_format( ( $list_metrics['clicks'] / $list_metrics['sent'] ) * 100, 0 ),
					'status'  => sprintf( $stamp, $date )
				);

			}
			krsort( $mailings );
			wp_reset_postdata();
		}
?>
	<?php if ( empty( $mailings ) ) : ?>
		<div class="rm-action-message">
			<span class="dashicons dashicons-email"></span>
            <h3><?php _e( 'This list doesn\'t have any mailings yet.', '' ); ?></h3>
			<a href="/admin/post-new.php?post_type=mail" class="button-primary"><?php _e( 'Send Your First Broadcast', '' ); ?></a>
        </div>
    <?php else : ?>
		<?php Rainmaker_Markup::rm_pagination( $items, $paged, $perpage ); ?>

		<table id="mailings_list" class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th class="manage-column column-title"     ><?php _e( 'Subject'   , '' ); ?></th>
					<th class="manage-column column-recipients"><?php _e( 'Recipients', '' ); ?></th>
					<th class="manage-column column-openrate"  ><?php _e( 'Open Rate' , '' ); ?></th>
					<th class="manage-column column-clickrate" ><?php _e( 'Click Rate', '' ); ?></th>
					<th class="manage-column column-status"    ><?php _e( 'Status'    , '' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $mailings as $mailing ) : ?>
				<tr>
					<td class="manage-column column-title"><strong><a href="<?php echo admin_url( '//post.php?post='.$mailing['id'].'&action=edit'); ?>"><?php echo $mailing['subject']; ?></a></strong></td>
					<td class="manage-column column-recipients"><?php echo number_format( $mailing['sent'], 0 ); ?></td>
					<td class="manage-column column-openrate"><?php echo number_format( $mailing['opens'], 1 ); ?>%</td>
					<td class="manage-column column-clickrate"><?php echo number_format( $mailing['clicks'], 1 ); ?>%</td>
					<td class="manage-column column-status"><?php echo $mailing['status']; ?></td>
				</tr>
				<?php endforeach; ?>

			</tbody>
		</table>

		<?php Rainmaker_Markup::rm_pagination( $items, $paged, $perpage, true ); ?>
		<?php endif; ?>
<?php
	}
}
