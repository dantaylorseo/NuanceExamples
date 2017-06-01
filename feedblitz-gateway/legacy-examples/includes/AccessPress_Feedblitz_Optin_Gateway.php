<?php


/**
 * Optin gateway class to configure and process Optin gateways.
 *
 * This class allows payment via optin.
 *
 * @since 0.1.0
 */
class AccessPress_Feedblitz_Optin_Gateway extends Premise_Member_Payment_Gateway {

	/**
	 * The Feedblitz opt-in API.
	 *
	 * @since 0.1.0
	 *
	 * @var object
	 */
	public $api;
	
	/**
	 * The user data submitted with the optin form.
	 *
	 * @since 0.1.0
	 *
	 * @var array of user data
	 */
	private $_user_data;

	/**
	 * The Premise meta for the current landing page.
	 *
	 * @since 0.1.0
	 *
	 * @var array of landing page meta
	 */
	private $_premise_meta;

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 */
	function __construct() {

		$this->create( 'Feedblitz' );

	}
	/**
	 * Initialize the payment gateway.
	 *
	 * @since 0.1.0
	 */
	public function configure() {
	
		add_action( 'load-toplevel_page_premise-member', array( $this, 'metaboxes' ), 11 );
	
		if( ! $this->gateway_inactive() || is_object( $this->api ) ){
			return false;
		}

		require_once( ACCESSPRESS_FEEDBLITZ_INCLUDES . 'api/feedblitz-email-api.php');
		
		global $Feedblitz_interface;
		
		$this->api = $Feedblitz_interface;
		
		add_action( 'premise_member_create_order', array( $this, 'subscribe_customer' ), 10, 2 );

		// product post type hooks
		add_action( 'admin_menu'                   , array( $this, 'add_metabox'    ) );
		add_action( 'premise_optin_complete_order' , array( $this, 'complete_order' ) );

		// never show this gateway on the checkout form
		return false;
		

	}
	
	/**
	 * Tests to see if the gateway settings are configured
	 *
	 * @since 0.1.0
	 **/
	public function gateway_inactive( $test = '' ){
		if( $test ){
			return $test;
		}
		
		global $Feedblitz_Optin_Gateway;
		return $Feedblitz_Optin_Gateway->get_credentials();
	}

	/**
	 * Adds the Feedblitz metabox to the product editor
	 *
	 * @since 0.1.0
	 **/
	function add_metabox( $test = false ) {
	
		$args = array(
			'id'        => 'accesspress-Feedblitz-metabox',
			'title'     => __( 'Feedblitz', '' ),
			'callback'  => array( $this, 'product_metabox' ), 
			'post_type' => 'acp-products',
			'context'   => 'normal',
	        'priority'  => 'low',
        );
        
		if( $test ){
			return $args;
		}
		else{
			add_meta_box( $args['id'], $args['title'], $args['callback'], $args['post_type'], $args['context'], $args['priority'] );
		}
		
	}

	function product_metabox( $test = false, $api = false ) {
	
		if( $test === true && is_object( $api ) ){
			$this->api = $api;
		}

		$lists = false;
		 
		$get_lists = $this->api->get_feedblitz_lists();
		
		//echo '<pre><code>'; var_dump( $lists ); echo '</pre></code>';

		if ( empty( $lists ) ) {
			_e( 'No Feedblitz lists found.', '' );
			return;
		}
		?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="accesspress_product_meta[_acp_product_Feedblitz_tag]"><?php _e( 'Feedblitz List', '' ); ?></label>
					</th>
					<td>
						<select name="accesspress_product_meta[_acp_product_Feedblitz_tag]">
							<option value=""><?php _e( 'None', '' ); ?></option>
							<?php
					
							foreach( $lists as $list )
								printf( 
									'<option value="%s" %s>%s</option>', 
									$list['id'], 
									selected( premise_get_custom_field( '_acp_product_Feedblitz_tag' ), $list['id'], false ), 
									$list['name'] 
								);
					
							?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	/**
	 * Adds the Feedblitz metabox to the AccessPress Settings
	 *
	 * @since 0.1.0
	 **/
	function metaboxes( $test = false ) {
	
		if( function_exists( 'rm_lp_get_option' ) && false === $test ){
			return;
		}
	
		$args = array(
			'id'        => 'accesspress-Feedblitz-settings',
			'title'     => __( 'Feedblitz Settings', '' ),
			'callback'  => array( $this, 'settings_metabox' ), 
			'post_type' => 'toplevel_page_premise-member',
			'context'   => 'main',
        );
        
		if( $test ){
			return $args;
		}
		else{
			add_meta_box( $args['id'], $args['title'], $args['callback'], $args['post_type'], $args['context'] );
		}
		
	}
	
	/**
	* Creates output for the metabox in AccessPress Setting*
	* @since 0.1.0
	*/
	function settings_metabox( $test = false ) {
	
		
		if( $test ){
			define( 'MEMBER_ACCESS_SETTINGS_FIELD', 'accesspress_setting_test' );
		}
		
		global $Feedblitz_Optin_Gateway;
		
		$api_key = $Feedblitz_Optin_Gateway->get_credentials();
		
		?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'API Key', '' ); ?>
					</th>
					<td>
						<input type="text" name="<?php echo MEMBER_ACCESS_SETTINGS_FIELD; ?>[Feedblitz_api]" id="<?php echo MEMBER_ACCESS_SETTINGS_FIELD; ?>[Feedblitz_api]" value="<?php echo $api_key; ?>" style="min-width:50%" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}
	
	/**
	 * Member can cancel flag.
	 *
	 * Feedblitz isn't for subscriptions
	 *
	 * @return bool false
	 * @since 2.1
	 */
	public function member_can_cancel() {

		return false;

	}

	function subscribe_customer( $member, $order_details, $test = false ) {
	
		if( is_object( $test ) ){
			$this->api = $test;
		}
	
		// don't resubscribe if this is a change payment method order
		if ( isset( $order_details['_acp_order_previous_order'] ) && $order_details['_acp_order_previous_order'] )
			return;

		$product_id = isset( $order_details['_acp_order_product_id'] ) ? $order_details['_acp_order_product_id'] : 0;
		$user = get_user_by( 'id', $member );
		if ( ( ! $product_id || ! $user ) && ! $test )
			return;

		$list_id = get_post_meta( $product_id, '_acp_product_Feedblitz_tag', true );
		if ( ! $list_id && ! $test ) {
			return;
		}
		
		$data = array(
			//'FirstName' => $user->first_name,
			//'LastName'  => $user->last_name,
			'email'     => $user->user_email,
		);

		$response = $this->api->add_subscriber( $list_id, $data['email'] );
		
		if( strpos( $response, 'error') ){
			return array( 'error' => $response );
		}
		
		
		return $response;
			
	}

	
	function unsubscribe_customer( $order_id, $product_id, $member ) {
		
		$list_id = get_post_meta( $product_id, '_acp_product_Feedblitz_tag', true );
		
		if ( ! $list_id ) {
			return $order_id;
		}
		
		$user = get_user_by( 'id', $member );
		
		$this->api->feedblitz_optout_subscriber( $list_id, $user->user_email );
		
		return $order_id;
		
	}
	
	/**
	 * required abstract function
	 */
	public function _process_order( $args ){ }

}
