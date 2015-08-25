<?php

// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgSystemSamlogin extends JPlugin {

    public function plgSystemSamlogin(&$subject, $config) {
        parent::__construct($subject, $config);
    }

    public static function getJRequestVar($name) {
        if (version_compare(JVERSION, '3.0', 'gt')) {

            $val = JFactory::getApplication()->input->getVar($name);
        } else {
            $val = JRequest::getVar($name);
        }
        return $val;
    }

    function getJRequestCmd($name) {
        if (version_compare(JVERSION, '3.0', 'gt')) {

            $val = JFactory::getApplication()->input->getCmd($name);
        } else {
            $val = JRequest::getCmd($name);
        }
        return $val;
    }

    function setNoCacheHeaders($enforce = false) {

        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        //for varnish and nginx fcgi cache
        if (!$user->guest || $enforce) {
            // Get input cookie object

            setcookie('x-logged-in', 'true', 0);

            JResponse::setHeader('X-Accel-Expires', '0', true);
            JResponse::setHeader('X-Accel-SamloginNoCache', 'debug', true);
            //The “X-Accel-Expires” header field sets caching time of a response in seconds. The zero value disables caching for a response. If the value starts with the @ prefix, it sets an absolute time in seconds since Epoch, up to which the response may be cached.
            //http://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_cache_valid
            JResponse::setHeader('X-Logged-In', $enforce ? "Maybe-In-Transition" : "True", true);
        } else {
            // Remove cookie
            setcookie('x-logged-in', 'false', 1);
            //JResponse::setHeader('X-Logged-In', 'False', true);
        }
    }

    public static function getReturnURLAdminMergeSess($params) {
        $url = Juri::root(false);//"/";
        $returl = self::getJRequestVar('return');
        // die($returl);
        if (isset($returl) && !$params->get('systemreturngotpriority', true)) {
            $session = JFactory::getSession();
            $session->set('samloginReturnURL', $returl);
            $url = base64_decode($returl);
        }

        try { //			die($url);
            $returnStateCheck = JFactory::getApplication()->getUserState('users.login.form.data');
            $returnStateCheck = $returnStateCheck["return"];
            //echo($returnStateCheck);
            if (!empty($returnStateCheck) && $returnStateCheck != "index.php?option=com_users&view=profile") {
                $b64url = base64_encode($returnStateCheck);
                $get = JRequest::getVar('return', null, 'GET', 'BASE64');

                //echo($b64url);
                if (isset($get) && !empty($get) && $get != $b64url) {
                    //echo($get); 
                    $url = $returnStateCheck;
                    $myurl = JUri::current();
                    //echo $myurl;
                    list($file, $parameters) = explode('?', $myurl);
                    parse_str($parameters, $output);
                    $output["return"] = $b64url;

                    $loginURLStatic = $file . '?' . http_build_query($output); // Rebuild the url to avoid state loss if refresh and wrong return param
                    //die($loginURLStatic);
                    JFactory::getApplication()->redirect($loginURLStatic);
                }
            }
        } catch (Exception $noretstate) {
            
        }


        $user = JFactory::getUser();
        $type = ($user->guest) ? 'login' : 'logout';


        if ($itemid = $params->get($type)) {
            $mainframe = JFactory::getApplication();
            $menu = $mainframe->getMenu();
            $item = $menu->getItem($itemid);
            if ($item) {
                $url = JRoute::_('index.php?Itemid=' . $itemid, false, $params->get('usesecure', false));
            } else {
                $uri = JFactory::getURI();
                $url = $uri->toString(array('path', 'query', 'fragment'));
            }
        } else {
            $uri = JFactory::getURI();
            $url = $uri->toString(array('path', 'query', 'fragment'));
        }
        if (isset($returl) && $params->get('systemreturngotpriority', true)) {
            $session = JFactory::getSession();
            $session->set('samloginReturnURL', base64_decode($returl));
            $url = base64_decode($returl);
        }

        if ($params->get('usesecure', false)) {
            $url = strtr($url, array("http://" => "https://"));
        }
        if (!stristr($url, "mergesess")) {
            if (stristr($url, "?")) {
                $url.="&mergesess=1";
            } else {
                $url.="?mergesess=1";
            }
        }
       
        if (!strpos($url, "http")!==0) { //ensure redirect is absolute URL 
                $url = JURI::root() . $url;
        }
        return base64_encode($url);
    }

    function onAfterRender() {
        $option = $this->getJRequestCmd('option');
        $view = $this->getJRequestCmd('view');
        $application = JFactory::getApplication();
        $this->setNoCacheHeaders(false);
 
        if ($application->isAdmin() && isset($_GET["mergesess"]) && $_GET["mergesess"] == "1") {
            $this->mergeFrontendWithAdminSessions(JURI::current());
        }

        if ($application->isAdmin() && $option == "com_login" && $view = "login") {

            //die($option.":".$view);
            $this->setNoCacheHeaders(true);

            if (version_compare(JVERSION, '1.6.0', 'ge')) {
                $componentParams = JComponentHelper::getParams('com_samlogin');
            } else {
                $component = JComponentHelper::getComponent('com_samlogin');
                $componentParams = new JParameter($component->params);
            }
            if ($componentParams->get("enable_adminside", 0)) {
                $body = JResponse::getBody();
                $variables = array();
                $variables["returnURL"] = self::getReturnURLAdminMergeSess($componentParams);


                $fbloginHTML = "";
                $extraMessage = "";
                $variables['ssoLink'] = JRoute::_(JURI::root() . 'index.php?option=com_samlogin&view=login&task=initSSO&return=' . $variables['returnURL'], false);
                $variables['facebookSSOLink'] = JRoute::_(JURI::root() . 'index.php?option=com_samlogin&view=login&task=initFacebookSSO&return=' . $variables['returnURL'], false);
                if ($componentParams->get("enable_fbconnect", 0)) {
                    $fbloginHTML = "
<a href=" . $variables['facebookSSOLink'] . " tabindex=3 class='btn btn-primary btn-large'>
<i class='icon-lock icon-white'></i> Facebook Login	</a>";
                }
$ssoHTML="";
  if ($componentParams->get("enable_samlogin", 1)) {
                $ssoHTML = "<a href=" . $variables['ssoLink'] . " tabindex=3 class='btn btn-success btn-large'>
<i class='icon-lock icon-white'></i> SSO Login	</a>";
  }
                $variables['ssoLink'] = JRoute::_(JURI::root() . 'index.php?option=com_samlogin&view=login&task=initSSO&return=' . $variables['returnURL'], false);
                if (isset($_GET["notAuthorized"]) && $_GET["notAuthorized"] == 403) {
                    $extraMessage = "<hr/><b>Your SSO attempt worked but you are still not authorized "
                            . "to access the administrative interface, if you think you need this"
                            . " kind of access contact site administrators<br/><br/>"
                            . "For security reasons this failed access caused a reset also of your frontend session,"
                            . "if any, but your SSO session is still active, if you need to retry logout from your SSO server"
                            . "central service or close/clean your browser";
                }
                $body = strtr($body, array("</form>" => "</from>".$ssoHTML . $fbloginHTML . $extraMessage));
                JResponse::setBody($body);
            }
        }
    }

    function onBeforeRender() { //DON't use onAfterInitialize: option and view are still not set
        $option = $this->getJRequestCmd('option');
        $view = $this->getJRequestCmd('view');
        $application = JFactory::getApplication();
        $this->setNoCacheHeaders(false);
        // die($option);

        if ($application->isSite() && $option == 'com_samlogin') {
            $this->setNoCacheHeaders(true); //also in non-logged view, we may need nocache to prevent caching of the login page itself
        }

        if ($application->isSite() && $view == 'login' && ($option == 'com_users' || $option == 'com_user') && $_SERVER['REQUEST_METHOD'] == 'GET') {
            $this->setNoCacheHeaders(true); //also in non-logged view, we may need nocache to prevent caching of the login page itself
        }
    }

        public function onAfterRoute() {
        $application = JFactory::getApplication();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');

        if (isset($_POST["wresult"]) && isset($_POST["wctx"]) ||
                (isset($_GET['wa']) && $_GET['wa'] == 'wsignoutcleanup1.0')) { //WSFED response redirect
            require JPATH_BASE . "/components/com_samlogin/loginReceiver.php";
            die();
        }


        if ($application->isSite() && $view == 'login' && ($option == 'com_users' || $option == 'com_user') && $_SERVER['REQUEST_METHOD'] == 'GET') {
            $return = self::getJRequestVar("return");
 /*           if(!empty($return)){
                strip_tags($return);
            }*/
           // die("ret: ".$return);
            jimport('joomla.application.component.helper');
            $samloginParams = JComponentHelper::getParams('com_samlogin');

            $overrideLogin = $samloginParams->get('overrideJoomlaLoginPage', 1) == 1;
            if ($overrideLogin) {

                $component = JComponentHelper::getComponent('com_samlogin');
                $menu = $application->getMenu();
                $items = version_compare(JVERSION, '2.5', 'ge') ? $menu->getItems('component_id', $component->id) : $menu->getItems('componentid', $component->id);
                if (count($items)) {
                    $router = JSite::getRouter();
                    $link = $router->getMode() == JROUTER_MODE_SEF ? 'index.php?Itemid=' . $items[0]->id : $items[0]->link . '&Itemid=' . $items[0]->id;
                     if (!empty($return)) {
                            $link .= "&return=" . $return;
                        }
                } else {
                    $link = 'index.php?option=com_samlogin&view=login';
                    if (!empty($return)) {
                        $link = 'index.php?option=com_samlogin&view=login&return=' . $return;
                    } else {
                        $link = 'index.php?option=com_samlogin&view=login&return=' . urlencode(base64_encode(JURI::current()));
                        $return = urlencode(base64_encode(JURI::current()));
                    }
                    $customItem = $samloginParams->get('loginPage', 0);
                    if ($customItem) {
                        $link = 'index.php?Itemid=' . $customItem;
                        
                        if (!empty($return)) {
                            $link .= "&return=" . $return;
                        }
                    }
                }
                $redirect = JRoute::_($link, false);
                $application->redirect($redirect);
            }
        }
    }

    public function mergeFrontendWithAdminSessions($finalURL) {
        $u = JFactory::getUser();
        if (JFactory::getApplication()->isAdmin() && $_GET["mergesess"] == "1") {

            if (version_compare(JVERSION, '1.6.0', 'ge')) {
                $componentParams = JComponentHelper::getParams('com_samlogin');
            } else {
                $component = JComponentHelper::getComponent('com_samlogin');
                $componentParams = new JParameter($component->params);
            }
            if ($componentParams->get("enable_adminside", 0)) {
                if ($u->guest) {

                    // die("test");
                    //    $currentSession = JFactory::getSession(array("name"=>"administratorjapplicationcms"));
//die(print_r($currentSession->get("SAMLoginAttrs", '-'),true));
                    $app = JFactory::getApplication();
                    $sessname = $app->input->cookie->get(md5(JApplication::getHash('site')));

                    $db = JFactory::getDbo();
                    $query = $db->getQuery(true)
                            ->select('*')
                            ->from('#__session')
                            ->where('session_id = ' . $db->quote(JFactory::getApplication()->input->cookie->get(md5(JApplication::getHash('site')))))
                            ->where('client_id = 0') //client id 0 is site 1 admin 
                            ->where('guest = 0');

                    $db->setQuery($query);
                    $sessrow = $db->loadAssoc();
                    //  $sessrow["decdata1"]= session_decode( (string) $sessrow["data"]);
                    $sessrow["session_decode"] = session_decode(str_replace('\0\0\0', chr(0) . '*' . chr(0), (string) $sessrow["data"]));
                    // die(print_r($_SESSION));
                    $sessrow["decdata"] = $_SESSION["__default"];
                    // echo "<pre>";
                    //   die(print_r( $sessrow["decdata"]["user"]->get("groups")));
                    // $instance = JFactory::getUser($userId);
                    $instance = $sessrow["decdata"]["user"];
                    if ($instance instanceof Exception) {
                        return $app->redirect('index.php', JText::_('User login failed'), 'error');
                    }

                    if ($instance->get('block') == 1) {
                        return $app->redirect('index.php', JText::_('JERROR_NOLOGIN_BLOCKED'), 'error');
                    }
                    JFactory::getApplication()->checkSession();

                    //   $allowedViewLevels = JAccess::getAuthorisedViewLevels($instance->id);
                    // die(print_r($allowedViewLevels));
                    $isadmin = $instance->authorise('core.login.admin');
                    $isroot = $instance->authorise('core.admin');
                    if ($isadmin || $isroot) {
                        // die("isadmin");
                        $instance->set('guest', 0);

                        $session = JFactory::getSession();
                        $session->set('user', $instance);

                        $app->checkSession();

                        $query = $db->getQuery(true)
                                ->update($db->quoteName('#__session'))
                                ->set($db->quoteName('guest') . ' = ' . $db->quote($instance->get('guest')))
                                ->set($db->quoteName('username') . ' = ' . $db->quote($instance->get('username')))
                                ->set($db->quoteName('userid') . ' = ' . (int) $instance->get('id'))
                                ->where($db->quoteName('session_id') . ' = ' . $db->quote($session->getId()));
                        $db->setQuery($query);
                        $db->execute();


                        // $app->enqueueMessage(JText::sprintf('You have login successfully as user &quot;%s&quot;', $instance->name));
                        $app->redirect($finalURL, JText::sprintf('You have login successfully as user &quot;%s&quot;', $instance->name));
                    } else {
                        // die("not auth");
                        // unset cookies to cleanup fb and also simplesamlphp
                        // @session_destroy();

                        /* TODO a better way to destroy only the sessions, 
                         * this anyway is a quick fix to destroy only local sessions
                         *  that it's what we need (and not to trigger an initSLO)
                         * but as drawback it erases also all cookies sets for preferences and advertising */
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
                        $currentSession = JFactory::getSession();


                        //  $currentSession->set("SAMLoginPreventDoubleLogout", true);
                        //  $currentSession->close();
                        //   JFactory::getApplication()->logout();
                        $app->redirect("index.php?notAuthorized=403", JText::sprintf('Not authorised', $instance->name));
                    }
                    //   JFactory::getApplication()->loadSession(new JSession('database', array("name"=>$sessname,"id"=>$sessname)));
                    //     echo($sessname."::".print_r($sessrow,true));
                    // $currentSession = JFactory::getSession(array("name"=>$sessname,"id"=>$sessname));
                    //         die(print_r($currentSession,true));
                } else {
                    JFactory::getApplication()->logout();
                }
            }
        }
    }

}
