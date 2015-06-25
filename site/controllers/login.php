<?php

defined('_JEXEC') or die;
jimport('joomla.application.component.controlleradmin');

class SAMLoginControllerLogin extends SAMLoginController {

    public function display($cachable = false) {
        parent::display($cachable);
    }

    const DISCOTYPE_ONE_IDP_ONLY = "-1";

    public function initFacebookSSO() {
        //   file_put_contents(JPATH_BASE . "/samlogin.debug", "\n\n ========= \nnew Facebook login initSSO phase: " . print_r($_REQUEST, true), FILE_APPEND);
        //  define('FACEBOOK_SDK_V4_SRC_DIR', JPATH_COMPONENT_SITE.'/libs/facebook-sdk/');
        require JPATH_COMPONENT_SITE . '/libs/facebook-sdk/autoload.php';

        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        //   $return = JRequest::getVar('return', null, 'GET', 'BASE64');
        $return = JRequest::getString('return');
        JFactory::getSession()->set("rret", $return);

        // JFactory::getSession()->close(true);
        $returnAfterFacebook = JURI::root() . 'index.php?option=com_samlogin&view=login&task=finishFacebookSSO'; /* ,$params->get('usesecure', false) */;
//die($returnAfterFacebook);
        Facebook\FacebookSession::setDefaultApplication($params->get("fb_appid", ""), $params->get("fb_appsecret", ""));

        $helper = new Facebook\SamloginFacebookRedirectLoginHelper($returnAfterFacebook);
        $scope = array("email");
        $loginUrl = $helper->getLoginUrl($scope);
        // die($loginUrl);
        $app->redirect($loginUrl);
    }

    public function initSSO() {

        $app = JFactory::getApplication();

        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        //   $return = JRequest::getVar('return', null, 'GET', 'BASE64');


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
            $extraReturnURLParams .= "&idp=" . urlencode($idp);
        }

        if ($discotype == "wsfed" || $discotype == "wsfed-enforced") {
            $wsfedsp = $params->get("wsfed_idp_realm_1", JURI::root());
            //    die($wsfedsp);
            $wsfedidp = $params->get("wsfed_idp_issuer_1", "please check you wsfed config");
            $extraReturnURLParams = "&proto=wsfed&wsfedidp=" . urlencode($wsfedidp) . "&wsfedsp=" . urlencode($wsfedsp);
        }

        $return = JRequest::getString('return');
        if (!is_null($return)) {
            $extraReturnURLParams .= "&rret=" . $return;
        } else {
            $extraReturnURLParams .= "&rret=" . base64_encode("/");  //default to homepage //no 
        }

        $returnTo = JURI::root() . '/components/com_samlogin/loginReceiver.php?task=initSSO' . $extraReturnURLParams;

