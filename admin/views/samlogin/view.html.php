<?php

// no direct access
defined('_JEXEC') or die;

class SAMLoginViewSamlogin extends SAMLoginView
{
    function display($tpl = null) {
        $doc = JFactory::getDocument();
        $app= JFactory::getApplication();
        $app->redirect(JURI::base()."?option=com_samlogin&view=ajax");
    }

}
