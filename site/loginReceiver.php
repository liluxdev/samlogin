<?php

/**
 * 
 * 
 * Concept and code copyright ? 2013 creativeprogrammig.it di Stefano Gargiulo
 * All rights reserved
 * http://creativeprogramming.it/license
 * 
 * 
 */
try {
    define('DS', DIRECTORY_SEPARATOR);
    /**
     * Getting Joomla BasePath
     */
    $joomla_base = dirname(__FILE__);
    $joomla_urlbacks = "";
    for ($i = 0; $i < 2; $i++) {
        $joomla_base = substr($joomla_base, 0, strrpos($joomla_base, DS));
        $joomla_urlbacks .= "/../";
    }
    define('JPATH_BASE', $joomla_base);

//include_once(JPATH_BASE . "///phpconsole-master///phpconsole/install.php");
////phpconsole("loginreceiver.php hit " . $_SERVER["REQUEST_URI"], "rastrano");

    function _getSimpleSAMLSessionFromJoomlaSessionBackup($type = "site") {
        define('_JEXEC', 1);
        require_once ( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
        require_once ( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );
        $app = JFactory::getApplication($type);
        $currentSession = JFactory::getSession();
        return $currentSession->get("SAMLoginSession");
    }

    function _getJoomlaApp($type = "site") {
        define('_JEXEC', 1);
        require_once ( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
        require_once ( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );
        $app = JFactory::getApplication($type);
        return $app;
    }

    /*
      require_once(JPATH_BASE . DS . 'components' . DS . 'com_samlogin' . DS . 'config' . DS . 'samlogin_config.inc.php');
      if (!$samlogin_config['using-metarefresh']) {
      // update federation metadata
      if (ini_get('allow_url_fopen') == '1') {
      file_put_contents(JPATH_BASE . DS . 'components' . DS . 'com_samlogin' . DS . 'config' . DS . 'idemauth-url-backed-metadata.xml', file_get_contents($idemauth_config['metadata-url'][0]));
      //TODO: support multiple remote xml metadata sources (idea: append metadata to one xml file)
      } else {
      die("SAMLogin Error: your php installation doesn't allow remote file open needed for fetching metadata (try to set allow_url_fopen to 1 in php.ini");
      }
      }
     */

    class SAMLoginSessionBridge {

        public static $isAuthN = false;
        public static $Post = NULL;
        public static $SAMLSess = NULL;
        public static $SAMLAttrs = NULL;
        public static $SAMLIdP = NULL;
        public static $SAMLSP = NULL;
        public static $SSBase = NULL;
        public static $SAMLNameId = NULL;

    }

    /**
     * The _include script registers a autoloader for the simpleSAMLphp libraries. It also
     * initializes the simpleSAMLphp config class with the correct path.
     */
    require_once(JPATH_BASE . '/components/com_samlogin/simplesamlphp/lib/_autoload.php');
    /*
     * Explisit instruct consent page to send no-cache header to browsers
     * to make sure user attribute information is not store on client disk.
     *
     * In an vanilla apache-php installation is the php variables set to:
     * session.cache_limiter = nocache
     * so this is just to make sure.
     */


    /* Load simpleSAMLphp, configuration and metadata */
    $config = SimpleSAML_Configuration::getInstance();
    SAMLoginSessionBridge::$SSBase = $config->getBaseURL();
    $session = SimpleSAML_Session::getInstance();

    $samlsession = null;
    $proto = isset($_GET["proto"]) ? $_GET["proto"] : "saml";

    if (!empty($_GET['wa']) and ( $_GET['wa'] == 'wsignoutcleanup1.0')) {
        if (isset($session) && $session->isValid('wsfed')) {
            error_reporting(0);
            $session->doLogout('wsfed');
            //TODO better callbacks
            /**
             * Booting joomla (for get control on its own session managment system)
             */
            define('_JEXEC', 1);
            if (!defined('_JDEFINES')) {
                require_once JPATH_BASE . '/includes/defines.php';
            }
            require_once JPATH_BASE . '/includes/framework.php';
            JDEBUG ? $_PROFILER->mark('afterLoad') : null;
// Instantiate the application.



            $app = JFactory::getApplication("site");
            /*
              $currentSession = JFactory::getSession();
              $currentSession->set("SAMLoginPreventDoubleLogout", true);
              $currentSession->close();

              $app->logout(); */
            $noCookieRemoved = true;
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
                        //    "SAMLoginCookieAuthToken",
                        //    "SAMLoginSimpleSAMLSessionID"
                );
                // die(print_r($cookies,true));

                foreach ($cookies as $cookie) {

                    $parts = explode('=', $cookie);
                    $name = trim($parts[0]);
                    $value = trim($parts[1]);
                    if (in_array($value, $sessionCookieValueToRemove) || in_array($name, $sessionCookieNameToRemove)) {
                        setcookie($name, '', 1);
                        setcookie($name, '', 1, '/');
                        $noCookieRemoved = false;
                    }
                }
            }
            if ($noCookieRemoved) {
                $currentSession = JFactory::getSession();
                $currentSession->set("SAMLoginPreventDoubleLogout", true);
                $currentSession->close();

                $app->logout();
            }
            die("logged out");
        } else {
            die("already logged out or session broken");
        }
        if (!empty($_GET['wreply'])) {
            SimpleSAML_Utilities::redirectUntrustedURL(urldecode($_GET['wreply']));
        }
        exit;
    }


    if (isset($_REQUEST["wresult"])) {
        $proto = "wsfed";
    }
    if ($proto == "wsfed") {

        $idpWSEnt = $_REQUEST["wsfedidp"];
        $spWSEnt = $_REQUEST["wsfedsp"];
        if (!$session->isValid('wsfed') && !isset($_REQUEST["wresult"])) {
            SimpleSAML_Utilities::redirectTrustedURL(
                    '/' . $config->getBaseURL() . 'wsfed/sp/initSSO.php', array('RelayState' => SimpleSAML_Utilities::selfURL()/* "/" */,
                "idpentityid" => $idpWSEnt,
                "spenityid" => $spWSEnt)
            );
        }

        if (!$config->getBoolean('enable.wsfed-sp', false))
            throw new SimpleSAML_Error_Error('NOACCESS');



        // print_r($_REQUEST["wresult"]);die();
        if (isset($_REQUEST["wresult"])) {
            /**
             * WS-Federation/ADFS PRP protocol support for simpleSAMLphp.
             *
             * The AssertionConsumerService handler accepts responses from a WS-Federation
             * Account Partner using the Passive Requestor Profile (PRP) and handles it as
             * a Resource Partner.  It receives a response, parses it and passes on the
             * authentication+attributes.
             *
             * @author Hans Zandbelt, SURFnet BV. <hans.zandbelt@surfnet.nl>
             * @package simpleSAMLphp
             * @version $Id$
             */
            $config = SimpleSAML_Configuration::getInstance();
            $session = SimpleSAML_Session::getInstance();
            $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();



            if (!$config->getBoolean('enable.wsfed-sp', false))
                throw new SimpleSAML_Error_Error('NOACCESS');


            /* Make sure that the correct query parameters are passed to this script. */
            try {
                if (empty($_POST['wresult'])) {
                    throw new Exception('Missing wresult parameter');
                }
                if (empty($_POST['wa'])) {
                    throw new Exception('Missing wa parameter');
                }
                if (empty($_POST['wctx'])) {
                    throw new Exception('Missing wctx parameter');
                }
            } catch (Exception $exception) {
                throw new SimpleSAML_Error_Error('ACSPARAMS', $exception);
            }


            try {

                $wa = $_POST['wa'];
                $wresult = $_POST['wresult'];
                $wctx = $_POST['wctx'];
                //print_r($wresult);die();
                /* Load and parse the XML. */
                $dom = new DOMDocument();
                /* Accommodate for MS-ADFS escaped quotes */
                $wresult = str_replace('\"', '"', $wresult);
                $dom->loadXML(str_replace("\r", "", $wresult));
                //print_r($wresult);die();
                //  print_r($_POST);
//echo "wsresult xml is: <textarea><![CDATA[".$wresult."]]></textarea>"; 
//echo "<hr/>parses xml is: ".print_r($dom,true)."";
//error_reporting(E_ALL);
                $xpath = new DOMXpath($dom);
                $xpath->registerNamespace('wst', 'http://schemas.xmlsoap.org/ws/2005/02/trust');
                $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:1.0:assertion');
                //   $xpath->registerNamespace('saml2','urn:oasis:names:tc:SAML:2.0:assertion');

                /* Find the saml:Assertion element in the response. */
                $assertions = $xpath->query('//saml:Assertion');
                //echo "<hr/> Ass:";      print_r($assertions);
                $tokenType = "saml11";
                //   die("test at ".__LINE__); 
                if (!isset($assertions) || empty($assertions) || $assertions->length === 0) {
                    //   die("test at ".__LINE__); 
                    $assertions = $xpath->query('//*[local-name()="Assertion"]');
                    //echo "<hr/>The assertions";      print_r($assertions);
                    if ($assertions->length === 0) {
                        throw new Exception('SAMLOGIN is not able to find a SAML assertion in the WSFED response, please check your token type.');
                    } else {
                        $tokenType = "saml20";
                    }
                }
                if ($assertions->length > 1) {
                    throw new Exception('The WS-Fed PRP handler currently only supports a single assertion in a response.');
                }
                $assertion = $assertions->item(0);

                /* Find the entity id of the issuer. */
                if ($tokenType == "saml11") {
                    $idpEntityId = $assertion->getAttribute('Issuer');
                }
                if ($tokenType == "saml20") {
                    $idpEntityId = $xpath->query('//*[local-name()="Issuer"]/text()')->item(0)->nodeValue;
                    // die(print_r($idpEntityId,true));
                }
                //die("test at ".__LINE__); 
                /* Load the IdP metadata. */
                $idpMetadata = $metadata->getMetaData($idpEntityId, 'wsfed-idp-remote');
                //  die(print_r($idpMetadata,true));
                /* Find the certificate used by the IdP. */
                if (array_key_exists('certificate', $idpMetadata)) {

                    $certFile = SimpleSAML_Utilities::resolveCert($idpMetadata['certificate']);
                } else {
                    if (array_key_exists('certFingerprint', $idpMetadata)) {
                        $certFingerprint = strtolower($idpMetadata['certFingerprint']);
                        $certFile = "fingerprint";
                    } else {
                        die("No certificate or fingerprint found in wsfed-remote metadata for the current IdP");
                    }
                }

                //echo($certFile);
                try {
                    /* Load the certificate. */
                    if ($certFile == "fingerprint") {
                        /* Verify that the assertion is signed by the issuer. */
                        if ($tokenType == "saml11") {
                            $validator = new SimpleSAML_XML_Validator($assertion, 'AssertionID', array("certFingerprint" => array($certFingerprint)));
                        }
                        if ($tokenType == "saml20") {
                            $validator = new SimpleSAML_XML_Validator($assertion, "ID", array("certFingerprint" => array($certFingerprint)));
                        }
                        if (!$validator->isNodeValidated($assertion)) {
                            throw new Exception('The assertion was not correctly signed by the WS-Fed IdP (fingerprint validation mode) \'' .
                            $idpEntityId . '\'.');
                        }
                    } else {
                        $certData = file_get_contents($certFile);
                        //  die($certData);
                        if ($certData === FALSE) {
                            die("Error loading IdP public key");
                            throw new Exception('Unable to load certificate file \'' . $certFile . '\' for wsfed-idp \'' .
                            $idpEntityId . '\'.');
                        }



                        /* Verify that the assertion is signed by the issuer. */
                        if ($tokenType == "saml11") {
                            $validator = new SimpleSAML_XML_Validator($assertion, 'AssertionID', $certData);
                        }
                        if ($tokenType == "saml20") {
                            $validator = new SimpleSAML_XML_Validator($assertion, "ID", $certData);
                        }

                        if (!$validator->isNodeValidated($assertion)) {
                            throw new Exception('The assertion was not correctly signed by the WS-Fed IdP \'' .
                            $idpEntityId . '\'.');
                        }
                    }
                } catch (Exception $securityEx) {
                    // print_r($securityEx->getTrace());
                    die("Error validating wsfed message signature: " . $securityEx->getMessage());
                }


                /* Check time constraints of contitions (if present). */
                foreach ($xpath->query('./saml:Conditions', $assertion) as $condition) {
                    $notBefore = $condition->getAttribute('NotBefore');
                    $notOnOrAfter = $condition->getAttribute('NotOnOrAfter');
                    if (!SimpleSAML_Utilities::checkDateConditions($notBefore, $notOnOrAfter)) {
                        throw new Exception('The response has expired.');
                    }
                }


                /* Extract the name identifier from the response. */
                if ($tokenType == "saml11") {
                    $nameid = $xpath->query('./saml:AuthenticationStatement/saml:Subject/saml:NameIdentifier', $assertion);
                }
                if ($tokenType == "saml20") {
                    $nameid = $xpath->query('//*[local-name()="NameID"]', $assertion);
                }
                if ($nameid->length === 0) {
                    throw new Exception('Could not find the name identifier in the response from the WS-Fed IdP \'' .
                    $idpEntityId . '\'.');
                }
                $nameid = array(
                    'Format' => $nameid->item(0)->getAttribute('Format'),
                    'Value' => $nameid->item(0)->textContent,
                );


                /* Extract the attributes from the response. */
                $attributes = array();
                if ($tokenType == "saml11") {
                    $attributeValues = $xpath->query('./saml:AttributeStatement/saml:Attribute/saml:AttributeValue', $assertion);
                    foreach ($attributeValues as $attribute) {
                        $name = $attribute->parentNode->getAttribute('AttributeName');
                        $value = $attribute->textContent;
                        if (!array_key_exists($name, $attributes)) {
                            $attributes[$name] = array();
                        }
                        $attributes[$name][] = $value;
                    }
                }
                if ($tokenType == "saml20") {
                    $attributeValues = $xpath->query('//*[local-name()="AttributeValue"]', $assertion);
                    foreach ($attributeValues as $attribute) {
                        $name = $attribute->parentNode->getAttribute('Name');
                        $value = $attribute->textContent;
                        if (!array_key_exists($name, $attributes)) {
                            $attributes[$name] = array();
                        }
                        $attributes[$name][] = $value;
                    }
                }

                //  die(print_r($attributes,true));

                /* Mark the user as logged in. */
                $authData = array(
                    'Attributes' => $attributes,
                    'saml:sp:NameID' => $nameid,
                    'saml:sp:IdP' => $idpEntityId,
                );
                $session->doLogin('wsfed', $authData);

                /* Redirect the user back to the page which requested the login. */
                SimpleSAML_Utilities::redirectUntrustedURL($wctx);
            } catch (Exception $exception) {
                die("Error: " . $exception->getMessage());
                throw new SimpleSAML_Error_Error('PROCESSASSERTION', $exception);
            }
        }

        $session = SimpleSAML_Session::getInstance();

        if ($session->isValid('wsfed')) {
            $debugWSFED = false;
            if ($debugWSFED) {
                $attributes = $session->getAuthData('wsfed', 'Attributes');

                $t = new SimpleSAML_XHTML_Template($config, 'status.php', 'attributes');

                $t->data['header'] = '{status:header_wsfed}';
                $t->data['remaining'] = $session->getAuthData('wsfed', 'Expire') - time();
                $t->data['sessionsize'] = $session->getSize();
                $t->data['attributes'] = $attributes;
                $t->data['logouturl'] = '/' . $config->getBaseURL() . 'wsfed/sp/initSLO.php?RelayState=' . $config->getBaseURL() . 'logout.php&spentityid=' . $spWSEnt;
                $t->show();
            } else {
                /* $logoutURL =  '/' . $config->getBaseURL() . 'wsfed/sp/initSLO.php?'
                  . 'RelayState=' . urlencode(SimpleSAML_Utilities::selfURL()) . "&task=finishSLO"
                  . '&spentityid=' . $spWSEnt; */

                // $logoutURL = '/' . $config->getBaseURL() . 'wsfed/sp/initSLO.php?';
                $logoutURL = '/' . $config->getBaseURL() . 'wsfed/sp/initSLO.php?'
                        . 'RelayState={LOGOUT_CALLBACK_URL}'
                        . '&spentityid=' . $spWSEnt;
                $attributes = $session->getAuthData('wsfed', 'Attributes');
                //  print_r($attributes); die("12testing");
                $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
                SAMLoginSessionBridge::$isAuthN = true;
                SAMLoginSessionBridge::$SAMLAttrs = $attributes;
                SAMLoginSessionBridge::$SAMLSess = $session;
                SAMLoginSessionBridge::$SAMLIdP = $session->getIdP();
                try {
                    SAMLoginSessionBridge::$SAMLSP = @$metadata->getMetaDataCurrentEntityID();
                } catch (Exception $ee) {
                    SAMLoginSessionBridge::$SAMLSP = "wsfed";
                }
                SAMLoginSessionBridge::$SAMLNameId = $session->getNameID();
                //TODO better callbacks
                /**
                 * Booting joomla (for get control on its own session managment system)
                 */
                define('_JEXEC', 1);
                if (!defined('_JDEFINES')) {
                    require_once JPATH_BASE . '/includes/defines.php';
                }
                require_once JPATH_BASE . '/includes/framework.php';
                JDEBUG ? $_PROFILER->mark('afterLoad') : null;
// Instantiate the application.



                $app = JFactory::getApplication('site');
                
                
                $siteRootURL=strtr(JURI::root(), array("/components/com_samlogin" => ""));
                $logoutURL = strtr($logoutURL,array("{LOGOUT_CALLBACK_URL}"=>$siteRootURL));
                $currentSession = JFactory::getSession();

                /*  $logoutURL .= 'RelayState=' . strtr(JURI::root(), array("/components/com_samlogin" => "")) . "&task=finishSLO"
                  . '&spentityid=' . $spWSEnt; */
                $wsfedLogoutURL = $logoutURL;
                //die($logoutURL);
                $currentSession->set("SAMLoginIsAuthN", SAMLoginSessionBridge::$isAuthN);

                $currentSession->set("SAMLoginSession", SAMLoginSessionBridge::$SAMLSess);
                $currentSession->set("SAMLoginAttrs", SAMLoginSessionBridge::$SAMLAttrs);
                $currentSession->set("SAMLoginIdP", SAMLoginSessionBridge::$SAMLIdP);
                $currentSession->set("SAMLoginSP", SAMLoginSessionBridge::$SAMLSP);
                $currentSession->set("SAMLoginNameId", json_encode(SAMLoginSessionBridge::$SAMLNameId));
                $currentSession->set("SAMLoginIsWSFEDSession", true);
                //     print_r($currentSession->get("SAMLoginAttrs")); die("123testing");
                /* this fixes issue 4 */ $currentSession->close(); //ensure session data storage session_write_close()
                //echo "index.php?option=com_samlogin&view=login&task=handleSAMLResponse&rret=" . $_GET['rret'];die();
                //print_r($currentSession->get("SAMLoginAttrs")); die("3testing");
                $redirectTo = JRoute::_("index.php?option=com_samlogin&view=login&task=handleSAMLResponse&rret=" . $_GET['rret'], false);
                $redirectTo = str_ireplace("/components/com_samlogin/", "/", $redirectTo);
                // phpconsole("finishing login at $redirectTo", "rastrano");


                if (isset($_REQUEST["dologout"])) {
                    //if ($_REQUEST["task"]==="finishSLO"){
                    $currentSession = JFactory::getSession();
                    $currentSession->set("SAMLoginPreventDoubleLogout", true);
                    $currentSession->close();
                    $app->logout();
                    // die($wsfedLogoutURL);
                    $redirectTo = $wsfedLogoutURL;
                }

                $app->redirect($redirectTo);
                // die($redirectTo);

                die(); //important to die here or session will be reset
            }
        }
    }
//end wsfed mode



    if (($_GET['task'] == "initSLO")) {

        $samlsession = _getSimpleSAMLSessionFromJoomlaSessionBackup();
    }



    if (($_GET['task'] == "initSSO")) {
        //disabled or problems TODO: find a way to prevent double logins
        // $samlsession = _getSimpleSAMLSessionFromJoomlaSessionBackup();
    }


    $selfUrl = SimpleSAML_Utilities::selfURL();


//new sp api
    $as = new SimpleSAML_Auth_Simple('default-sp'); //new sp api


    if ($_GET['task'] == "logoutCallback") {
        try {
            $state = SimpleSAML_Auth_State::loadState((string) $_REQUEST['SAMLLoginSLOState'], 'SAMLLoginSLOStage');
            $ls = $state['saml:sp:LogoutStatus']; /* Only works for SAML SP */
            if ($ls['Code'] === 'urn:oasis:names:tc:SAML:2.0:status:Success' && !isset($ls['SubCode'])) {
                /* Successful logout. */
                $msg = "SLOE0";
            } else {
                /* Logout failed. Tell the user to close the browser. */
                $msg = "SLOE1";
                //TODO: language file  in handleError  $msg = "We were unable to log you out of all your IdP/SP sessions. To be completely sure that you are logged out, you need to close your web browser. [MessageCode: SLOE2]";
            }
        } catch (Exception $logoutE) {
            if (!isset($_GET["noSLO"])) {
                $msg = "SLOE2";
            } else {
                $msg = "NOSLO";
            }
            //TODO: language file  in handleError  $msg = "We were unable to log you out of all your IdP/SP sessions. To be completely sure that you are logged out, you need to close your web browser. [MessageCode: SLOE2]";
        }
    }



    if ($_GET['task'] == "initSLO") {

        // die($samlsession->getIdP());
        $usingPhpSession = false; //TODO: detect from params, anyway get rid of this loginReceiver.php is better
        if ($samlsession != null && $usingPhpSession) {
            SimpleSAML_Session::setInstance($samlsession);
            //phpconsole("logging out at idp: " . $samlsession->getIdP(), "rastrano");
        }

        //phpconsole("are we authN?? " . $as->isAuthenticated(), "rastrano");
        $returnTo = substr($selfUrl, 0, strpos($selfUrl, "?")) . '?task=logoutCallback&rret=' . $_GET["rret"];

        //phpconsole("logging out callb2: " . $returnTo, "rastrano");
        //  if (!$auth->isAuthenticated()) {
        $doSLO = isset($_GET["trySLO"]); //TODO: param;
        if ($doSLO) {
            $as->logout(array(
                "ReturnStateParam" => "SAMLLoginSLOState",
                "ReturnStateStage" => "SAMLLoginSLOStage",
                //    "ErrorURL" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleErr' . $extraReturnURLParams),
                //"ReturnTo" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleSuccess' . $extraReturnURLParams),
                "ReturnTo" => $returnTo
                    //  "KeepPost" => FALSE
            ));
        } else {  //deprecated please always trySLO or SSP session will not closed
            $app = _getJoomlaApp();
            $app->redirect($returnTo . "&noSLO=1");
        }

        /* old but working way:
          SimpleSAML_Utilities::redirect(
          '/' . SAMLoginSJSessionBridge::$SSBase. 'saml2/sp/idemSLO.php?nameid='.urlencode($currentSession->get("SAMLoginNameId")).'&spentityid='.$currentSession->get("SAMLoginSP").'&idpentityid='.$currentSession->get("SAMLoginIdP"),
          array('RelayState' => $selfUrl."?slostate=callback&joomlatoken=".$_POST['return'])

          );
         */
    }



    $extraInitSSOParam = "";
    if ($_GET['task'] == "initSSO") {

        $returnTo = substr($selfUrl, 0, strpos($selfUrl, "?")) . '?task=loginCallback&rret=' . $_GET["rret"];


        // //phpconsole($samlsession, "rastrano");
        if ($samlsession != null) {
            //phpconsole("simpleSAMLphp session restored " . $samlsession->getIdP(), "rastrano");
            SimpleSAML_Session::setInstance($samlsession);
        }

        ////phpconsole("initSSO. are we already loggedIn?" . $as->isAuthenticated(), "rastrano");
        if (isset($_GET["idp"]) && $_GET["idp"] != "" && $_GET["idp"] != "DS") {

            if (!$as->isAuthenticated()) {
                if (preg_match('/(?i)msie [1-8]/', $_SERVER['HTTP_USER_AGENT'])) {
                    // if IE<=8$url = $auth->getLoginURL();
                    $loginUrl = htmlspecialchars($as->getLoginURL($returnTo)); //."&idpentityid=".$_GET["idp"]);
                    //print('<a href="' . htmlspecialchars($url) . '">Login</a>');

                    echo("<script type='text/javascript'>window.location.href='" . $loginUrl . "';</script>");
                    echo("<a href='" . $loginUrl . "'>click here if you don't get automatically redirected to the login page...</a>");
                    die();
                }
                $as->requireAuth(array(
                    'saml:idp' => urldecode($_GET["idp"]),
                    "ReturnTo" => $returnTo,
                ));
            } else {
                $app = _getJoomlaApp();
                $app->redirect($returnTo);
            }
        } else {
            //i can use the new api
            //    //phpconsole("requiring authN", "rastrano");
            //    //phpconsole($as->isAuthenticated(), "rastrano");

            if (!$as->isAuthenticated()) {
                if (preg_match('/(?i)msie [1-8]/', $_SERVER['HTTP_USER_AGENT'])) {
                    // if IE<=8$url = $auth->getLoginURL();
                    $loginUrl = htmlspecialchars($as->getLoginURL($returnTo));
                    //print('<a href="' . htmlspecialchars($url) . '">Login</a>');
                    echo("<script type='text/javascript'>window.location.href='" . $loginUrl . "';</script><a href='" . $loginUrl . "'>click here if you don't get automatically redirected to the login page...</a>");
                    die();
                }

                $as->requireAuth(array(
                    "ReturnTo" => $returnTo
                ));
            } else {
                $app = _getJoomlaApp();
                $app->redirect($returnTo);
            }
        }
    }

//An embedded DS was used, so i need old raw initSSO way

    /**
     * Check if valid local session exists, and the authority is the SAML 2.0 SP
     * part of simpleSAMLphp. If the currenct session is not valid, the user is
     * redirected to the initSSO.php script. This script will send the user to
     * a SAML 2.0 IdP with an authentication request, and thereafter the user
     * will be asked at the SAML 2.0 IdP to authenticate. You add one important
     * parameter when you send the user to the initSSO script, the RelayState.
     * The RelayState URL is the URL that you want to send the user to after
     * authentication is complete - and usually you want to send the user back
     * to this very page. To get the URL of the current page we use the selfURL()
     * helper function.
     *
     * When the user is complete authenticating at the IdP, the user will be sent
     * back to the AssertionConsumerService.php script in simpleSAMLphp. The assertion
     * is validated, and if trusted, the user's session is set to be valid, and the user
     * is redirected back to the RelayState URL. And then the user is here again, but
     * authenticated, and therefore passes the if sentence below, and moves on to
     * retrieving attributes from the session.

      if (!$session->isValid('saml2') && !($_POST['task'] == "logout") && !($_GET['slostate'] == "callback")) {

      //FIXED: eliminare completamente se possibile: scrivere a ML su come fare il requireAuth verso un idp specifico, se non si pu? fare, perlomeno trovare il modo di passare idemauth-sp come enityID di questa chiamata:
      SimpleSAML_Utilities::redirect(
      '/' . $config->getBaseURL() . 'saml2/sp/initSSO.php' . $extraInitSSOParam,
      array('RelayState' => $selfUrl)
      );
      }
     *
     */
//error_reporting(E_ALL);
    if (($as->isAuthenticated() || $session->isValid('default-sp')) && (!($_GET['task'] == "logout"))) {

        /**
         * Preparing SimpleSAML to Joomla Session Bridge
         */
        // SAMLoginSessionBridge::$Post = $_SESSION['SAMLogin-Transient-PostBridge'];

        /*
         * getting SAML user attributes, new way
         */
        //if ($as->isAuthenticated()) {
        $attributes = $as->getAttributes();

        //}
        //else {
        //  $attributes = $session->getAttributes();
        //}

        $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

        SAMLoginSessionBridge::$isAuthN = $as->isAuthenticated();

        SAMLoginSessionBridge::$SAMLAttrs = $attributes;

        SAMLoginSessionBridge::$SAMLSess = $session;

        SAMLoginSessionBridge::$SAMLIdP = $session->getIdP();

        try {
            SAMLoginSessionBridge::$SAMLSP = @$metadata->getMetaDataCurrentEntityID();
        } catch (Exception $ee) {
            SAMLoginSessionBridge::$SAMLSP = "idp-initiated?";
        }
        //SAMLoginSessionBridge::$SAMLNameId = json_encode($session->getNameId());
        // die("testing @".__LINE__);
        SAMLoginSessionBridge::$SAMLNameId = $session->getNameID();


        //  echo ("Attributes are <pre>". print_r($attributes,true) ."</pre>");
        //  die ("Name ID is <pre>". print_r(SAMLoginSessionBridge::$SAMLNameId,true) ."</pre>");
    }


    /**
     * Booting joomla (for get control on its own session managment system)
     */
// Set flag that this is a parent file.
    define('_JEXEC', 1);
//already def at top: define('DS', DIRECTORY_SEPARATOR);



    if (!defined('_JDEFINES')) {
        require_once JPATH_BASE . '/includes/defines.php';
    }

    require_once JPATH_BASE . '/includes/framework.php';

// Mark afterLoad in the profiler.
    JDEBUG ? $_PROFILER->mark('afterLoad') : null;

// Instantiate the application.
    $app = JFactory::getApplication('site');


    if ($_GET['task'] == "logoutCallback") {

        /**
         * Destroying Joomla Session SSP data
         */
        $currentSession = JFactory::getSession();
        $currentSession->set("SAMLoginIsAuthN", null);
        $currentSession->set("SAMLoginSession", null);
        $currentSession->set("SAMLoginAttrs", null);
        $currentSession->set("SAMLoginIdP", null);
        $currentSession->set("SAMLoginSP", null);
        $currentSession->set("SAMLoginNameId", null);
        /* this fixes issue 4 */ $currentSession->close(); //ensure session data storage session_write_close()


        $redirectTo = JRoute::_("index.php?option=com_samlogin&view=login&task=finishSLO&rret=" . $_GET['rret'] . "&msg=" . $msg, false);
        $redirectTo = str_ireplace("/components/com_samlogin/", "/", $redirectTo);
        //  //phpconsole("finishing logout at $redirectTo", "rastrano");
        $app->redirect($redirectTo);
    }

    if ($_GET['task'] == "loginCallback") {
        /**
         * Creating Joomla Session
         */
        //die("testing @".__LINE__);
        $currentSession = JFactory::getSession();
        $currentSession->set("SAMLoginIsAuthN", SAMLoginSessionBridge::$isAuthN);
        $currentSession->set("SAMLoginSession", SAMLoginSessionBridge::$SAMLSess);
        $currentSession->set("SAMLoginAttrs", SAMLoginSessionBridge::$SAMLAttrs);
        $currentSession->set("SAMLoginIdP", SAMLoginSessionBridge::$SAMLIdP);
        $currentSession->set("SAMLoginSP", SAMLoginSessionBridge::$SAMLSP);
        $currentSession->set("SAMLoginNameId", json_encode(SAMLoginSessionBridge::$SAMLNameId));
        /* this fixes issue 4 */ $currentSession->close(); //ensure session data storage session_write_close()


        $redirectTo = JRoute::_("index.php?option=com_samlogin&view=login&task=handleSAMLResponse&rret=" . $_GET['rret'], false);
        $redirectTo = str_ireplace("/components/com_samlogin/", "/", $redirectTo);
        //  //phpconsole("finishing login at $redirectTo", "rastrano");
        $app->redirect($redirectTo);
    }

    /* need to be in main joomla or unuseful
      $app->login(array('username' => '', 'password' => ''));

      $user = JFactory::getUser();
      //phpconsole($user->username, "rastrano");
      if (!$user->guest) {
      //phpconsole($user->username, "rastrano");
      //phpconsole($_SERVER["REQUEST_URI"], "rastrano");
      $rret = $_GET['rret'];
      if (isset($rret)) {
      //phpconsole("rret is:" . $rret, "rastrano");
      $return = base64_decode($rret);
      //phpconsole("rret dec. is:" . $return, "rastrano");
      $app->redirect($return);
      } else {
      $msg = "No return URI";
      $redirectTo = JRoute::_("index.php?option=com_samlogin&view=login&task=handleError&msg=" . $msg);
      $redirectTo = str_ireplace("/components/com_samlogin/", "/", $redirectTo);
      //phpconsole($redirectTo, "rastrano");
      $app->redirect($redirectTo);
      }
      } else {
      $msg = "User not logged in after SAML login success";
      $redirectTo = JRoute::_("index.php?option=com_samlogin&view=login&task=handleError&msg=" . $msg);
      $redirectTo = str_ireplace("/components/com_samlogin/", "/", $redirectTo);
      //phpconsole($redirectTo, "rastrano");
      $app->redirect($redirectTo);
      }
     * */
} catch (Exception $thrown) {
    die("Error: " . $thrown->getMessage());
}

    