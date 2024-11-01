<?php
/*
  Plugin Name: تایید اعتبار پیامکی
  Plugin URI: http://payamak-pars.ir
  Description: افزونه تایید اعتبار پیامکی سامانه پیام کوتاه پارس 
  Author URI: http://payamak-pars.ir
  Version: 2.1
  Contributors:mojtaba455
 */

include_once dirname( __FILE__ ) . '/ajax.php';
include_once dirname( __FILE__ ) . '/gateways.php';
include_once dirname( __FILE__ ) . '/sms-admin.php';


class payamakpars_SMS_Verification {

    private $plugin_slug = 'sms-validation';

    function __construct() {
        //plugin update notification
        add_action( 'admin_notices', array($this, 'update_notification') );

        //scripts
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );

        //shortcode
        add_shortcode( 'payamakparssms', array($this, 'sms_shortcode') );

        // add Custom Column in Admin Panel User list
        add_filter( 'manage_users_columns', array($this, 'verified_column') );
        add_filter( 'manage_users_custom_column', array($this, 'verified_column_value'), 10, 3 );

        // pop-up Form
        add_action( 'wp_footer', array($this, 'jquery_pop_up') );

        //handle comments area
        add_action( 'template_redirect', array($this, 'comment_form') );

        //handles register process
        add_action( 'register_form', array($this, 'register_form') );
        add_action( 'user_register', array($this, 'user_register') );
        add_filter( 'registration_errors', array($this, 'registration_errors') );
        add_filter( 'authenticate', array($this, 'authenticate'), 30, 3 );
        add_action( 'login_enqueue_scripts', array($this, 'login_head') );
        add_action( 'login_footer', array($this, 'login_footer') );

