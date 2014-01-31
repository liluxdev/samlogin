<?php

defined('_JEXEC') or die;
jimport('joomla.application.component.controlleradmin');

class SAMLoginControllerLogin extends SAMLoginController {

    public function display($cachable = false) {
        parent::display($cachable);
    }

    const DISCOTYPE_ONE_IDP_ONLY = "-1";

    public function initSSO() {
        $app = JFactory::getApplication();

        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        $return = JRequest::getVar('return', null, 'GET', 'BASE64');
        if (!is_null($return)) {
            $extraReturnURLParams .= "&rret=" . $return;
        } else {
            $extraReturnURLParams .= "&rret=" . base64_encode(JURI::root(true));  //default to homepage
        }

        $idp = JRequest::getVar('idp', null, 'GET', 'STRING');
        $discotype = $params->get("sspas_discotype", "0");
        $singleIDP = $params->get("sspas_idpentityid", "");
        if ($discotype == self::DISCOTYPE_ONE_IDP_ONLY) {
            if (empty($singleIDP)) {
                $this->handleError("The \"No discovery service mode\" requires you to provide an IdP entityID");
            } else {
                $idp = $singleIDP;
            }
        }
        if (!is_null($idp)) {
            $extraReturnURLParams .= "&idp=" . $idp;
        }


        $returnTo = JURI::root() . '/components/com_samlogin/loginReceiver.php?task=initSSO' . $extraReturnURLParams;
        $app->redirect($returnTo);
    }

    public function _deprecatedinitSSO() {

        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        $return = JRequest::getVar('return', null, 'GET', 'BASE64');
        if (!is_null($return)) {
            $extraReturnURLParams .= "&rret=" . $return;
        }
        /* echo JRoute::_('index.php?option=com_users&view=reset');
          echo JRoute::_('index.php?option=com_samlogin&view=login&task=initSSO');
          die(""); */
        require_once(JPATH_BASE . '/components/com_samlogin/simplesamlphp/lib/_autoload.php');
        $auth = new SimpleSAML_Auth_Simple('default-sp');



        $returnTo = JURI::root() . '/components/com_samlogin/loginReceiver.php?task=login' . $extraReturnURLParams;
        //  phpconsole("Setting callback url to " . $returnTo, "rastrano");
        //  if (!$auth->isAuthenticated()) {
        $auth->login(array(
            "isPassive" => FALSE,
            "ErrorURL" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleErr' . $extraReturnURLParams),
            //"ReturnTo" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleSuccess' . $extraReturnURLParams),
            "ReturnTo" => $returnTo,
            "KeepPost" => FALSE
        ));
        //   }
    }

    public function initSLO() {

        $app = JFactory::getApplication();


        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        $return = JRequest::getVar('return', null, 'GET', 'BASE64');
        if (!is_null($return)) {
            $extraReturnURLParams .= "&rret=" . $return;
        }
        $trySLO = $params->get("trysinglelogout", 0) == 1;
        if ($trySLO) {
            $extraReturnURLParams .= "&trySLO=1";
        }
        $returnTo = JURI::root() . '/components/com_samlogin/loginReceiver.php?task=initSLO' . $extraReturnURLParams;
        $app->redirect($returnTo);
        //   }
    }

    public function finishSLO() {
        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $app->logout();
        $user = JFactory::getUser();
        if (!$user->guest) {
            $this->handleError("Joomla LogOut Failed");
        } else {

            $rret = JRequest::getVar('rret', null, 'GET', 'BASE64');
            $msg = JRequest::getVar('msg', null, 'GET', 'STRING');

            if (is_null($rret) && isset($msg) && !empty($msg)) {
                $errUrl = JRoute::_('index.php?option=com_samlogin&view=login&task=logoutAlert&msg=' . $msg);
                //  phpconsole("Errurl: ".$errUrl,"rastrano");
                $app->redirect($errUrl);
            } else {
                $errtype = "error";
                if (stripos($msg, "SLOE0") === "0") {
                    $errtype = "";
                }
                if (!isset($msg)) {
                    $msg = "SHIBBOLETHSLO";
                }
                $custommsg = $params->get("logout_customhtmlmessage", "");

                $translatedmsg = JText::_("SAMLOGIN_LOGOUT_ALERT_$msg");
                if ($errtype == "notice") {
                    if (!empty($custommsg)) {
                        $translatedmsg = $custommsg;
                    }
                }



                //$app->enqueueMessage($translatedmsg, $errtype);

                if (!is_null($rret)) {
                    $return = base64_decode($rret);
                    //  die("redirecting to ".$return." with msg".$translatedmsg." type: ".$errtype);
                    //FIXME: not works: cookie unset by the joomla logout TODO: param to enforce handleMessage redirect
                    $sess->set("sloErrMsg", $translatedmsg);
                    $sess->set("sloErrType", $errtype);
                    //2 scopes: to persist the message and to prevent a redirect bug that doesn't show message
                    // $app->redirect($return,$translatedmsg,$errtype);
                    $errUrl = JRoute::_('index.php?option=com_samlogin&view=login&task=logoutAlert&msg=' . $msg);
                    //  phpconsole("Errurl: ".$errUrl,"rastrano");
                    $alwaysShowLogoutAlert = $params->get("alwayslogoutalert", 0) == 1;
                    if ($alwaysShowLogoutAlert) {
                        $app->redirect($errUrl);
                    } else {
                        $app->redirect($return);
                    }
                }
            }
        }
    }