        if (preg_match('/(?i)msie [1-8]/', $_SERVER['HTTP_USER_AGENT'])) {
            // if IE<=8
            echo("<script type='text/javascript'>window.location.href='$returnTo';</script><a href='" . $returnTo . "'>click here if you don't get automatically redirected to the login page...</a>");
            die();
        }
        /*   if (!stristr($returnTo,"http")){
          $returnTo= JURI::root().$return;
          $returnTo=strtr($returnTo,array(JURI::root()."/"=>JURI::root()));
          } */
        $app->redirect($returnTo);
    }

    public function _deprecatedinitSSO() {

        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        //$return = JRequest::getVar('return', null, 'GET', 'BASE64');
        $return = JRequest::getString('return');
        if (!is_null($return)) {
            $extraReturnURLParams .= "&rret=" . $return;
        }
        /* echo JRoute::_('index.php?option=com_users&view=reset');
          echo JRoute::_('index.php?option=com_samlogin&view=login&task=initSSO');
          //die(""); */
        require_once(JPATH_BASE . '/components/com_samlogin/simplesamlphp/lib/_autoload.php');
        $auth = new SimpleSAML_Auth_Simple('default-sp');



        $returnTo = JURI::root() . '/components/com_samlogin/loginReceiver.php?task=login' . $extraReturnURLParams;
        //  phpconsole("Setting callback url to " . $returnTo, "rastrano");
        //  if (!$auth->isAuthenticated()) {
        $auth->login(array(
            "isPassive" => FALSE,
            "ErrorURL" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleErr' . $extraReturnURLParams, false),
            //"ReturnTo" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleSuccess' . $extraReturnURLParams),
            "ReturnTo" => $returnTo,
            "KeepPost" => FALSE
        ));
        //   }
    }

    public function initFacebookSLO() {

        $app = JFactory::getApplication();


        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');


        $extraReturnURLParams = "";
        //  $return = JRequest::getVar('return', null, 'GET', 'BASE64');
        $return = JRequest::getString('return');
        if (!is_null($return)) {
            $extraReturnURLParams .= "&rret=" . $return;
        }


        $sess->set("SAMLoginIsAuthN", false);
        require JPATH_COMPONENT_SITE . '/libs/facebook-sdk/autoload.php';

        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        //   $return = JRequest::getVar('return', null, 'GET', 'BASE64');
        $return = JRequest::getString('return');
        if ($this->is_base64($return)) {
            $return = base64_decode($return);
        }
        JFactory::getSession()->set("rret", $return);
        // die(var_dump($params->toArray()));
        if ($params->get("fb_fulllogout", 0)) {
            // JFactory::getSession()->close(true);
            $returnAfterFacebook = JURI::root() . 'index.php?option=com_samlogin&view=login&task=finishFacebookSSO'; /* ,$params->get('usesecure', false) */;
//die($returnAfterFacebook);
            Facebook\FacebookSession::setDefaultApplication($params->get("fb_appid", ""), $params->get("fb_appsecret", ""));
            // die($returnAfterFacebook);
            $fbsess = new Facebook\FacebookSession($sess->get("FacebookAccessToken"));
            $helper = new Facebook\SamloginFacebookRedirectLoginHelper("");
            $logoutUrl = $helper->getLogoutUrl($fbsess, JURI::root() . $return);

            // die($logoutUrl);
            $app->logout();
            $app->redirect($logoutUrl);
        } else { //NOrmal SSO case
            //Destroy only local fb session
            self::cleanSessionViaCookie();
            if (!stristr($return, "http")) {
                $return = JURI::root() . strtr($return,array(''.JURI::root(true)=>'')); //remove double path 
                $return = strtr($return, array(JURI::root() . "/" => JURI::root()));
               

            }
            $app->redirect($return);
        }

        //   }
    }

    public static function cleanSessionViaCookie() {
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

    public function initSLO() {

        $app = JFactory::getApplication();


        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');

        $extraReturnURLParams = "";
        //  $return = JRequest::getVar('return', null, 'GET', 'BASE64');
        $return = JRequest::getString('return');
        if (!is_null($return)) {
            $extraReturnURLParams .= "&rret=" . $return;
        }

        $trySLO = $params->get("trysinglelogout", 1) == 1;
        if ($trySLO) {
            $extraReturnURLParams .= "&trySLO=1";
        }
        $isWSFED = $sess->get("SAMLoginIsWSFEDSession", false);
        if ($isWSFED) {
            $wsfedsp = $params->get("wsfed_idp_realm_1", JURI::root());
            //  die($wsfedsp);
            $wsfedidp = $params->get("wsfed_idp_issuer_1", "please check you wsfed config");
            $extraReturnURLParams .= "&proto=wsfed&wsfedidp=" . urlencode($wsfedidp) . "&wsfedsp=" . urlencode($wsfedsp) . "&dologout=1";
        }
        $returnTo = JURI::root() . '/components/com_samlogin/loginReceiver.php?task=initSLO' . $extraReturnURLParams;

        $sess->set("SAMLoginIsAuthN", false);
        /*   if (!stristr($returnTo,"http")){
          $returnTo= JURI::root().$returnTo;
          $returnTo=strtr($returnTo,array(JURI::root()."/"=>JURI::root()));
          } */
        $app->redirect($returnTo);
        //   }
    }

    public function is_base64($str) {
        return (bool) preg_match('`^[a-zA-Z0-9+/]+={0,2}$`', $str);
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

            // $rret = JRequest::getVar('rret', null, 'GET', 'BASE64');
            $rret = JRequest::getString('rret');
            $msg = JRequest::getVar('msg', null, 'GET', 'STRING');




            if (is_null($rret) && isset($msg) && !empty($msg)) {
                $errUrl = JRoute::_('index.php?option=com_samlogin&view=login&task=logoutAlert&msg=' . $msg, false);
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
                    if ($this->is_base64($rret)) {
                        $return = base64_decode($rret);
                    } else {
                        $return = $rret;
                    }
                    //    echo("redirecting to ".$return." with msg".$translatedmsg." type: ".$errtype);
                    //FIXME: not works: cookie unset by the joomla logout TODO: param to enforce handleMessage redirect
                    $sess->set("sloErrMsg", $translatedmsg);
                    $sess->set("sloErrType", $errtype);
                    //2 scopes: to persist the message and to prevent a redirect bug that doesn't show message
                    // $app->redirect($return,$translatedmsg,$errtype);
                    $errUrl = JRoute::_('index.php?option=com_samlogin&view=login&task=logoutAlert&msg=' . $msg, false);
                    //  phpconsole("Errurl: ".$errUrl,"rastrano");

                    $useCustomSLO = $params->get("useCustomSLO", 0) == 1;
                    if ($useCustomSLO) {
                        $customSLOURL = $params->get("customSLOURL", ''); //TODO implement variable $idp in custom slo url
                        //  die("@".__LINE__.$customSLOURL);
                        if (!empty($customSLOURL)) {
                            $app->redirect($customSLOURL);
                        } else {
                            $app->redirect($return);
                        }
                    }



                    $alwaysShowLogoutAlert = $params->get("alwayslogoutalert", 0) == 1;
                    if ($alwaysShowLogoutAlert) {
                        //   die("@".__LINE__.$errUrl);
                        $app->redirect($errUrl);
                    } else {
                        //  die("@".__LINE__.$return);
                        if (!stristr($return, "http")) {
                            $return = JURI::root() . strtr($return,array(''.JURI::root(true)=>'')); //remove double path
                            $return = strtr($return, array(JURI::root() . "/" => JURI::root()));
                        }
                        $app->redirect($return);
                    }
                }
            }
        }
    }

    public function finishFacebookSSO() {
        $user = JFactory::getUser();
        //die("testing at line".print_r($user,true).__LINE__);
        if (true || $user->guest) {
            $app = JFactory::getApplication();
            $sess = JFactory::getSession();
            $user = JFactory::getUser();
            $params = JComponentHelper::getParams('com_samlogin');
            // die("test");



            $returnAfterFacebookRebuilded = JURI::root() . 'index.php?option=com_samlogin&view=login&task=finishFacebookSSO'; /* ,$params->get('usesecure', false) */;
//die($returnAfterFacebookRebuilded);
            $rret = JFactory::getSession()->get("rret", JURI::root());


            if (!is_null($rret)) {
                if ($this->is_base64($rret)) {
                    $return = base64_decode($rret);
                } else {
                    $return = $rret;
                }
            }

            require JPATH_COMPONENT_SITE . '/libs/facebook-sdk/autoload.php';
            Facebook\FacebookSession::setDefaultApplication($params->get("fb_appid", ""), $params->get("fb_appsecret", ""));

            $helper = new Facebook\SamloginFacebookRedirectLoginHelper($returnAfterFacebookRebuilded);
            try {

                $session = $helper->getSessionFromRedirect();
                //die("test2");
                if ($session) {

                    $fbrequest = (new Facebook\FacebookRequest($session, 'GET', '/me'));
                    //  $me= new Facebook\GraphUser();
                    $me = $fbrequest->execute()->getGraphObject(Facebook\GraphUser::className());
                } else {

                    $errcode = "FACEBOOK_CONNECT_NOSESS";
                    $this->handleError("JOOMLA_LOGIN_FAILED_" . $errcode);
                    return;
                }
                //    die(print_r($me, true));
            } catch (FacebookRequestException $ex) {
                // When Facebook returns an error
                $errcode = "FACEBOOK_CONNECT_REQFAIL";
                $this->handleError("JOOMLA_LOGIN_FAILED_" . $errcode);
                return;
            } catch (Exception $ex) {
                // When validation fails or other local issues
                $errcode = "FACEBOOK_CONNECT_ERROR " . $ex->getMessage();
                $this->handleError("JOOMLA_LOGIN_FAILED_" . $errcode);
                return;
            }
            if ($session) {
                $currentSession = JFactory::getSession();
                $currentSession->set("FacebookAccessToken", $session->getToken());
                $currentSession->set("FacebookExchangeAccessToken", $session->getExchangeToken());
                $currentSession->set("SAMLoginIsAuthN", true);

                $currentSession->set("SAMLoginSession", null);
                $fbAttrs = array(
                    "givenName" => array($me->getFirstName()),
                    "sn" => array($me->getMiddleName() . "" . $me->getLastName()),
                    "fbid" => array($me->getId()),
                    "mail" => array($me->getEmail()),
                    "gender" => array($me->getGender()),
                    "fbURL" => array($me->getLink()),
                    "locale" => array($me->getProperty("locale")),
                    "cn" => array($me->getName()),
                    "timezone" => array($me->getProperty("timezone")),
                    "fbVerified" => array($me->getProperty("verified")),
                        //"username"=>$me->getProperty("username"),seems not avail anymore
                );
                $email = $me->getEmail();
                if (!isset($email) || empty($email)) {
                    //    file_put_contents(JPATH_BASE . "/samlogin.debug", "\n\n ========= \nnew NOEMAIL Facebook login: \n " . print_r($fbAttrs, true), FILE_APPEND);

                    $app->enqueueMessage("Devi fornire l'email per accedere a questo sito", "error");
                    if (!stristr($return, "http")) {
                        $return = JURI::root() . strtr($return,array(''.JURI::root(true)=>'')); //remove double path
                        $return = strtr($return, array(JURI::root() . "/" => JURI::root()));
                    }
                    $app->redirect($return);
                }
                $currentSession->set("SAMLoginAttrs", $fbAttrs);
                $currentSession->set("SAMLoginIdP", "Facebook");
                $currentSession->set("SAMLoginSP", "FacebookSDK");
                $currentSession->set("SAMLoginNameId", $me->getId());
                $currentSession->set("SAMLoginIsFacebookSession", true);
                //    file_put_contents(JPATH_BASE . "/samlogin.debug", "\n\n ========= \nnew Facebook login: \n " . print_r($fbAttrs, true), FILE_APPEND);
                //     print_r($currentSession->get("SAMLoginAttrs")); die("123testing");
                /* this fixes issue 4 */ $currentSession->close();


                // $rret = JRequest::getVar('rret', null, 'GET', 'BASE64');
                //  phpconsole("rret decoded is ".$return,"rastrano");
                // die($return);
                //  die(print_r($user,true));

                $returnAfterFacebookFixSessionIssue = JRoute::_(
                                JURI::root() . 'index.php?option=com_samlogin&view=login&task=handleSAMLResponse&rret=' . base64_encode($return)); /* ,$params->get('usesecure', false) */;

                $app->redirect($returnAfterFacebookFixSessionIssue);
            }
        } else {
            $sess = JFactory::getSession();
            $errcode = $sess->get("samloginFailErrcode", "ALREADY_LOGGED");
            $this->handleError("JOOMLA_LOGIN_FAILED_" . $errcode);
            return;
        }
    }

    public function handleSAMLResponse() {
        //  file_put_contents(JPATH_BASE . "/samlogin.debug", "\n\n ========= \nnew SSO login HANDLERESP: \n " . print_r(null, true), FILE_APPEND);

        $rret = JRequest::getString('rret');
        if (!is_null($rret)) {
            if ($this->is_base64($rret)) {
                $return = base64_decode($rret);
            } else {
                $return = $rret;
            }
        }
        if (stristr($return, "administrator/")) {
            $app = JFactory::getApplication(); //not admin or it will auto authorise
            // $app = JFactory::getApplication("administrator");
            // $app = JApplicationAdministrator::getInstance("administrator");
            // $app->loadSession();
            //die("xxxa".print_r($app,true));
        } else {
            $app = JFactory::getApplication();
        }
        $sess = JFactory::getSession();
        $user = JFactory::getUser();
        $params = JComponentHelper::getParams('com_samlogin');
        //die(print_r($app,true));
        $loginStatus = $app->login(array('username' => '', 'password' => ''), array('silent' => true, 'remember' => false)); //TODO: when silent is enabled and login fails this returns false, add a check for this

        $user = JFactory::getUser();
        //die("testing at line".print_r($user,true).__LINE__);
        if (!$user->guest) {
            //      file_put_contents(JPATH_BASE . "/samlogin.debug", "\n\n ========= \nnew Facebook login LOGGEDIN: \n " . print_r($user, true), FILE_APPEND);
            // $rret = JRequest::getVar('rret', null, 'GET', 'BASE64');
            //  phpconsole("rret decoded is ".$return,"rastrano");
            if (!stristr($return, "http")) { //ensure redirect is absolute URL or problems in mobile browsers
                $return = JURI::root() . strtr($return,array(''.JURI::root(true)=>'')); //remove double path
                $return = strtr($return, array(JURI::root() . "/" => JURI::root()));
            }
            $app->redirect($return);
        } else { //TODO: better error page, see ssp error redirection
            //      file_put_contents(JPATH_BASE . "/samlogin.debug", "\n\n ========= \nnew Facebook login FAIL: \n " . print_r($user, true), FILE_APPEND);
            $sess = JFactory::getSession();
            $errcode = $sess->get("samloginFailErrcode", "GENERIC");
            $this->handleError("JOOMLA_LOGIN_FAILED_" . $errcode);
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

    public function unauthzAlert($msg = "") {
        if ($msg == "") {
            $msg = JRequest::getVar('msg', "", 'GET', 'STRING');
        }
        $app = JFactory::getApplication();
        $sess = JFactory::getSession();
        $user = JFactory::getUser();

        $errtype = "error";

        if ($msg == "session") {
            $msg = $sess->get("samloginUnauthzMessage", "");
        }
        $app->enqueueMessage($msg, $errtype);
        $errReturnUrl = JURI::root();
        $errReturnUrl = JRoute::_('index.php?option=com_user&view=login&nocache=unauthz', false);
        $app->redirect($errReturnUrl);
        //    die($msg);
        $vName = 'message';
        $vFormat = 'html'; // raw
        if ($view = $this->getView($vName, $vFormat)) {
            //         $app->enqueueMessage($msg, $errtype);
            $view->assignRef('message', ' ');
            $view->display();
        } else {
            $app->enqueueMessage($msg, $errtype);
        }
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