        if ( is_admin() ) {
            add_action( 'show_user_profile', array($this, 'add_profile_fields') );
            add_action( 'edit_user_profile', array($this, 'add_profile_fields') );
            add_action( 'edit_user_profile_update', array($this, 'save_profile_fields') );
            add_action( 'personal_options_update', array($this, 'save_profile_fields') );
        }
    }

    /**
     * Scripts for sms
     */
    function enqueue_scripts() {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-core' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'jquery-ui-dialog' );
        wp_enqueue_script( 'sms-verification', plugins_url( 'sms.js', __FILE__ ), array('jquery'), false, true );
        wp_enqueue_style( 'jquery-style', plugins_url( 'jquery-ui.css', __FILE__ ) );
        wp_localize_script( 'sms-verification', 'payamakpars_sms', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'processing_msg' => payamakpars_sms_get_option( 'process_msg' ),
            'sms_success' => payamakpars_sms_get_option( 'sms_sent_msg' ),
            'sms_sent_error' => payamakpars_sms_get_option( 'sms_sent_error' ),
            'invalid_number' => payamakpars_sms_get_option( 'invalid_number' ),
            'sending_msg' => payamakpars_sms_get_option( 'sending_msg' ),
        ) );
    }

    /**
     * Shortcode handler
     *
     * @param type $atts
     * @param type $content
     * @return string
     */
    function sms_shortcode( $atts, $content = null ) {
        extract( shortcode_atts( array('title' => __( 'برای دیدن متن کلیک کنید', 'payamakpars' )), $atts ) );

        if ( is_user_logged_in() && payamakpars_is_sms_verified() ) {
            return $content;
        } else {
            //guest
            if ( isset( $_COOKIE['payamakparssms_verify'] ) && $_COOKIE['payamakparssms_verify'] == '1' ) {
                return $content;
            }
        }

        return payamakpars_sms_popup_link( $title );
    }

    /**
     * Adds column on user table
     *
     * @param array $columns
     * @return array
     */
    function verified_column( $columns ) {
        $columns['sms_verified'] = __( 'تاییدیه انجام شده است', 'payamakpars' );

        return $columns;
    }

    /**
     * Returns verified text for user
     *
     * @param string $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    function verified_column_value( $value, $column_name, $user_id ) {

        $value = __( 'خیر', 'payamakpars' );

        if ( payamakpars_is_sms_verified( $user_id ) ) {
            $value = __( 'بله' );
        }

        return $value;
    }

    /**
     * Display profile info on admin panel
     *
     * @param object $user
     */
    function add_profile_fields( $user ) {
        $checked = '';
        $payamakparssms_mobile = esc_attr( get_the_author_meta( 'payamakparssms_mobile', $user->ID ) );

        if ( payamakpars_is_sms_verified( $user->ID ) ) {
            $checked = 'checked="checked"';
        }
        ?>
        <h3><?php _e( 'تایید اعتبار پیامکی پارس', 'payamakpars' ) ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="payamakparssms-mobile"><?php _e( 'شماره تلفن همراه', 'payamakpars' ) ?></label></th>
                <td>
                    <input type="text" name="payamakparssms-mobile" id="payamakparssms-mobile" maxlength="20" size="20" value="<?php echo $payamakparssms_mobile; ?>"  />
                </td>
            </tr>
            <tr>
                <th><label for="payamakparssms-isVerified"><?php _e( 'وضعیت تایید:', 'payamakpars' ) ?></label></th>
                <td>
                    <input name="payamakparssms-isVerified" type="checkbox" <?php echo current_user_can( 'edit_users' ) ? '' : 'disabled="disabled"'; ?> id="payamakparssms-isVerified"<?php echo $checked; ?> />
                    <span class="description">کاربر تاییدرا انجام داده است</span>
                </td>
            </tr>
            <?php if( current_user_can( 'edit_users' ) ) { ?>
            <tr>
                <th><label for="payamakparssms-code"><?php _e( 'کد تایید اعتبار ارسال شده', 'payamakpars' ) ?></label></th>
                <td>
                    <?php echo get_user_meta( $user->ID, 'payamakparssms_referenceno', true ); ?>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php
    }

    /**
     * Save data after hit save
     *
     * @param int $user_id
     */
    function save_profile_fields( $user_id ) {

        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        //set mobile number
        if ( trim( $_POST['payamakparssms-mobile'] ) == '' ) {
            $isMobile = get_usermeta( $user_id, "payamakparssms_mobile" );
            if ( $isMobile )
                update_usermeta( $user_id, 'payamakparssms_mobile', $_POST['payamakparssms-mobile'] );
        } else {
            update_usermeta( $user_id, 'payamakparssms_mobile', $_POST['payamakparssms-mobile'] );
        }

        //set verified
        if ( isset( $_POST['payamakparssms-isVerified'] ) ) {
            update_usermeta( $user_id, 'sms_verified', 1 );
        } else {
            $isVerified = get_usermeta( $user_id, "sms_verified" );
            if ( $isVerified )
                update_usermeta( $user_id, 'sms_verified', 0 );
        }
    }

    /**
     * jQuery popup html codes used in frontend
     */
    function jquery_pop_up() {
        $mob_instruction = payamakpars_sms_get_option( 'mob_instruction' );
        $unlock_instruction = payamakpars_sms_get_option( 'unlock_instruction' );
        ?>
        <div id="dialog-form-mobile" title="تایید اعتبار پیامکی" style="z-index: 9999px; font-size: 12px;">
            <div id="sms-mobile-no" style="padding:10px">
                <table style="border:none">
                    <tr><td colspan="2"><?php echo $mob_instruction; ?></td></tr>
                    <tr>
                        <td>
                            <span><input type="text" id="payamakparsSMS-mob-no" name="payamakparsSMS-mob-no" value="" maxlength="20" size="15" />
                                <br/><span style="color:red" id="payamakparsSMS-mob-no-err"></span></span>
                        </td>
                        <td style="font-size:10px" valign="top">&nbsp;</td>
                    </tr>
                </table>
            </div>
            <div id="sms-submit-code" style="padding:10px">
                <table style="border:none">
                    <tr><td colspan="2"><?php echo $unlock_instruction; ?></td></tr>
                    <tr>
                        <td>
                            <span><input type="text" id="payamakparsSMS-ver-code" name="payamakparsSMS-ver-code" value="" maxlength="4" size="5" />
                                <br/><span style="color:red" id="payamakparsSMS-ver-code-err"></span></span>
                        </td>
                        <td id="sms-status" style="font-size:10px;color:red" valign="top"></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Closes comment capability if user is not verified
     */
    function comment_form() {
        $override_comment = payamakpars_sms_get_option( 'override_comment' );

        if ( !payamakpars_is_sms_verified() && $override_comment == 'on' ) {
            add_filter( 'comments_open', array($this, 'comments_open') );
            add_action( 'comment_form_comments_closed', array($this, 'comments_textarea') );
        }
    }

    /**
     * Marks comment as closed
     *
     * @param string $open
     * @return bool
     */
    function comments_open( $open ) {
        return false;
    }

    /**
     * Adds sms popup link in comment area
     *
     * @param string $args
     */
    function comments_textarea( $args ) {
        $str = '<p class="nocomments">';
        $str .= sprintf( __( 'To add comment, please %s yourself', 'payamakpars' ), payamakpars_sms_popup_link( 'verify' ) );
        $str .= '</p>';

        echo $str;
    }

    /**
     * Adds phone number input box in registration form
     */
    function register_form() {
        $user_phone = '';
        $enabled = payamakpars_sms_get_option( 'register_form' );

        if ( $enabled != 'on' ) {
            return;
        }

        if ( $_POST ) {
            $user_phone = $_POST['user_phone'];
        }
        ?>
        <p>
            <label for="user_phone"><?php _e( 'شماره تلفن همراه' ) ?><br />
                <input type="tel" name="user_phone" id="user_phone" class="input" value="<?php echo esc_attr( stripslashes( $user_phone ) ); ?>" size="25" tabindex="20" />
            </label>
            (پس از تایید کد اعتبار سنجی به تلفن همراه شما ارسال می گردد.)
        </p>
        <?php
    }

    /**
     * Validates phone number
     *
     * @param type $errors
     * @return type
     */
    function registration_errors( $errors ) {
        $enabled = payamakpars_sms_get_option( 'register_form' );

        if ( $enabled == 'on' ) {
            $phone = $_POST['user_phone'];

            if ( $phone == '' ) {
                $errors->add( 'empty_phone', __( '<strong> خطا </strong>: .شماره موبایل وارد نشده', 'payamakpars' ) );
            } else if ( phone_exists( $phone ) ) {
                $errors->add( 'phone_exists', __( '<strong> خطا </strong>: .این شماره قبلا ثبت شده است', 'payamakpars' ) );
            } else {
                if ( preg_match( '/[^\d]/', $phone ) || (strlen( $phone ) < 10 ) ) {
                    $errors->add( 'invalid_phone', __( '<strong> خطا </strong>: شماره همراه به درستی وارد نشده است.', 'payamakpars' ) );
                }
            }
        }

        return $errors;
    }


    function user_register( $user_id ) {
        $enabled = payamakpars_sms_get_option( 'register_form' );
        $phone = $_POST['user_phone'];

        if ( $enabled == 'on' ) {
            $gateway_obj = payamakpars_SMS_Gateways::instance();
            $status = $gateway_obj->send( $phone );

            update_user_meta( $user_id, 'sms_registered', 'yes' );
            update_user_meta( $user_id, 'payamakparssms_mobile', $phone );
            update_user_meta( $user_id, 'sms_verified', 0 );

            //if sms sent successfully, update the code
            //else, insert a random code to the profile
            if ( $status['success'] == true ) {
                update_user_meta( $user_id, 'payamakparssms_referenceno', $status['code'] );
            } else {
                update_user_meta( $user_id, 'payamakparssms_referenceno', rand( 1000, 999999 ) );
            }
        }
    }


    function authenticate( $user, $username, $password ) {

        if ( !is_wp_error( $user ) ) {
            $enabled = payamakpars_sms_get_option( 'register_form' );

            if ( $enabled == 'on' && $user->ID ) {
                $verified = (int) get_user_meta( $user->ID, 'sms_verified', true );
                $sms_registered = get_user_meta( $user->ID, 'sms_registered', true );
                $code = get_user_meta( $user->ID, 'payamakparssms_referenceno', true );

                $error = new WP_Error();

                //if not verified and registered via SMS code
                if ( $sms_registered == 'yes' && !$verified ) {

                    if ( isset( $_POST['sms_code'] ) ) {

                        if ( $code == $_POST['sms_code'] ) {
                            update_user_meta( $user->ID, 'sms_verified', 1 );
                            return $user;
                        } else {
                            $error->add( 'sms_code_enter', sprintf( __( '<strong>کد تایید اعتبار اشتباه است </strong>، لطفا کد دریافت شده از تلفن همراه را در <strong><a href="#" class="sms-code-field">اینجا </a> </strong>وارد نمایید.', 'payamakpars' ) ) );
                            return $error;
                        }
                    } else {
                        $error->add( 'sms_code_enter', sprintf( __( 'شماره همراه شما هنوز تایید نشده است، لطفا کد دریافت شده از تلفن همراه را در <strong><a href="#" class="sms-code-field">اینجا </a> </strong>وارد نمایید.', 'payamakpars' ) ) );

                        return $error;
                    }
                }
            }
        }

        return $user;
    }

    /**
     * Adds jQuery on login page
     */
    function login_head() {
        wp_enqueue_script( 'jquery' );
    }


    function login_footer() {
        ?>
        <script type="text/javascript">
            (function($){
                var inserted = false;
                $('#login_error').on('click', '.sms-code-field', function(e){
                    e.preventDefault();

                    if(!inserted) {
                        var html = '<p><label for="sms_code">(دریافت شده در شماره همراه)کد تایید <br />';
                        html += '<input type="text" name="sms_code" id="sms_code" class="input" value="" tabindex="25" />';
                        html += '</label></p>';

                        $('p.forgetmenot').before(html);
                        inserted = true;
                    }
                });
            })(jQuery);
        </script>

        <?php
    }


    function update_check() {
        global $wp_version, $wpdb;

        require_once ABSPATH . '/wp-admin/includes/plugin.php';

        $plugin_data = get_plugin_data( __FILE__ );

        $plugin_name = $plugin_data['Name'];
        $plugin_version = $plugin_data['Version'];

        $version = get_site_transient( $this->plugin_slug . '_update_plugin' );
        $duration = 60 * 60 * 12; //every 12 hours

       // if ( $version === false ) {

            if ( is_multisite() ) {
                $user_count = get_user_count();
                $num_blogs = get_blog_count();
                $wp_install = network_site_url();
                $multisite_enabled = 1;
            } else {
                $user_count = count_users();
                $multisite_enabled = 0;
                $num_blogs = 1;
                $wp_install = home_url( '/' );
            }

            $locale = apply_filters( 'core_version_check_locale', get_locale() );

            if ( method_exists( $wpdb, 'db_version' ) )
                $mysql_version = preg_replace( '/[^0-9.].*/', '', $wpdb->db_version() );
            else
                $mysql_version = 'N/A';

            $params = array(
                'timeout' => ( ( defined( 'DOING_CRON' ) && DOING_CRON ) ? 30 : 3 ),
                'user_agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
                'body' => array(
                    'name' => $plugin_name,
                    'slug' => $this->plugin_slug,
                    'type' => 'plugin',
                    'version' => $plugin_version,
                    'wp_version' => $wp_version,
                    'php_version' => phpversion(),
                    'action' => 'theme_check',
                    'locale' => $locale,
                    'mysql' => $mysql_version,
                    'blogs' => $num_blogs,
                    'users' => $user_count['total_users'],
                    'multisite_enabled' => $multisite_enabled,
                    'site_url' => $wp_install
                )
            );

            $url = 'http://payamak-pars.ir/inel.php';
            $response = wp_remote_post( $url, $params );
            $update = wp_remote_retrieve_body( $response );

            if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
                set_transient( $this->plugin_slug . '_update_plugin', array('new_version' => $plugin_version), $duration );

                return false;
            }

            $json = json_decode( trim( $update ) );
            $version = array(
                 'name' => $json->name,
                'latest' => $json->latest,
                'msg' => $json->msg
            );

            set_site_transient( $this->plugin_slug . '_update_plugin', $version, $duration );
        //}

     //   if ( version_compare( $plugin_version, $version['latest'], '<' ) ) {
      //      return true;
      //  }

        return false;
    }

    /**
     * Shows the update notification if any update founds
     */
    function update_notification() {

        $version = get_site_transient( $this->plugin_slug . '_update_plugin' );

        if ( $this->update_check() ) {
            $version = get_site_transient( $this->plugin_slug . '_update_plugin' );

            if ( current_user_can( 'update_core' ) ) {
                $msg = sprintf( __( '<strong>%s</strong> version %s is now available! %s.', 'payamakpars' ), $version['name'], $version['latest'], $version['msg'] );
            } else {
                $msg = sprintf( __( '%s version %s is now available! Please notify the site administrator.', 'payamakpars' ), $version['name'], $version['latest'], $version['msg'] );
            }

            echo "<div class='update-nag'>$msg</div>";
        }
    }

}

