<?php

class Gotowebinar_Integration_Admin {

    const CLIENT_ID              = 'SfpYgBS6SlFVeuUxfmqOlKvhuJDqAsKd';
    const CLIENT_SECRET          = 'Nve1u3QvGjU78aEE';

    const REDIRECT_URI           = 'https://oauth.newrainmaker.com/authorization';
    const AUTHORIZATION_ENDPOINT = 'https://api.citrixonline.com/oauth/authorize';
    const TOKEN_ENDPOINT         = 'https://api.citrixonline.com/oauth/access_token';

    static $client;
    static $state;
    static $redirect_uri;

    static public function admin_enqueue_scripts() {

        wp_enqueue_script( 'jquery-magnific-popup', GTW_URL .'/assets/js/jquery.magnific-popup.min.js', array('jquery' )                          );
        wp_enqueue_script( 'wp_gtw_admin'         , GTW_URL .'/assets/js/admin.js'                    , array('jquery', 'jquery-magnific-popup' ) );

        wp_enqueue_style ( 'magnific-popup-css'   , GTW_URL .'/assets/css/magnific-popup.css' );
        wp_enqueue_style ( 'wp_gtw_admin_style'   , GTW_URL .'/assets/css/admin.css'          );

    }

    public static function add_my_media_button() {

        if ( ( isset( $_GET['page'] ) && ( $_GET['page'] == 'universal-settings' || $_GET['page'] == 'rainmail-edit-list' ) || get_current_screen()->post_type == 'mail' || get_current_screen()->post_type == 'autoresponder' || ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'autoresponder' ) ) ) {
            return;
        }

        $credentials = get_option( 'wp_gtw_credentials' );

        if ( ! empty( $credentials ) ) {
            echo '<button id="insert_gtw_form_link" class="button"><img src="'.GTW_URL.'/assets/images/gtw_icon.png"> Add Webinar</button>';
        }

    }

    static function footer_modal() { ?>
        <div id="insert_gtw_form" class="white-popup mfp-hide">
            <h1>Add Webinar Registration</h1>
            <form method="post" id="gtw_insert_form">
                <table class="form-table">
                    <tr>
                        <th>Select webinar</th>
                        <td>
                            <select id="webinar_key">
                                <option>Loading webinars....</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td>
                            <input type="regular-text" id="button_text" placeholder="Register Now">
                            <p class="description">Leave blank for default</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Auto register? (No button click)</th>
                        <td><input type="checkbox" id="gtw_register_check" value="1"></td>
                    </tr>
                    <tr>
                        <th>Redirect to thank you page?</th>
                        <td><input type="checkbox" id="gtw_redirect_check" value="1"></td>
                    </tr>
                    <tr id="gtw_redirect_row">
                        <th>Thank you page</th>
                        <td><?php wp_dropdown_pages( array( 'id' => 'thank_page' ) ); ?></td>
                    </tr>
                </table>
                <button type="submit" class="button-primary">Insert Registration Form</button>
            </form>
        </div>
    <?php }

    public static function ajax_get_webinars() {

        $output = '';

        $webinars = WP_G2W()->get_webinars();

        foreach( $webinars as $webinar ) {

            $output .= sprintf(
                '<option value="%d">%s</option>',
                $webinar['key'],
                $webinar['title'].' ('.$webinar['date'].')'
            );

        }

        echo $output;

        die();
    }

    static function setup_oauth() {

        if ( ! class_exists( 'oAuth_Client' ) ) {
            require_once( 'oAuth2_Client.php' );
        }

        require_once( 'GrantType/IGrantType.php' );
        require_once( 'GrantType/AuthorizationCode.php' );

        self::$state   = urlencode( add_query_arg( array( 'page' => 'universal-settings', 'tab' => 'content', 'service' => 'gotowebinar' ), admin_url( '/admin.php' ) ) );
        self::$client  = new oAuth_Client\oAuth_Client( self::CLIENT_ID, self::CLIENT_SECRET );

        $credentials   = get_option( 'wp_gtw_credentials' );

        if ( $credentials ) {

            self::$client->setAccessToken( $credentials['token'] );
            self::$client->setAccessTokenType( 1 );

        }

    }

    public function get_credentials( $code ){
        return self::_get_credentials( $code );
    }

    private static function _get_credentials( $code ){

        self::setup_oauth();

        $params   = array( 'code' => $code, 'redirect_uri' => self::REDIRECT_URI );

        $response = self::$client->getAccessToken( self::TOKEN_ENDPOINT, 'authorization_code', $params );

        if ( empty( $response['result']['ErrorCode'] ) ) {

            $token = array(
                'token'         => $response['result']['access_token'],
                'organiser_key' => $response['result']['organizer_key']
            );

            update_option( 'wp_gtw_credentials', $token );

        }

    }

    public static function get_oAuth_url() {

        self::setup_oauth();
        $url = self::$client->getAuthenticationUrl(self::AUTHORIZATION_ENDPOINT, self::REDIRECT_URI, array( 'state' => self::$state ) );

        return $url;

    }

}