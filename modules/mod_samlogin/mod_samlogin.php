<?php

//helpers:
if (!function_exists("getSAMLoginReturnURL")) {

     function getSAMLoginReturnURL($params) {
        $url="/";
        $returl=JRequest::getVar('return', null, 'GET', 'BASE64');
        if (isset($returl) && !$params->get('systemreturngotpriority', true)) {
            $session = JFactory::getSession();
            $session->set('samloginReturnURL', $returl);
            $url = base64_decode($returl);
        }
        
        $user = JFactory::getUser();
        $type = ($user->guest) ? 'login' : 'logout';
        if ($itemid = $params->get($type)) {
            $mainframe = JFactory::getApplication();
            $menu = $mainframe->getMenu();
            $item = $menu->getItem($itemid);
            if ($item) {
                $url = JRoute::_('index.php?Itemid=' . $itemid, false,$params->get('usesecure', false));
            } else {
                $uri = JFactory::getURI();
                $url = $uri->toString(array(
                    'path',
                    'query',
                    'fragment'
                ));
            }
        } else {
            $uri = JFactory::getURI();
            $url = $uri->toString(array(
                'path',
                'query',
                'fragment'
            ));
        }

        if (isset($returl) && $params->get('systemreturngotpriority', true)) {
            $session = JFactory::getSession();
            $session->set('samloginReturnURL', $returl);
            $url = base64_decode($returl);
        }
        
       
        if ($params->get('usesecure', false)) {
            $url = strtr($url, array("http://" => "https://")); //ensure return url is SSL
        }
        return base64_encode($url);
    }
}



// no direct access
defined('_JEXEC') or die;

$user = JFactory::getUser();
$layout = ($user->guest) ? 'default' : 'logout';


$returnURL = getSAMLoginReturnURL($params);
$samloginParams = JComponentHelper::getParams('com_samlogin');
$discotype = $samloginParams->get('sspas_discotype', '0');
if ($discotype == "nonsaml" || $discotype == "nonsaml-enforced") {
    $customRediLogin = $samloginParams->get('sspas_discocustomnonauthnurl', '');
    if (!empty($customRediLogin)) {
        $ssoLink = $customRediLogin;
        if (stristr($ssoLink, "?")){
             $ssoLink .= urlencode("&rret=".$returnURL);
        }else{
             $ssoLink .= urlencode("?rret=".$returnURL);
        }
        if ($discotype=="nonsaml-enforced"){
            if ($user->guest){
                $app = JFactory::getApplication();
                $app->redirect($ssoLink);
            }
        }
    } else {
        die("Error: custom non-saml redirect login mode set but no custom url provided");
    }
} else {
    $ssoLink = JRoute::_('index.php?option=com_samlogin&view=login&task=initSSO', true, $params->get('usesecure',false));
       if ($params->get('usesecure', false)) {
            $ssoLink = strtr($ssoLink, array("http://" => "https://"));
       }
       if (stristr($ssoLink, "?")){
             $ssoLink .= "&return=".$returnURL;
        }else{
             $ssoLink .= "?return=".$returnURL;
        }
}

$layoutFile = JModuleHelper::getLayoutPath('mod_samlogin', $params->get('template', 'default') . '/' . $layout);

require ($layoutFile);