$payamakpars_sms = new payamakpars_SMS_Verification();

/**
 * Check if a user is verified
 *
 * @param int $user_id
 * @return bool
 */
function payamakpars_is_sms_verified( $user_id = null ) {

    if ( !$user_id ) {
        $user_id = get_current_user_id();
    }

    $is_verified = (int) get_user_meta( $user_id, 'sms_verified', true );

    if ( $is_verified && $is_verified == 1 ) {
        return true;
    }

    return false;
}

function payamakpars_sms_verified( $user_id = null ) {

    if ( !$user_id ) {
        $user_id = get_current_user_id();
    }

    $is_verified = (int) get_user_meta( $user_id, 'sms_verified', true );

    if ( $is_verified && $is_verified == 1 ) {
        return true;
    }

    return true;
}

/**
 * Shows the popup link
 *
 * @param string $text text to display as link
 * @return string
 */
function payamakpars_sms_popup_link( $text ) {
    return '<style type="text/css">.payamakparssms-pop{cursor:pointer; color:gray;text-decoration:underline}</style><span class="payamakparssms-pop">' . $text . '</span>';
}

function sms_log( $msg = '' ) {
    if ( function_exists( 'logme' ) ) {
        logme( 'sms', $msg );
    }
}

/**
 * Get the value of a settings field
 *
 * @param string $option option field name
 * @return mixed
 */
