<?php

if ( !class_exists( 'payamakpars_Settings_API' ) ) {
    include_once dirname( __FILE__ ) . '/lib/class.settings-api.php';
}

/**
 * Admin options handler class
 *
 * @author payamak-pars.ir
 */
class payamakpars_SMS_Admin {

    private $settings_api;

    function __construct() {
        $this->settings_api = new payamakpars_Settings_API();

        //plugin options
        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_options_page( __( 'تایید اعتبار پیامکی پارس', 'paramakpars' ), __( 'تایید اعتبار پیامکی پارس', 'paramakpars' ), 'install_plugins', 'payamakpars_sms', array($this, 'plugin_page') );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'payamakpars_sms_labels',
                'title' => __( 'پیام ها', 'paramakpars' )
            ),
            array(
                'id' => 'payamakpars_sms_options',
                'title' => __( 'تنظیمات ورود', 'paramakpars' )
            ),
            array(
                'id' => 'payamakpars_sms_gateways',
                'title' => __( 'تنظیمات پیامک', 'paramakpars' )
            ),
        );

        return apply_filters( 'payamakpars_sms_sections', $sections );
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public static function get_settings_fields() {
        $settings_fields = array();
        $gateways = array();
        $gateway_obj = payamakpars_SMS_Gateways::instance();
        $registered_gateways = $gateway_obj->get_gateways();



        foreach ($registered_gateways as $gateway => $option) {
            $gateways[$gateway] = $option['label'];
        }

        $settings_fields['payamakpars_sms_labels'] = array(
            'sender_name' => array(
                'name' => 'sender_name',
                'label' => __( 'نام ارسال کننده :', 'paramakpars' ),
                'default' => 'سامانه پیامکی پارس'
            ),
            'mob_instruction' => array(
                'name' => 'mob_instruction',
                'label' => __( 'راهنمای شماره همراه', 'paramakpars' ),
                'default' => __( 'جهت دریافت کد تاییدیه لطفا شماره همراه را وارد نمایید', 'paramakpars' )
            ),
            'unlock_instruction' => array(
                'name' => 'unlock_instruction',
                'label' => __( 'پیام راهنما', 'paramakpars' ),
                'default' => __( 'لطفا کد تایید اعتبار را وارد نمایید.', 'paramakpars' )
            ),
            'sms_text' => array(
                'name' => 'sms_text',
                'label' => __( 'متن پیامک', 'paramakpars' ),
                'type' => 'textarea',
                'default' => __( 'کاربر محترم سایت پارس لطفا در هنگام ورود از کد تایید  %CODE% استفاده نمایید', 'paramakpars' ),
                'desc' => __( ' شما می توانید به صورت دلخواه این متن را تغییر داده .( <strong>%CODE%</strong> ) ', 'paramakpars' )
            ),
            'process_msg' => array(
                'name' => 'process_msg',
                'label' => __( 'پردازش پیام', 'paramakpars' ),
                'default' => __( 'پردازش تایید اعتبار لطفا صبر کنید', 'paramakpars' )
            ),
            'sending_msg' => array(
                'name' => 'sending_msg',
                'label' => __( 'پیام ارسال پیامک', 'paramakpars' ),
                'default' => __( 'پیامک در حال ارسال است لطفا منتظر بمانید', 'paramakpars' )
            ),
            'error_msg' => array(
                'name' => 'error_msg',
                'label' => __( 'پیام خطا', 'paramakpars' ),
                'default' => __( 'خطا لطفا دوباره امتحان کنید', 'paramakpars' )
            ),
            'success_msg' => array(
                'name' => 'success_msg',
                'label' => __( 'پیام تایید', 'paramakpars' ),
                'default' => __( 'تایید شماره همراه انجام شد لطفا صبر کنید', 'paramakpars' )
            ),
            'invalid_number' => array(
                'name' => 'invalid_number',
                'label' => __( 'پیام خطای شماره تلفن', 'paramakpars' ),
                'default' => __( 'شماره تلفن نامعتبر می باشد', 'paramakpars' )
            ),
            'sms_sent_msg' => array(
                'name' => 'sms_sent_msg',
                'label' => __( 'پیام ارسال موفق پیامک', 'paramakpars' ),
                'default' => __( 'پیامک با موفقیت ارسال شد لطفا در بخش ورود کد تایید اعتبار را وارد نمایید.', 'paramakpars' )
            ),
            'sms_sent_error' => array(
                'name' => 'sms_sent_error',
                'label' => __( 'پیام خطا در ارسال پیامک', 'paramakpars' ),
                'default' => __( 'ارسال پیام با خطا مواجه شد لطفا با مدیر تماس بگیرید', 'paramakpars' )
            ),
        );

        $settings_fields['payamakpars_sms_options'] = array(
            array(
                'name' => 'override_comment',
                'label' => __( 'تایید کاربر در زمان ارسال نظرات', 'paramakpars' ),
                'desc' => __( 'فعال کردن تایید کاربر هنگام ارسال نظرات', 'paramakpars' ),
                'type' => 'checkbox'
            ),
            array(
                'name' => 'register_form',
                'label' => __( 'تایید کاربر در زمان ثبت نام', 'paramakpars' ),
                'desc' => __( 'فعال کردن تایید در روند ثبت نام کاربر ', 'paramakpars' ),
                'type' => 'checkbox'
            ),
        );

        $settings_fields['payamakpars_sms_gateways'] = array(
            array(
                'name' => 'active_gateway',
                'label' => __( 'تنظیمات پنل پیامک', 'paramakpars' ),
                'type' => 'select',
                'options' => $gateways
            ),
            array(
                'name' => 'payamakpars_header',
                'label' => '',
                'type' => 'html',
                'desc' => __( '<span style="font-size: 14px;font-weight: bold;"><a href="http://www.payamak-pars.ir" class="sms-gateway-api" data-rows="3">ثبت نام </a></span>', 'paramakpars' )
            ),
            array(
                'name' => 'payamakpars_username',
                'label' => __( '			نام کاربری : 		', 'paramakpars' )
            ),
            array(
                'name' => 'payamakpars_pass',
                'label' => __( '			رمز :		', 'paramakpars' )
            ),
           array(
                'name' => 'payamakpars_number',
                'label' => __( 'شماره :', 'paramakpars' )
            ),
        );

        $gateway_toggle_js = '<script>(function($){$("#payamakpars_sms_gateways").on("click","a.sms-gateway-api",function(e){e.preventDefault();var self=$(this),rows=self.data("rows"),parent=self.parents("tr");var next=parent.nextAll();if(next.length){var elems=next.slice(0,rows);$(elems).each(function(ind,el){$(el).slideToggle()})}})})(jQuery);</script>';


        return apply_filters( 'payamakpars_sms_fields', $settings_fields );
    }

    function plugin_page() {
        echo '<div class="wrap">';
        settings_errors();

        echo '<div id="icon-themes" class="icon32"></div>';
        echo __( '<h2>تایید اعتبار پیامکی پارس (www.payamak-pars.ir)</h2>', 'paramakpars' );
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

}

$sms_admin = new payamakpars_SMS_Admin();
