function samlogin_regenkeys(){
    if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_GENKEY')) ){
        if (!Joomla.popupWindow){
            Joomla.popupWindow=window.popupWindow; //old joomla version have it global
        }
        Joomla.popupWindow(window.JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=genkey", Joomla.JText._('SAMLOGIN_JS_REGENKEY_POPUP_TITLE') , 990, 600, 1);
    }
}

function samlogin_keyRotateEndPeriod(){
    if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_ROTATEKEY')) ){
        if (!Joomla.popupWindow){
            Joomla.popupWindow=window.popupWindow; //old joomla version have it global
        }
        Joomla.popupWindow(window.JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=keyRotateEndPeriod", Joomla.JText._('SAMLOGIN_JS_REGENKEY_POPUP_TITLE') , 990, 600, 1);
    }
}



function samlogin_saveSSPConf(){
    if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_WRITESSP')) ){
        if (!Joomla.popupWindow){
            Joomla.popupWindow=window.popupWindow; //old joomla version have it global
        }
        Joomla.popupWindow(window.JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=saveSSPConf", Joomla.JText._('SAMLOGIN_JS_REGENKEY_POPUP_TITLE') , 990, 600, 1);
    }
}


function samlogin_installSSP(){
    if (confirm(Joomla.JText._('SAMLOGIN_JS_CONFIRM_INSTALLSSP')) ){
        if (!Joomla.popupWindow){
            Joomla.popupWindow=window.popupWindow; //old joomla version have it global
        }
        Joomla.popupWindow(window.JS_JOOMLA_JURI_ADMIN_BASE+"?option=com_samlogin&view=ajax&task=installSimpleSAMLphp", Joomla.JText._('SAMLOGIN_JS_INSTALLSSP_POPUP_TITLE') , 990, 600, 1);
    }
}

