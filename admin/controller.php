<?php
class SAMLoginController extends SAMLoginController
{
    /**
     * display task
     *
     * @return void
     */
    function display($cachable = false, $urlparams = false) 
    {
        $defaultView="ajax";
        // set default view if not set
        JRequest::setVar('view', JRequest::getCmd('view', $defaultView));

        // call parent behavior
        parent::display($cachable);

        SAMLoginHelper::addSubmenu('messages');
	}
}
