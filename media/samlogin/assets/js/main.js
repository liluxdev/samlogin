/**
 * 
  creativeprograming.it 
 * 
 */

/**
 * CONFIGURATION PARAMETERS
 */

/**
 * Configure here your language strings
 */
window._SUBMITTING_REQUEST_STR      =   "Meldingen er sendt";
window._EMPTY_EMAIL_STR             =   "Vennligst oppgi e-postadresse";
window._INVALID_EMAIL_STR           =   "Ugyldig e-postadresse. Vennligst kontroller";
window._EMPTY_NAME_STR              =   "Vennligst oppgi firmanavn";
window._INVALID_NAME_STR            =   "Ugyldig firmanavn. Vennligst kontroller";

/* Configure here your tooltip font styling */
window._TOOLTIP_MESSAGE_STYLE_CSS   =   "font-weight:bold; color:tomato;";
/* How long should the tooltip stay on? in seconds. */
window._TOOLTIP_DURATION_IN_SECS    =   7;
/* Configure here the message showing methods (true= enabled, false= disabled): */
window._MESSAGE_SHOWING_METHODS     =  { tooltipOnError: true, toasterOnError:false, toasterOnSubmitting:true};

/* Put it to true to enable classical form submitting (false for ajax)*/
window.backupSubmitting = false;
/**
 * END CONFIGURATION PARAMETERS, CODE SECTION FOLLOWING
 */




if (window._MESSAGE_SHOWING_METHODS.toasterOnError==undefined || window._MESSAGE_SHOWING_METHODS.tooltipOnError==undefined || window._MESSAGE_SHOWING_METHODS.toasterOnSubmitting==undefined 
           || (window._MESSAGE_SHOWING_METHODS.toasterOnError==false && window._MESSAGE_SHOWING_METHODS.tooltipOnError==false) ){
        alert("Error in configuration of main.js, ensure to respect the syntax and that at least one error showing method is enabled");
}





jQuery(document).ready(function() {
    var formSelector = "form[name=contactform]";
    jQuery(formSelector).on("submit", function(e) {
        var firstname_input = jQuery("input[name=first_name]");
        var email_input = jQuery("input[name=email]");
        
        var firstname = firstname_input.val();
        var email = email_input.val();

        if (!validateFirstName(firstname, firstname_input)) {

            e.preventDefault();
            return false;
        }

        if (!validateEmail(email, email_input)) {

            e.preventDefault();
            return false;
        }

        if (!window.backupSubmitting){
            ajaxSubmitForm(formSelector);
            /* this is to workaround a strange IE <= 9 bug */
            window.ieErrorFixResubmitter = setInterval(function() {
                ajaxSubmitForm(formSelector);
            }, 3000);
        }


        jQuery(".formpiece").addClass("hiddenAnimate");
        if (window._MESSAGE_SHOWING_METHODS.toasterOnSubmitting){
            jQuery.UIkit.notify("<i class='uk-icon-check'></i> "+window._SUBMITTING_REQUEST_STR+"...", {status: 'success', pos: 'bottom-center'});
        }
        if (window.backupSubmitting) {
            return true;
        }
        e.preventDefault();
        return false;
    });

});

window.ieErrorFixResubmitter = null;


function ajaxSubmitForm(formSelector) {
    var url = jQuery(formSelector).attr("action"); // the script where you handle the form input.
    var formdata = jQuery(formSelector).serialize();

    jQuery.ajax({
        type: "POST",
        url: url,
        data: formdata, // serializes the form's elements.
        success: function(data)
        {
            try {
                window.clearInterval(window.ieErrorFixResubmitter);
            } catch (ie) {

            }
            var msg = "<i class='uk-icon-check'></i> " + data;
            jQuery(".formrealpiece").html("<td colspan='19' style='text-align:center; width: 100%; color: green;'>" + msg + "</td>");
            jQuery(".formrealpiece").removeClass("hiddenAnimate");
            //   jQuery.UIkit.notify(msg, {status: 'success', pos: 'bottom-center'});

        }
    });



}

function validateEmail(v, i) {
    if (v == undefined || v == null || v == "") {
        popalert(window._EMPTY_EMAIL_STR, i);
        return false;

    }
    if (v.length < 3 || !v.match(/^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/)) {
        popalert(window._INVALID_EMAIL_STR, i);
        return false;
    }
    return true;
}

function validateFirstName(v, i) {
    if (v == undefined || v == null || v == "") {
        popalert(window._EMPTY_NAME_STR, i);
        return false;
    }

    if (v.length < 3 || !v.match(/^[ÆØÅæøåA-Za-z0-9 .'-]+$/)) {
        popalert(window._INVALID_NAME_STR, i);
        return false;
    }

    return true;
}

function popalert(msg, i) {
   if(window._MESSAGE_SHOWING_METHODS.toasterOnError){ 
        jQuery.UIkit.notify(msg, {status: 'warning', pos: 'bottom-center'});
   }
   if(window._MESSAGE_SHOWING_METHODS.tooltipOnError){ 
        try {
            i.tooltip('destroy');
        } catch (ie) {
        }
        i.tooltip({html: true, title: '<span style="'+window._TOOLTIP_MESSAGE_STYLE_CSS+'">' + msg + '</span>'
            , placement: "top"}).tooltip("show")
        setTimeout(function() {
            try {
                i.tooltip('hide');
            } catch (ie) {
            }
            try {
                i.tooltip('destroy');
            } catch (ie) {
            }
        }, window._TOOLTIP_DURATION_IN_SECS*1000);
   }
   i.focus();

}
