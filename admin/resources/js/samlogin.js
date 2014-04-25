function samlogin_saveSettings(formSourceSuffix) {
    /* if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_GENKEY')) ){
     if (!Joomla.popupWindow){
     Joomla.popupWindow=window.popupWindow; //old joomla version have it global
     }
     Joomla.popupWindow(window.SAMLOGIN_JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=genkey", Joomla.JText._('SAMLOGIN_JS_REGENKEY_POPUP_TITLE') , 990, 600, 1);
     }*/
 
        try {
            window.clearTimeout(window.samlogin_configTestTimeout);
        } catch (ie) {
        }

        samlogin_showToaster("Saving Settings...","success");

jQuery(".saveSettingsButton").removeClass("uk-button-primary");

        jQuery.ajax({
            url: window.samloginBaseAjaxURL+"&task=saveSettings",
            dataType: "json",
            data: jQuery(".samloginParamForm_"+formSourceSuffix).serialize(),
            type: "post"
        }).done(function(data) {
            samlogin_processMessages(data);
            setTimeout(function() {
                samlogin_doConfigTests();
            }, 1500);
        });
    
}



function samlogin_regenkeys() {
    /* if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_GENKEY')) ){
     if (!Joomla.popupWindow){
     Joomla.popupWindow=window.popupWindow; //old joomla version have it global
     }
     Joomla.popupWindow(window.SAMLOGIN_JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=genkey", Joomla.JText._('SAMLOGIN_JS_REGENKEY_POPUP_TITLE') , 990, 600, 1);
     }*/
    if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_GENKEY')) ){
        try {
            window.clearTimeout(window.samlogin_configTestTimeout);
        } catch (ie) {
        }

        samlogin_showToaster("Generating a new key and rotating old (this will change your SP metadata)","warning");

        jQuery.ajax({
            url: window.samloginBaseAjaxURL,
            dataType: "json",
            data: {
                task: "genkey",
            }
        }).done(function(data) {
            samlogin_processMessages(data);
            setTimeout(function() {
                samlogin_doConfigTests();
            }, 1000);
        });
    }
}

function samlogin_keyRotateEndPeriod() {
    
    /*if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_ROTATEKEY')) ){
     if (!Joomla.popupWindow){
     Joomla.popupWindow=window.popupWindow; //old joomla version have it global
     }
     Joomla.popupWindow(window.SAMLOGIN_JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=keyRotateEndPeriod", Joomla.JText._('SAMLOGIN_JS_REGENKEY_POPUP_TITLE') , 990, 600, 1);
     }*/
    if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_ROTATEKEY')) ){
        try {
            window.clearTimeout(window.samlogin_configTestTimeout);
        } catch (ie) {
        }

        samlogin_showToaster("Ending key-rotation period... (this changed your SP metadata)","warning");
        jQuery.ajax({
            url: window.samloginBaseAjaxURL,
            dataType: "json",
            data: {
                task: "keyRotateEndPeriod",
            }
        }).done(function(data) {
            samlogin_processMessages(data);
            setTimeout(function() {
                samlogin_doConfigTests();
            }, 1000);
        });
    }
}

window.samlogin_configTestTimeout = 0;

function samlogin_saveSSPConf() {
    /*if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_WRITESSP')) ){
     if (!Joomla.popupWindow){
     Joomla.popupWindow=window.popupWindow; //old joomla version have it global
     }
     Joomla.popupWindow(window.SAMLOGIN_JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=saveSSPConf", Joomla.JText._('SAMLOGIN_JS_REGENKEY_POPUP_TITLE') , 990, 600, 1);
     }*/
    try {
        window.clearTimeout(window.samlogin_configTestTimeout);
    } catch (ie) {
    }
    samlogin_showToaster("Applying configuration to simpleSAMLphp...","warning");
    jQuery.ajax({
        url: window.samloginBaseAjaxURL,
        dataType: "json",
        data: {
            task: "saveSSPConf",
        }
    }).done(function(data) {
        samlogin_processMessages(data);
        setTimeout(function() {
            samlogin_doConfigTests();
        }, 1000);
    });
}

