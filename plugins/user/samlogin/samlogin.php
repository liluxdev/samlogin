<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgUserSamlogin extends JPlugin {

    function plgUserSamlogin(&$subject, $config) {
        parent::__construct($subject, $config);
    }

    /**
     * This method should handle any logout logic and report back to the subject
     *
     * @param   array  $user		Holds the user data.
     * @param   array  $options	Array holding options (client, ...).
     *
     * @return  object  True on success
     * @since   1.5
     */
    public function onUserLogout($user, $options = array()) {
        $my = JFactory::getUser();
        $session = JFactory::getSession();
        $app = JFactory::getApplication();

        jimport('joomla.application.component.helper');
        $samloginParams = JComponentHelper::getParams('com_samlogin');
        //    //phpconsole("params are: " . print_r($samloginParams->toArray(), true), "rastrano");
        // Include the externally configured SimpleSAMLphp instance 

        $SAMLoginIsAuthN = $session->get("SAMLoginIsAuthN", false);
        $SAMLoginPreventDoubleLogout = $session->get("SAMLoginPreventDoubleLogout", false);
        //die("debugging".print_r($SAMLoginPreventDoubleLogout,true));
        $session->set("SAMLoginPreventDoubleLogout", false); //for avoiding deadlocks
        if ($SAMLoginIsAuthN && !$SAMLoginPreventDoubleLogout) {
            $relayState = JRequest::getString("return");
            $rediSLO = JRoute::_('index.php?option=com_samlogin&view=login&task=initSLO&return=' . $relayState, true);
            $app->redirect($rediSLO);
        }
    }

}
