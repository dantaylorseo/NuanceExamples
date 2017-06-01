<?php


/**
 * Optin gateway class to configure and process Optin gateways.
 *
 * @since 0.1.0
 */
class LandingPage_Feedblitz_Optin_Gateway {

	/**
	 * The Feedblitz opt-in API.
	 *
	 * @since 0.1.0
	 *
	 * @var object
	 */
	public $api;
	
	/**
	 * The landing page settings pagehook.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	public $admin_pagehook;
	
	/**
	 * Used for testing.
	 *
	 * @since 0.1.0
	 *
	 * @var boolean
	 */
	public $test;

	/**
	 * Class constructor.
	 *
	 * @since 0.1.0
	 */
	function __construct() {
		
		add_action( 'rm_lp_after_core_opt_in_settings', array( $this, 'settings' ) );
		
		if( $this->configure() ){
			add_filter( 'rm_lp_opt_in_services'  , array( $this, 'add_service'        )        );
			add_filter( 'rm_lp_opt_in_lists'     , array( $this, 'add_list'           ), 10, 2 );
			add_filter( 'rm_lp_opt_in_form'      , array( $this, 'opt_in_form'        ), 10, 4 );
			add_filter( 'rm_lp_opt_in_submission', array( $this, 'process_submission' ), 10, 3 );
		}
		
	}
	
	/**
	 * Initialize the payment gateway.
	 *
	 * @since 0.1.0
	 */
	public function configure() {
	
		if( ! $this->gateway_inactive() || is_object( $this->api ) ){
			return false;
		}

		require_once( ACCESSPRESS_FEEDBLITZ_INCLUDES . 'api/feedblitz-email-api.php');
		
		global $Feedblitz_interface;
		
		$this->api = $Feedblitz_interface;	
		
		return true;	

	}
	
	/**
	 * Tests to see if the gateway settings are configured
	 *
	 * @since 0.1.0
	 **/
	public function gateway_inactive(){
		if( null !== $this->test ){
			return $this->test;
		}
		
		global $Feedblitz_Optin_Gateway;
		return $Feedblitz_Optin_Gateway->get_credentials();
		
	}
	
	/**
	* Creates output for the metabox in AccessPress Setting*
	* @since 0.1.0
	*/
	function settings() {
	
		global $Feedblitz_Optin_Gateway;
		
		$key = $Feedblitz_Optin_Gateway->get_credentials();
	
		printf( '<h3>%s</h3>', __( 'Feedblitz', 'rm_lp_settings' ) );
		?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'API Key', '' ); ?>
					</th>
					<td>
						<input type="text" name="<?php echo RAINMAKER_LP_SETTINGS_FIELD; ?>[Feedblitz_api]" id="<?php echo RAINMAKER_LP_SETTINGS_FIELD; ?>_Feedblitz_api" value="<?php echo $key; ?>" style="min-width:50%" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Sets the Feedblitz value for the opt-in service list on the landing page editor
	 *
	 * @param   array $opt_in_services
	 * @returns array
	 * @since   0.1.0
	 */
	function add_service( $opt_in_services = array() ){
	
		$opt_in_services['Feedblitz'] = __( 'Feedblitz', '' );
		
		return $opt_in_services;
		
	}
	
	/**
	 * Gets the list of Feedblitz groups if available and returns them for the Landing Pages editor
	 *
	 * @param   mixed  $list    null or an array of lists
	 * @param   string $service the service requesting lists
	 * @returns mixed
	 * @since   0.1.0
	 */
	function add_list( $lists, $service ){
	
		//abort early if this is not for the Feedblitz service
		if( $service !== 'Feedblitz' ){
			return $lists;
		}
		
		$lists      = array();  
		$get_lists = $this->api->get_feedblitz_lists();
		
		if( empty( $get_lists ) ){
			return;
		}
		
		foreach( $get_lists as $list ){
			$lists[] = array( 
				'id'   => $list['id'],
				'name' => $list['name'],
				);
		}
		
		return $lists;
		
	}
	
	/**
	 * Build the opt-in form output if Feedblitz is selected service
	 *
	 * @param   string $default                     the default form HTML
	 * @param   array  $details                     the module details
	 * @param   string $submit                      the submit button HTML
	 * @param   object $Landing_Page_Default_Output Landing_Page_Default_Output object
	 * @returns mixed
	 * @since   0.1.0
	 */
	function opt_in_form( $default, $details, $submit, $Landing_Page_Default_Output ){
	
		//exit early if Feedblitz is not the selected service
		if( 'Feedblitz' !== $details['service'] ){
			return $default;
		}
		
		$input_array = array( 
			//'fname' => __( 'First Name'   , '' ),
			//'lname' => __( 'Last Name'    , '' ),
			'email' => __( 'Email Address', '' ),
		);		
		
		$inputs = '';
		$count  = 0;
		
		foreach( $input_array as $id => $name ) {
		
			$type = 'email' === $id ? $id : 'text';
							
			$inputs .= sprintf( 
					'<input type="%1$s" value="" placeholder="%2$s" name="%3$s" class="field-%4$s field-%3$s opt-in-field">',
					$type,
					$name,
					$id,
					++$count
				);
				
		}
			
		$hidden = sprintf( '
			<input type="hidden" name="list-id" value="%s" />
			<input type="hidden" name="service" value="%s" />', 
			$details['list'][$details['service' ]],
			$details['service' ] 
			);
			
		$Landing_Page_Default_Output->build_form( $count, $inputs, $hidden, $submit );
		
		//return null value so default will no be displayed as well
		return; 
		
	}
	
	/**
	 * Process opt-in form if Feedblitz is selected service
	 *
	 * @param   boolean $pre     the default form HTML
	 * @param   string  $service the submitted service
	 * @param   string  $list_id the submitted list ID
	 * @returns boolean
	 * @since   0.1.0
	 */
	function process_submission( $pre, $service, $list_id ){
		
		//exist early if Feedblitz is not the selected service
		if( 'Feedblitz' !== $service ){
			return $pre;
		}
		
		$data = array(
			//'FirstName' => $_POST['fname'],
			//'LastName'  => $_POST['lname'],
			'email'     => $_POST['email'],
		);

		$response = $this->api->add_subscriber( $list_id, $data['email'] );
		
		if( strpos( $response, 'error') ){
			return array( 'error' => $response );
		}
		
		
		return $response;
		
	}
	
}

global $LandingPage_Feedblitz_Optin_Gateway;

$LandingPage_Feedblitz_Optin_Gateway = new LandingPage_Feedblitz_Optin_Gateway;