function samlogin_showToaster(msg,level){
      jQuery.UIkit.notify(msg, {status: level, pos: "top-right"});
}

function  samlogin_processMessages(data) {
    if (data.additionalMessages && data.additionalMessages.length > 0) {
        var i = -1;
        while (++i < data.additionalMessages.length) {
            jQuery.UIkit.notify(data.additionalMessages[i].msg, {status: data.additionalMessages[i].level, pos: "top-right"});
        }
    }
}

function samlogin_installSSP(version,migrationMode) {
    jQuery(".install-ssp-modal-a").hide();
    jQuery(".install-ssp-modal-b").show();
    var tm1 = setTimeout(function() {
        jQuery(".install-ssp-modal-b .uk-progress-bar").css("width", "7%");
    }, 500);
    var tm2 = setTimeout(function() {
        jQuery(".install-ssp-modal-b .uk-progress-bar").css("width", "7%");
    }, 1500);
  
    if (migrationMode==undefined){
        migrationMode=0;
    }else{
        migrationMode=1;
    }
       
    jQuery.ajax({
        url: window.samloginBaseAjaxURL,
        dataType: "json",
        data: {
            task: "installSimpleSAMLphp_download",
            dlid: version,
            migrationMode:migrationMode
        }
    }).done(function(data) {
        samlogin_processMessages(data);
        if (data.bytes) {
            jQuery(".install-ssp-modal-b-msg").html(jQuery(".install-ssp-modal-b-msg").html() + " (done, " + data.bytes + " bytes written) ");
            jQuery(".install-ssp-modal-b .uk-progress-bar").css("width", "65%");
            jQuery.ajax({
                url: window.samloginBaseAjaxURL,
                dataType: "json",
                data: {
                    task: "installSimpleSAMLphp_extract",
                    dlid: version,
                    migrationMode:migrationMode
                }
            }).done(function(data) {
                try {
                    window.clearTimeout(tm1);
                } catch (ie) {
                }
                try {
                    window.clearTimeout(tm2);
                } catch (ie) {
                }

                samlogin_processMessages(data);
                if (data.versionInfo) {
                    jQuery(".install-ssp-modal-b-msg").html(jQuery(".install-ssp-modal-b-msg").html() + "<hr/> (done, version info: " + data.versionInfo + ") ");
                    jQuery(".install-ssp-modal-b .uk-progress-bar").css("width", "100%");
                    setTimeout(function() {
                        location.reload(true);
                    }, 6000);

                } else {

                    jQuery(".install-ssp-modal-a").show();
                    jQuery(".install-ssp-modal-b").hide();
                }
            }).fail(function(e) {
                try {
                    window.clearTimeout(tm1);
                } catch (ie) {
                }
                try {
                    window.clearTimeout(tm2);
                } catch (ie) {
                }
                jQuery(".install-ssp-modal-b .uk-progress-bar").css("width", "0%");
                jQuery(".install-ssp-modal-b-msg").html("Failed. AJAX request error #SLAJX002");
            });

        } else {
            jQuery(".install-ssp-modal-a").show();
            jQuery(".install-ssp-modal-b").hide();
        }
    }).fail(function(e) {
        jQuery(".install-ssp-modal-b .uk-progress-bar").css("width", "0%");
        jQuery(".install-ssp-modal-b-msg").html("Failed. AJAX request error #SLAJX001");
    });

    /*if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_INSTALLSSP')) ){
     if (!Joomla.popupWindow){
     Joomla.popupWindow=window.popupWindow; //old joomla version have it global
     }
     Joomla.popupWindow(window.SAMLOGIN_JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=installSimpleSAMLphp", Joomla.JText._('SAMLOGIN_JS_INSTALLSSP_POPUP_TITLE') , 990, 600, 1);
     }*/
}

