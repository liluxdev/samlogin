<?php
//helpers:
if (!function_exists("getSAMLoginReturnURL")){
function getSAMLoginReturnURL($params)
{
            
		$user = JFactory::getUser();
		$type = ($user->guest) ? 'login' : 'logout';
		if ($itemid = $params->get($type))
		{
			$mainframe = JFactory::getApplication();
			$menu = $mainframe->getMenu();
			$item = $menu->getItem($itemid);
			if ($item)
			{
				$url = JRoute::_('index.php?Itemid='.$itemid, false);
			}
			else
			{
				$uri = JFactory::getURI();
				$url = $uri->toString(array(
					'path',
					'query',
					'fragment'
				));
			}
		}
		else
		{
			$uri = JFactory::getURI();
			$url = $uri->toString(array(
				'path',
				'query',
				'fragment'
			));
		}
                
                if (isset($_GET["return"])){
                    $session = JFactory::getSession();
	            $session->set('samloginReturnURL', base64_decode($_GET["return"]));
                    return base64_encode(base64_decode($_GET["return"]));
                }
                if ($params->get('usesecure',false)){
                   $url = strtr($url,array("http://"=>"https://")); //ensure return url is SSL
                }
		return base64_encode($url);
                
 }
}



// no direct access
defined('_JEXEC') or die ;

$user = JFactory::getUser();
$layout = ($user->guest) ? 'default' : 'logout';


$returnURL=  getSAMLoginReturnURL($params);

$layoutFile=JModuleHelper::getLayoutPath('mod_samlogin', $params->get('template', 'default').'/'.$layout);

require ($layoutFile);










