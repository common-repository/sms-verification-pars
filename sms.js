(function($){
    var statusArea = $('#sms-status'),
    smsArea = $("#sms-mobile-no" ),
    codeArea = $("#sms-submit-code" ),
    numberError = $("#payamak-pars-mob-no-err");

    var payamakpars_SMS = {
        getSms: function(mob) {
            $.ajax({
                type : 'POST',
                url : payamakpars_sms.ajaxurl,
                data: {
                    action : 'send_sms',
                    mobile : mob
                },
                dataType: 'json',
                success : function(response){
                    console.log(response);

                    if(response.success == true) {
                        statusArea.html(response.message);
                        $(".ui-dialog-buttonpane button:contains('Unlock')").button("enable");
                    } else{
                        statusArea.html(response.message);
                        $(".ui-dialog-buttonpane button:contains('Unlock')").button("disable");
                    }
                },
                error: function (xhr, status, error) {
                    statusArea.html(payamakpars_sms.sms_sent_error);
                }
            });
        },
        unlock: function(code) {
            $.ajax({
                type : 'POST',
                url : payamakpars_sms.ajaxurl,
                data: {
                    action : 'sms_verify_code',
                    code : code
                },
                dataType: 'json',
                success : function(response){
                    if( response.success){
                        statusArea.html(response.message);
                        location.reload();
                    } else{
                        statusArea.html(response.message);
                    }
                },
                error: function (xhr, status, error) {
                    statusArea.html(payamakpars_sms.sms_sent_error);
                }
            });
        },
        clearArea: function() {
            statusArea.html('');
            numberError.html('');
        }
    };

    $('#dialog-form-mobile').dialog({
        autoOpen: false,
        modal: true,
        height: 260,
        width: 600,
        zIndex: 9999,
        buttons: {
            'Cancel': function() {
                $( this ).dialog( "close" );
                $(".ui-dialog-buttonpane button:contains('Get SMS Code')").button('enable');
            },
            'I have code': function() {
                $(".ui-dialog-buttonpane button:contains('Get SMS Code')").button('enable');
                $(".ui-dialog-buttonpane button:contains('Unlock')").button('disable');

                smsArea.hide();
                codeArea.show();
                payamakpars_SMS.clearArea();

                var code = $("#payamak-pars-ver-code").val();

                if (/^[1-9][0-9]{3,3}$/.test(code)) {
                    //Submit
                    payamakpars_SMS.clearArea();
                    statusArea.html(payamakpars_sms.processing_msg);
                    payamakpars_SMS.unlock(code);

                } else {
                    $("#payamak-pars-ver-code-err").html(payamakpars_sms.invalid_number);
                }
            },
            'Unlock':function(){
                $(".ui-dialog-buttonpane button:contains('I have code')").button('disable');
                $(".ui-dialog-buttonpane button:contains('Get SMS Code')").button('disable');

                var code = $("#payamak-pars-ver-code").val();
                payamakpars_SMS.clearArea();

                if (/^[1-9][0-9]{3,3}$/.test(code)) {
                    //Submit
                    payamakpars_SMS.clearArea();
                    statusArea.html(payamakpars_sms.processing_msg);
                    payamakpars_SMS.unlock(code);

                } else {
                    $("#payamak-pars-ver-code-err").html(payamakpars_sms.invalid_number);
                }
            },
            "Get SMS Code": function(){
                smsArea.show();
                codeArea.hide();
                payamakpars_SMS.clearArea();

                //Validate
                var mob = $("#payamak-pars-mob-no").val();

                if (/^[0-9]+$/.test(mob)){
                    smsArea.hide();
                    codeArea.show();
                    $(".ui-dialog-buttonpane button:contains('Get SMS Code')").button("disable");
                    $(".ui-dialog-buttonpane button:contains('Unlock')").button("disable");
                    statusArea.html(payamakpars_sms.sending_msg);

                    //Send SMS
                    payamakpars_SMS.getSms(mob);

                }else{
                    numberError.html(payamakpars_sms.invalid_number);
                }

            }
        }
    });

    $( ".payamak-pars-pop" ).live('click', function(){
        $("#dialog-form-mobile").dialog("open");
        smsArea.show();
        codeArea.hide();
        $(".ui-dialog-buttonpane button:contains('Unlock')").button("disable");
        $(".ui-dialog-buttonpane button:contains('I have code')").button("enable");
        $("#payamak-pars-ver-code-err").html("");
        $("#payamak-pars-mob-no-err").html("");
        $("#payamak-pars-ver-code").val("");
        $("#payamak-pars-mob-no").val("");

        return false;
    });

    $( "#tabs" ).tabs();
})(jQuery);
