<?php
/*
Plugin Name: Feedblitz API Integration
Plugin URI: http://newrainmaker.com/
Description: Feedblitz API Key claim
Version: 0.1.1
Author: Copyblogger Media LLC
Author URI: http://www.copyblogger.com
*/

add_action( 'init', 'feedblitz_api_shortcodes');


function feedblitz_api_shortcodes(){

   add_shortcode('feedblitz_api_claim', 'feedblitz_api_claim');
   add_shortcode('feedblitz_api_stats', 'feedblitz_api_stats');

   
}

// display Feedblitz feed stats
function feedblitz_api_stats( $atts, $content = "" )
{

	// get the API key		
	$feedblitz_info = get_option( "feedblitz", array() );	

	if ( count( $feedblitz_info ) >= 1 )
	{

		// get the details from the options
		$feedblitz_api = urldecode( $feedblitz_info[ 'apikey' ] );
		$feedblitz_feed = $feedblitz_info[ 'rssfeed' ];
		$feedblitz_feed_id = $feedblitz_info[ 'rssfeedid' ];
	

		// get the stats for the feed
		$stats = get_feedblitz_stats( $feedblitz_feed_id, $feedblitz_api );
		
		return show_stats( $stats );
	}
	else
	{
		return "Sorry, no stats to display";
	}


}

// display claim button / API key
function feedblitz_api_claim( $atts, $content = "" )
{

	$api_key = "";
				
	// get the info on the member
	$member = wp_get_current_user( );
	$member_email = $member->user_email;
	
	//print_r(get_user_meta( $member->ID));
	
	// get the API key		
	$feedblitz_info = get_option( "feedblitz", array() );	
	
	if ( isset( $_GET['feedid'] ) )
	{
		$feedblitz_info['rssfeedid'] = $_GET['feedid'];
		$feedblitz_info['rssfeed'] = $_GET['feedurl'];
		
		update_site_option( "feedblitz", $feedblitz_info ) ;
	}

	
	if ( isset( $_GET['reset'] ) ) $feedblitz_info = array();	
				//print_r( $feedblitz_info );	
	
		
	if ( $_SERVER['REQUEST_METHOD'] != "POST" )
	{

		// does the user not have an API key?
		if ( count( $feedblitz_info ) < 1 )
		{
			return "
			
			<form method=\"POST\">
				<input type=\"submit\" value=\"Claim Your RSS Feed\">
			</form>
			
			
			";
		}
		else
		{

			$feedblitz_api = urldecode( $feedblitz_info[ 'apikey' ] );
			$feedblitz_feed = $feedblitz_info[ 'rssfeed' ];
			$feedblitz_feed_id = $feedblitz_info[ 'rssfeedid' ];
			
			$content = "Your Feedblitz URL is <form><input type=\"text\" value=\"$feedblitz_feed\">
			Your Feedblitz URL ID is <form><input type=\"text\" value=\"$feedblitz_feed_id\">
			Your Feedblitz API Key <form><input type=\"text\" value=\"$feedblitz_api\">
			
			</form>";
		
			
			return $content;
		}	
	}
	else
	{
		$response = call_feedblitz( $member_email, $member );	
	}		
	
	

}

function call_feedblitz( $member_email, $member )
{
				
	// we need to get these from the users site
	// rather than hard code them right now
	$feed_url = urlencode( "cpgarrett.org/feed" );  // feed location
	$feed_uri = urlencode( "cpgarrett" );			// the URI will be at the end of the custom feed url, eg. http://feeds.newrainmaker.com/rmkr/example


	$member_email = urlencode( $member_email );
	$master_api_key = urlencode( "ODEwNmU5MGQ0Mzg1MWMwOTc4MzMzNTE2ODNjNDkwNWU=" );
	$api_url = "https://feedblitz.com/f?OemAddSite&key=$master_api_key&email=$member_email&url=$feed_url&uri=$feed_uri";
	
	// print $api_url;
	
	
	$json_payload = "";
	
	$args = array(
					'method'  => "GET",
					'timeout' => 1000,
					'user-agent' => "Rainmaker",
					'headers' => array( "Content-Type" => "application/xml" ),
					'body'    => $json_payload,
													);
	
	
	$response = wp_remote_get( $api_url, $args );
	if ( is_wp_error( $response ) )
	{
	
		return "Error getting API key for $member_email <p/>";
	
	
	}
	else
	{
	
		$xml      = simplexml_load_string( $response['body'] );
		$json     = json_encode( $xml );
		$result	  = json_decode( $json, TRUE );
		
		// get back the results
		$rssfeed 	= $result['rssfeed'];
	    $rssfeedid	= $result['rssfeedid'];
	    $api_key	= $result['apikey'];
		
		// create the array
		$feedblitz_info = array(
		
					"rssfeed" 	=> $rssfeed,
					"rssfeedid" => $rssfeedid,
					"apikey"	=> $api_key
		);
		
		// update the user's values
		update_site_option( "feedblitz", $feedblitz_info ) ;

	
	}


	return $feedblitz_info;

}


function get_feedblitz_stats( $feedblitz_feed_id, $feedblitz_api )
{

	$api_url = "https://feedblitz.com/f.api/feeds/$feedblitz_feed_id/stats/?key=$feedblitz_api";

		$args = array(
					'method'  => "GET",
					'timeout' => 1000,
					'user-agent' => "Rainmaker",
					'headers' => array( "Content-Type" => "application/json" ),
					'body'    => $json_payload,
													);
	
	
	//print "$api_url";
	
	$response = wp_remote_get( $api_url, $args );
	
	$xml      = simplexml_load_string( $response['body'] );
	$json     = json_encode( $xml );
	$result	  = json_decode( $json, TRUE );
	
	return $result;

}

function show_stats( $stats = array() )
{

	$feeds = $stats['feeds'];
	return print_r( $feeds, true );

}
