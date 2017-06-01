<?php

class Rainmaker_Mail_AJAX_Lists {

	/**
	 * Checks if the gateway is registered based on an AJAX call.
	 * Then calls edit_list of the Gateway.
	 *
	 * @access public
	 * @param
	 * @return string
	 */
	static function edit_optin_list() {

		$data    = $_POST['data'];

		if( ! empty( $_POST['action2'] ) ) {
			$action  = $_POST['action2'];
		} elseif( ! empty( $_POST['data']['id'] ) ) {
			$action = 'edit';
		} else {
			$action = 'new';
		}

		$results = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::edit_list( $data, $action );

		if ( isset( $_POST['triggers'] ) ) {
			$triggers = static::edit_optin_triggers( $results );
		}

		print_r( $results );

		die();
	}

	/**
	 * Adds/edits the opt-in triggers.
	 *
	 * @access public
	 * @static
	 * @param  array $list
	 * @return array
	 */
	static function edit_optin_triggers( $list ) {

		$list_id    = ( isset( $list['syndications'] ) && isset( $list['syndications']['syndication'] ) && isset( $list['syndications']['syndication']['id'] ) ) ? $list['syndications']['syndication']['id'] : '';
		$list_id    = isset( $_POST['data']['id']                ) ? $_POST['data']['id']                : $list_id;
		$trigger_id = isset( $_POST['triggers']['triggerid']     ) ? $_POST['triggers']['triggerid']     : '';

		if ( empty( $list_id ) ) {
			return;
		}

		if ( empty( $trigger_id ) ) {
			$trigger_id = Rainmaker_Mail_AJAX_Lists::get_trigger_id( $list_id );
		}

		$trigger = array(
			'id'     => $trigger_id,
			'event'  => 'Unsubscribe',
			'action' => isset( $_POST['triggers']['triggeraction'] ) ? $_POST['triggers']['triggeraction'] : '',
			'listid' => isset( $_POST['triggers']['triggerlist']   ) ? $_POST['triggers']['triggerlist']   : '',
		);

		return Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::edit_trigger( $list_id, $trigger_id, $trigger );

	}

	/**
	 * Gets the trigger ID from remote to ensure empty trigger ID states do not result in multiple triggers.
	 *
	 * @access public
	 * @static
	 * @param  int $list_id
	 * @return int
	 */
	static function get_trigger_id( $list_id ) {

		$trigger_id = '';

		if ( $list_id ) {

			$triggers = Rainmaker_Opt_In_Gateway_FeedBlitz_Pro::get_triggers( $list_id );

			if ( $triggers && isset( $triggers['trigger'] ) && isset( $triggers['trigger']['id'] ) ) {

				if ( 'Unsubscribe' !== $triggers['trigger']['event'] ) {
					return $trigger_id;
				}

				$trigger_id = $triggers['trigger']['id'];

			} elseif ( $triggers && isset( $triggers['trigger'] ) ) {
				foreach ( $triggers['trigger'] as $trigger ) {

					if ( 'Unsubscribe' !== $trigger['event'] ) {
						continue;
					}

					$trigger_id = $trigger['id'];

				}
			}

		}

		return $trigger_id;

	}

}
