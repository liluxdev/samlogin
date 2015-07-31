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
        //if (! ($app->isAdmin() && $session->get("SAMLoginIdP", "")=="Facebook") ){ //TODO: implement fb logout
          //  $app->logout();
        
        jimport('joomla.application.component.helper');
        if (version_compare(JVERSION, '1.6.0', 'ge')) {
                $componentParams = JComponentHelper::getParams('com_samlogin');
            } else {
                $component = JComponentHelper::getComponent('com_samlogin');
                $componentParams = new JParameter($component->params);
            }
        //    //phpconsole("params are: " . print_r($samloginParams->toArray(), true), "rastrano");
        // Include the externally configured SimpleSAMLphp instance 

        if ($session->get("SAMLoginIdP", "")=="Facebook"){ 
            $SAMLoginIsAuthN = $session->get("SAMLoginIsAuthN", false);
            $SAMLoginPreventDoubleLogout = $session->get("SAMLoginPreventDoubleLogout", false);
            //die("debugging".print_r($SAMLoginPreventDoubleLogout,true));
            $session->set("SAMLoginPreventDoubleLogout", false); //for avoiding deadlocks
            if ($SAMLoginIsAuthN && !$SAMLoginPreventDoubleLogout) {
                $relayState = JRequest::getString("return");
                $rediSLO = JRoute::_(JURI::root().'index.php?option=com_samlogin&view=login&task=initFacebookSLO&return=' . $relayState, true);
              // die($rediSLO);
                $app->redirect($rediSLO);
            }
            }else{
            
                $SAMLoginIsAuthN = $session->get("SAMLoginIsAuthN", false);
                $SAMLoginPreventDoubleLogout = $session->get("SAMLoginPreventDoubleLogout", false);
                //die("debugging".print_r($SAMLoginPreventDoubleLogout,true));
                $session->set("SAMLoginPreventDoubleLogout", false); //for avoiding deadlocks
                if ($SAMLoginIsAuthN && !$SAMLoginPreventDoubleLogout) {
                    $relayState = JRequest::getString("return");
                    $rediSLO = JRoute::_(JURI::root().'index.php?option=com_samlogin&view=login&task=initSLO&return=' . $relayState, true);
                  // die($rediSLO);
                    $app->redirect($rediSLO);
                }
             }
        }
       
       
    
    
    public static function cleanSessionViaCookie(){
         if (isset($_SERVER['HTTP_COOKIE'])) {
                            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                            $frontendSessId = JFactory::getApplication()->input->cookie->get(md5(JApplication::getHash('site')));
                            $backendSessId = JFactory::getApplication()->input->cookie->get(md5(JApplication::getHash('administrator')));
                            //print "<hr/>front: ".$frontendSessId;
                            // print "<hr/>back: ".$backendSessId;
                            //print "<hr/>sessname: ".$sessname;
                            $sessionCookieValueToRemove = array(
                                $frontendSessId,
                                $backendSessId
                            );

                            $sessionCookieNameToRemove = array(
                                "SAMLoginCookieAuthToken",
                                "SAMLoginSimpleSAMLSessionID"
                            );
                            // die(print_r($cookies,true));

                            foreach ($cookies as $cookie) {

                                $parts = explode('=', $cookie);
                                $name = trim($parts[0]);
                                $value = trim($parts[1]);
                                if (in_array($value, $sessionCookieValueToRemove) || in_array($name, $sessionCookieNameToRemove)) {
                                    setcookie($name, '', 1);
                                    setcookie($name, '', 1, '/');
                                }
                            }
        }
    }

}
