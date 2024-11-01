<?php

/**
 * SMS Gateway handler class
 *
 * @author PayamakPars
 */
class payamakpars_SMS_Gateways {

    private static $_instance;

    /**
     * Gateway slug
     *
     * @param string $provider name of the gateway
     */
    function __construct() {
        add_filter( 'payamakpars_sms_via_PayamakPars', array($this, 'PayamakParsAPI') );
    }

    public static function instance() {
        if ( !self::$_instance ) {
            self::$_instance = new payamakpars_SMS_Gateways();
        }

        return self::$_instance;
    }


    function get_gateways() {
        $gateways = array(
            'PayamakPars' => array('label' => 'سامانه پیامکی پارس'),
        );

        return apply_filters( 'payamakpars_sms_gateways', $gateways );
    }

 
    function send( $to ) {

        $active_gateway = payamakpars_sms_get_option( 'active_gateway' );

        if ( empty( $active_gateway ) ) {
            $response = array(
                'success' => false,
                'message' => 'No active gateway found'
            );

            return $response;
        }

        $code = rand( 100000, 999999 );
        $sms_text = payamakpars_sms_get_option( 'sms_text' );
        $sms_text = str_replace( '%CODE%', $code, $sms_text );
        $sms_data = array('text' => $sms_text, 'to' => $to, 'code' => $code);
  
        $status = apply_filters( 'payamakpars_sms_via_' . $active_gateway, $sms_data );

        return $status;
    }


    /**
     * Sends SMS via WWW.paramak-pars.IR api
     *
     * @uses `payamakpars_sms_via_PayamakPars` filter to fire
     *
     * @param type $sms_data
     * @return boolean
     */
    function PayamakParsAPI( $sms_data ) {
        $response = array(
            'success' => false,
            'message' => payamakpars_sms_get_option( 'sms_sent_error' )
        );

        $username = payamakpars_sms_get_option( 'payamakpars_username' );
        $password = payamakpars_sms_get_option( 'payamakpars_pass' );
		$number11 = payamakpars_sms_get_option( 'payamakpars_number' );
		$msg = urlencode($sms_data['text']);

        //bail out if nothing provided
        if ( empty( $username ) || empty( $password ) ) {
            return $response;
        }

	   // auth call
        $baseurl = "http://37.130.202.188/class/sms/webservice/send_url.php";

        $url = sprintf( '%s?from=%s&uname=%s&pass=%s&to=%s&msg=%s', $baseurl,$number11, $username, $password, $sms_data['to'], $msg );

            // do sendmsg call
            $ret = file( $url );
            $send = explode( "\n", $ret[0] );

            if ( $send[0] ) {
                $response = array(
                    'success' => true,
                    'code' => $sms_data['code'],
                    'message' => payamakpars_sms_get_option( 'sms_sent_msg' )
                );
            #}
                return $response;
	}


    }
	function usser() {
		$username = payamakpars_sms_get_option( 'payamakpars_username' );
		return $username;
	}
		function paass() {
		$paasss = payamakpars_sms_get_option( 'payamakpars_pass' );
		return $paasss;
	}
	
	
			

}