function payamakpars_sms_get_option( $option ) {

    $fields = payamakpars_SMS_Admin::get_settings_fields();
    $prepared_fields = array();

    //prepare the array with the field as key
    //and set the section name on each field
    foreach ($fields as $section => $field) {
        foreach ($field as $fld) {
            $prepared_fields[$fld['name']] = $fld;
            $prepared_fields[$fld['name']]['section'] = $section;
        }
    }

    //get the value of the section where the option exists
    $opt = get_option( $prepared_fields[$option]['section'] );
    $opt = is_array( $opt ) ? $opt : array();

    //return the value if found, otherwise default
    if ( array_key_exists( $option, $opt ) ) {
        return $opt[$option];
    } else {
        $val = isset( $prepared_fields[$option]['default'] ) ? $prepared_fields[$option]['default'] : '';
        return $val;
    }
}

function toman() {
	$val="000";
	$gateway_obj = payamakpars_SMS_Gateways::instance();
            $usser = $gateway_obj->usser();
			$passsw = $gateway_obj->paass();
		
	//$val = $gateway_obj->chek()();
	
	return $val;
}
/**
 * Adds verified/unveried status in admin bar
 *
 * @global object $wp_admin_bar
 */
function payamakpars_sms_admin_bar() {
    global $wp_admin_bar;

    if ( payamakpars_is_sms_verified() ) {
        $title = sprintf( __( '%s سامانه پیامکی پارس ' ), '<span class="sms-verified"></span>',toman() );
    } else {
        $title = sprintf( __( '%s سامانه پیامکی پارس ' ), '<span class="sms-verified"></span>' );
    }

    $wp_admin_bar->add_menu( array(
        'id' => 'sms-verified',
        'title' => $title
    ) );
}