    public function handleSAMLResponse() {
        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $loginStatus = $app->login(array('username' => '', 'password' => ''));

        $user = JFactory::getUser();
        if (!$user->guest) {
            $rret = JRequest::getVar('rret', null, 'GET', 'BASE64');
            if (!is_null($rret)) {
                $return = base64_decode($rret);
                //  phpconsole("rret decoded is ".$return,"rastrano");
                $app->redirect($return);
            }
        } else {
            $this->handleError("JOOMLA_LOGIN_FAILED");
        }
    }

    public function handleError($msg = "") {
        if ($msg == "") {
            $msg = JRequest::getVar('msg', "", 'GET', 'STRING');
        }
        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');
        if (stripos($msg, "SLOE") == "0") {
            $errtype = "notice";
        } else {
            $errtype = "error";
        }
        $app->enqueueMessage(JText::_("SAMLOGIN_ERROR_ALERT_$msg"), $errtype);
        // $app->redirect(JURI::root());
    }

    public function logoutAlert($msg = "") {
        if ($msg == "") {
            $msg = JRequest::getVar('msg', "", 'GET', 'STRING');
        }
        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');
        if (stripos($msg, "SLOE") == "0") {
            $errtype = "notice";
        } else {
            $errtype = "error";
        }
        $custommsg = $params->get("logout_customhtmlmessage", "");

        $translatedmsg = JText::_("SAMLOGIN_LOGOUT_ALERT_$msg");

        if ($errtype == "notice") {
            if (!empty($custommsg)) {
                $translatedmsg = $custommsg;
            }
        }

//render the view!
        $vName = 'message';
        $vFormat = 'html'; // raw
        if ($view = $this->getView($vName, $vFormat)) {
            $view->assignRef('message', $translatedmsg);
            $view->display();
        } else {
            $app->enqueueMessage(JText::_("SAMLOGIN_LOGOUT_ALERT_$msg"), $errtype);
        }


        // $app->redirect(JURI::root());
    }

    public function logoutAlertOld($msg = "") {
        if ($msg == "") {
            $msg = JRequest::getVar('msg', "", 'GET', 'STRING');
        }
        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');
        if (stripos($msg, "SLOE") == "0") {
            $errtype = "notice";
        } else {
            $errtype = "error";
        }
        $custommsg = $params->get("logout_customhtmlmessage", "");

        $translatedmsg = JText::_("SAMLOGIN_LOGOUT_ALERT_$msg");
        if ($errtype == "notice") {
            if (!empty($custommsg)) {
                $translatedmsg = $custommsg;
            }
        }

        $app->enqueueMessage($translatedmsg, $errtype);


        // $app->redirect(JURI::root());
    }

    /**
     * @deprecated since version number

      public function handleSuccess() {
      $app = JFactory::getApplication();
      $sess = JFactory::getSession();
      $user = JFactory::getUser();
      $params = JComponentHelper::getParams('com_samlogin');

      $app->login(array('username' => '', 'password' => ''));

      $user = JFactory::getUser();
      if (!$user->guest) {
      $return = JRequest::getVar('rret', null, 'GET', 'BASE64');
      if (!is_null($return)) {
      $return = base64_decode($rret);
      $app->redirect($return);
      }
      }  else {
      $this->handleError("Joomla Login Failed");
      }
      }
     * 
     * 
     */
}
