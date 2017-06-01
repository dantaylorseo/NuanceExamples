<?php

class Rainmaker_Mail_Duplicate {

	/**
	 * Callback for AccessPress 'premise_copy_draft_status' filter.
	 *
	 * Registers the mail CPT to be duplicated as draft.
	 *
	 * @since 2.4.2
	 *
	 * @param  array  $post_types An associative array of post types.
	 * @return array              An associative array of post types.
	 */
	static function draft_status( $post_types ) {

		$post_types[] = 'mail';

		return $post_types;

	}

	/**
	 * Callback for Premise 'premise_copy_post_meta' action.
	 *
	 * Copy post meta from the original to the copied item.
	 * Changed the publish time to +12 hours if not already scheduled for a future time.
	 *
	 * @since  2.4.2
	 *
	 * @param  integer $copy_id       The copied object ID.
	 * @param  integer $original_id   The original object ID.
	 * @param  array   $original_meta The original post meta.
	 * @return void
	 */
	static public function copy_post_meta( $copy_id, $original_id, $original_meta ) {

		$type = get_post_type( $copy_id );

		if ( 'mail' !== $type ) {
			return;
		}

		$gmt_timestamp = get_post_time( 'U', true, $copy_id );

		if ( $gmt_timestamp < time() ) {

			$response = wp_update_post( array(
				'ID'            => $copy_id,
				'post_date'     => date( 'Y-m-d H:i:s', time() + ( get_option( 'gmt_offset' ) * 3600 ) + ( DAY_IN_SECONDS / 2 ) ), //make duplicate broadcasts default publish to +12 hours.
				'post_date_gmt' => date( 'Y-m-d H:i:s', time() + ( DAY_IN_SECONDS / 2 ) )
			) );

		}

		foreach ( $original_meta as $key => $value ) {

			if ( '_edit_lock' === $key || '_edit_last' === $key || '_broadcast_message_ids' === $key ) {
				continue;
			}

			$new = get_post_meta( $original_id, $key, true );

			if ( '' === $new ) {

				delete_post_meta( $copy_id, $key );

				continue;

			}

			update_post_meta( $copy_id, $key, $new );

		}

		delete_post_meta( $copy_id, '_broadcast_message_ids' );

	}

}