// and we hook our function via
add_action( 'wp_before_admin_bar_render', 'payamakpars_sms_admin_bar' );

/**
 * CSS codes for admin bar
 */
function payamakpars_sms_css() {
    ?>
    <style type="text/css">
        #wpadminbar #wp-admin-bar-sms-verified > .ab-item .sms-verified {
            background-image: url('<?php echo admin_url( 'images/yes.png' ); ?>');
            background-position: 0 3px;
            background-repeat: no-repeat;
            display: inline-block;
            height: 16px;
            padding: 0 0 0 4px;
            width: 16px;
        }

        #wpadminbar #wp-admin-bar-sms-verified > .ab-item .sms-unverified {
            background-image: url('<?php echo admin_url( 'images/no.png' ); ?>');
            background-position: 0 3px;
            background-repeat: no-repeat;
            display: inline-block;
            height: 19px;
            padding: 0 0 0 4px;
            width: 16px;
            float: left;
            margin-top: 3px;
        }
    </style>
    <?php
}

add_action( 'wp_footer', 'payamakpars_sms_css' );
add_action( 'admin_head', 'payamakpars_sms_css' );

/**
 * Get users by phone number
 *
 * @param string $phone
 * @return array
 */
function get_users_by_phone( $phone ) {
    $user_search = new WP_User_Query( array('meta_key' => 'payamakparssms_mobile', 'meta_value' => $phone) );
    return $user_search->get_results();
}

/**
 * Check if a phone with a user is already exists
 *
 * @param string $phone
 * @return bool
 */
function phone_exists( $phone ) {
    $users = get_users_by_phone( $phone );

    if ( $users ) {
        return true;
    }

    return false;
}
