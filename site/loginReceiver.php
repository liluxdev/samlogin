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

include_once(JPATH_BASE . "///phpconsole-master///phpconsole/install.php");
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
//print_r($as->getAttributes());


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
       if (!isset($_GET["noSLO"])){
           $msg = "SLOE2";
   
           
       }else{
                      $msg = "NOSLO";
       }
        //TODO: language file  in handleError  $msg = "We were unable to log you out of all your IdP/SP sessions. To be completely sure that you are logged out, you need to close your web browser. [MessageCode: SLOE2]";

    }
}



if ($_GET['task'] == "initSLO") {

    // die($samlsession->getIdP());
    if ($samlsession != null) {
        SimpleSAML_Session::setInstance($samlsession);
        //phpconsole("logging out at idp: " . $samlsession->getIdP(), "rastrano");
    }

    //phpconsole("are we authN?? " . $as->isAuthenticated(), "rastrano");
    $returnTo = substr($selfUrl, 0, strpos($selfUrl, "?")) . '?task=logoutCallback&rret=' . $_GET["rret"];

    //phpconsole("logging out callb2: " . $returnTo, "rastrano");
    //  if (!$auth->isAuthenticated()) {
    $doSLO=isset($_GET["trySLO"]); //TODO: param;
    if ($doSLO){
    $as->logout(array(
        "ReturnStateParam" => "SAMLLoginSLOState",
        "ReturnStateStage" => "SAMLLoginSLOStage",
        //    "ErrorURL" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleErr' . $extraReturnURLParams),
        //"ReturnTo" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleSuccess' . $extraReturnURLParams),
        "ReturnTo" => $returnTo
            //  "KeepPost" => FALSE
    ));
    }else{
        $app=  _getJoomlaApp();
        $app->redirect($returnTo."&noSLO=1");
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

            $as->requireAuth(array(
                'saml:idp' => $_GET["idp"],
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
    SAMLoginSessionBridge::$SAMLSP = $metadata->getMetaDataCurrentEntityID();
    //   SAMLoginSJSessionBridge::$SAMLNameId = json_encode($session->getNameId());
}


/**
 * Booting joomla (for get control on its own session managment system)
 */
// Set flag that this is a parent file.
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);



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


    $redirectTo = JRoute::_("index.php?option=com_samlogin&view=login&task=finishSLO&rret=" . $_GET['rret'] . "&msg=" . $msg);
    $redirectTo = str_ireplace("/components/com_samlogin/", "/", $redirectTo);
  //  //phpconsole("finishing logout at $redirectTo", "rastrano");
    $app->redirect($redirectTo);
}
if ($_GET['task'] == "loginCallback") {
    /**
     * Creating Joomla Session
     */
    $currentSession = JFactory::getSession();
    $currentSession->set("SAMLoginIsAuthN", SAMLoginSessionBridge::$isAuthN);
    $currentSession->set("SAMLoginSession", SAMLoginSessionBridge::$SAMLSess);
    $currentSession->set("SAMLoginAttrs", SAMLoginSessionBridge::$SAMLAttrs);
    $currentSession->set("SAMLoginIdP", SAMLoginSessionBridge::$SAMLIdP);
    $currentSession->set("SAMLoginSP", SAMLoginSessionBridge::$SAMLSP);
    $currentSession->set("SAMLoginNameId", SAMLoginSessionBridge::$SAMLNameId);
    /* this fixes issue 4 */ $currentSession->close(); //ensure session data storage session_write_close()


    $redirectTo = JRoute::_("index.php?option=com_samlogin&view=login&task=handleSAMLResponse&rret=" . $_GET['rret']);
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


    